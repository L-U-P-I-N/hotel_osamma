@extends('layouts.app')
@section('title', 'تقرير المصروفات الشهرية')
@section('page-title', 'تقرير المصروفات الشهرية')

@section('content')
<div dir="rtl">

{{-- Filter & Export --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-5">
    <div class="flex flex-wrap items-end gap-3 justify-between mb-3">
        <form method="GET" class="flex gap-2 items-end flex-wrap">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">الشهر والسنة</label>
                <input type="month" name="month" value="{{ request('month', now()->format('Y-m')) }}"
                       class="border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-blue-400">
            </div>
            <button type="submit" class="px-4 py-2 text-white rounded-lg text-sm bg-blue-600 hover:bg-blue-700">عرض</button>
        </form>

        <div class="flex gap-2">
            <button onclick="window.print()" class="px-3 py-2 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50 flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                طباعة
            </button>
            <a href="javascript:;" onclick="exportToExcel()" class="px-3 py-2 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50 flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Excel
            </a>
        </div>
    </div>
</div>

{{-- Summary Cards --}}
@php
    $totalByCategory = collect($expensesByCategory)->keyBy('category');
@endphp

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-5">
    <div class="bg-white rounded-xl shadow-sm border border-red-100 p-4">
        <p class="text-xs text-gray-500 mb-1">إجمالي المصروفات</p>
        <p class="text-2xl font-bold text-red-600">{{ number_format($totalExpenses, 0) }}</p>
        <p class="text-xs text-gray-400 mt-1">ر.ي</p>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-orange-100 p-4">
        <p class="text-xs text-gray-500 mb-1">عدد البنود</p>
        <p class="text-2xl font-bold text-orange-600">{{ $expenseCount }}</p>
        <p class="text-xs text-gray-400 mt-1">عملية تسجيل</p>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-purple-100 p-4">
        <p class="text-xs text-gray-500 mb-1">متوسط البند</p>
        <p class="text-2xl font-bold text-purple-600">{{ number_format($totalExpenses / max($expenseCount, 1), 0) }}</p>
        <p class="text-xs text-gray-400 mt-1">ر.ي</p>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-blue-100 p-4">
        <p class="text-xs text-gray-500 mb-1">أكبر بند</p>
        @php $max = $expensesByCategory->max('total'); @endphp
        <p class="text-2xl font-bold text-blue-600">{{ number_format($max, 0) }}</p>
        <p class="text-xs text-gray-400 mt-1">ر.ي</p>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-green-100 p-4">
        <p class="text-xs text-gray-500 mb-1">أقل بند</p>
        @php $min = $expensesByCategory->min('total'); @endphp
        <p class="text-2xl font-bold text-green-600">{{ number_format($min, 0) }}</p>
        <p class="text-xs text-gray-400 mt-1">ر.ي</p>
    </div>
</div>

{{-- Charts Row --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-5">
    {{-- Pie Chart --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <h3 class="font-semibold text-gray-700 text-sm mb-4">توزيع المصروفات حسب الفئة</h3>
        <canvas id="expenseDistributionChart" height="100"></canvas>
    </div>

    {{-- Bar Chart --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <h3 class="font-semibold text-gray-700 text-sm mb-4">مقارنة الفئات</h3>
        <canvas id="expenseCategoryChart" height="100"></canvas>
    </div>
</div>

{{-- Detailed Table --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-100">
    <div class="px-5 py-4 border-b border-gray-100">
        <h3 class="font-semibold text-gray-700 text-sm">تفصيل المصروفات حسب الفئة</h3>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الفئة</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500">عدد العمليات</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500">الإجمالي (ر.ي)</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500">النسبة</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500">المتوسط</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($expensesByCategory as $row)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-medium text-gray-800">
                        {{ \App\Models\Expense::categoryLabel($row->category) }}
                    </td>
                    <td class="px-4 py-3 text-center text-gray-600">{{ $row->count }}</td>
                    <td class="px-4 py-3 text-center font-semibold text-red-600">{{ number_format($row->total, 0) }}</td>
                    <td class="px-4 py-3 text-center">
                        <span class="px-2 py-1 rounded-lg text-xs font-bold bg-red-100 text-red-700">
                            {{ number_format(($row->total / $totalExpenses) * 100, 1) }}%
                        </span>
                    </td>
                    <td class="px-4 py-3 text-center text-gray-600">{{ number_format($row->total / $row->count, 0) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot class="bg-gray-50 font-bold">
                <tr>
                    <td class="px-4 py-3 text-gray-700">الإجمالي</td>
                    <td class="px-4 py-3 text-center">{{ $expensesByCategory->sum('count') }}</td>
                    <td class="px-4 py-3 text-center text-red-600">{{ number_format($totalExpenses, 0) }}</td>
                    <td class="px-4 py-3 text-center text-red-600">100%</td>
                    <td class="px-4 py-3 text-center">{{ number_format($totalExpenses / max($expenseCount, 1), 0) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

</div>

<style media="print">
    form, [onclick*="export"], [onclick*="print"] { display: none !important; }
    body { background: white; }
</style>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
(function() {
    // Pie Chart
    const pieCtx = document.getElementById('expenseDistributionChart');
    if (pieCtx) {
        new Chart(pieCtx, {
            type: 'doughnut',
            data: {
                labels: @json($expensesByCategory->map(fn($r) => \App\Models\Expense::categoryLabel($r->category))),
                datasets: [{
                    data: @json($expensesByCategory->pluck('total')),
                    backgroundColor: ['#ef4444', '#f97316', '#f59e0b', '#ec4899', '#8b5cf6', '#6366f1'],
                    borderColor: '#fff',
                    borderWidth: 2,
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { position: 'bottom' } }
            }
        });
    }

    // Bar Chart
    const barCtx = document.getElementById('expenseCategoryChart');
    if (barCtx) {
        new Chart(barCtx, {
            type: 'bar',
            data: {
                labels: @json($expensesByCategory->map(fn($r) => \App\Models\Expense::categoryLabel($r->category))),
                datasets: [{
                    label: 'المبلغ (ر.ي)',
                    data: @json($expensesByCategory->pluck('total')),
                    backgroundColor: ['#ef4444', '#f97316', '#f59e0b', '#ec4899', '#8b5cf6', '#6366f1'],
                    borderRadius: 6,
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                plugins: { legend: { position: 'top' } },
                scales: { x: { beginAtZero: true } }
            }
        });
    }
})();

function exportToExcel() {
    const table = document.querySelector('table');
    let html = '<table>' + table.innerHTML + '</table>';
    const link = document.createElement('a');
    link.href = 'data:application/vnd.ms-excel,' + encodeURIComponent(html);
    link.download = 'monthly-expenses-{{ now()->format("d-m-Y") }}.xls';
    link.click();
}
</script>
@endpush
