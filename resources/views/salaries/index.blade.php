@extends('layouts.app')
@section('title', 'الرواتب')
@section('page-title', 'معالجة الرواتب الشهرية')

@section('content')
<div dir="rtl">

<!-- Header & Filter -->
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
                @foreach(range(now()->year, now()->year - 3, -1) as $y)
                <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="px-4 py-2 text-white rounded-lg text-sm transition" style="background:#0F4C75;">عرض</button>
        @can('hr.create')
        <a href="{{ route('salaries.create') }}" class="flex items-center gap-2 px-4 py-2 text-white rounded-lg text-sm transition mr-auto" style="background:#0F4C75;">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            إنشاء قسيمة راتب
        </a>
        @endcan
    </form>
</div>

<!-- Salaries Table -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100">
    <div class="px-6 py-4 border-b border-gray-100">
        <h3 class="font-semibold text-gray-700">رواتب {{ \App\Models\Salary::monthName($month) }} {{ $year }}</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm" dir="rtl">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الموظف</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الراتب الأساسي</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">المكافآت</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الخصومات</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الصافي</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الحالة</th>
                    @can('hr.edit')
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">إجراءات</th>
                    @endcan
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($salaries as $salary)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-semibold text-gray-800">{{ $salary->employee->name }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ number_format($salary->base_salary, 2) }}</td>
                    <td class="px-4 py-3 text-green-700">{{ number_format($salary->bonuses, 2) }}</td>
                    <td class="px-4 py-3 text-red-600">{{ number_format($salary->deductions, 2) }}</td>
                    <td class="px-4 py-3 font-bold" style="color:#0F4C75;">{{ number_format($salary->net_salary, 2) }}</td>
                    <td class="px-4 py-3">
                        @if($salary->status === 'paid')
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">مدفوعة</span>
                        @else
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">مسودة</span>
                        @endif
                    </td>
                    @can('hr.edit')
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2">
                            {{-- PDF slip --}}
                            <a href="{{ route('salaries.pdf', $salary) }}" target="_blank"
                               class="inline-flex items-center gap-1 text-xs px-2.5 py-1.5 rounded-lg bg-red-600 text-white hover:bg-red-700 transition">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                تصدير PDF
                            </a>
                            @if($salary->status === 'draft')
                            {{-- Edit --}}
                            <a href="{{ route('salaries.edit', $salary) }}"
                               class="text-xs px-2.5 py-1.5 rounded-lg border border-blue-200 text-blue-600 hover:bg-blue-50 transition">
                                تعديل
                            </a>
                            {{-- Mark paid --}}
                            <form method="POST" action="{{ route('salaries.markPaid', $salary) }}">
                                @csrf @method('PATCH')
                                <button type="submit" class="text-xs px-2.5 py-1.5 rounded-lg text-white transition" style="background:#0F4C75;">
                                    مدفوعة
                                </button>
                            </form>
                            {{-- Delete --}}
                            @can('hr.delete')
                            <form method="POST" action="{{ route('salaries.destroy', $salary) }}"
                                  onsubmit="return confirm('هل تريد حذف قسيمة الراتب هذه؟')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-xs px-2.5 py-1.5 rounded-lg border border-red-200 text-red-600 hover:bg-red-50 transition">
                                    حذف
                                </button>
                            </form>
                            @endcan
                            @endif
                        </div>
                    </td>
                    @endcan
                </tr>
                @empty
                <tr><td colspan="7" class="px-4 py-8 text-center text-gray-400">لا توجد رواتب لهذا الشهر</td></tr>
                @endforelse
            </tbody>
            @if($salaries->isNotEmpty())
            <tfoot class="bg-gray-50">
                <tr>
                    <td class="px-4 py-3 font-bold text-gray-700">الإجمالي</td>
                    <td class="px-4 py-3 font-bold text-gray-700">{{ number_format($salaries->sum('base_salary'), 2) }}</td>
                    <td class="px-4 py-3 font-bold text-green-700">{{ number_format($salaries->sum('bonuses'), 2) }}</td>
                    <td class="px-4 py-3 font-bold text-red-600">{{ number_format($salaries->sum('deductions'), 2) }}</td>
                    <td class="px-4 py-3 font-bold" style="color:#0F4C75;">{{ number_format($salaries->sum('net_salary'), 2) }}</td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
</div>

</div>
@endsection
