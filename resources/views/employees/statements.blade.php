@extends('layouts.app')
@section('title', 'كشف حساب الموظفين')
@section('page-title', 'كشف حساب الموظفين')
@section('back-url', route('employees.index'))

@section('content')
<div class="space-y-5" dir="rtl">

    <div class="flex items-start justify-between flex-wrap gap-3">
        <div>
            <h2 class="text-2xl font-black text-gray-800">كشف حساب الموظفين</h2>
            <p class="text-gray-500 text-sm mt-1">إجماليات كل موظف خلال الفترة — الرواتب المستحقة والمدفوعة والسلف</p>
        </div>
        <a href="{{ route('employees.statements.pdf', ['from' => $from, 'to' => $to]) }}" target="_blank"
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

    {{-- بطاقات الإجمالي --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
            <div class="text-xs text-gray-500">إجمالي صافي الرواتب</div>
            <div class="text-xl font-black text-gray-800 mt-1">{{ number_format($totals['salaries_net'], 0) }} <span class="text-xs font-normal text-gray-400">ر.ي</span></div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
            <div class="text-xs text-gray-500">المدفوع</div>
            <div class="text-xl font-black text-green-700 mt-1">{{ number_format($totals['salaries_paid'], 0) }} <span class="text-xs font-normal text-gray-400">ر.ي</span></div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
            <div class="text-xs text-gray-500">غير المدفوع (مستحق للموظفين)</div>
            <div class="text-xl font-black text-red-700 mt-1">{{ number_format($totals['salaries_due'], 0) }} <span class="text-xs font-normal text-gray-400">ر.ي</span></div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
            <div class="text-xs text-gray-500">إجمالي السلف والمسحوبات</div>
            <div class="text-xl font-black text-amber-700 mt-1">{{ number_format($totals['advances'], 0) }} <span class="text-xs font-normal text-gray-400">ر.ي</span></div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-x-auto">
        <table class="w-full text-sm text-right">
            <thead class="bg-gray-800 text-white">
                <tr class="text-xs">
                    <th class="px-4 py-3 font-bold">الموظف</th>
                    <th class="px-4 py-3 font-bold">الوظيفة</th>
                    <th class="px-4 py-3 font-bold">الراتب الأساسي</th>
                    <th class="px-4 py-3 font-bold">عدد الأشهر</th>
                    <th class="px-4 py-3 font-bold">صافي الرواتب</th>
                    <th class="px-4 py-3 font-bold">المدفوع</th>
                    <th class="px-4 py-3 font-bold">غير المدفوع</th>
                    <th class="px-4 py-3 font-bold">السلف</th>
                    <th class="px-4 py-3 font-bold"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($rows as $row)
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-4 py-3 font-bold text-gray-800">{{ $row['employee']->name }}</td>
                    <td class="px-4 py-3 text-gray-500 text-xs">{{ $row['employee']->position ?? '—' }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ number_format((float) $row['employee']->base_salary, 0) }}</td>
                    <td class="px-4 py-3 text-gray-500 text-center">{{ $row['months_count'] }}</td>
                    <td class="px-4 py-3 font-semibold text-gray-800">{{ number_format($row['salaries_net'], 0) }}</td>
                    <td class="px-4 py-3 font-semibold text-green-700">{{ number_format($row['salaries_paid'], 0) }}</td>
                    <td class="px-4 py-3 font-semibold {{ $row['salaries_due'] > 0 ? 'text-red-700' : 'text-gray-300' }}">{{ number_format($row['salaries_due'], 0) }}</td>
                    <td class="px-4 py-3 font-semibold {{ $row['advances'] > 0 ? 'text-amber-700' : 'text-gray-300' }}">{{ number_format($row['advances'], 0) }}</td>
                    <td class="px-4 py-3">
                        <a href="{{ route('employees.statement', ['employee' => $row['employee']->id, 'from' => $from, 'to' => $to]) }}"
                           class="text-xs px-3 py-1.5 rounded-lg border border-gray-200 text-gray-600 hover:border-blue-400 hover:text-blue-600 transition whitespace-nowrap">
                            كشف تفصيلي
                        </a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="9" class="px-4 py-12 text-center text-gray-400">لا يوجد موظفون</td></tr>
                @endforelse
            </tbody>
            @if($rows->isNotEmpty())
            <tfoot class="bg-blue-50 font-bold text-primary-900">
                <tr>
                    <td class="px-4 py-3" colspan="4">الإجمالي</td>
                    <td class="px-4 py-3">{{ number_format($totals['salaries_net'], 0) }}</td>
                    <td class="px-4 py-3 text-green-700">{{ number_format($totals['salaries_paid'], 0) }}</td>
                    <td class="px-4 py-3 text-red-700">{{ number_format($totals['salaries_due'], 0) }}</td>
                    <td class="px-4 py-3 text-amber-700">{{ number_format($totals['advances'], 0) }}</td>
                    <td></td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
</div>
@endsection
