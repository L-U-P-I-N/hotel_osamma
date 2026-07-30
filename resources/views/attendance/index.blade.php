@extends('layouts.app')
@section('title', 'كشف الحضور الشهري')
@section('page-title', 'كشف الحضور الشهري')

@section('content')
<div dir="rtl">

@if(session('success'))
<div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">{{ session('success') }}</div>
@endif

{{-- Header --}}
<div class="flex items-center justify-between mb-5 flex-wrap gap-3">
    @can('attendance.create')
    <a href="{{ route('attendance.daily') }}"
       class="flex items-center gap-2 px-4 py-2 text-white rounded-lg text-sm transition" style="background:#0F4C75;">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        تسجيل حضور اليوم
    </a>
    @endcan
</div>

{{-- Filter --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-5">
    <form method="GET" class="flex flex-wrap gap-3 items-end">
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">الشهر</label>
            <select name="month" class="border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none">
                @foreach(range(1,12) as $m)
                <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>{{ \App\Models\Salary::monthName($m) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">السنة</label>
            <select name="year" class="border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none">
                @foreach(range(now()->year, now()->year - 2, -1) as $y)
                <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="px-4 py-2 text-white rounded-lg text-sm transition" style="background:#0F4C75;">عرض</button>
        <a href="{{ route('attendance.pdf', ['month' => $month, 'year' => $year]) }}" target="_blank"
           class="flex items-center gap-2 px-4 py-2 bg-red-600 text-white rounded-lg text-sm hover:bg-red-700 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
            تصدير PDF
        </a>
    </form>
</div>

@if($employees->isEmpty())
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-10 text-center text-gray-400">
    لا يوجد موظفون نشطون
</div>
@else

{{-- Summary Cards --}}
@php
    $allStatuses = $records->values();
    $presentCount  = $allStatuses->where('status', 'present')->count();
    $absentCount   = $allStatuses->where('status', 'absent')->count();
    $lateCount     = $allStatuses->where('status', 'late')->count();
    $leaveCount    = $allStatuses->where('status', 'leave')->count();
    $totalRecorded = $allStatuses->count();
@endphp
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-5">
    <div class="bg-white rounded-xl shadow-sm border border-green-100 p-4 text-center">
        <p class="text-2xl font-bold text-green-700">{{ $presentCount }}</p>
        <p class="text-xs text-gray-500 mt-1">سجل حضور</p>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-red-100 p-4 text-center">
        <p class="text-2xl font-bold text-red-700">{{ $absentCount }}</p>
        <p class="text-xs text-gray-500 mt-1">سجل غياب</p>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-yellow-100 p-4 text-center">
        <p class="text-2xl font-bold text-yellow-700">{{ $lateCount }}</p>
        <p class="text-xs text-gray-500 mt-1">سجل تأخير</p>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-blue-100 p-4 text-center">
        <p class="text-2xl font-bold text-blue-700">{{ $leaveCount }}</p>
        <p class="text-xs text-gray-500 mt-1">سجل إجازة</p>
    </div>
</div>

{{-- Monthly Sheet --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-100">
    <div class="px-5 py-3 border-b border-gray-100">
        <h3 class="font-semibold text-gray-700 text-sm">
            كشف {{ \App\Models\Salary::monthName($month) }} {{ $year }}
        </h3>
    </div>
    <div class="overflow-x-auto">
        <table class="text-xs" style="min-width: max-content;">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2 text-right font-medium text-gray-600 sticky right-0 bg-gray-50 z-10 min-w-[140px]">الموظف</th>
                    @for($d = 1; $d <= $daysInMonth; $d++)
                    @php $dayDate = \Carbon\Carbon::createFromDate($year, $month, $d); @endphp
                    <th class="px-2 py-2 text-center font-medium min-w-[36px]
                        {{ $dayDate->isWeekend() ? 'text-red-400' : 'text-gray-500' }}">
                        {{ $d }}<br>
                        <span class="text-gray-300 font-normal">{{ ['أحد','اثن','ثلث','أرب','خمس','جمع','سبت'][$dayDate->dayOfWeek] }}</span>
                    </th>
                    @endfor
                    <th class="px-3 py-2 text-center font-medium text-green-700 min-w-[48px]">حضور</th>
                    <th class="px-3 py-2 text-center font-medium text-red-600 min-w-[48px]">غياب</th>
                    <th class="px-3 py-2 text-center font-medium text-yellow-600 min-w-[48px]">تأخير</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($employees as $emp)
                @php
                    $empPresent = 0; $empAbsent = 0; $empLate = 0;
                @endphp
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-2 font-medium text-gray-800 sticky right-0 bg-white z-10">{{ $emp->name }}</td>
                    @for($d = 1; $d <= $daysInMonth; $d++)
                    @php
                        $key = $emp->id . '_' . $d;
                        $rec = $records[$key] ?? null;
                        $st  = $rec?->status ?? null;
                        if ($st === 'present') $empPresent++;
                        if ($st === 'absent')  $empAbsent++;
                        if ($st === 'late')    $empLate++;
                        $cell = match($st) {
                            'present' => ['●', 'text-green-600'],
                            'absent'  => ['●', 'text-red-500'],
                            'late'    => ['●', 'text-yellow-500'],
                            'leave'   => ['●', 'text-blue-400'],
                            'holiday' => ['—', 'text-gray-300'],
                            default   => ['·', 'text-gray-200'],
                        };
                    @endphp
                    <td class="text-center py-2 {{ $cell[1] }}" title="{{ \App\Models\Attendance::statusLabel($st ?? '') }}">
                        {{ $cell[0] }}
                    </td>
                    @endfor
                    <td class="text-center py-2 font-bold text-green-700">{{ $empPresent }}</td>
                    <td class="text-center py-2 font-bold text-red-600">{{ $empAbsent }}</td>
                    <td class="text-center py-2 font-bold text-yellow-600">{{ $empLate }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="px-5 py-3 border-t border-gray-100">
        <div class="flex flex-wrap gap-4 text-xs text-gray-500">
            <span><span class="text-green-600 font-bold">●</span> حاضر</span>
            <span><span class="text-red-500 font-bold">●</span> غائب</span>
            <span><span class="text-yellow-500 font-bold">●</span> متأخر</span>
            <span><span class="text-blue-400 font-bold">●</span> إجازة</span>
            <span><span class="text-gray-300 font-bold">—</span> عطلة</span>
            <span><span class="text-gray-200">·</span> لم يُسجَّل</span>
        </div>
    </div>
</div>
@endif

</div>
@endsection
