<?php
namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $month = (int) $request->input('month', now()->month);
        $year  = (int) $request->input('year', now()->year);

        $employees = Employee::where('is_active', true)->orderBy('name')->get();

        $daysInMonth = Carbon::createFromDate($year, $month, 1)->daysInMonth;

        $records = Attendance::whereYear('date', $year)
            ->whereMonth('date', $month)
            ->whereIn('employee_id', $employees->pluck('id'))
            ->get()
            ->keyBy(fn($a) => $a->employee_id . '_' . $a->date->day);

        return view('attendance.index', compact('employees', 'month', 'year', 'daysInMonth', 'records'));
    }

    public function daily(Request $request)
    {
        $date = $request->input('date', today()->toDateString());

        $employees = Employee::where('is_active', true)->orderBy('name')->get();

        $records = Attendance::whereDate('date', $date)
            ->whereIn('employee_id', $employees->pluck('id'))
            ->get()
            ->keyBy('employee_id');

        return view('attendance.daily', compact('employees', 'date', 'records'));
    }

    public function saveDaily(Request $request)
    {
        $date = $request->input('date', today()->toDateString());

        $request->validate([
            'date'                   => 'required|date',
            'attendance'             => 'required|array',
            'attendance.*.status'    => 'required|in:present,absent,late,leave,holiday',
            'attendance.*.check_in'  => 'nullable|date_format:H:i',
            'attendance.*.check_out' => 'nullable|date_format:H:i',
            'attendance.*.notes'     => 'nullable|string|max:255',
        ]);

        foreach ($request->input('attendance', []) as $employeeId => $data) {
            Attendance::updateOrCreate(
                ['employee_id' => $employeeId, 'date' => $date],
                [
                    'status'    => $data['status'],
                    'check_in'  => $data['check_in'] ?? null,
                    'check_out' => $data['check_out'] ?? null,
                    'notes'     => $data['notes'] ?? null,
                ]
            );
        }

        return back()->with('success', 'تم حفظ سجل الحضور بنجاح');
    }

    public function destroy(Request $request, int $employeeId)
    {
        $date = $request->input('date');
        Attendance::where('employee_id', $employeeId)->whereDate('date', $date)->delete();
        return back()->with('success', 'تم حذف سجل الحضور');
    }
}
