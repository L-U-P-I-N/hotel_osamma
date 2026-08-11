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

    public function pdf(Request $request)
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

        $pdf = pdf_load_view('attendance.pdf', compact('employees', 'month', 'year', 'daysInMonth', 'records'));
        $pdf->setPaper('a3', 'landscape');

        $dompdf = $pdf->getDomPDF();
        $options = $dompdf->getOptions();
        $options->setFontDir(storage_path('fonts'));
        $options->setFontCache(storage_path('fonts'));
        $dompdf->setOptions($options);

        return $pdf->download('attendance-' . \App\Models\Salary::monthName($month) . '-' . $year . '.pdf');
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

        // upsert بدل updateOrCreate: الأخير يقرأ ثم يكتب على دفعتين منفصلتين،
        // فإذا وصل طلبان لنفس الموظف/التاريخ بفارق لحظات (نقرتان على أحد زرَّي
        // "حفظ" في نفس الصفحة، أو بطء الشبكة) يحاول كلاهما الإدراج فيتصادمان مع
        // القيد الفريد (employee_id, date) ويُرمى استثناء غير مُعالَج (500).
        // upsert عملية ذرّية واحدة (INSERT ... ON DUPLICATE KEY UPDATE) لا تصطدم.
        $now = now();
        $rows = [];
        foreach ($request->input('attendance', []) as $employeeId => $data) {
            $rows[] = [
                'employee_id' => $employeeId,
                'date'        => $date,
                'status'      => $data['status'],
                'check_in'    => $data['check_in'] ?? null,
                'check_out'   => $data['check_out'] ?? null,
                'notes'       => $data['notes'] ?? null,
                'created_at'  => $now,
                'updated_at'  => $now,
            ];
        }

        if (!empty($rows)) {
            Attendance::upsert($rows, ['employee_id', 'date'], ['status', 'check_in', 'check_out', 'notes', 'updated_at']);
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
