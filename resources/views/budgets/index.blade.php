@extends('layouts.app')
@section('title', 'الميزانية الشهرية')
@section('page-title', 'الميزانية الشهرية')

@section('content')
<div class="mb-5 flex gap-3">
    <form class="flex gap-2 items-center">
        <label class="text-sm text-gray-600">السنة:</label>
        <select name="year" onchange="this.form.submit()" class="border border-gray-200 rounded-lg px-3 py-1.5 text-sm">
            @foreach($years as $y)
            <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
            @endforeach
        </select>
    </form>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100">
    <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
        <h3 class="font-semibold text-gray-800">مقارنة الميزانية vs الفعلي — {{ $year }}</h3>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm" dir="rtl">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-right font-medium text-gray-600">الشهر</th>
                    <th class="px-4 py-3 text-center font-medium text-gray-600">الهدف الإيرادي</th>
                    <th class="px-4 py-3 text-center font-medium text-gray-600">الإيراد الفعلي</th>
                    <th class="px-4 py-3 text-center font-medium text-gray-600">نسبة التحقق %</th>
                    <th class="px-4 py-3 text-center font-medium text-gray-600">سقف المصروفات</th>
                    <th class="px-4 py-3 text-center font-medium text-gray-600">المصروفات الفعلية</th>
                    <th class="px-4 py-3 text-center font-medium text-gray-600">الوفر / الزيادة</th>
                    <th class="px-4 py-3 text-center font-medium text-gray-600">الربح المتوقع</th>
                    <th class="px-4 py-3 text-center font-medium text-gray-600">الربح الفعلي</th>
                    <th class="px-4 py-3 text-center font-medium text-gray-600">إجراءات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($months as $m)
                @php
                    $revPct   = $m['revenue_target'] > 0 ? round(($m['actual_revenue'] / $m['revenue_target']) * 100) : 0;
                    $expDiff  = $m['expense_limit'] - $m['actual_expense'];
                    $profitPct = $m['net_budget'] > 0 ? round(($m['net_actual'] / $m['net_budget']) * 100) : 0;
                @endphp
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-medium text-gray-800">{{ $m['month_name'] }}</td>
                    <td class="px-4 py-3 text-center text-gray-600">{{ number_format($m['revenue_target'], 0) }}</td>
                    <td class="px-4 py-3 text-center font-semibold text-gray-800">{{ number_format($m['actual_revenue'], 0) }}</td>
                    <td class="px-4 py-3 text-center">
                        <span class="px-2 py-1 rounded-lg text-xs font-bold {{ $revPct >= 100 ? 'bg-green-100 text-green-700' : ($revPct >= 80 ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700') }}">
                            {{ $revPct }}%
                        </span>
                    </td>
                    <td class="px-4 py-3 text-center text-gray-600">{{ number_format($m['expense_limit'], 0) }}</td>
                    <td class="px-4 py-3 text-center font-semibold">{{ number_format($m['actual_expense'], 0) }}</td>
                    <td class="px-4 py-3 text-center {{ $expDiff >= 0 ? 'text-green-600 font-bold' : 'text-red-600 font-bold' }}">
                        {{ $expDiff >= 0 ? '+' : '' }}{{ number_format($expDiff, 0) }}
                    </td>
                    <td class="px-4 py-3 text-center text-gray-600">{{ number_format($m['net_budget'], 0) }}</td>
                    <td class="px-4 py-3 text-center font-bold {{ $m['net_actual'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                        {{ $m['net_actual'] >= 0 ? '+' : '' }}{{ number_format($m['net_actual'], 0) }}
                    </td>
                    <td class="px-4 py-3 text-center">
                        @if($m['budget_id'])
                        <a href="#" class="text-xs text-primary-600 hover:underline">تعديل</a>
                        @else
                        <button @click="$dispatch('open-budget', { month: {{ $m['month'] }} })" class="text-xs text-primary-600 hover:underline">إضافة</button>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- Modal for adding budget --}}
<div x-cloak x-show="$dispatch('open-budget')" class="fixed inset-0 bg-black/20 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl max-w-md w-full p-6">
        <h3 class="font-semibold text-gray-800 mb-4">إضافة ميزانية</h3>
        <form id="budgetForm" action="{{ route('budgets.store') }}" method="POST" class="space-y-3">
            @csrf
            <input type="hidden" name="year" value="{{ $year }}">
            <input type="hidden" name="month" id="budgetMonth">
            <div>
                <label class="text-sm text-gray-600 block mb-1">الهدف الإيرادي</label>
                <input type="number" name="revenue_target" class="w-full border border-gray-200 rounded-lg px-3 py-2">
            </div>
            <div>
                <label class="text-sm text-gray-600 block mb-1">سقف المصروفات</label>
                <input type="number" name="expense_limit" class="w-full border border-gray-200 rounded-lg px-3 py-2">
            </div>
            <div class="flex gap-2 pt-2">
                <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-lg text-sm">حفظ</button>
                <button type="button" @click="close()" class="px-4 py-2 border border-gray-200 rounded-lg text-sm">إلغاء</button>
            </div>
        </form>
    </div>
</div>
@endsection
