<?php
namespace App\Services;

use App\Models\GuestCredit;
use App\Models\Refund;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * المبالغ المتبقية للنزلاء: ترحيلها عند الخروج، صرفها لاحقاً، أو إسقاطها.
 *
 * النقد لا يتحرك لحظة الترحيل — يبقى في صندوق الوردية — لذا لا تُنشأ مستلمة
 * استرجاع إلا عند الصرف الفعلي، وإلا لظهر الصندوق ناقصاً وهو سليم. محاسبياً
 * يُنقل المبلغ من إيراد الغرف (4100) إلى التزام على الفندق (2300).
 */
class GuestCreditService
{
    /** كود حساب المبالغ المتبقية للنزلاء (خصوم). */
    private const CREDIT_ACCOUNT = '2300';
    private const ROOMS_REVENUE  = '4100';
    private const SHIFT_CASH     = '1110';

    /**
     * يرحّل مبلغاً زائداً دفعه النزيل إلى رصيد باسمه، ويعيد حساب "المدفوع" على
     * الحجز حتى يصبح المتبقي صفراً فيُسمح بالخروج.
     */
    public function carry(Reservation $reservation, float $amount, string $reason, User $user): GuestCredit
    {
        $amount = round($amount, 2);

        if ($amount <= 0) {
            throw new \InvalidArgumentException('مبلغ الرصيد المتبقي يجب أن يكون أكبر من صفر.');
        }

        return DB::transaction(function () use ($reservation, $amount, $reason, $user) {
            $credit = GuestCredit::create([
                'reservation_id' => $reservation->id,
                'guest_id'       => $reservation->guest_id,
                'amount'         => $amount,
                'currency'       => 'YER',
                'status'         => GuestCredit::STATUS_OPEN,
                'reason'         => $reason,
                'created_by'     => $user->id,
            ]);

            // يُخصم المبلغ من "المدفوع" لأنه لم يعد مقابل إقامة
            $reservation->refresh()->recalculatePaidAmount();

            // ريال يمني فقط حالياً — نقل من الإيراد إلى التزام على الفندق
            app(JournalService::class)->post(
                now()->toDateString(),
                'ترحيل مبلغ متبقٍّ لنزيل — حجز #' . $reservation->id,
                GuestCredit::class,
                $credit->id,
                [
                    ['account_code' => self::ROOMS_REVENUE,  'debit'  => $amount],
                    ['account_code' => self::CREDIT_ACCOUNT, 'credit' => $amount],
                ],
                $user->id
            );

            AuditLogService::log('create', $credit, null, [
                'action' => 'guest_credit_carried',
                'amount' => $amount,
                'reason' => $reason,
            ], $user);

            return $credit;
        });
    }

    /**
     * يصرف الرصيد للنزيل نقداً/شبكة/تحويل. الصرف مستلمة استرجاع مرتبطة بوردية
     * الموظف حتى ينقص صندوقها بالمبلغ فعلاً ويظهر في تقارير الاسترجاعات.
     */
    public function settle(GuestCredit $credit, array $data, User $user): GuestCredit
    {
        if ($credit->status !== GuestCredit::STATUS_OPEN) {
            throw new \RuntimeException('هذا الرصيد ليس قائماً — لا يمكن صرفه مرة أخرى.');
        }

        return DB::transaction(function () use ($credit, $data, $user) {
            $shiftService = app(ShiftService::class);
            $shift        = $shiftService->getActiveShift($user);

            // affects_paid_amount = false: "المدفوع" على الحجز خُصم منه المبلغ
            // وقت الترحيل، فخصمه ثانيةً هنا يُنقصه مرتين.
            $refund = Refund::create([
                'reservation_id'      => $credit->reservation_id,
                'payment_id'          => null,
                'shift_id'            => $shift?->id,
                'processed_by'        => $user->id,
                'amount'              => $credit->amount,
                'affects_paid_amount' => false,
                'currency'            => $credit->currency,
                'method'              => $data['settlement_method'] ?? 'cash',
                'reason'              => 'صرف مبلغ متبقٍّ للنزيل — ' . $credit->reason,
                'notes'               => $data['settlement_notes'] ?? null,
                'refunded_at'         => now(),
            ]);

            $credit->update([
                'status'            => GuestCredit::STATUS_SETTLED,
                'settled_at'        => now(),
                'settled_by'        => $user->id,
                'settlement_method' => $data['settlement_method'] ?? 'cash',
                'settlement_notes'  => $data['settlement_notes'] ?? null,
                'refund_id'         => $refund->id,
            ]);

            if ($shift) {
                $shiftService->computeTotals($shift);
            }

            // إسقاط الالتزام مقابل خروج نقدية من صندوق الوردية
            app(JournalService::class)->post(
                now()->toDateString(),
                'صرف مبلغ متبقٍّ لنزيل — حجز #' . $credit->reservation_id,
                GuestCredit::class,
                $credit->id,
                [
                    ['account_code' => self::CREDIT_ACCOUNT, 'debit'  => $credit->amount],
                    ['account_code' => self::SHIFT_CASH,     'credit' => $credit->amount],
                ],
                $user->id
            );

            AuditLogService::log('update', $credit, ['status' => GuestCredit::STATUS_OPEN], [
                'action' => 'guest_credit_settled',
                'amount' => (float) $credit->amount,
                'method' => $credit->settlement_method,
            ], $user);

            return $credit->refresh();
        });
    }

    /**
     * إسقاط الرصيد بتنازل النزيل عنه — يعود المبلغ إيراداً للفندق، ويعود
     * "المدفوع" على الحجز كما كان.
     */
    public function cancel(GuestCredit $credit, string $reason, User $user): GuestCredit
    {
        if ($credit->status !== GuestCredit::STATUS_OPEN) {
            throw new \RuntimeException('هذا الرصيد ليس قائماً — لا يمكن إسقاطه.');
        }

        return DB::transaction(function () use ($credit, $reason, $user) {
            $credit->update([
                'status'           => GuestCredit::STATUS_CANCELLED,
                'settled_at'       => now(),
                'settled_by'       => $user->id,
                'settlement_notes' => $reason,
            ]);

            $credit->reservation?->refresh()->recalculatePaidAmount();

            app(JournalService::class)->post(
                now()->toDateString(),
                'إسقاط مبلغ متبقٍّ لنزيل (تنازل) — حجز #' . $credit->reservation_id,
                GuestCredit::class,
                $credit->id,
                [
                    ['account_code' => self::CREDIT_ACCOUNT, 'debit'  => $credit->amount],
                    ['account_code' => self::ROOMS_REVENUE,  'credit' => $credit->amount],
                ],
                $user->id
            );

            AuditLogService::log('update', $credit, ['status' => GuestCredit::STATUS_OPEN], [
                'action' => 'guest_credit_cancelled',
                'amount' => (float) $credit->amount,
                'reason' => $reason,
            ], $user);

            return $credit->refresh();
        });
    }
}
