<?php
namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Services\GroupPaymentService;
use Illuminate\Http\Request;

/**
 * دفعة موحدة لعدة حجوزات: يختار الموظف عدة حجوزات ذات رصيد متبقٍّ ويسجّل دفعةً
 * واحدةً تُوزَّع عليها (متساوٍ تلقائي أو يدوي)، فيُغطّى دفع شخصٍ واحد لعدة حجوزات
 * (أب يدفع لأبنائه، أو نزيل حجز عدة غرف باسمه) دون تسجيل كل حجز على حدة.
 */
class GroupPaymentController extends Controller
{
    public function __construct(private GroupPaymentService $service) {}

    /**
     * شاشة الدفع الموحد: تعرض الحجوزات النشطة ذات الرصيد المتبقّي لاختيارها.
     */
    public function create(Request $request)
    {
        $reservations = $this->outstandingQuery()->get()
            ->filter(fn ($r) => $r->balance > 0.009)
            ->values();

        return view('payments.group', compact('reservations'));
    }

    /**
     * بحث AJAX عن الحجوزات ذات الرصيد المتبقّي (بالاسم أو رقم الحجز أو الغرفة).
     */
    public function search(Request $request)
    {
        $term = trim((string) $request->input('q', ''));

        $reservations = $this->outstandingQuery()
            ->when($term !== '', function ($query) use ($term) {
                $query->where(function ($q) use ($term) {
                    $q->whereHas('guest', fn ($g) => $g->where('full_name', 'like', "%{$term}%"))
                      ->orWhereHas('room', fn ($rm) => $rm->where('room_number', 'like', "%{$term}%"))
                      ->orWhere('id', $term);
                });
            })
            ->get()
            ->filter(fn ($r) => $r->balance > 0.009)
            ->map(fn ($r) => [
                'id'           => $r->id,
                'guest_name'   => $r->guest?->full_name ?? '—',
                'room_number'  => $r->display_room_number,
                'check_in'     => $r->check_in_date?->format('d/m/Y'),
                'check_out'    => $r->check_out_date?->format('d/m/Y'),
                'nights'       => $r->nights,
                'total'        => round((float) $r->total_amount, 2),
                'paid'         => round((float) $r->paid_amount, 2),
                'balance'      => round($r->balance, 2),
            ])
            ->values();

        return response()->json(['reservations' => $reservations]);
    }

    /**
     * حفظ الدفعة الموحدة: يتحقّق من التوزيع (كل مبلغ ≤ رصيد حجزه) ثم يوزّعها.
     */
    public function store(Request $request)
    {
        // يجب أن تكون للموظف وردية مفتوحة قبل استلام أي دفعة (موحّدة أو مفردة).
        if (!app(\App\Services\ShiftService::class)->getActiveShift(auth()->user())) {
            return back()->withErrors(['error' => 'لا يمكن استلام دفعة دون وردية مفتوحة — افتح وردية أولاً من صفحة الورديات']);
        }

        $request->validate([
            'reservation_ids'   => 'required|array|min:1',
            'reservation_ids.*' => 'integer|exists:reservations,id',
            'amounts'           => 'required|array',
            'amounts.*'         => 'numeric|min:0',
            'method'            => 'required|in:cash,bank_transfer,pos',
            'paid_by_name'      => 'nullable|string|max:150',
            'notes'             => 'nullable|string|max:500',
            'bank_receipt'      => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:10240',
            'bank_transfer_ref' => 'nullable|string|max:100',
        ], [
            'reservation_ids.required' => 'يجب اختيار حجز واحد على الأقل',
            'reservation_ids.min'      => 'يجب اختيار حجز واحد على الأقل',
            'method.required'          => 'طريقة الدفع مطلوبة',
        ]);

        // التحويل البنكي يتطلّب صورة سند أو رقم مرجع على الأقل
        if ($request->method === 'bank_transfer'
            && !$request->hasFile('bank_receipt')
            && empty($request->bank_transfer_ref)) {
            return back()->withInput()
                ->withErrors(['bank_transfer' => 'عند اختيار التحويل البنكي يجب إرفاق صورة السند أو إدخال رقم المرجع على الأقل']);
        }

        $reservationIds = $request->input('reservation_ids');
        $amounts        = $request->input('amounts', []);

        $reservations = Reservation::whereIn('id', $reservationIds)->get()->keyBy('id');

        $allocations = [];
        $errors      = [];
        $totalToPay  = 0.0;

        foreach ($reservationIds as $id) {
            $reservation = $reservations->get($id);
            if (!$reservation) {
                continue;
            }
            $amount  = round((float) ($amounts[$id] ?? 0), 2);
            $balance = round($reservation->balance, 2);

            if ($amount <= 0) {
                continue; // نتجاهل حجزاً بمبلغ صفر (لم يُخصَّص له شيء)
            }
            if ($amount > $balance + 0.01) {
                $errors[] = 'المبلغ المخصَّص للحجز #' . $id . ' (' . number_format($amount, 0)
                    . ') يتجاوز رصيده المتبقّي (' . number_format($balance, 0) . ')';
                continue;
            }

            $allocations[] = ['reservation' => $reservation, 'amount' => $amount];
            $totalToPay += $amount;
        }

        if (!empty($errors)) {
            return back()->withInput()->withErrors(['distribution' => $errors]);
        }
        if (empty($allocations) || $totalToPay <= 0) {
            return back()->withInput()->withErrors(['distribution' => 'لم يُخصَّص أي مبلغ لأي حجز']);
        }

        $meta = [
            'method'            => $request->method,
            'paid_by_name'      => $request->paid_by_name,
            'notes'             => $request->notes,
            'bank_transfer_ref' => $request->bank_transfer_ref,
            'bank_receipt'      => $request->hasFile('bank_receipt') ? $request->file('bank_receipt') : null,
        ];

        $groupId = $this->service->createGroupPayment($allocations, $meta, auth()->user());

        return redirect()->route('payments.group.create')
            ->with('success', 'تم تسجيل الدفعة الموحدة بنجاح (' . number_format($totalToPay, 0)
                . ' ر.ي على ' . count($allocations) . ' حجز) — رقم المجموعة: ' . $groupId);
    }

    /**
     * الحجوزات النشطة ذات رصيد متبقٍّ محتمل (paid_amount < total_amount) — نُرشّح
     * الرصيد الفعلي بعد التحميل لأن balance خاصية محسوبة.
     */
    private function outstandingQuery()
    {
        // كل الحجوزات التي عليها رصيد متبقٍّ — وليس المقيمين حالياً فقط، فالنزيل
        // المغادر (checked_out) قد يبقى عليه دَين يُراد تحصيله بدفعة موحّدة أيضاً.
        return Reservation::with(['guest', 'room'])
            ->whereIn('status', ['checked_in', 'checked_out'])
            ->whereColumn('paid_amount', '<', 'total_amount')
            ->orderBy('check_in_date');
    }
}
