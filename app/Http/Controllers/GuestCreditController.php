<?php

namespace App\Http\Controllers;

use App\Models\GuestCredit;
use App\Services\GuestCreditService;
use Illuminate\Http\Request;

/**
 * قسم "المبالغ المتبقية للنزلاء": كل مبلغ دفعه نزيل ولم يستهلكه (مغادرة مبكرة
 * مثلاً) يظهر هنا التزاماً على الفندق حتى يُصرف له أو يتنازل عنه.
 */
class GuestCreditController extends Controller
{
    public function __construct(private GuestCreditService $credits) {}

    public function index(Request $request)
    {
        $status = $request->input('status', GuestCredit::STATUS_OPEN);
        $search = trim((string) $request->input('search', ''));

        $query = GuestCredit::with(['guest', 'reservation.room', 'createdBy', 'settledBy']);

        if (array_key_exists($status, GuestCredit::STATUS_LABELS)) {
            $query->where('status', $status);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->whereHas('guest', fn ($g) => $g->where('full_name', 'like', "%{$search}%"))
                  ->orWhereHas('reservation.room', fn ($r) => $r->where('room_number', 'like', "%{$search}%"));
                // رقم الحجز يُبحَث به كثيراً، لكنه ليس نصّاً — نطابقه فقط إن كان رقماً
                if (ctype_digit($search)) {
                    $q->orWhere('reservation_id', (int) $search);
                }
            });
        }

        // العدّادات على كل الأرصدة لا على الصفحة المعروضة — الالتزام الكلي
        // يجب أن يظهر كاملاً مهما كان الفلتر.
        $openTotal  = (float) GuestCredit::open()->sum('amount');
        $openCount  = GuestCredit::open()->count();
        $settledSum = (float) GuestCredit::where('status', GuestCredit::STATUS_SETTLED)->sum('amount');

        $credits = $query->orderByDesc('id')->paginate(25)->withQueryString();

        return view('guest-credits.index', compact('credits', 'status', 'search', 'openTotal', 'openCount', 'settledSum'));
    }

    public function settle(Request $request, GuestCredit $credit)
    {
        $validated = $request->validate([
            'settlement_method' => 'required|in:cash,pos,bank_transfer',
            'settlement_notes'  => 'nullable|string|max:500',
        ], [
            'settlement_method.required' => 'طريقة الصرف مطلوبة',
            'settlement_method.in'       => 'طريقة الصرف غير معروفة',
        ]);

        try {
            $this->credits->settle($credit, $validated, auth()->user());
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'تم صرف المبلغ المتبقي للنزيل وخروجه من صندوق الوردية.');
    }

    public function cancel(Request $request, GuestCredit $credit)
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ], [
            'reason.required' => 'سبب الإسقاط مطلوب — يُوثَّق تنازل النزيل عن المبلغ',
        ]);

        try {
            $this->credits->cancel($credit, $validated['reason'], auth()->user());
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'تم إسقاط الرصيد — عاد المبلغ إيراداً للفندق.');
    }
}
