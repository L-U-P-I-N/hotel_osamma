@extends('layouts.app')
@section('title', 'تقرير الإيرادات')
@section('page-title', 'تقرير الإيرادات')

@section('content')
<div class="space-y-5" dir="rtl">
@php $methodLabels = ['cash'=>'نقدي','pos'=>'POS','bank_transfer'=>'تحويل بنكي']; @endphp

{{-- فلتر + أزرار تصدير --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
    <div class="flex flex-wrap gap-3 items-end justify-between">
        <form method="GET" class="flex flex-wrap gap-3 items-end">
            <div class="flex flex-col gap-1">
                <label class="text-xs font-medium text-gray-500">من تاريخ</label>
                <input type="date" name="from" value="{{ $from }}" onchange="this.form.submit()"
                       class="border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400 transition bg-white">
            </div>
            <div class="flex flex-col gap-1">
                <label class="text-xs font-medium text-gray-500">إلى تاريخ</label>
                <input type="date" name="to" value="{{ $to }}" onchange="this.form.submit()"
                       class="border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400 transition bg-white">
            </div>
            <button type="submit" class="sr-only">عرض</button>
        </form>
        <div class="flex gap-2">
            <a href="{{ route('reports.revenue.pdf', ['from' => $from, 'to' => $to]) }}"
               class="flex items-center gap-2 px-4 py-2 bg-red-600 text-white rounded-lg text-sm hover:bg-red-700 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                PDF
            </a>
            <a href="{{ route('reports.revenue.excel', ['from' => $from, 'to' => $to]) }}"
               class="flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg text-sm hover:bg-green-700 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                Excel
            </a>
        </div>
    </div>
</div>

{{-- تنبيه عملات أجنبية --}}
@if($foreignPayments->isNotEmpty())
<div class="bg-amber-50 border border-amber-200 rounded-xl p-4 flex gap-3">
    <svg class="w-5 h-5 text-amber-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
    <div>
        <p class="text-sm font-semibold text-amber-800">مدفوعات بعملة أجنبية (غير مشمولة في الإجمالي)</p>
        <ul class="mt-1 space-y-0.5">
            @foreach($foreignPayments as $fp)
            @php $sym = $fp->currency === 'SAR' ? 'ر.س' : '$'; @endphp
            <li class="text-sm text-amber-700">
                {{ $fp->count }} دفعة · <strong>{{ number_format($fp->total, 0) }} {{ $sym }}</strong>
                ({{ $fp->currency === 'SAR' ? 'ريال سعودي' : 'دولار أمريكي' }})
            </li>
            @endforeach
        </ul>
    </div>
</div>
@endif

{{-- بطاقات الملخص --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
    <div class="rounded-xl p-5 border-2 col-span-2 lg:col-span-1" style="background:#e8f0f7; border-color:#0F4C75;">
        <p class="text-xs font-medium" style="color:#0F4C75;">إجمالي الإيرادات (ر.ي)</p>
        <p class="text-2xl font-bold mt-1" style="color:#0F4C75;">{{ number_format($totalRevenue, 0) }}</p>
        <p class="text-xs mt-0.5" style="color:#5b90c5;">ريال يمني</p>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <p class="text-xs text-gray-500 font-medium">عدد الدفعات</p>
        <p class="text-2xl font-bold text-gray-800 mt-1">{{ number_format($paymentCount) }}</p>
        <p class="text-xs text-gray-400 mt-0.5">دفعة</p>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <p class="text-xs text-gray-500 font-medium">الحجوزات المدفوعة</p>
        <p class="text-2xl font-bold text-gray-800 mt-1">{{ number_format($reservationCount) }}</p>
        <p class="text-xs text-gray-400 mt-0.5">حجز فريد</p>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <p class="text-xs text-gray-500 font-medium">متوسط الدفعة</p>
        <p class="text-2xl font-bold text-gray-800 mt-1">{{ number_format($avgPayment, 0) }}</p>
        <p class="text-xs text-gray-400 mt-0.5">ر.ي</p>
    </div>
</div>

{{-- نوع الغرفة + طريقة الدفع --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

    {{-- نوع الغرفة --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="px-5 py-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-700">الإيرادات حسب نوع الغرفة</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">نوع الغرفة</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الحجوزات</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الدفعات</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الإجمالي (ر.ي)</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">النسبة</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($revenueByType as $r)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium text-gray-800">{{ $r->name }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $r->reservation_count }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $r->payment_count }}</td>
                        <td class="px-4 py-3 font-bold text-green-700">{{ number_format($r->total, 0) }}</td>
                        <td class="px-4 py-3">
                            @php $pct = $totalRevenue > 0 ? ($r->total / $totalRevenue) * 100 : 0; @endphp
                            <div class="flex items-center gap-2">
                                <div class="flex-1 bg-gray-100 rounded-full h-1.5">
                                    <div class="h-1.5 rounded-full" style="width:{{ min($pct,100) }}%; background:#0F4C75;"></div>
                                </div>
                                <span class="text-xs text-gray-500 w-8">{{ round($pct) }}%</span>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="px-4 py-6 text-center text-gray-400">لا توجد بيانات</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- طريقة الدفع --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="px-5 py-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-700">الإيرادات حسب طريقة الدفع</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الطريقة</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">عدد الدفعات</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الإجمالي (ر.ي)</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">النسبة</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($revenueByMethod as $m)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium text-gray-800">{{ $methodLabels[$m->method] ?? $m->method }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $m->count }}</td>
                        <td class="px-4 py-3 font-bold text-green-700">{{ number_format($m->total, 0) }}</td>
                        <td class="px-4 py-3">
                            @php $pct = $totalRevenue > 0 ? ($m->total / $totalRevenue) * 100 : 0; @endphp
                            <div class="flex items-center gap-2">
                                <div class="flex-1 bg-gray-100 rounded-full h-1.5">
                                    <div class="h-1.5 rounded-full bg-blue-500" style="width:{{ min($pct,100) }}%;"></div>
                                </div>
                                <span class="text-xs text-gray-500 w-8">{{ round($pct) }}%</span>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="px-4 py-6 text-center text-gray-400">لا توجد بيانات</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- مخطط الإيرادات اليومية --}}
@if($dailyRevenue->isNotEmpty())
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
    <h3 class="font-semibold text-gray-700 mb-4">الإيرادات اليومية <span class="text-xs text-gray-400 font-normal">(ر.ي)</span></h3>
    <div style="height:220px;">
        <canvas id="dailyChart"></canvas>
    </div>
</div>
@endif

{{-- أفضل الغرف إيرادًا + الإيرادات اليومية جدول --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

    {{-- أفضل الغرف --}}
    @if($topRooms->isNotEmpty())
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="px-5 py-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-700">أعلى الغرف إيرادًا</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">#</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">رقم الغرفة</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">النوع</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الحجوزات</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الإيراد (ر.ي)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($topRooms as $i => $room)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-gray-400 text-xs">{{ $i + 1 }}</td>
                        <td class="px-4 py-3 font-bold text-gray-800">{{ $room->room_number }}</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-700">{{ $room->type_name }}</span>
                        </td>
                        <td class="px-4 py-3 text-gray-600">{{ $room->reservation_count }}</td>
                        <td class="px-4 py-3 font-bold text-green-700">{{ number_format($room->total, 0) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- الإيرادات اليومية جدول --}}
    @if($dailyRevenue->isNotEmpty())
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-semibold text-gray-700">تفصيل يومي</h3>
            <span class="text-xs text-gray-400">{{ $dailyRevenue->count() }} يوم</span>
        </div>
        <div class="overflow-x-auto max-h-80 overflow-y-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 sticky top-0">
                    <tr>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">التاريخ</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الدفعات</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الإيراد (ر.ي)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($dailyRevenue->sortByDesc('date') as $day)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-2.5 text-gray-600 whitespace-nowrap">
                            {{ \Carbon\Carbon::parse($day->date)->format('d/m/Y') }}
                        </td>
                        <td class="px-4 py-2.5 text-gray-500">{{ $day->count }}</td>
                        <td class="px-4 py-2.5 font-semibold text-green-700">{{ number_format($day->total, 0) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

</div>

</div>
@endsection

@push('scripts')
@if($dailyRevenue->isNotEmpty())
<script>
new Chart(document.getElementById('dailyChart'), {
    type: 'bar',
    data: {
        labels: {!! json_encode($dailyRevenue->map(fn($d) => \Carbon\Carbon::parse($d->date)->format('d/m'))) !!},
        datasets: [{
            label: 'الإيراد اليومي (ر.ي)',
            data: {!! json_encode($dailyRevenue->pluck('total')) !!},
            backgroundColor: '#0F4C75',
            borderRadius: 4,
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, ticks: { callback: v => v.toLocaleString('ar') } },
            x: { ticks: { font: { size: 10 } } }
        }
    }
});
</script>
@endif
@endpush
