<?php
namespace App\Http\Controllers;

use App\Models\Shift;
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
            $this->service->computeTotals($activeShift);
            $activeShift->refresh();
            $activeShift->load(['payments.reservation.guest', 'withdrawals']);
        }

        $allActive       = $user->isAdmin() ? $this->service->getAllActiveShifts() : collect();
        $allUsersStatus  = $user->isAdmin() ? $this->service->getAllUsersShiftStatus() : collect();

        return view('shifts.index', compact('activeShift', 'recentShifts', 'allActive', 'allUsersStatus'));
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
            return back()->with('success', $isExchange ? 'تم تسجيل عملية صرف العملة بنجاح' : 'تم تسجيل السحب بنجاح');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function close(Request $request)
    {
        $request->validate([
            'notes'         => 'nullable|string|max:1000',
            'actual_amount' => 'nullable|numeric|min:0',
        ]);

        $shift = $this->service->getActiveShift(auth()->user());
        if (!$shift) {
            return back()->withErrors(['error' => 'لا توجد وردية مفتوحة']);
        }

        try {
            $actualAmount = $request->filled('actual_amount') ? (float) $request->actual_amount : null;
            $this->service->closeShift($shift, $request->notes ?? '', $actualAmount);
            return redirect()->route('shifts.index')->with('success', 'تم إقفال الوردية بنجاح');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function reopen(Shift $shift)
    {
        try {
            $this->service->reopenShift($shift, auth()->user());
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
        $shift->load(['user', 'payments.reservation.guest', 'payments.reservation.room', 'withdrawals']);
        return view('shifts.handover', compact('shift'));
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
