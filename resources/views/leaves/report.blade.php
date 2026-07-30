@extends('layouts.app')
@section('title', 'تقرير الإجازات')
@section('page-title', 'تقرير الإجازات والغياب')

@section('content')
<div class="mb-5 flex gap-3 items-center justify-between flex-wrap">
    <form class="flex gap-2 items-center">
        <label class="text-sm text-gray-600">السنة:</label>
        <select name="year" onchange="this.form.submit()" class="border border-gray-200 rounded-lg px-3 py-1.5 text-sm">
            @foreach($years as $y)
            <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
            @endforeach
        </select>
    </form>
    <a href="{{ route('leaves.report.pdf', ['year' => $year]) }}" target="_blank"
       class="flex items-center gap-2 px-4 py-2 bg-red-600 text-white rounded-lg text-sm hover:bg-red-700 transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
        تصدير PDF
    </a>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100">
    <div class="px-5 py-4 border-b border-gray-100">
        <h3 class="font-semibold text-gray-800">ملخص الإجازات — {{ $year }}</h3>
    </div>

    <div class="divide-y divide-gray-50">
        @foreach($summary as $emp)
        @php
            $annualColor = match(true) {
                $emp['annual_pct'] >= 100 => 'bg-red-50',
                $emp['annual_pct'] >= 80 => 'bg-amber-50',
                default => 'bg-green-50'
            };
        @endphp
        <div class="px-5 py-4 hover:bg-gray-50 transition">
            <div class="flex items-center justify-between mb-3">
                <div>
                    <p class="font-semibold text-gray-800">{{ $emp['employee']->name }}</p>
                    <p class="text-xs text-gray-500">{{ $emp['employee']->job_title ?? 'موظف' }}</p>
                </div>
                <div class="text-right">
                    <p class="text-lg font-bold text-gray-800">{{ $emp['total_taken'] }} أيام</p>
                    <p class="text-xs text-gray-500">إجمالي الأيام المأخوذة</p>
                </div>
            </div>

            {{-- Breakdown --}}
            <div class="grid grid-cols-2 sm:grid-cols-5 gap-2 mb-3">
                @foreach($leaveTypes as $type)
                @php $count = $emp['by_type'][$type] ?? 0; @endphp
                <div class="border border-gray-200 rounded-lg p-2 text-center">
                    <p class="text-xs text-gray-500 mb-0.5">{{ \App\Models\Leave::typeLabel($type) }}</p>
                    <p class="text-sm font-bold text-gray-800">{{ $count }}</p>
                </div>
                @endforeach
            </div>

            {{-- Annual Leave Progress --}}
            <div class="bg-gray-50 rounded-lg p-3">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-sm font-medium text-gray-700">الإجازة السنوية</p>
                    <span class="text-sm font-bold {{ $emp['annual_remaining'] > 0 ? 'text-green-600' : 'text-red-600' }}">
                        {{ $emp['annual_taken'] }}/{{ $annualEntitlement }} أيام
                    </span>
                </div>
                <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                    <div class="h-full {{ $emp['annual_pct'] >= 100 ? 'bg-red-500' : ($emp['annual_pct'] >= 80 ? 'bg-amber-400' : 'bg-green-500') }}"
                         style="width:{{ min($emp['annual_pct'], 100) }}%"></div>
                </div>
                <p class="text-xs text-gray-600 mt-1">
                    {{ $emp['annual_remaining'] > 0 ? '✓ متبقي: '.$emp['annual_remaining'].' أيام' : '✗ استنفذ الحد المسموح' }}
                </p>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endsection
