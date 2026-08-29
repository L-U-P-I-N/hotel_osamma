@extends('layouts.app')
@section('title', 'كشف حساب ' . $employee->name)
@section('page-title', 'كشف حساب موظف')
@section('back-url', route('employees.index'))

@php
    $monthNames = [1=>'يناير',2=>'فبراير',3=>'مارس',4=>'أبريل',5=>'مايو',6=>'يونيو',
                   7=>'يوليو',8=>'أغسطس',9=>'سبتمبر',10=>'أكتوبر',11=>'نوفمبر',12=>'ديسمبر'];
    $leaveTypes = ['annual'=>'سنوية','sick'=>'مرضية','unpaid'=>'بدون راتب','emergency'=>'طارئة'];
@endphp

@section('content')
<div class="space-y-5" dir="rtl">

    {{-- رأس الصفحة --}}
    <div class="flex items-start justify-between flex-wrap gap-3">
        <div>
            <h2 class="text-2xl font-black text-gray-800">{{ $employee->name }}</h2>
            <p class="text-gray-500 text-sm mt-1">
                {{ $employee->position ?? '—' }}
                @if($employee->hire_date) · تاريخ التعيين {{ $employee->hire_date->format('d/m/Y') }} @endif
                · الراتب الأساسي <b class="text-gray-700">{{ number_format((float) $employee->base_salary, 0) }} ر.ي</b>
            </p>
        </div>
        <a href="{{ route('employees.statement.pdf', ['employee' => $employee->id, 'from' => $from, 'to' => $to]) }}" target="_blank"
           class="flex items-center gap-2 px-5 py-2.5 bg-red-600 text-white rounded-xl text-sm font-semibold hover:bg-red-700 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
            تصدير PDF
        </a>
    </div>

    {{-- فلتر الفترة --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
        <form method="GET" class="flex flex-wrap gap-3 items-end">
            <div class="flex flex-col gap-1">
                <label class="text-xs font-medium text-gray-500">من تاريخ</label>
                <input type="date" name="from" value="{{ $from }}"
                       class="border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400 bg-white">
            </div>
            <div class="flex flex-col gap-1">
                <label class="text-xs font-medium text-gray-500">إلى تاريخ</label>
                <input type="date" name="to" value="{{ $to }}"
                       class="border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400 bg-white">
            </div>
            <button type="submit" class="px-4 py-2 text-white rounded-lg text-sm font-semibold" style="background:#0F4C75;">عرض</button>
        </form>
    </div>

    {{-- بطاقات الملخص --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
            <div class="text-xs text-gray-500">صافي الرواتب المستحقة</div>
            <div class="text-xl font-black text-gray-800 mt-1">{{ number_format($totals['salaries_net'], 0) }} <span class="text-xs font-normal text-gray-400">ر.ي</span></div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
            <div class="text-xs text-gray-500">المدفوع منها</div>
            <div class="text-xl font-black text-green-700 mt-1">{{ number_format($totals['salaries_paid'], 0) }} <span class="text-xs font-normal text-gray-400">ر.ي</span></div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
            <div class="text-xs text-gray-500">غير المدفوع (مستحق له)</div>
            <div class="text-xl font-black text-red-700 mt-1">{{ number_format($totals['salaries_due'], 0) }} <span class="text-xs font-normal text-gray-400">ر.ي</span></div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
            <div class="text-xs text-gray-500">السلف والمسحوبات</div>
            <div class="text-xl font-black text-amber-700 mt-1">{{ number_format($totals['advances'], 0) }} <span class="text-xs font-normal text-gray-400">ر.ي</span></div>
        </div>
    </div>

    {{-- الرواتب --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200">
        <div class="px-5 py-3 border-b border-gray-100">
            <h3 class="font-bold text-gray-800 text-sm">الرواتب</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-right">
                <thead class="bg-gray-50">
                    <tr class="text-xs text-gray-500">
                        <th class="px-4 py-2.5 font-medium">الشهر</th>
                        <th class="px-4 py-2.5 font-medium">الأساسي</th>
                        <th class="px-4 py-2.5 font-medium">حوافز</th>
                        <th class="px-4 py-2.5 font-medium">خصومات</th>
                        <th class="px-4 py-2.5 font-medium">خصم سلف</th>
                        <th class="px-4 py-2.5 font-medium">خصم غياب</th>
                        <th class="px-4 py-2.5 font-medium">الصافي</th>
                        <th class="px-4 py-2.5 font-medium">الحالة</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($salaries as $s)
                    <tr>
                        <td class="px-4 py-2.5 whitespace-nowrap font-semibold text-gray-700">{{ $monthNames[$s->month] ?? $s->month }} {{ $s->year }}</td>
                        <td class="px-4 py-2.5 text-gray-600">{{ number_format((float) $s->base_salary, 0) }}</td>
                        <td class="px-4 py-2.5 text-green-600">{{ (float) $s->bonuses > 0 ? number_format((float) $s->bonuses, 0) : '—' }}</td>
                        <td class="px-4 py-2.5 text-red-600">{{ (float) $s->deductions > 0 ? number_format((float) $s->deductions, 0) : '—' }}</td>
                        <td class="px-4 py-2.5 text-amber-600">{{ (float) $s->withdrawals_deduction > 0 ? number_format((float) $s->withdrawals_deduction, 0) : '—' }}</td>
                        <td class="px-4 py-2.5 text-amber-600">{{ (float) $s->attendance_deduction > 0 ? number_format((float) $s->attendance_deduction, 0) : '—' }}</td>
                        <td class="px-4 py-2.5 font-black text-gray-800">{{ number_format((float) $s->net_salary, 0) }}</td>
                        <td class="px-4 py-2.5">
                            @if($s->status === 'paid')
                            <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-700">مدفوع</span>
                            @else
                            <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-700">غير مدفوع</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="px-4 py-8 text-center text-gray-400">لا توجد رواتب مسجَّلة خلال هذه الفترة</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- السلف والمسحوبات --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200">
        <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-bold text-gray-800 text-sm">السلف والمسحوبات</h3>
            <span class="text-xs font-bold text-amber-700 bg-amber-50 px-2.5 py-1 rounded-lg">
                الإجمالي: {{ number_format($totals['advances'], 0) }} ر.ي
            </span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-right">
                <thead class="bg-gray-50">
                    <tr class="text-xs text-gray-500">
                        <th class="px-4 py-2.5 font-medium">التاريخ</th>
                        <th class="px-4 py-2.5 font-medium">المبلغ</th>
                        <th class="px-4 py-2.5 font-medium">التصنيف</th>
                        <th class="px-4 py-2.5 font-medium">البيان</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($advances as $a)
                    <tr>
                        <td class="px-4 py-2.5 whitespace-nowrap text-gray-600">{{ $a->expense_date?->format('d/m/Y') }}</td>
                        <td class="px-4 py-2.5 font-semibold text-amber-700">{{ number_format((float) $a->amount, 0) }} {{ $a->currency }}</td>
                        <td class="px-4 py-2.5 text-gray-600">{{ \App\Models\Expense::categoryLabel($a->category) }}</td>
                        <td class="px-4 py-2.5 text-gray-400 text-xs">{{ $a->description ?? '—' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="px-4 py-8 text-center text-gray-400">لا توجد سلف أو مسحوبات خلال هذه الفترة</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- الحضور والإجازات --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
            <h3 class="font-bold text-gray-800 text-sm mb-4">ملخص الحضور</h3>
            <div class="grid grid-cols-3 gap-3 text-center">
                <div class="rounded-xl p-3 bg-green-50 border border-green-100">
                    <div class="text-2xl font-black text-green-700">{{ $totals['present_days'] }}</div>
                    <div class="text-xs text-green-600 mt-0.5">أيام حضور</div>
                </div>
                <div class="rounded-xl p-3 bg-red-50 border border-red-100">
                    <div class="text-2xl font-black text-red-700">{{ $totals['absent_days'] }}</div>
                    <div class="text-xs text-red-600 mt-0.5">أيام غياب</div>
                </div>
                <div class="rounded-xl p-3 bg-amber-50 border border-amber-100">
                    <div class="text-2xl font-black text-amber-700">{{ $totals['late_days'] }}</div>
                    <div class="text-xs text-amber-600 mt-0.5">أيام تأخير</div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
                <h3 class="font-bold text-gray-800 text-sm">الإجازات</h3>
                <span class="text-xs text-gray-500">{{ $totals['leave_days'] }} يوم</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-right">
                    <thead class="bg-gray-50">
                        <tr class="text-xs text-gray-500">
                            <th class="px-4 py-2.5 font-medium">النوع</th>
                            <th class="px-4 py-2.5 font-medium">من</th>
                            <th class="px-4 py-2.5 font-medium">إلى</th>
                            <th class="px-4 py-2.5 font-medium">أيام</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse($leaves as $l)
                        <tr>
                            <td class="px-4 py-2.5 text-gray-700">{{ $leaveTypes[$l->type] ?? $l->type }}</td>
                            <td class="px-4 py-2.5 text-gray-600 whitespace-nowrap">{{ $l->from_date?->format('d/m/Y') }}</td>
                            <td class="px-4 py-2.5 text-gray-600 whitespace-nowrap">{{ $l->to_date?->format('d/m/Y') }}</td>
                            <td class="px-4 py-2.5 font-semibold text-gray-700">{{ $l->days }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="px-4 py-8 text-center text-gray-400">لا توجد إجازات</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
