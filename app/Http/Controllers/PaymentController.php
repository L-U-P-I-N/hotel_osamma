<?php
namespace App\Http\Controllers;

use App\Helpers\StorageHelper;
use App\Models\Reservation;
use App\Services\CashSettlementService;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PaymentController extends Controller
{
    public function __construct(private PaymentService $paymentService) {}

    public function store(Request $request)
    {
        $reservation = Reservation::findOrFail($request->reservation_id);

        $request->validate([
            'reservation_id' => 'required|exists:reservations,id',
            'amount'         => ['required', 'numeric', 'min:0.01', 'max:' . $reservation->balance],
            'method'         => 'required|in:cash,bank_transfer,pos',
            'currency'       => 'nullable|in:YER',
            'notes'          => 'nullable|string|max:500',
            'bank_receipt'   => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:10240',
            'bank_transfer_ref' => 'nullable|string|max:100',
        ], [
            'amount.max'      => 'المبلغ المدخل يتجاوز الرصيد المتبقي (' . number_format($reservation->balance, 2) . ')',
            'amount.min'      => 'يجب أن يكون المبلغ أكبر من صفر',
            'amount.required' => 'حقل المبلغ مطلوب',
        ]);

        // For bank transfer: at least one of receipt image or reference number is required
        if ($request->method === 'bank_transfer'
            && !$request->hasFile('bank_receipt')
            && empty($request->bank_transfer_ref)) {
            return back()
                ->withInput()
                ->withErrors(['bank_transfer' => 'عند اختيار التحويل البنكي يجب إرفاق صورة السند أو إدخال رقم المرجع على الأقل']);
        }

        $data = $request->except(['_token', 'reservation_id']);
        $data['currency'] = 'YER';
        if ($request->hasFile('bank_receipt')) {
            $data['bank_receipt'] = $request->file('bank_receipt');
        }

        $payment = $this->paymentService->addPayment($reservation, $data, auth()->user());

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'payment' => $payment]);
        }
        return redirect()->route('reservations.show', $reservation)
            ->with('success', 'تم تسجيل الدفعة بنجاح');
    }

    public function update(Request $request, \App\Models\Payment $payment)
    {
        $reservation = $payment->reservation;

        $oldAmount = (float) $payment->amount;
        // الحد الأقصى: بقية الدفعات (بدون هذه الدفعة) + المبلغ الجديد يجب ألا يتجاوز الإجمالي
        $otherPaid  = (float) $reservation->paid_amount - $oldAmount;
        $maxAllowed = (float) $reservation->total_amount - $otherPaid;

        // نتحقّق يدوياً حتى نتمكّن من إعادة فتح نافذة التصحيح للدفعة نفسها عند أي خطأ
        // (كانت النافذة تبقى مخفية فيبدو للمستخدم أن التصحيح "لم يُحفَظ" دون سبب ظاهر).
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'amount'            => ['required', 'numeric', 'min:0.01'],
            'correction_reason' => ['required', 'string', 'max:255'],
        ], [
            'amount.required'            => 'حقل المبلغ مطلوب',
            'amount.numeric'             => 'يجب أن يكون المبلغ رقماً',
            'amount.min'                 => 'يجب أن يكون المبلغ أكبر من صفر',
            'correction_reason.required' => 'سبب التصحيح مطلوب',
        ]);

        $validator->after(function ($v) use ($request, $maxAllowed) {
            if (!$v->errors()->has('amount') && (float) $request->input('amount') > $maxAllowed) {
                $v->errors()->add('amount',
                    'المبلغ الجديد يجعل إجمالي المدفوع يتجاوز إجمالي الحجز (الحد الأقصى ' . number_format($maxAllowed, 2) . ')');
            }
        });

        if ($validator->fails()) {
            return back()->withInput()
                ->withErrors($validator)
                ->with('correcting_payment_id', $payment->id);
        }

        $validated = $validator->validated();
        $newAmount = (float) $validated['amount'];

        $old = $payment->only(['amount', 'notes']);
        $delta = $newAmount - $oldAmount;

        $correctionNote = '[تصحيح مبلغ من ' . number_format($oldAmount, 0) . ' إلى ' . number_format($newAmount, 0)
            . ' — ' . now()->format('Y-m-d H:i') . ' — ' . $validated['correction_reason'] . ']';

        $payment->update([
            'amount' => $newAmount,
            'notes'  => $payment->notes ? $payment->notes . "\n" . $correctionNote : $correctionNote,
        ]);

        $reservation->increment('paid_amount', $delta);
        $reservation->refresh()->updatePaymentStatus();

        if ($payment->shift_id && $payment->shift) {
            app(\App\Services\ShiftService::class)->computeTotals($payment->shift);
        }

        \App\Services\AuditLogService::log('update', $payment, $old, [
            'amount' => $newAmount,
            'reason' => $validated['correction_reason'],
        ]);

        return redirect()->route('reservations.show', $reservation)
            ->with('success', 'تم تصحيح مبلغ الدفعة بنجاح');
    }

    public function slip(\App\Models\Payment $payment)
    {
        $payment->load(['reservation.guest', 'reservation.room', 'receivedBy']);
        $pdf = pdf_load_view('payments.slip', compact('payment'));
        $pdf->setPaper([0, 0, 420, 595], 'portrait'); // half A4

        $dompdf = $pdf->getDomPDF();
        $opts   = $dompdf->getOptions();
        $opts->setFontDir(storage_path('fonts'));
        $opts->setFontCache(storage_path('fonts'));
        $dompdf->setOptions($opts);

        $filename = 'receipt-' . str_pad($payment->id, 6, '0', STR_PAD_LEFT) . '.pdf';
        return $pdf->stream($filename);
    }

    public function viewReceipt(string $file)
    {
        $path = $file;
        if (!StorageHelper::exists($path)) {
            abort(404);
        }

        \App\Services\AuditLogService::log('view_sensitive', null, null, ['file' => $path]);

        return StorageHelper::response($path);
    }
}
