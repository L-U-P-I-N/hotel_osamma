<?php
namespace App\Http\Controllers;

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
            'currency'       => 'required|in:YER,SAR,USD',
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
        if ($request->hasFile('bank_receipt')) {
            $data['bank_receipt'] = $request->file('bank_receipt');
        }

        $payment = $this->paymentService->addPayment($reservation, $data, auth()->user());

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'payment' => $payment]);
        }
        return back()->with('success', 'تم تسجيل الدفعة بنجاح');
    }

    public function viewReceipt(string $file)
    {
        $path = $file;
        if (!Storage::disk('private')->exists($path)) {
            abort(404);
        }

        \App\Services\AuditLogService::log('view_sensitive', null, null, ['file' => $path]);

        return response()->file(Storage::disk('private')->path($path));
    }
}
