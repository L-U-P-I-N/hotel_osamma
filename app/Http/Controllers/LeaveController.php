<?php
namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Leave;
use Carbon\Carbon;
use Illuminate\Http\Request;

class LeaveController extends Controller
{
    public function index(Request $request)
    {
        $query = Leave::with('employee', 'creator')->orderBy('from_date', 'desc');

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->input('employee_id'));
        }
        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }
        if ($request->filled('year')) {
            $query->whereYear('from_date', $request->input('year'));
        }

        $leaves    = $query->paginate(25)->withQueryString();
        $employees = Employee::where('is_active', true)->orderBy('name')->get();

        return view('leaves.index', compact('leaves', 'employees'));
    }

    public function create()
    {
        $employees = Employee::where('is_active', true)->orderBy('name')->get();
        return view('leaves.create', compact('employees'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'type'        => 'required|in:annual,sick,emergency,unpaid',
            'from_date'   => 'required|date',
            'to_date'     => 'required|date|after_or_equal:from_date',
            'notes'       => 'nullable|string|max:500',
        ]);

        $from = Carbon::parse($data['from_date']);
        $to   = Carbon::parse($data['to_date']);
        $data['days']       = $from->diffInDays($to) + 1;
        $data['created_by'] = auth()->id();

        Leave::create($data);

        return redirect()->route('leaves.index')->with('success', 'تم تسجيل الإجازة بنجاح');
    }

    public function destroy(Leave $leave)
    {
        $leave->delete();
        return back()->with('success', 'تم حذف الإجازة');
    }
}
