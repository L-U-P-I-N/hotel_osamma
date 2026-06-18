<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Employee;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $date = $request->input('date', today()->toDateString());
        $selectedDate = Carbon::parse($date);

        $employees = Employee::where('is_active', true)->orderBy('name')->get();

        // Load attendance for the selected date
        $attendanceMap = Attendance::where('date', $date)
            ->pluck('status', 'employee_id');

        return view('attendance.index', compact('employees', 'selectedDate', 'attendanceMap', 'date'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'date'         => 'required|date',
            'attendance'   => 'required|array',
            'attendance.*' => 'required|in:present,absent,late,on_leave',
        ]);

        $date = $request->input('date');

        foreach ($request->input('attendance', []) as $employeeId => $status) {
            Attendance::updateOrCreate(
                ['employee_id' => $employeeId, 'date' => $date],
                ['status' => $status]
            );
        }

        return redirect()->route('attendance.index', ['date' => $date])
            ->with('success', 'تم حفظ سجل الحضور بنجاح');
    }
}
