<?php
namespace App\Http\Controllers;

use App\Models\CashWithdrawal;
use App\Models\Shift;
use App\Services\ShiftService;
use App\Services\AuditLogService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class ShiftController extends Controller
{
    public function __construct(private ShiftService $service) {}

    public function index()
    {
        $user = auth()->user();
        $activeShift = $this->service->getActiveShift($user);
        $recentShifts = $this->service->getHistory($user, 10);

        if ($activeShift) {
            $this->service->computeTotals($activeShift);
            $activeShift->refresh();
            $activeShift->load([
                // withTrashed ضروري: قد تخصّ الدفعة/الاسترجاع حجزاً أُلغي (محذوف
                // حذفاً ناعماً) — فنعرض اسم النزيل والغرفة بدل "—".
                'payments' => fn($q) => $q->with(['reservation' => fn($q2) => $q2->withTrashed(), 'reservation.guest']),
                'withdrawals',
                'refunds' => fn($q) => $q->with(['reservation' => fn($q2) => $q2->withTrashed(), 'reservation.guest']),
            ]);
        }

        $allActive       = $user->isAdmin() ? $this->service->getAllActiveShifts() : collect();
        $allUsersStatus  = $user->isAdmin() ? $this->service->getAllUsersShiftStatus() : collect();

        // الورديات المقفلة لكل الموظفين القابلة لإعادة الفتح — مقصورة على المدير
        // فقط (لا يكفي امتلاك صلاحية shifts.reopen وحدها، فهذه تمنح الموظف حق
        // إعادة فتح ورديته هو، لا وردية أي موظف آخر). كانت "الوردية الأخيرة" تعرض
        // تاريخ المستخدم الحالي فقط، فلم يكن للمدير مكان يعيد منه فتح وردية موظف.
        $reopenableShifts = $user->isAdmin()
            ? $this->service->getReopenableShifts()->where('user_id', '!=', $user->id)->values()
            : collect();

        // الورديات الصالحة كوجهة لنقل الدفعات — تشمل المفتوحة والمقفلة الحديثة،
        // لأن الدفعة المُساءة الإسناد غالباً تخصّ وردية سابقة مقفلة.
        $canReassign = $user->can('payments.create') || $user->isAdmin();
        $reassignTargets = $canReassign
            ? Shift::with('user')
                ->orderByDesc('shift_date')->orderByDesc('id')->limit(40)->get()
            : collect();

        // مستلمات غير مرتبطة بأي وردية (shift_id = null) — قد تنشأ من دفعات
        // سابقة لم تُربط بوردية. تُعرض ليتمكن الموظف من ضمّها إلى وردية مفتوحة.
        // نعرض كل المستلمات غير المرتبطة بوردية (لا نقصرها على مستلم بعينه) حتى
        // لا تختفي بسبب اختلاف المستخدم المُستلِم (حساب مشترك/تسجيل بواسطة زميل).
        $orphanPayments = collect();
        $orphanWithdrawals = collect();
        if ($activeShift) {
            $orphanPayments = \App\Models\Payment::with(['reservation.guest', 'receivedBy'])
                ->whereNull('shift_id')
                ->orderByDesc('payment_date')
                ->limit(100)
                ->get();

            // سحبيات/مصروفات غير مرتبطة بأي وردية (قد تنشأ من حذف وردية سابقة)
            // تُعرض ليتمكن الموظف من ضمّها إلى وردية مفتوحة.
            $orphanWithdrawals = CashWithdrawal::whereNull('shift_id')
                ->orderByDesc('withdrawal_date')
                ->limit(100)
                ->get();
        }

        return view('shifts.index', compact('activeShift', 'recentShifts', 'allActive', 'allUsersStatus', 'reassignTargets', 'orphanPayments', 'orphanWithdrawals', 'reopenableShifts'));
    }

    public function attachOrphans(Request $request)
    {
        $request->validate([
            'payment_ids'   => 'required|array|min:1',
            'payment_ids.*' => 'integer|exists:payments,id',
        ], [
            'payment_ids.required' => 'يجب تحديد مستلمة واحدة على الأقل',
            'payment_ids.min'      => 'يجب تحديد مستلمة واحدة على الأقل',
        ]);

        $shift = $this->service->getActiveShift(auth()->user());
        if (!$shift) {
            return back()->withErrors(['error' => 'لا توجد وردية مفتوحة لك لضمّ المستلمات إليها']);
        }

        try {
            $payments = \App\Models\Payment::whereIn('id', $request->payment_ids)
                ->whereNull('shift_id')
                ->get();
            $count = 0;
            foreach ($payments as $payment) {
                $old = ['shift_id' => null];
                $payment->update(['shift_id' => $shift->id]);
                AuditLogService::log('update', $payment, $old, ['shift_id' => $shift->id], auth()->user());
                $count++;
            }
            $this->service->computeTotals($shift);
            return back()->with('success', "تم ضمّ {$count} مستلمة إلى ورديتك المفتوحة بنجاح");
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function attachOrphanWithdrawals(Request $request)
    {
        $request->validate([
            'withdrawal_ids'   => 'required|array|min:1',
            'withdrawal_ids.*' => 'integer|exists:cash_withdrawals,id',
        ], [
            'withdrawal_ids.required' => 'يجب تحديد سحب واحد على الأقل',
            'withdrawal_ids.min'      => 'يجب تحديد سحب واحد على الأقل',
        ]);

        $shift = $this->service->getActiveShift(auth()->user());
        if (!$shift) {
            return back()->withErrors(['error' => 'لا توجد وردية مفتوحة لك لضمّ السحبيات إليها']);
        }

        try {
            $count = $this->service->attachOrphanWithdrawals($shift, $request->withdrawal_ids, auth()->user());
            return back()->with('success', "تم ضمّ {$count} سحب إلى ورديتك المفتوحة بنجاح");
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * حذف وردية (لتصحيح وردية أُنشئت أو أُقفلت بأخطاء إدخال). تُفكّ ارتباطات
     * السجلات المالية بها دون حذفها، فتُصبح غير مرتبطة بوردية ويمكن ضمّها لوردية
     * جديدة. الموظف يحذف ورديته فقط؛ المدير يحذف أي وردية.
     */
    public function destroy(Shift $shift)
    {
        $user = auth()->user();
        if (!$user->isAdmin() && $shift->user_id !== $user->id) {
            return back()->withErrors(['error' => 'لا يمكنك حذف وردية موظف آخر']);
        }

        try {
            $this->service->deleteShift($shift, $user);
            return redirect()->route('shifts.index')
                ->with('success', 'تم حذف الوردية. أصبحت مستلماتها وسحبياتها ومصروفاتها غير مرتبطة بوردية — يمكنك ضمّها إلى وردية جديدة من نفس الشاشة.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * نقل مستلمة (غير مرتبطة بوردية، أو مرتبطة بوردية خطأ) إلى وردية محدَّدة
     * بعينها — يُستخدم عندما تخصّ المستلمة تاريخاً سابقاً فتُنسَب لورديتها
     * الصحيحة بدل الوردية المفتوحة حالياً فقط.
     */
    public function reassignPayment(Request $request, \App\Models\Payment $payment)
    {
        $request->validate([
            'target_shift_id' => 'required|exists:shifts,id',
        ], [
            'target_shift_id.required' => 'يجب اختيار الوردية المستهدفة',
        ]);

        try {
            $targetShift = Shift::findOrFail($request->target_shift_id);
            $this->service->reassignPayment($payment, $targetShift, auth()->user());
            return back()->with('success', 'تم نقل المستلمة إلى وردية ' . $targetShift->shift_date->format('d/m/Y') . ' (' . ($targetShift->user?->name ?? '—') . ') بنجاح');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * نقل سحب (مصروف/صرف عملة) إلى وردية محدَّدة بعينها — لتصحيح سحب نُسِب خطأً
     * لوردية غير التي يخصّها تاريخه فعلياً.
     */
    public function reassignWithdrawal(Request $request, CashWithdrawal $withdrawal)
    {
        $request->validate([
            'target_shift_id' => 'required|exists:shifts,id',
        ], [
            'target_shift_id.required' => 'يجب اختيار الوردية المستهدفة',
        ]);

        try {
            $targetShift = Shift::findOrFail($request->target_shift_id);
            $this->service->reassignWithdrawal($withdrawal, $targetShift, auth()->user());
            return back()->with('success', 'تم نقل السحب إلى وردية ' . $targetShift->shift_date->format('d/m/Y') . ' (' . ($targetShift->user?->name ?? '—') . ') بنجاح');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function addWithdrawal(Request $request)
    {
        $isExchange = $request->input('withdrawal_type') === 'currency_exchange';

        $request->validate([
            'amount'               => 'required|numeric|min:0.01',
            'currency'             => 'required|in:YER,SAR,USD',
            'withdrawn_by_name'    => 'required|string|max:100',
            'notes'                => 'nullable|string|max:500',
            'withdrawal_type'      => 'nullable|in:expense,currency_exchange',
            'exchange_to_currency' => 'required_if:withdrawal_type,currency_exchange|nullable|in:YER,SAR,USD|different:currency',
            'exchange_to_amount'   => 'required_if:withdrawal_type,currency_exchange|nullable|numeric|min:0.01',
            'employee_id'          => 'nullable|exists:employees,id',
            'funding_source'       => 'nullable|in:shift,general_safe',
            'category'             => 'nullable|in:maintenance,electricity,salary,cleaning,food,other',
        ], [
            'amount.required'                  => 'المبلغ مطلوب',
            'amount.numeric'                   => 'يجب أن يكون المبلغ رقماً',
            'amount.min'                       => 'المبلغ يجب أن يكون أكبر من صفر',
            'currency.required'                => 'العملة مطلوبة',
            'withdrawn_by_name.required'       => 'اسم المستلم مطلوب',
            'exchange_to_currency.required_if' => 'عملة الصرف المقابلة مطلوبة',
            'exchange_to_currency.different'   => 'عملة الصرف يجب أن تختلف عن عملة السحب',
            'exchange_to_amount.required_if'   => 'المبلغ المُحوَّل إليه مطلوب',
        ]);

        $shift = $this->service->getActiveShift(auth()->user());
        $isGeneralSafe = $request->input('funding_source') === 'general_safe';

        // مصروف الصندوق العام لا يتطلب وردية مفتوحة (ليس سحباً من درج الوردية).
        if (!$shift && !$isGeneralSafe) {
            return back()->withErrors(['error' => 'لا توجد وردية مفتوحة لك']);
        }

        try {
            $data = array_merge($request->all(), ['handed_by_name' => auth()->user()->name]);
            // مصروف الصندوق العام لا يُربط بوردية — لا يدخل في احتساب درجها.
            $this->service->addWithdrawal($isGeneralSafe ? null : $shift, $data);
            return back()->with('success', $isExchange ? 'تم تسجيل عملية صرف العملة بنجاح' : 'تم تسجيل السحب بنجاح');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function updateWithdrawal(Request $request, CashWithdrawal $withdrawal)
    {
        $request->validate([
            'amount'            => 'required|numeric|min:0.01',
            'withdrawn_by_name' => 'required|string|max:100',
            'notes'             => 'nullable|string|max:500',
        ]);

        if ($withdrawal->shift && $withdrawal->shift->is_closed) {
            return back()->withErrors(['error' => 'لا يمكن تعديل سحب لوردية مقفلة']);
        }

        $withdrawal->update($request->only(['amount', 'withdrawn_by_name', 'notes']));

        if ($withdrawal->shift_id) {
            $this->service->computeTotals(Shift::find($withdrawal->shift_id));
        }

        return back()->with('success', 'تم تعديل السحب بنجاح');
    }

    public function destroyWithdrawal(CashWithdrawal $withdrawal)
    {
        if ($withdrawal->shift && $withdrawal->shift->is_closed) {
            return back()->withErrors(['error' => 'لا يمكن حذف سحب من وردية مقفلة']);
        }

        $shiftId = $withdrawal->shift_id;
        $withdrawal->delete();

        if ($shiftId) {
            $this->service->computeTotals(Shift::find($shiftId));
        }

        return back()->with('success', 'تم حذف السحب بنجاح');
    }

    public function close(Request $request)
    {
        $request->validate([
            'notes'         => 'nullable|string|max:1000',
            'actual_amount' => 'required|numeric|min:0',
            'ended_at'      => 'nullable|date',
        ], [
            'actual_amount.required' => 'يجب إدخال المبلغ الفعلي في الصندوق قبل الإقفال',
            'actual_amount.numeric'  => 'المبلغ الفعلي يجب أن يكون رقماً',
            'actual_amount.min'      => 'المبلغ الفعلي لا يمكن أن يكون سالباً',
            'ended_at.date'          => 'وقت انتهاء الوردية غير صالح',
        ]);

        $shift = $this->service->getActiveShift(auth()->user());
        if (!$shift) {
            return back()->withErrors(['error' => 'لا توجد وردية مفتوحة']);
        }

        try {
            $actualAmount = $request->filled('actual_amount') ? (float) $request->actual_amount : null;
            $endedAt      = $request->filled('ended_at') ? \Carbon\Carbon::parse($request->ended_at) : null;
            $this->service->closeShift($shift, $request->notes ?? '', $actualAmount, $endedAt);
            return redirect()->route('shifts.index')->with('success', 'تم إقفال الوردية بنجاح');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function closePastShift(Request $request)
    {
        $request->validate([
            'shift_date'      => 'required|date|before_or_equal:today',
            'payment_ids'     => 'nullable|array',
            'payment_ids.*'   => 'integer|exists:payments,id',
            'withdrawal_ids'   => 'nullable|array',
            'withdrawal_ids.*' => 'integer|exists:cash_withdrawals,id',
            'actual_amount'   => 'nullable|numeric|min:0',
            'notes'           => 'nullable|string|max:1000',
        ], [
            'shift_date.required'         => 'يجب تحديد تاريخ الوردية',
            'shift_date.before_or_equal'  => 'تاريخ الوردية يجب أن يكون اليوم أو تاريخاً سابقاً',
        ]);

        if (empty($request->payment_ids) && empty($request->withdrawal_ids)) {
            return back()->withErrors(['error' => 'يجب تحديد مستلمة أو سحب واحد على الأقل']);
        }

        try {
            $actualAmount = $request->filled('actual_amount') ? (float) $request->actual_amount : null;
            $shift = $this->service->closePastShiftFromPayments(
                auth()->user(),
                $request->payment_ids ?? [],
                $request->shift_date,
                $actualAmount,
                $request->notes ?? '',
                auth()->user(),
                $request->withdrawal_ids ?? []
            );
            return back()->with('success', 'تم إنشاء وإقفال وردية بتاريخ ' . $shift->shift_date->format('d/m/Y') . ' تحتوي المستلمات والسحبيات المحددة');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function reopen(Request $request, Shift $shift)
    {
        $request->validate([
            'reopen_notes' => 'required|string|max:1000',
        ], [
            'reopen_notes.required' => 'يجب كتابة سبب إعادة الفتح',
        ]);

        try {
            $this->service->reopenShift($shift, auth()->user(), $request->reopen_notes);
            return redirect()->route('shifts.index')->with('success', 'تم فتح الإقفال بنجاح، يمكنك الآن إجراء التعديلات وإقفال الوردية مجدداً');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function deductSalary(Shift $shift)
    {
        try {
            $this->service->deductFromSalary($shift, auth()->user());
            return back()->with('success', 'تم خصم العجز من راتب الموظف بنجاح');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function handover(Shift $shift)
    {
        $shift->load([
            'user', 'payments.reservation.guest', 'payments.reservation.room', 'withdrawals',
            'refunds' => fn($q) => $q->with(['reservation' => fn($q2) => $q2->withTrashed(), 'reservation.guest', 'reservation.room']),
        ]);
        return view('shifts.handover', compact('shift'));
    }

    public function exportPdf(Shift $shift)
    {
        $shift->load([
            'user', 'payments.reservation.guest', 'payments.reservation.room', 'withdrawals',
            'refunds' => fn($q) => $q->with(['reservation' => fn($q2) => $q2->withTrashed(), 'reservation.guest', 'reservation.room']),
        ]);

        // كل النزلاء الذين سجّلهم موظف هذه الوردية خلال فترتها — بصرف النظر عن
        // الدفع: يظهر حتى النزيل المؤجَّل/غير المدفوع مع مديونيته. نعتمد على من
        // أنشأ الحجز (created_by) ووقت الإنشاء ضمن نافذة الوردية (البداية → الإقفال
        // أو الآن إن كانت مفتوحة)، لا على وجود فترة مسعّرة مرتبطة (فقد لا توجد
        // للنزيل المؤجَّل)، حتى لا يسقط أيٌّ منهم من التقرير.
        $shiftStart = $shift->started_at;
        $shiftEnd   = $shift->closed_at ?? $shift->ended_at ?? now();
        $checkedInGuests = \App\Models\Reservation::with(['guest', 'room'])
            ->where('created_by', $shift->user_id)
            ->when($shiftStart, fn($q) => $q->where('created_at', '>=', $shiftStart))
            ->where('created_at', '<=', $shiftEnd)
            ->orderBy('created_at')
            ->get();

        $pdf = pdf_load_view('shifts.report_pdf', compact('shift', 'checkedInGuests'));
        $pdf->setPaper('a4', 'portrait');

        $dompdf = $pdf->getDomPDF();
        $options = $dompdf->getOptions();
        $options->setFontDir(storage_path('fonts'));
        $options->setFontCache(storage_path('fonts'));
        $dompdf->setOptions($options);

        return $pdf->download('shift-report-' . $shift->id . '.pdf');
    }
}
