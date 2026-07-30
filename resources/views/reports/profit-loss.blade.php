@extends('layouts.app')
@section('title', 'تقرير الأرباح والخسائر')
@section('page-title', 'تقرير الأرباح والخسائر')

@section('content')
<div dir="rtl">

{{-- Filter & Export --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-5">
    {{-- تصفية سريعة: يوم / أسبوع / شهر / سنة --}}
    <div class="flex flex-wrap gap-2 mb-4">
        @php
            $presets = [
                'today' => 'اليوم',
                'week'  => 'هذا الأسبوع',
                'month' => 'هذا الشهر',
                'year'  => 'هذه السنة',
            ];
        @endphp
        @foreach($presets as $key => $label)
        <a href="{{ route('reports.profitLoss', ['preset' => $key]) }}"
           class="px-4 py-2 rounded-lg text-sm font-semibold transition
                  {{ ($preset ?? '') === $key ? 'bg-blue-600 text-white' : 'border border-gray-200 text-gray-600 hover:bg-gray-50' }}">
            {{ $label }}
        </a>
        @endforeach
        <a href="{{ route('reports.profitLoss', ['from' => now()->subMonth()->startOfMonth()->toDateString(), 'to' => now()->subMonth()->endOfMonth()->toDateString()]) }}"
           class="px-4 py-2 rounded-lg text-sm font-semibold transition border border-gray-200 text-gray-600 hover:bg-gray-50">
            الشهر الماضي
        </a>
    </div>

    <div class="flex flex-wrap items-end gap-3 justify-between">
        <form method="GET" class="flex flex-wrap items-end gap-3">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">من تاريخ (تصفية مخصَّصة)</label>
                <input type="date" name="from" value="{{ $from }}"
                       class="border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none focus:border-blue-400">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">إلى تاريخ</label>
                <input type="date" name="to" value="{{ $to }}"
                       class="border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none focus:border-blue-400">
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

{{-- شفافية: إجمالي الإيرادات قبل الاسترجاعات، والاسترجاعات، وأي مدفوعات بعملة أجنبية --}}
@if($totalRefunds > 0 || $foreignRevenue->isNotEmpty())
<div class="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-5 text-sm space-y-1.5">
    <p class="text-gray-500">إجمالي المُستلَم (قبل الاسترجاعات): <span class="font-semibold text-gray-700">{{ number_format($totalRevenueGross, 0) }} ر.ي</span></p>
    @if($totalRefunds > 0)
    <p class="text-red-700">الاسترجاعات (مخصومة من الإيراد أدناه): <span class="font-bold">- {{ number_format($totalRefunds, 0) }} ر.ي</span></p>
    @endif
    @if($foreignRevenue->isNotEmpty())
    <p class="text-amber-800">
        مدفوعات بعملة أجنبية (غير محتسبة في الأرقام أدناه — لا سعر صرف موحَّد):
        @foreach($foreignRevenue as $fr)
        <span class="font-semibold">{{ number_format($fr->total, 0) }} {{ $fr->currency }}</span> ({{ $fr->count }} دفعة)@if(!$loop->last)، @endif
        @endforeach
    </p>
    @endif
</div>
@endif

{{-- P&L Summary with KPIs --}}
@php
    $margin = $totalRevenue > 0 ? ($netProfit / $totalRevenue) * 100 : 0;
    $expenseRatio = $totalRevenue > 0 ? ($totalExpenses / $totalRevenue) * 100 : 0;
@endphp

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-5">
    <div class="bg-white rounded-xl shadow-sm border border-green-100 p-4">
        <div class="flex items-center justify-between mb-2">
            <p class="text-xs font-medium text-gray-500">إجمالي الإيرادات</p>
            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <p class="text-2xl font-bold text-green-700">{{ number_format($totalRevenue, 0) }}</p>
        <p class="text-xs text-gray-400 mt-1">ر.ي</p>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-red-100 p-4">
        <div class="flex items-center justify-between mb-2">
            <p class="text-xs font-medium text-gray-500">إجمالي المصروفات</p>
            <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
        </div>
        <p class="text-2xl font-bold text-red-600">{{ number_format($totalExpenses, 0) }}</p>
        <p class="text-xs text-gray-400 mt-1">{{ number_format($expenseRatio, 1) }}% من الإيراد</p>
    </div>

    <div class="rounded-xl p-4 shadow-sm border {{ $netProfit >= 0 ? 'bg-blue-50 border-blue-100' : 'bg-red-50 border-red-200' }}">
        <div class="flex items-center justify-between mb-2">
            <p class="text-xs font-medium text-gray-500">{{ $netProfit >= 0 ? 'صافي الربح' : 'صافي الخسارة' }}</p>
            <svg class="w-5 h-5 {{ $netProfit >= 0 ? 'text-blue-600' : 'text-red-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
        </div>
        <p class="text-2xl font-bold {{ $netProfit >= 0 ? 'text-blue-700' : 'text-red-700' }}">{{ number_format($netProfit, 0) }}</p>
        <p class="text-xs text-gray-400 mt-1">{{ $netProfit >= 0 ? '+' : '' }}{{ number_format($margin, 1) }}% هامش</p>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-purple-100 p-4">
        <p class="text-xs font-medium text-gray-500 mb-2">متوسط يومي</p>
        @php $days = \Carbon\Carbon::parse($from)->diffInDays(\Carbon\Carbon::parse($to)) + 1; @endphp
        <p class="text-2xl font-bold text-purple-700">{{ number_format($totalRevenue / $days, 0) }}</p>
        <p class="text-xs text-gray-400 mt-1">إيراد يومي</p>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-amber-100 p-4">
        <p class="text-xs font-medium text-gray-500 mb-2">نسبة التغطية</p>
        @php $coverage = $totalExpenses > 0 ? ($totalRevenue / $totalExpenses) : 0; @endphp
        <p class="text-2xl font-bold text-amber-700">{{ number_format($coverage, 2) }}x</p>
        <p class="text-xs text-gray-400 mt-1">الإيراد يغطي المصروفات</p>
    </div>
</div>

{{-- Charts Row --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-5">
    {{-- Revenue Chart --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <h3 class="font-semibold text-gray-700 text-sm mb-3">توزيع الإيرادات حسب الطريقة</h3>
        <canvas id="revenueChart" height="80"></canvas>
    </div>

    {{-- Expense Chart --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <h3 class="font-semibold text-gray-700 text-sm mb-3">توزيع المصروفات حسب الفئة</h3>
        <canvas id="expenseChart" height="80"></canvas>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-5">
    {{-- Revenue breakdown table --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-semibold text-gray-700 text-sm">تفصيل الإيرادات</h3>
            <span class="text-sm font-bold text-green-700">{{ number_format($totalRevenue, 0) }} ر.ي</span>
        </div>
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2.5 text-right text-xs font-medium text-gray-500">المصدر</th>
                    <th class="px-4 py-2.5 text-right text-xs font-medium text-gray-500">المبلغ (ر.ي)</th>
                    <th class="px-4 py-2.5 text-right text-xs font-medium text-gray-500">النسبة</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($revenueByMethod as $row)
                <tr>
                    <td class="px-4 py-2.5 text-gray-700">
                        {{ match($row->method) {'cash'=>'نقداً','bank_transfer'=>'تحويل بنكي','pos'=>'POS',default=>$row->method} }}
                        <span class="text-xs text-gray-400">({{ $row->count }} دفعة)</span>
                    </td>
                    <td class="px-4 py-2.5 font-semibold text-green-700">{{ number_format($row->total, 0) }}</td>
                    <td class="px-4 py-2.5 text-gray-500">
                        {{ $totalRevenue > 0 ? number_format(($row->total / $totalRevenue) * 100, 1) : 0 }}%
                    </td>
                </tr>
                @endforeach
                @if($revenueByMethod->isEmpty())
                <tr><td colspan="3" class="px-4 py-6 text-center text-gray-400">لا توجد إيرادات</td></tr>
                @endif
            </tbody>
        </table>
    </div>

    {{-- Expense breakdown table --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-semibold text-gray-700 text-sm">تفصيل المصروفات</h3>
            <span class="text-sm font-bold text-red-600">{{ number_format($totalExpenses, 0) }} ر.ي</span>
        </div>
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2.5 text-right text-xs font-medium text-gray-500">الفئة</th>
                    <th class="px-4 py-2.5 text-right text-xs font-medium text-gray-500">المبلغ (ر.ي)</th>
                    <th class="px-4 py-2.5 text-right text-xs font-medium text-gray-500">النسبة من الإيرادات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($expensesByCategory as $row)
                <tr>
                    <td class="px-4 py-2.5 text-gray-700">
                        {{ \App\Models\Expense::categoryLabel($row->category) }}
                        <span class="text-xs text-gray-400">({{ $row->count }})</span>
                    </td>
                    <td class="px-4 py-2.5 font-semibold text-red-600">{{ number_format($row->total, 0) }}</td>
                    <td class="px-4 py-2.5 text-gray-500">
                        {{ $totalRevenue > 0 ? number_format(($row->total / $totalRevenue) * 100, 1) : '—' }}%
                    </td>
                </tr>
                @endforeach
                @if($expensesByCategory->isEmpty())
                <tr><td colspan="3" class="px-4 py-6 text-center text-gray-400">لا توجد مصروفات</td></tr>
                @endif
            </tbody>
        </table>
    </div>
</div>

{{-- Monthly trend --}}
@if($monthlyTrend->isNotEmpty())
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 mb-5">
    <h3 class="font-semibold text-gray-700 text-sm mb-4">الاتجاه الشهري</h3>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2.5 text-right text-xs font-medium text-gray-500">الشهر</th>
                    <th class="px-4 py-2.5 text-right text-xs font-medium text-gray-500">الإيرادات</th>
                    <th class="px-4 py-2.5 text-right text-xs font-medium text-gray-500">المصروفات</th>
                    <th class="px-4 py-2.5 text-right text-xs font-medium text-gray-500">الربح/الخسارة</th>
                    <th class="px-4 py-2.5 text-right text-xs font-medium text-gray-500">الهامش</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($monthlyTrend as $row)
                @php $monthNet = $row->revenue - $row->expenses; @endphp
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-2.5 font-medium text-gray-700">{{ $row->month_label }}</td>
                    <td class="px-4 py-2.5 text-green-700">{{ number_format($row->revenue, 0) }}</td>
                    <td class="px-4 py-2.5 text-red-600">{{ number_format($row->expenses, 0) }}</td>
                    <td class="px-4 py-2.5 font-bold {{ $monthNet >= 0 ? 'text-blue-700' : 'text-red-600' }}">
                        {{ number_format($monthNet, 0) }}
                    </td>
                    <td class="px-4 py-2.5 text-gray-500">
                        {{ $row->revenue > 0 ? number_format(($monthNet / $row->revenue) * 100, 1) . '%' : '—' }}
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot class="bg-gray-50 font-bold">
                <tr>
                    <td class="px-4 py-3 text-gray-700">الإجمالي</td>
                    <td class="px-4 py-3 text-green-700">{{ number_format($totalRevenue, 0) }}</td>
                    <td class="px-4 py-3 text-red-600">{{ number_format($totalExpenses, 0) }}</td>
                    <td class="px-4 py-3 {{ $netProfit >= 0 ? 'text-blue-700' : 'text-red-600' }}">{{ number_format($netProfit, 0) }}</td>
                    <td class="px-4 py-3 text-gray-500">{{ $totalRevenue > 0 ? number_format(($netProfit / $totalRevenue) * 100, 1) . '%' : '—' }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endif

</div>

<style media="print">
    .btn-group, form, [onclick*="export"], [onclick*="print"] { display: none !important; }
    body { background: white; }
    .rounded-xl { page-break-inside: avoid; }
</style>

@endsection

@push('scripts')
@php
    // لون ثابت لكل عنصر (لا يتغيّر بترتيب البيانات) — من الباليت الفئوي المُتحقَّق
    // منه (تباين واضح بين كل لونين متجاورين، آمن لعمى الألوان). "اللون يتبع
    // الكيان لا رتبته": بدل تلوين حسب موضع العنصر في القائمة (يتغيّر مع كل فترة
    // زمنية إذ يُعاد ترتيب الفئات حسب المبلغ)، كل طريقة دفع/فئة مصروف لها لون
    // ثابت دائماً — راجع مهارة dataviz (references/palette.md).
    $categoricalPalette = ['#2a78d6', '#eb6834', '#1baf7a', '#eda100', '#e87ba4', '#008300', '#4a3aa7', '#e34948'];
    $methodColorMap = ['cash' => $categoricalPalette[0], 'bank_transfer' => $categoricalPalette[1], 'pos' => $categoricalPalette[2]];
    $categoryColorMap = [
        'maintenance' => $categoricalPalette[0], 'salary'  => $categoricalPalette[1],
        'electricity' => $categoricalPalette[2], 'food'    => $categoricalPalette[3],
        'cleaning'    => $categoricalPalette[4], 'other'   => $categoricalPalette[5],
    ];

    $revenueLabels = $revenueByMethod->map(fn($r) => match($r->method) {
        'cash' => 'نقداً',
        'bank_transfer' => 'تحويل بنكي',
        'pos' => 'POS',
        default => $r->method,
    });
    $revenueColors = $revenueByMethod->map(fn($r) => $methodColorMap[$r->method] ?? '#898781');
    $expenseLabels = $expensesByCategory->map(fn($r) => \App\Models\Expense::categoryLabel($r->category));
    $expenseColors = $expensesByCategory->map(fn($r) => $categoryColorMap[$r->category] ?? '#898781');
@endphp
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
(function() {
    // Revenue Pie Chart
    const revenueCtx = document.getElementById('revenueChart');
    if (revenueCtx) {
        new Chart(revenueCtx, {
            type: 'doughnut',
            data: {
                labels: @json($revenueLabels),
                datasets: [{
                    data: @json($revenueByMethod->pluck('total')),
                    backgroundColor: @json($revenueColors),
                    borderColor: '#fff',
                    borderWidth: 2,
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'bottom', labels: { font: { size: 12 } } }
                }
            }
        });
    }

    // Expense Pie Chart
    const expenseCtx = document.getElementById('expenseChart');
    if (expenseCtx) {
        new Chart(expenseCtx, {
            type: 'doughnut',
            data: {
                labels: @json($expenseLabels),
                datasets: [{
                    data: @json($expensesByCategory->pluck('total')),
                    backgroundColor: @json($expenseColors),
                    borderColor: '#fff',
                    borderWidth: 2,
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'bottom', labels: { font: { size: 12 } } }
                }
            }
        });
    }
})();

function exportToExcel() {
    const table = document.body.innerHTML;
    const link = document.createElement('a');
    link.href = 'data:application/vnd.ms-excel,' + encodeURIComponent(table);
    link.download = 'profit-loss-{{ now()->format("d-m-Y") }}.xls';
    link.click();
}
</script>
@endpush
