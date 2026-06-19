@extends('layouts.app')
@section('title', 'تقرير المصروفات')
@section('page-title', 'تقرير المصروفات')

@section('content')
<div dir="rtl">

<!-- Filter Form -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-5">
    <form method="GET" class="flex flex-wrap gap-3 items-end">
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">من تاريخ</label>
            <input type="date" name="date_from" value="{{ $dateFrom }}" class="border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">إلى تاريخ</label>
            <input type="date" name="date_to" value="{{ $dateTo }}" class="border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">الفئة</label>
            <select name="category" class="border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none">
                <option value="">جميع الفئات</option>
                @foreach($categories as $key => $label)
                <option value="{{ $key }}" {{ request('category')==$key?'selected':'' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="px-4 py-2 text-white rounded-lg text-sm transition" style="background:#0F4C75;">توليد التقرير</button>
        <a href="{{ route('expenses.index') }}" class="px-4 py-2 bg-gray-100 text-gray-600 rounded-lg text-sm hover:bg-gray-200 transition">← القائمة</a>
    </form>
</div>

<!-- Total Card -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 mb-5">
    <div class="flex items-center justify-between">
        <div>
            <p class="text-xs text-gray-500 font-medium">إجمالي المصروفات</p>
            <p class="text-3xl font-bold text-red-600 mt-1">{{ number_format($total, 0) }} <span class="text-base font-normal text-gray-400">ر.ي</span></p>
            <p class="text-xs text-gray-400 mt-1">{{ $expenses->count() }} عملية · من {{ $dateFrom }} إلى {{ $dateTo }}</p>
        </div>
        <div class="w-14 h-14 bg-red-50 rounded-xl flex items-center justify-center">
            <svg class="w-7 h-7 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
        </div>
    </div>
</div>

<!-- By Category -->
@if($byCategory->isNotEmpty())
<div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-5">
    <div class="px-6 py-4 border-b border-gray-100">
        <h3 class="font-semibold text-gray-700">توزيع المصروفات حسب الفئة</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm" dir="rtl">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الفئة</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">عدد العمليات</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الإجمالي (ر.ي)</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">النسبة</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($byCategory as $cat => $data)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3">
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                            {{ \App\Models\Expense::categoryLabel($cat) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-gray-600">{{ $data['count'] }}</td>
                    <td class="px-4 py-3 font-bold text-red-600">{{ number_format($data['total'], 0) }}</td>
                    <td class="px-4 py-3 text-gray-500 text-xs">
                        @if($total > 0)
                        {{ number_format(($data['total'] / $total) * 100, 1) }}%
                        @else—@endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

<!-- Detailed Table -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100">
    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
        <h3 class="font-semibold text-gray-700">تفاصيل المصروفات</h3>
        <span class="text-xs text-gray-400">{{ $dateFrom }} — {{ $dateTo }}</span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm" dir="rtl">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">التاريخ</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الفئة</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">المبلغ (ر.ي)</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">اسم المستلم</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الوصف</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">سُجِّل بواسطة</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($expenses as $expense)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 text-gray-600 whitespace-nowrap">{{ $expense->expense_date->format('d/m/Y') }}</td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                            {{ \App\Models\Expense::categoryLabel($expense->category) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 font-bold text-red-600">{{ number_format($expense->amount, 0) }}</td>
                    <td class="px-4 py-3 text-gray-700">{{ $expense->recipient_name ?? '—' }}</td>
                    <td class="px-4 py-3 text-gray-500 max-w-xs truncate">{{ $expense->description ?? '—' }}</td>
                    <td class="px-4 py-3 text-gray-500 whitespace-nowrap">{{ $expense->paidBy?->name ?? '—' }}</td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">لا توجد مصروفات في هذه الفترة</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

</div>
@endsection
