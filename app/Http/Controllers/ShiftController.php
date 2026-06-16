<?php
namespace App\Http\Controllers;

use App\Models\Shift;
use App\Models\User;
use App\Services\ShiftService;
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
            $activeShift->load(['payments.reservation.guest', 'withdrawals']);
        }

        $allActive = $user->isAdmin() ? $this->service->getAllActiveShifts() : collect();
        $suggestedShiftType = ShiftService::guessShiftType();

        // Admin: list of employees to assign shifts to
        $employees = $user->isAdmin()
            ? User::where('is_active', true)->orderBy('name')->get()
            : collect();

        return view('shifts.index', compact(
            'activeShift', 'recentShifts', 'allActive', 'suggestedShiftType', 'employees'
        ));
    }

    public function open(Request $request)
    {
        // Only admin can open shifts
        if (!auth()->user()->isAdmin()) {
            return back()->withErrors(['error' => 'فتح الوردية من صلاحيات الإدارة فقط']);
        }

        $request->validate([
            'user_id'    => 'required|exists:users,id',
            'shift_type' => 'required|in:morning,evening,night',
        ], [
            'user_id.required'    => 'يجب تحديد الموظف',
            'user_id.exists'      => 'الموظف غير موجود',
            'shift_type.required' => 'نوع الوردية مطلوب',
            'shift_type.in'       => 'نوع الوردية غير صالح',
        ]);

        $targetUser = User::findOrFail($request->user_id);

        try {
            $shift = $this->service->openShift($targetUser, $request->shift_type);
            return redirect()->route('shifts.index')
                ->with('success', 'تم فتح وردية ' . $shift->type_label . ' للموظف ' . $targetUser->name);
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
            'handed_by_name'       => 'nullable|string|max:100',
            'notes'                => 'nullable|string|max:500',
            'withdrawal_type'      => 'nullable|in:expense,currency_exchange',
            'exchange_to_currency' => 'required_if:withdrawal_type,currency_exchange|nullable|in:YER,SAR,USD|different:currency',
            'exchange_to_amount'   => 'required_if:withdrawal_type,currency_exchange|nullable|numeric|min:0.01',
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
        if (!$shift) {
            return back()->withErrors(['error' => 'لا توجد وردية مفتوحة لك']);
        }

        try {
            $this->service->addWithdrawal($shift, $request->all());
            $msg = $isExchange ? 'تم تسجيل عملية صرف العملة بنجاح' : 'تم تسجيل السحب بنجاح';
            return back()->with('success', $msg);
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function close(Request $request)
    {
        $request->validate([
            'notes'              => 'nullable|string|max:1000',
            'employee_signature' => 'nullable|string',
            'admin_signature'    => 'nullable|string',
        ]);

        $shift = $this->service->getActiveShift(auth()->user());
        if (!$shift) {
            return back()->withErrors(['error' => 'لا توجد وردية مفتوحة']);
        }

        try {
            $this->service->closeShift(
                $shift,
                $request->notes ?? '',
                $request->employee_signature ?? '',
                $request->admin_signature ?? '',
            );
            return redirect()->route('shifts.index')->with('success', 'تم إقفال الوردية بنجاح');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function exportPdf(Shift $shift)
    {
        $shift->load(['user', 'payments.reservation.guest', 'payments.reservation.room', 'withdrawals']);

        $pdf = Pdf::loadView('shifts.report_pdf', compact('shift'));
        $pdf->setPaper('a4', 'portrait');

        $dompdf = $pdf->getDomPDF();
        $options = $dompdf->getOptions();
        $options->setFontDir(storage_path('fonts'));
        $options->setFontCache(storage_path('fonts'));
        $dompdf->setOptions($options);

        return $pdf->download('shift-report-' . $shift->id . '.pdf');
    }
}
