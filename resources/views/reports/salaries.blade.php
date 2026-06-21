@extends('layouts.app')
@section('title', 'تقرير الرواتب')
@section('page-title', 'تقرير الرواتب')

@section('content')
<div class="space-y-5">

<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
    <div class="flex flex-wrap gap-3 items-end">
    <form method="GET" class="flex flex-wrap gap-3 items-end">
        <div class="flex flex-col gap-1">
            <label class="text-xs font-medium text-gray-500">السنة</label>
            <select name="year" onchange="this.form.submit()"
                    class="border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400 transition bg-white">
                @foreach($years as $y)
                <option value="{{ $y }}" {{ $y == $year ? 'selected' : '' }}>{{ $y }}</option>
                @endforeach
                @if($years->isEmpty())
                <option value="{{ now()->year }}" selected>{{ now()->year }}</option>
                @endif
            </select>
        </div>
        <button type="submit" class="sr-only">عرض</button>
    </form>
    <div class="mr-auto flex gap-2">
        <a href="{{ route('reports.salaries.pdf', ['year' => $year]) }}"
           class="flex items-center gap-2 px-4 py-2 bg-red-600 text-white rounded-lg text-sm hover:bg-red-700 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
            PDF
        </a>
        <a href="{{ route('reports.salaries.excel', ['year' => $year]) }}"
           class="flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg text-sm hover:bg-green-700 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
            Excel
        </a>
    </div>
    </div>
</div>

<div class="rounded-xl p-5 border" style="background:#e8f0f7; border-color:#9fbedd;">
    <div class="text-xs" style="color:#0F4C75;">إجمالي صافي الرواتب — {{ $year }}</div>
    <div class="text-3xl font-bold mt-1" style="color:#0F4C75;">{{ number_format($totalNet, 0) }} <span class="text-lg font-normal">ر.ي</span></div>
</div>

@php
    $monthNames = [1=>'يناير',2=>'فبراير',3=>'مارس',4=>'أبريل',5=>'مايو',6=>'يونيو',7=>'يوليو',8=>'أغسطس',9=>'سبتمبر',10=>'أكتوبر',11=>'نوفمبر',12=>'ديسمبر'];
@endphp

<div class="bg-white rounded-xl shadow-sm border border-gray-100">
    <div class="px-6 py-4 border-b border-gray-100">
        <h3 class="font-semibold text-gray-700">ملخص شهري — {{ $year }}</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الشهر</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">عدد الموظفين</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">إجمالي الأساسي</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">المكافآت</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الخصومات</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الصافي</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">مدفوع / معلق</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($byMonth as $month => $data)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-medium text-gray-800">{{ $monthNames[$month] ?? $month }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $data['count'] }}</td>
                    <td class="px-4 py-3 text-gray-700">{{ number_format($data['total_base'], 0) }}</td>
                    <td class="px-4 py-3 text-green-600">{{ number_format($data['total_bonus'], 0) }}</td>
                    <td class="px-4 py-3 text-red-500">{{ number_format($data['total_ded'], 0) }}</td>
                    <td class="px-4 py-3 font-bold" style="color:#0F4C75;">{{ number_format($data['total_net'], 0) }}</td>
                    <td class="px-4 py-3 text-xs">
                        <span class="text-green-600">{{ $data['paid'] }} مدفوع</span>
                        @if($data['pending'] > 0) / <span class="text-amber-600">{{ $data['pending'] }} معلق</span>@endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="px-4 py-8 text-center text-gray-400">لا توجد رواتب مسجلة لهذه السنة</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if($salaries->isNotEmpty())
<div class="bg-white rounded-xl shadow-sm border border-gray-100">
    <div class="px-6 py-4 border-b border-gray-100">
        <h3 class="font-semibold text-gray-700">تفاصيل الرواتب</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الموظف</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الشهر</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الأساسي</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">مكافآت</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">خصومات</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الصافي</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الحالة</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($salaries as $sal)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-medium text-gray-800">{{ $sal->employee->name ?? '—' }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $monthNames[$sal->month] ?? $sal->month }} {{ $sal->year }}</td>
                    <td class="px-4 py-3 text-gray-700">{{ number_format($sal->base_salary, 0) }}</td>
                    <td class="px-4 py-3 text-green-600">{{ number_format($sal->bonuses, 0) }}</td>
                    <td class="px-4 py-3 text-red-500">{{ number_format($sal->deductions, 0) }}</td>
                    <td class="px-4 py-3 font-bold" style="color:#0F4C75;">{{ number_format($sal->net_salary, 0) }}</td>
                    <td class="px-4 py-3">
                        @if($sal->status === 'paid')
                        <span class="px-2 py-0.5 rounded-full text-xs bg-green-100 text-green-700">مدفوع</span>
                        @else
                        <span class="px-2 py-0.5 rounded-full text-xs bg-amber-100 text-amber-700">معلق</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
</div>
@endsection
