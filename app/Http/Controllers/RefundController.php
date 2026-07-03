<?php
namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Refund;
use App\Models\Reservation;
use App\Services\AuditLogService;
use Illuminate\Http\Request;

class RefundController extends Controller
{
    public function index(Request $request)
    {
        $query = Refund::with(['reservation.guest', 'reservation.room', 'payment', 'processedBy'])
            ->orderBy('refunded_at', 'desc');

        if ($request->filled('from')) {
            $query->whereDate('refunded_at', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('refunded_at', '<=', $request->to);
        }

        $refunds      = $query->paginate(25)->withQueryString();
        $totalRefunds = Refund::sum('amount');

        return view('refunds.index', compact('refunds', 'totalRefunds'));
    }

    public function create(Reservation $reservation)
    {
        $reservation->load(['guest', 'room', 'payments']);
        return view('refunds.create', compact('reservation'));
    }

    public function store(Request $request, Reservation $reservation)
    {
        $validated = $request->validate([
            'amount'     => 'required|numeric|min:0.01|max:' . $reservation->paid_amount,
            'payment_id' => 'nullable|exists:payments,id',
            'method'     => 'required|in:cash,bank_transfer,pos',
            'reason'     => 'required|string|max:500',
            'notes'      => 'nullable|string|max:500',
        ], [
            'amount.required'  => 'مبلغ الاسترجاع مطلوب',
            'amount.max'       => 'لا يمكن استرجاع أكثر مما تم دفعه (' . number_format($reservation->paid_amount, 0) . ' ر.ي)',
            'method.required'  => 'طريقة الاسترجاع مطلوبة',
            'reason.required'  => 'سبب الاسترجاع مطلوب',
        ]);

        $refund = Refund::create([
            'reservation_id' => $reservation->id,
            'payment_id'     => $validated['payment_id'] ?? null,
            'processed_by'   => auth()->id(),
            'amount'         => $validated['amount'],
            'currency'       => 'YER',
            'method'         => $validated['method'],
            'reason'         => $validated['reason'],
            'notes'          => $validated['notes'] ?? null,
            'refunded_at'    => now(),
        ]);

        // Deduct from paid_amount
        $reservation->decrement('paid_amount', $validated['amount']);
        $reservation->refresh()->updatePaymentStatus();

        // 'action' عمود enum ثابت القيم في audit_logs — لا يقبل 'refund_created'
        AuditLogService::log('update', $reservation, null, [
            'amount' => $validated['amount'],
            'reason' => $validated['reason'],
        ]);

        return redirect()->route('reservations.show', $reservation)
            ->with('success', 'تم تسجيل الاسترجاع بنجاح — ' . number_format($validated['amount'], 0) . ' ر.ي');
    }
}
