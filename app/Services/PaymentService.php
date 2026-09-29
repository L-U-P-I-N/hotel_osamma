<?php
namespace App\Services;

use App\Helpers\StorageHelper;
use App\Models\Payment;
use App\Models\PaymentAccount;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function __construct(private ShiftService $shiftService, private JournalService $journalService) {}

    public function addPayment(Reservation $reservation, array $data, User $user): Payment
    {
        return DB::transaction(function () use ($reservation, $data, $user) {
            // المسار قد يأتي جاهزاً (تسجيل الدخول يرفع السند قبل إنشاء الحجز)
            $bankReceiptPath = $data['bank_receipt_path'] ?? null;
            if (!empty($data['bank_receipt'])) {
                // نستخدم القرص الخاص المُعرَّف (نفس الذي يقرأ منه عارض السند) حتى
                // لا يُخزَّن على قرص ويُبحَث عنه في آخر فيظهر خطأ 404.
                $bankReceiptPath = StorageHelper::store($data['bank_receipt'], 'bank_receipts');
            }

            $shift = $this->shiftService->getActiveShift($user);

            // الوعاء الذي دخله المبلغ فعلاً: درج الوردية، حساب بنكي بعينه، أو
            // جهاز شبكة. بدونه كان كل مقبوض يُسجَّل نقداً في الدرج مهما كانت
            // طريقته، فيُطالَب الموظف عند الإقفال بنقدٍ لم يستلمه.
            $account = $this->resolveAccount($data);

            $payment = Payment::create([
                'reservation_id'    => $reservation->id,
                'shift_id'          => $shift?->id,
                'received_by'       => $user->id,
                'amount'            => $data['amount'],
                'currency'          => $data['currency'] ?? 'YER',
                'method'            => $data['method'],
                'payment_account_id' => $account?->id,
                'bank_receipt_path' => $bankReceiptPath,
                'bank_transfer_ref' => $data['bank_transfer_ref'] ?? null,
                'payment_date'      => now(),
                'type'              => $data['type'] ?? 'reservation',
                'notes'             => $data['notes'] ?? null,
            ]);

            $reservation->refresh()->recalculatePaidAmount();

            if ($shift) {
                $this->shiftService->computeTotals($shift);
            }

            // ريال يمني فقط حالياً (الشجرة لا تدعم SAR/USD بعد)
            if ($payment->currency === 'YER') {
                $this->journalService->post(
                    $payment->payment_date->toDateString(),
                    'دفعة نزيل — حجز #' . $reservation->id,
                    Payment::class,
                    $payment->id,
                    [
                        ['account_code' => $account?->account_code ?? '1111', 'debit' => $payment->amount],
                        ['account_code' => '4110', 'credit' => $payment->amount],
                    ],
                    $user->id,
                    'payment.received'
                );
            }

            AuditLogService::log('create', $payment, null, $payment->toArray(), $user);
            return $payment;
        });
    }

    /**
     * يحدّد وعاء المال. الموظف يختاره صراحةً حين يكون للفندق أكثر من حساب بنكي؛
     * وإن لم يختر نأخذ الوسيلة الافتراضية لطريقة الدفع بدل افتراض النقدية.
     * ونتحقق أن الوسيلة المختارة تناسب الطريقة، فلا يُسجَّل تحويل بنكي في درج.
     */
    private function resolveAccount(array $data): ?PaymentAccount
    {
        $method = $data['method'] ?? 'cash';

        if (!empty($data['payment_account_id'])) {
            $account = PaymentAccount::find($data['payment_account_id']);

            if ($account && in_array($account->type, PaymentAccount::METHOD_TYPES[$method] ?? [], true)) {
                return $account;
            }
        }

        return PaymentAccount::defaultFor($method);
    }
}
