<?php
namespace App\Http\Controllers;

use App\Services\ShiftService;
use Illuminate\Http\Request;

class ShiftController extends Controller
{
    public function __construct(private ShiftService $service) {}

    public function index()
    {
        $user = auth()->user();
        $activeShift = $this->service->getActiveShift($user);
        $recentShifts = $this->service->getHistory($user, 7);

        if ($activeShift) {
            $activeShift->load(['payments.reservation.guest', 'withdrawals']);
        }

        $allActive = $user->isAdmin() ? $this->service->getAllActiveShifts() : collect();

        return view('shifts.index', compact('activeShift', 'recentShifts', 'allActive'));
    }

    public function open(Request $request)
    {
        $request->validate([
            'shift_type' => 'required|in:morning,evening,night',
        ], [
            'shift_type.required' => 'نوع الوردية مطلوب',
            'shift_type.in'       => 'نوع الوردية غير صالح',
        ]);

        try {
            $shift = $this->service->openShift(auth()->user(), $request->shift_type);
            return redirect()->route('shifts.index')
                ->with('success', 'تم فتح الوردية ' . $shift->type_label . ' بنجاح');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function addWithdrawal(Request $request)
    {
        $request->validate([
            'amount'             => 'required|numeric|min:0.01',
            'currency'           => 'required|in:YER,SAR,USD',
            'withdrawn_by_name'  => 'required|string|max:100',
            'handed_by_name'     => 'nullable|string|max:100',
            'notes'              => 'nullable|string|max:500',
        ], [
            'amount.required'            => 'المبلغ مطلوب',
            'amount.numeric'             => 'يجب أن يكون المبلغ رقماً',
            'amount.min'                 => 'المبلغ يجب أن يكون أكبر من صفر',
            'currency.required'          => 'العملة مطلوبة',
            'withdrawn_by_name.required' => 'اسم المستلم مطلوب',
        ]);

        $shift = $this->service->getActiveShift(auth()->user());
        if (!$shift) {
            return back()->withErrors(['error' => 'لا توجد وردية مفتوحة، افتح وردية أولاً']);
        }

        try {
            $this->service->addWithdrawal($shift, $request->all());
            return back()->with('success', 'تم تسجيل السحب بنجاح');
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
}
