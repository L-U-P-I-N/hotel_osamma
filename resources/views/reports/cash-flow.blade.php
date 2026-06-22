@extends('layouts.app')
@section('title', 'تدفق النقد')
@section('page-title', 'تقرير تدفق النقد (Cash Flow)')

@section('content')
<div class="mb-5 flex gap-3">
    <div class="flex gap-2 items-center">
        <label class="text-sm text-gray-600">من:</label>
        <input type="date" id="fromDate" value="{{ $from }}" class="border border-gray-200 rounded-lg px-3 py-1.5 text-sm">
    </div>
    <div class="flex gap-2 items-center">
        <label class="text-sm text-gray-600">إلى:</label>
        <input type="date" id="toDate" value="{{ $to }}" class="border border-gray-200 rounded-lg px-3 py-1.5 text-sm">
    </div>
    <button onclick="filterByDate()" class="px-4 py-1.5 bg-primary-600 text-white rounded-lg text-sm">فلتر</button>
</div>

<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-5">
    <div class="bg-white rounded-xl border border-green-200 shadow-sm p-4">
        <p class="text-xs text-gray-500 mb-1">إجمالي الدخل</p>
        <p class="text-2xl font-bold text-green-600">{{ number_format($totalIn, 0) }} <span class="text-sm font-normal">ر.ي</span></p>
    </div>
    <div class="bg-white rounded-xl border border-red-200 shadow-sm p-4">
        <p class="text-xs text-gray-500 mb-1">إجمالي الخروج</p>
        <p class="text-2xl font-bold text-red-600">{{ number_format($totalOut, 0) }} <span class="text-sm font-normal">ر.ي</span></p>
    </div>
    <div class="bg-white rounded-xl border shadow-sm p-4">
        <p class="text-xs text-gray-500 mb-1">صافي التدفق</p>
        <p class="text-2xl font-bold {{ $netFlow >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
            {{ $netFlow >= 0 ? '+' : '' }}{{ number_format($netFlow, 0) }}
        </p>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 mb-5">
    <h3 class="font-semibold text-gray-800 mb-4">رسم بياني التدفق اليومي</h3>
    <canvas id="cashFlowChart" height="80"></canvas>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100">
    <div class="px-5 py-4 border-b border-gray-100">
        <h3 class="font-semibold text-gray-800">تفصيل يومي</h3>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm" dir="rtl">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-right font-medium text-gray-600">التاريخ</th>
                    <th class="px-4 py-3 text-center font-medium text-gray-600">الدخل</th>
                    <th class="px-4 py-3 text-center font-medium text-gray-600">الخروج</th>
                    <th class="px-4 py-3 text-center font-medium text-gray-600">الصافي اليومي</th>
                    <th class="px-4 py-3 text-center font-medium text-gray-600">الرصيد المتراكم</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($days as $day)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-medium text-gray-800">{{ $day['label'] }}</td>
                    <td class="px-4 py-3 text-center font-bold text-green-600">{{ number_format($day['revenue'], 0) }}</td>
                    <td class="px-4 py-3 text-center font-bold text-red-600">{{ number_format($day['expense'], 0) }}</td>
                    <td class="px-4 py-3 text-center {{ $day['net'] >= 0 ? 'text-emerald-600' : 'text-red-600' }} font-bold">
                        {{ $day['net'] >= 0 ? '+' : '' }}{{ number_format($day['net'], 0) }}
                    </td>
                    <td class="px-4 py-3 text-center text-gray-800 font-bold">{{ number_format($day['balance'], 0) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
(function() {
    const ctx = document.getElementById('cashFlowChart');
    if (!ctx) return;
    const labels = @json($days->pluck('label'));
    const revenue = @json($days->pluck('revenue'));
    const expense = @json($days->pluck('expense'));
    const balance = @json($days->pluck('balance'));
    new Chart(ctx, {
        type: 'line',
        data: {
            labels,
            datasets: [
                {
                    label: 'الرصيد المتراكم',
                    data: balance,
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59,130,246,.05)',
                    borderWidth: 3,
                    pointRadius: 4,
                    pointBackgroundColor: '#3b82f6',
                    yAxisID: 'y',
                    tension: 0.3,
                },
                {
                    label: 'الدخل',
                    data: revenue,
                    backgroundColor: 'rgba(16,185,129,.6)',
                    borderRadius: 4,
                    yAxisID: 'y1',
                },
                {
                    label: 'الخروج',
                    data: expense,
                    backgroundColor: 'rgba(239,68,68,.5)',
                    borderRadius: 4,
                    yAxisID: 'y1',
                },
            ]
        },
        options: {
            responsive: true,
            interaction: { mode: 'index', intersect: false },
            plugins: { legend: { position: 'bottom', labels: { font: { size: 11 } } } },
            scales: {
                y: { type: 'linear', position: 'left', title: { display: true, text: 'الرصيد المتراكم' } },
                y1: { type: 'linear', position: 'right', title: { display: true, text: 'الدخل/الخروج' } },
            }
        }
    });
})();

function filterByDate() {
    const from = document.getElementById('fromDate').value;
    const to = document.getElementById('toDate').value;
    window.location.href = `{{ route('reports.cashFlow') }}?from=${from}&to=${to}`;
}
</script>
@endpush
@endsection
