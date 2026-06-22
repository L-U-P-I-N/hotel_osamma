@extends('layouts.app')
@section('title', 'تقرير أعمار الديون')
@section('page-title', 'تقرير أعمار الديون')

@section('content')
<div dir="rtl">

{{-- Filter & Export --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-5">
    <div class="flex flex-wrap items-end gap-3 justify-between mb-3">
        <form method="GET" class="flex gap-2 items-center flex-wrap">
            <input type="text" name="search" placeholder="🔍 ابحث عن النزيل..." value="{{ request('search') }}"
                   class="border border-gray-200 rounded-lg px-3 py-1.5 text-sm outline-none focus:border-blue-400">
            <select name="bucket" class="border border-gray-200 rounded-lg px-3 py-1.5 text-sm outline-none focus:border-blue-400">
                <option value="">كل الفئات</option>
                <option value="current" {{ request('bucket') == 'current' ? 'selected' : '' }}>جارية (≤30 يوم)</option>
                <option value="30_60" {{ request('bucket') == '30_60' ? 'selected' : '' }}>31–60 يوم</option>
                <option value="60_90" {{ request('bucket') == '60_90' ? 'selected' : '' }}>61–90 يوم</option>
                <option value="over_90" {{ request('bucket') == 'over_90' ? 'selected' : '' }}>أكثر من 90 يوم</option>
            </select>
            <button type="submit" class="px-4 py-1.5 text-white rounded-lg text-sm bg-blue-600 hover:bg-blue-700">فلتر</button>
            <a href="{{ route('reports.agedDebts') }}" class="px-3 py-1.5 text-xs text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50">إعادة تعيين</a>
        </form>

        <div class="flex gap-2">
            <button onclick="window.print()" class="px-3 py-1.5 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50 flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                طباعة
            </button>
            <a href="javascript:;" onclick="exportDebtsToExcel()" class="px-3 py-1.5 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50 flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Excel
            </a>
        </div>
    </div>
</div>

{{-- Summary Cards --}}
<div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-5">
    <div class="bg-white rounded-xl shadow-sm border border-red-100 p-4 text-center">
        <p class="text-xs text-gray-500 mb-1">إجمالي الديون</p>
        <p class="text-xl font-bold text-red-700">{{ number_format($totalDebt, 0) }}</p>
        <p class="text-xs text-gray-400">ر.ي</p>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-yellow-100 p-4 text-center">
        <p class="text-xs text-gray-500 mb-1">🟡 جارية (≤30 يوم)</p>
        <p class="text-xl font-bold text-yellow-600">{{ number_format($buckets['current']['total'], 0) }}</p>
        <p class="text-xs text-gray-400">{{ $buckets['current']['count'] }} حجز</p>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-orange-100 p-4 text-center">
        <p class="text-xs text-gray-500 mb-1">🟠 31–60 يوم</p>
        <p class="text-xl font-bold text-orange-600">{{ number_format($buckets['30_60']['total'], 0) }}</p>
        <p class="text-xs text-gray-400">{{ $buckets['30_60']['count'] }} حجز</p>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-red-100 p-4 text-center">
        <p class="text-xs text-gray-500 mb-1">🔴 61–90 يوم</p>
        <p class="text-xl font-bold text-red-600">{{ number_format($buckets['60_90']['total'], 0) }}</p>
        <p class="text-xs text-gray-400">{{ $buckets['60_90']['count'] }} حجز</p>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-red-200 p-4 text-center">
        <p class="text-xs text-gray-500 mb-1">⛔ +90 يوم</p>
        <p class="text-xl font-bold text-red-800">{{ number_format($buckets['over_90']['total'], 0) }}</p>
        <p class="text-xs text-gray-400">{{ $buckets['over_90']['count'] }} حجز</p>
    </div>
</div>

{{-- Chart --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 mb-5">
    <h3 class="font-semibold text-gray-700 text-sm mb-4">توزيع الديون حسب فترة التأخر</h3>
    <canvas id="debtsAgeChart" height="60"></canvas>
</div>

{{-- Detailed table --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-100">
    <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
        <h3 class="font-semibold text-gray-700 text-sm">تفاصيل الديون مصنّفة حسب العمر</h3>
        <span class="text-xs text-gray-400">اليوم: {{ now()->format('d/m/Y') }}</span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">النزيل</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الغرفة</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">تاريخ الخروج</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">أيام منذ الخروج</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الإجمالي</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">المدفوع</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">المتبقي</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">التصنيف</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($reservations as $res)
                @php
                    $balance  = $res->total_amount - $res->paid_amount;
                    $refDate  = $res->actual_check_out ?? $res->check_out_date;
                    $daysAgo  = $refDate ? now()->startOfDay()->diffInDays($refDate->startOfDay()) : 0;
                    if ($daysAgo <= 30)      { $badge = ['جارية', 'bg-yellow-100 text-yellow-700']; }
                    elseif ($daysAgo <= 60)  { $badge = ['31–60 يوم', 'bg-orange-100 text-orange-700']; }
                    elseif ($daysAgo <= 90)  { $badge = ['61–90 يوم', 'bg-red-100 text-red-600']; }
                    else                     { $badge = ['+90 يوم', 'bg-red-200 text-red-800']; }
                @endphp
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-medium text-gray-800">{{ $res->guest->full_name ?? '—' }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $res->room->room_number ?? '—' }}</td>
                    <td class="px-4 py-3 text-gray-500 text-xs">{{ $refDate?->format('d/m/Y') ?? '—' }}</td>
                    <td class="px-4 py-3 text-center">
                        <span class="font-bold {{ $daysAgo > 60 ? 'text-red-600' : 'text-gray-700' }}">{{ $daysAgo }}</span>
                    </td>
                    <td class="px-4 py-3 text-gray-700">{{ number_format($res->total_amount, 0) }}</td>
                    <td class="px-4 py-3 text-green-700">{{ number_format($res->paid_amount, 0) }}</td>
                    <td class="px-4 py-3 font-bold text-red-600">{{ number_format($balance, 0) }}</td>
                    <td class="px-4 py-3">
                        <span class="text-xs px-2 py-0.5 rounded-full {{ $badge[1] }}">{{ $badge[0] }}</span>
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex gap-1.5">
                            <a href="{{ route('reservations.show', $res) }}" class="text-xs px-2 py-1 rounded border border-gray-200 text-gray-600 hover:bg-gray-50">تفاصيل</a>
                            <a href="{{ route('guests.statement', $res->guest) }}" class="text-xs px-2 py-1 rounded border border-blue-200 text-blue-600 hover:bg-blue-50">كشف حساب</a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="9" class="px-4 py-8 text-center text-gray-400">لا توجد ديون — ممتاز!</td></tr>
                @endforelse
            </tbody>
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
    const ctx = document.getElementById('debtsAgeChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['جارية\n(≤30 يوم)', '31–60 يوم', '61–90 يوم', '+90 يوم'],
                datasets: [{
                    label: 'إجمالي الديون (ر.ي)',
                    data: [
                        {{ $buckets['current']['total'] }},
                        {{ $buckets['30_60']['total'] }},
                        {{ $buckets['60_90']['total'] }},
                        {{ $buckets['over_90']['total'] }}
                    ],
                    backgroundColor: ['#fcd34d', '#fb923c', '#ef4444', '#7f1d1d'],
                    borderRadius: 6,
                }]
            },
            options: {
                responsive: true,
                indexAxis: 'x',
                plugins: {
                    legend: { position: 'top' },
                    tooltip: { callbacks: { label: (c) => number_format(c.parsed.y, 0) + ' ر.ي' } }
                },
                scales: { y: { beginAtZero: true } }
            }
        });
    }
})();

function exportDebtsToExcel() {
    const table = document.querySelector('table');
    let html = '<table>' + table.innerHTML + '</table>';
    const link = document.createElement('a');
    link.href = 'data:application/vnd.ms-excel,' + encodeURIComponent(html);
    link.download = 'aged-debts-{{ now()->format("d-m-Y") }}.xls';
    link.click();
}

function number_format(n) {
    return n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}
</script>
@endpush
