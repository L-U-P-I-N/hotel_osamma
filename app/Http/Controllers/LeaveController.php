<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Leave;
use Illuminate\Http\Request;

class LeaveController extends Controller
{
    public function index(Request $request)
    {
        $query = Leave::with('employee', 'reviewer')->orderBy('id', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->input('employee_id'));
        }

        $leaves    = $query->paginate(20);
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
            'type'        => 'required|in:annual,sick,emergency',
            'start_date'  => 'required|date',
            'end_date'    => 'required|date|after_or_equal:start_date',
            'reason'      => 'nullable|string',
        ]);

        Leave::create($data);

        return redirect()->route('leaves.index')->with('success', 'تم تقديم طلب الإجازة بنجاح');
    }

    public function update(Request $request, Leave $leave)
    {
        $data = $request->validate([
            'status'       => 'required|in:approved,rejected',
            'review_notes' => 'nullable|string',
        ]);

        $data['reviewed_by'] = auth()->id();

        $leave->update($data);

        $msg = $data['status'] === 'approved' ? 'تمت الموافقة على الإجازة' : 'تم رفض الإجازة';
        return back()->with('success', $msg);
    }
}
