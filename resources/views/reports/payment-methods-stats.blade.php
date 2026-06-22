@extends('layouts.app')
@section('title', 'إحصائيات طرق الدفع')
@section('page-title', 'إحصائيات طرق الدفع')

@section('content')
<div dir="rtl">

{{-- Filter --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-5">
    <form method="GET" class="flex gap-3 items-end flex-wrap">
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">من تاريخ</label>
            <input type="date" name="from" value="{{ $from }}" onchange="this.form.submit()"
                   class="border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-blue-400">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">إلى تاريخ</label>
            <input type="date" name="to" value="{{ $to }}" onchange="this.form.submit()"
                   class="border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-blue-400">
        </div>
        <button type="submit" class="px-4 py-2 text-white rounded-lg text-sm bg-blue-600 hover:bg-blue-700">عرض</button>
        <button type="button" onclick="window.print()" class="px-4 py-2 text-blue-600 rounded-lg text-sm border border-blue-300 hover:bg-blue-50">طباعة</button>
        <button type="button" onclick="exportToExcel()" class="px-4 py-2 text-green-600 rounded-lg text-sm border border-green-300 hover:bg-green-50">تصدير</button>
    </form>
</div>

{{-- Summary Cards --}}
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-5">
    <div class="bg-white rounded-xl shadow-sm border border-blue-100 p-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs text-gray-500 mb-1">إجمالي المدفوعات</p>
                <p class="text-2xl font-bold text-blue-600">{{ number_format($totalAmount, 0) }}</p>
            </div>
            <div class="text-3xl">💰</div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-green-100 p-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs text-gray-500 mb-1">عدد العمليات</p>
                <p class="text-2xl font-bold text-green-600">{{ $totalCount }}</p>
            </div>
            <div class="text-3xl">📊</div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-purple-100 p-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs text-gray-500 mb-1">المتوسط لكل عملية</p>
                <p class="text-2xl font-bold text-purple-600">{{ $totalCount > 0 ? number_format($totalAmount / $totalCount, 0) : 0 }}</p>
            </div>
            <div class="text-3xl">📈</div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-orange-100 p-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs text-gray-500 mb-1">طرق الدفع المستخدمة</p>
                <p class="text-2xl font-bold text-orange-600">{{ $byMethod->count() }}</p>
            </div>
            <div class="text-3xl">🎯</div>
        </div>
    </div>
</div>

{{-- Payment Methods Breakdown --}}
<div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mb-5">
    {{-- Pie Chart --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <h3 class="font-bold text-gray-800 mb-4">توزيع المدفوعات حسب الطريقة</h3>
        <div class="h-80 flex items-center justify-center">
            <canvas id="paymentMethodChart"></canvas>
        </div>
    </div>

    {{-- Methods Table --}}
    <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <h3 class="font-bold text-gray-800 mb-4">تفاصيل كل طريقة دفع</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">طريقة الدفع</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">العدد</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الإجمالي (ر.ي)</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">المتوسط (ر.ي)</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">النسبة %</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($byMethod as $method => $data)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-semibold text-gray-700">
                            <span class="inline-block px-2.5 py-1 rounded-full text-xs font-bold
                                {{ $method === 'cash' ? 'bg-green-100 text-green-800' : ($method === 'bank_transfer' ? 'bg-blue-100 text-blue-800' : ($method === 'pos' ? 'bg-purple-100 text-purple-800' : 'bg-gray-100 text-gray-800')) }}">
                                {{ $methodLabels[$method] ?? $method }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-gray-700 font-medium">{{ $data['count'] }}</td>
                        <td class="px-4 py-3 font-bold text-gray-900">{{ number_format($data['total'], 0) }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ number_format($data['average'], 0) }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <div class="flex-1 h-2 bg-gray-200 rounded-full overflow-hidden">
                                    <div class="h-full rounded-full bg-blue-500" style="width:{{ ($data['total'] / $totalAmount * 100) }}%"></div>
                                </div>
                                <span class="text-xs font-bold text-gray-700 min-w-fit">{{ round(($data['total'] / $totalAmount * 100)) }}%</span>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-gray-400">لا توجد بيانات</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Daily Trends --}}
@if($dailyByMethod->isNotEmpty())
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
    <h3 class="font-bold text-gray-800 mb-4">اتجاه المدفوعات اليومي</h3>
    <div class="h-96 flex items-center justify-center">
        <canvas id="dailyTrendChart"></canvas>
    </div>
</div>
@endif

</div>

<style media="print">
    .no-print { display: none; }
    body { margin: 0; padding: 1cm; }
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js"></script>
<script>
    const methodColors = {
        'cash': '#10b981',
        'bank_transfer': '#3b82f6',
        'pos': '#a855f7',
        'check': '#f59e0b',
        'credit_card': '#ef4444'
    };

    // Payment Method Pie Chart
    const ctx1 = document.getElementById('paymentMethodChart')?.getContext('2d');
    if (ctx1) {
        new Chart(ctx1, {
            type: 'doughnut',
            data: {
                labels: [
                    @foreach($byMethod as $method => $data)
                    '{{ $methodLabels[$method] ?? $method }} ({{ $data['count'] }})',
                    @endforeach
                ],
                datasets: [{
                    data: [
                        @foreach($byMethod as $method => $data)
                        {{ $data['total'] }},
                        @endforeach
                    ],
                    backgroundColor: [
                        @foreach($byMethod as $method => $data)
                        '{{ methodColors[$method] ?? "#808080" }}',
                        @endforeach
                    ],
                    borderColor: '#fff',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { usePointStyle: true, padding: 15 }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const value = context.parsed;
                                const percent = (value / {{ $totalAmount }} * 100).toFixed(1);
                                return context.label.split('(')[0] + ': ' + number_format(value) + ' ر.ي (' + percent + '%)';
                            }
                        }
                    }
                }
            }
        });
    }

    // Daily Trend Line Chart
    @if($dailyByMethod->isNotEmpty())
    const ctx2 = document.getElementById('dailyTrendChart')?.getContext('2d');
    if (ctx2) {
        const dates = [
            @foreach($dailyByMethod as $date => $methods)
            '{{ substr($date, 8) }}/{{ substr($date, 5, 2) }}',
            @endforeach
        ];

        const datasets = [];
        const methodsUsed = collect(@json($byMethod->keys()->toArray()));

        @foreach($byMethod->keys() as $method)
        datasets.push({
            label: '{{ $methodLabels[$method] ?? $method }}',
            data: [
                @foreach($dailyByMethod as $date => $methods)
                {{ $methods[$method] ?? 0 }},
                @endforeach
            ],
            borderColor: '{{ methodColors[$method] ?? "#808080" }}',
            backgroundColor: '{{ methodColors[$method] ?? "#808080" }}22',
            borderWidth: 2,
            fill: true,
            tension: 0.4
        });
        @endforeach

        new Chart(ctx2, {
            type: 'line',
            data: { labels: dates, datasets: datasets },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: { usePointStyle: true, padding: 15 }
                    }
                },
                scales: {
                    y: { beginAtZero: true, title: { display: true, text: 'المبلغ (ر.ي)' } }
                }
            }
        });
    }
    @endif

    function number_format(num, decimals = 0) {
        return num.toFixed(decimals).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }

    function exportToExcel() {
        const table = document.querySelector('table');
        if (!table) return;
        const csv = [];
        const rows = table.querySelectorAll('tr');

        rows.forEach(row => {
            const cells = [];
            row.querySelectorAll('td, th').forEach(cell => {
                cells.push('"' + cell.textContent.trim().replace(/"/g, '""') + '"');
            });
            csv.push(cells.join(','));
        });

        const link = document.createElement('a');
        link.href = 'data:text/csv;charset=utf-8,%EF%BB%BF' + encodeURIComponent(csv.join('\n'));
        link.download = `طرق_الدفع_${new Date().toLocaleDateString('ar-SA')}.csv`;
        link.click();
    }
</script>

@endsection
