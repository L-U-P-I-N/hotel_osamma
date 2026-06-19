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
        <button type="submit" class="px-4 py-2 text-white rounded-lg text-sm transition" style="background:#0F4C75;">عرض</button>
    </form>
</div>

@if($foreignPayments->isNotEmpty())
<div class="bg-amber-50 border border-amber-200 rounded-xl p-4 flex gap-3">
    <svg class="w-5 h-5 text-amber-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
    <div>
        <p class="text-sm font-semibold text-amber-800">ملاحظة: يوجد مدفوعات بعملة أجنبية في هذه الفترة (غير مشمولة في الإجمالي)</p>
        <ul class="mt-1 space-y-0.5">
            @foreach($foreignPayments as $fp)
            @php $sym = $fp->currency === 'SAR' ? 'ر.س' : '$'; @endphp
            <li class="text-sm text-amber-700">
                {{ $fp->count }} دفعة بـ <strong>{{ number_format($fp->total, 2) }} {{ $sym }}</strong>
                ({{ $fp->currency === 'SAR' ? 'ريال سعودي' : 'دولار أمريكي' }})
            </li>
            @endforeach
        </ul>
    </div>
</div>
@endif

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
    @php $methodLabels = ['cash'=>'نقدي','pos'=>'POS','bank_transfer'=>'تحويل بنكي']; @endphp
    @foreach($revenueByMethod as $m)
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <div class="text-xs text-gray-500">{{ $methodLabels[$m->method] ?? $m->method }}</div>
        <div class="text-2xl font-bold text-gray-800 mt-1">{{ number_format($m->total, 2) }}</div>
        <div class="text-xs text-gray-400 mt-0.5">ر.ي</div>
    </div>
    @endforeach
    <div class="rounded-xl p-5 border" style="background:#e8f0f7; border-color:#9fbedd;">
        <div class="text-xs" style="color:#0F4C75;">إجمالي الإيرادات (YER)</div>
        <div class="text-2xl font-bold mt-1" style="color:#0F4C75;">{{ number_format($totalRevenue, 2) }}</div>
        <div class="text-xs mt-0.5" style="color:#5b90c5;">ريال يمني</div>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
    <h3 class="font-semibold text-gray-700 mb-4">الإيرادات حسب نوع الغرفة <span class="text-xs text-gray-400 font-normal">(ر.ي)</span></h3>
    <div style="height:256px;">
        <canvas id="revenueChart"></canvas>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100">
    <div class="px-6 py-4 border-b border-gray-100">
        <h3 class="font-semibold text-gray-700">تفصيل الإيرادات <span class="text-xs text-gray-400 font-normal">(ر.ي)</span></h3>
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
                    <td class="px-4 py-3 font-bold text-green-700">{{ number_format($r->total, 2) }} ر.ي</td>
                    <td class="px-4 py-3 text-gray-500">
                        {{ $totalRevenue > 0 ? round(($r->total / $totalRevenue) * 100) : 0 }}%
                    </td>
                </tr>
                @empty
                <tr><td colspan="3" class="px-4 py-6 text-center text-gray-400">لا توجد إيرادات بالريال اليمني في هذه الفترة</td></tr>
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
            label: 'الإيرادات (ر.ي)',
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
