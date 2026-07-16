<?php
namespace App\Services;

use App\Helpers\StorageHelper;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * ينشئ "دفعة موحدة لعدة حجوزات": يدفع شخص واحد المبلغ الإجمالي لعدة حجوزات
 * فيُوزَّع على كل حجز كدفعةٍ مستقلة، وتُربَط جميعها برقم مجموعة واحد (group_id)
 * للتتبّع والتقارير. يعالج كل الحجوزات ضمن معاملة واحدة: إمّا أن تنجح جميعها أو
 * لا يُحفَظ شيء.
 */
class GroupPaymentService
{
    public function __construct(private ShiftService $shiftService) {}

    /**
     * @param array<int, array{reservation: Reservation, amount: float}> $allocations
     *        قائمة التوزيع: كل عنصر يحمل الحجز والمبلغ المخصَّص له.
     * @param array $meta بيانات الدفعة المشتركة: method, paid_by_name, notes,
     *        bank_receipt (ملف اختياري), bank_transfer_ref.
     * @return string رقم المجموعة المُنشأ (group_id).
     */
    public function createGroupPayment(array $allocations, array $meta, User $user): string
    {
        return DB::transaction(function () use ($allocations, $meta, $user) {
            $groupId = $this->generateGroupId();
            $shift   = $this->shiftService->getActiveShift($user);

            // نُخزّن سند التحويل مرّة واحدة ونشاركه بين دفعات المجموعة (تحويل واحد).
            $bankReceiptPath = null;
            if (!empty($meta['bank_receipt'])) {
                $bankReceiptPath = StorageHelper::store($meta['bank_receipt'], 'bank_receipts');
            }

            $affectedShiftIds = [];

            foreach ($allocations as $alloc) {
                /** @var Reservation $reservation */
                $reservation = $alloc['reservation'];
                $amount      = round((float) $alloc['amount'], 2);
                if ($amount <= 0) {
                    continue;
                }

                $payment = Payment::create([
                    'reservation_id'    => $reservation->id,
                    'group_id'          => $groupId,
                    'paid_by_name'      => $meta['paid_by_name'] ?? null,
                    'shift_id'          => $shift?->id,
                    'received_by'       => $user->id,
                    'amount'            => $amount,
                    'currency'          => 'YER',
                    'method'            => $meta['method'],
                    'bank_receipt_path' => $bankReceiptPath,
                    'bank_transfer_ref' => $meta['bank_transfer_ref'] ?? null,
                    'payment_date'      => now(),
                    'type'              => 'reservation',
                    'notes'             => $meta['notes'] ?? null,
                ]);

                $reservation->increment('paid_amount', $amount);
                $reservation->refresh()->updatePaymentStatus();

                if ($shift) {
                    $affectedShiftIds[$shift->id] = true;
                }

                AuditLogService::log('create', $payment, null, $payment->toArray(), $user);
            }

            foreach (array_keys($affectedShiftIds) as $shiftId) {
                $this->shiftService->computeTotals(\App\Models\Shift::find($shiftId));
            }

            return $groupId;
        });
    }

    /**
     * رقم مجموعة قابل للقراءة يبدأ بـ GRP ثم التاريخ ثم لاحقة عشوائية قصيرة، مع
     * ضمان التفرّد في جدول الدفعات.
     */
    private function generateGroupId(): string
    {
        do {
            $groupId = 'GRP-' . now()->format('Ymd') . '-' . strtoupper(Str::random(4));
        } while (Payment::where('group_id', $groupId)->exists());

        return $groupId;
    }
}
