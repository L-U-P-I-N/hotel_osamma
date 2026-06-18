@extends('layouts.app')
@section('title', 'تقرير الإيرادات')
@section('page-title', 'تقرير الإيرادات')

@section('content')
<div class="space-y-5">

<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
    <form method="GET" class="flex flex-wrap gap-3 items-end">
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">من تاريخ</label>
            <input type="date" name="from" value="{{ $from }}" class="border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">إلى تاريخ</label>
            <input type="date" name="to" value="{{ $to }}" class="border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">العملة</label>
            <select name="currency" class="border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none">
                @foreach(['YER' => 'ريال يمني (YER)', 'SAR' => 'ريال سعودي (SAR)', 'USD' => 'دولار أمريكي (USD)'] as $val => $label)
                <option value="{{ $val }}" {{ $currency === $val ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="px-4 py-2 text-white rounded-lg text-sm transition" style="background:#0F4C75;">عرض</button>
    </form>
</div>

<!-- Currency tabs summary -->
@if(count($currencyTotals) > 1)
<div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
    @foreach(['YER' => ['label' => 'ريال يمني', 'symbol' => 'ر.ي', 'color' => 'amber'], 'SAR' => ['label' => 'ريال سعودي', 'symbol' => 'ر.س', 'color' => 'green'], 'USD' => ['label' => 'دولار أمريكي', 'symbol' => '$', 'color' => 'indigo']] as $cur => $info)
    @if(isset($currencyTotals[$cur]) && $currencyTotals[$cur] > 0)
    @php $isActive = $currency === $cur; @endphp
    <a href="{{ request()->fullUrlWithQuery(['currency' => $cur]) }}"
       class="rounded-xl p-4 border-2 transition-all {{ $isActive ? 'border-primary-500 bg-primary-50' : 'border-gray-200 bg-white hover:border-gray-300' }}">
        <div class="text-xs {{ $isActive ? 'text-primary-600' : 'text-gray-500' }} font-medium">{{ $info['label'] }}</div>
        <div class="text-xl font-bold {{ $isActive ? 'text-primary-800' : 'text-gray-700' }} mt-1">{{ number_format($currencyTotals[$cur], 2) }}</div>
        <div class="text-xs {{ $isActive ? 'text-primary-500' : 'text-gray-400' }} mt-0.5">{{ $info['symbol'] }} · في الفترة المحددة</div>
    </a>
    @endif
    @endforeach
</div>
@endif

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
    @php
        $methodLabels = ['cash'=>'نقدي','pos'=>'POS','bank_transfer'=>'تحويل بنكي'];
    @endphp
    @foreach($revenueByMethod as $m)
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <div class="text-xs text-gray-500">{{ $methodLabels[$m->method] ?? $m->method }}</div>
        <div class="text-2xl font-bold text-gray-800 mt-1">{{ number_format($m->total, 2) }}</div>
        <div class="text-xs text-gray-400 mt-0.5">{{ $currencySymbol }}</div>
    </div>
    @endforeach
    <div class="rounded-xl p-5 border" style="background:#e8f0f7; border-color:#9fbedd;">
        <div class="text-xs" style="color:#0F4C75;">إجمالي الإيرادات</div>
        <div class="text-2xl font-bold mt-1" style="color:#0F4C75;">{{ number_format($totalRevenue, 2) }}</div>
        <div class="text-xs mt-0.5" style="color:#5b90c5;">{{ $currencySymbol }}</div>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
    <h3 class="font-semibold text-gray-700 mb-4">الإيرادات حسب نوع الغرفة <span class="text-xs text-gray-400 font-normal">({{ $currencySymbol }})</span></h3>
    <div style="height:256px;">
        <canvas id="revenueChart"></canvas>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100">
    <div class="px-6 py-4 border-b border-gray-100">
        <h3 class="font-semibold text-gray-700">تفصيل الإيرادات <span class="text-xs text-gray-400 font-normal">({{ $currencySymbol }})</span></h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50"><tr>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">نوع الغرفة</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الإجمالي</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">النسبة</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($revenueByType as $r)
                <tr>
                    <td class="px-4 py-3 font-medium text-gray-800">{{ $r->name }}</td>
                    <td class="px-4 py-3 font-bold text-green-700">{{ number_format($r->total, 2) }} {{ $currencySymbol }}</td>
                    <td class="px-4 py-3 text-gray-500">
                        {{ $totalRevenue > 0 ? round(($r->total / $totalRevenue) * 100) : 0 }}%
                    </td>
                </tr>
                @empty
                <tr><td colspan="3" class="px-4 py-6 text-center text-gray-400">لا توجد بيانات بالعملة المحددة في هذه الفترة</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
</div>
@endsection

@push('scripts')
<script>
new Chart(document.getElementById('revenueChart'), {
    type: 'bar',
    data: {
        labels: {!! json_encode($revenueByType->pluck('name')) !!},
        datasets: [{
            label: 'الإيرادات ({{ $currencySymbol }})',
            data: {!! json_encode($revenueByType->pluck('total')) !!},
            backgroundColor: ['#0F4C75','#D4A574','#16a34a','#2563eb','#d97706'],
            borderRadius: 6,
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true } }
    }
});
</script>
@endpush
