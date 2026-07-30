@extends('layouts.app')
@section('title', 'التقارير المالية')
@section('page-title', 'التقارير المالية')

@section('content')
<div dir="rtl">

{{-- Tabs Navigation --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-5">
    <div class="flex overflow-x-auto">
        @php
        $hubTabs = [
            'revenue'  => ['label' => 'الإيرادات', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>'],
            'expenses' => ['label' => 'المصروفات الشهرية', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>'],
            'methods'  => ['label' => 'طرق الدفع', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>'],
            'ratios'   => ['label' => 'نسب الأداء المالي', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>'],
        ];
        @endphp
        @foreach($hubTabs as $key => $info)
        <a href="{{ route('reports.financeHub', array_merge(request()->only(['from','to','month','period']), ['tab' => $key])) }}"
           class="flex items-center gap-2 px-5 py-4 text-sm font-medium border-b-2 whitespace-nowrap transition
                  {{ $tab === $key ? 'border-blue-600 text-blue-600 bg-blue-50/50' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">{!! $info['icon'] !!}</svg>
            {{ $info['label'] }}
        </a>
        @endforeach
    </div>
</div>

{{-- ════════════════════════════════════════════════════════
     TAB 1 — الإيرادات
     ════════════════════════════════════════════════════════ --}}
@if($tab === 'revenue')
@php $methodLabels = ['cash'=>'نقدي','pos'=>'POS','bank_transfer'=>'تحويل بنكي']; @endphp

<div class="space-y-5">
{{-- فلتر + تصدير --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
    <div class="flex flex-wrap gap-3 items-end justify-between">
        <form method="GET" class="flex flex-wrap gap-3 items-end">
            <input type="hidden" name="tab" value="revenue">
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
                تصدير PDF
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
        <canvas id="revDailyChart"></canvas>
    </div>
</div>
@endif

{{-- أفضل الغرف + جدول يومي --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
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
                        <td class="px-4 py-2.5 text-gray-600 whitespace-nowrap">{{ \Carbon\Carbon::parse($day->date)->format('d/m/Y') }}</td>
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
</div>{{-- end revenue space-y-5 --}}
@endif

{{-- ════════════════════════════════════════════════════════
     TAB 2 — المصروفات الشهرية
     ════════════════════════════════════════════════════════ --}}
@if($tab === 'expenses')
<div>
{{-- فلتر --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-5">
    <div class="flex flex-wrap items-end gap-3 justify-between">
        <form method="GET" class="flex gap-2 items-end flex-wrap">
            <input type="hidden" name="tab" value="expenses">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">الشهر والسنة</label>
                <input type="month" name="month" value="{{ $month }}"
                       class="border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-blue-400">
            </div>
            <button type="submit" class="px-4 py-2 text-white rounded-lg text-sm bg-blue-600 hover:bg-blue-700">عرض</button>
        </form>
        <div class="flex gap-2">
            <button onclick="window.print()" class="px-3 py-2 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50 flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                طباعة
            </button>
            <button type="button" onclick="expExportToExcel()" class="px-3 py-2 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50 flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Excel
            </button>
        </div>
    </div>
</div>

{{-- بطاقات --}}
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
        @php $expMax = $expensesByCategory->max('total') ?? 0; @endphp
        <p class="text-2xl font-bold text-blue-600">{{ number_format($expMax, 0) }}</p>
        <p class="text-xs text-gray-400 mt-1">ر.ي</p>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-green-100 p-4">
        <p class="text-xs text-gray-500 mb-1">أقل بند</p>
        @php $expMin = $expensesByCategory->min('total') ?? 0; @endphp
        <p class="text-2xl font-bold text-green-600">{{ number_format($expMin, 0) }}</p>
        <p class="text-xs text-gray-400 mt-1">ر.ي</p>
    </div>
</div>

{{-- رسوم بيانية --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-5">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <h3 class="font-semibold text-gray-700 text-sm mb-4">توزيع المصروفات حسب الفئة</h3>
        <canvas id="expDistributionChart" height="100"></canvas>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <h3 class="font-semibold text-gray-700 text-sm mb-4">مقارنة الفئات</h3>
        <canvas id="expCategoryChart" height="100"></canvas>
    </div>
</div>

{{-- جدول تفصيلي --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-100">
    <div class="px-5 py-4 border-b border-gray-100">
        <h3 class="font-semibold text-gray-700 text-sm">تفصيل المصروفات حسب الفئة</h3>
    </div>
    <div class="overflow-x-auto">
        <table id="expensesTable" class="w-full text-sm">
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
                    <td class="px-4 py-3 font-medium text-gray-800">{{ \App\Models\Expense::categoryLabel($row->category) }}</td>
                    <td class="px-4 py-3 text-center text-gray-600">{{ $row->count }}</td>
                    <td class="px-4 py-3 text-center font-semibold text-red-600">{{ number_format($row->total, 0) }}</td>
                    <td class="px-4 py-3 text-center">
                        @if($totalExpenses > 0)
                        <span class="px-2 py-1 rounded-lg text-xs font-bold bg-red-100 text-red-700">
                            {{ number_format(($row->total / $totalExpenses) * 100, 1) }}%
                        </span>
                        @endif
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
</div>{{-- end expenses --}}
@endif

{{-- ════════════════════════════════════════════════════════
     TAB 3 — طرق الدفع
     ════════════════════════════════════════════════════════ --}}
@if($tab === 'methods')
@php
$pmColors = ['cash'=>'#10b981','bank_transfer'=>'#3b82f6','pos'=>'#a855f7','check'=>'#f59e0b','credit_card'=>'#ef4444'];
@endphp
<div>
{{-- فلتر --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-5">
    <form method="GET" class="flex gap-3 items-end flex-wrap">
        <input type="hidden" name="tab" value="methods">
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
        <button type="button" onclick="pmExportToExcel()" class="px-4 py-2 text-green-600 rounded-lg text-sm border border-green-300 hover:bg-green-50">تصدير</button>
    </form>
</div>

{{-- بطاقات ملخص --}}
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-5">
    <div class="bg-white rounded-xl shadow-sm border border-blue-100 p-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs text-gray-500 mb-1">إجمالي المدفوعات</p>
                <p class="text-2xl font-bold text-blue-600">{{ number_format($totalMethodAmount, 0) }}</p>
            </div>
            <div class="text-3xl">💰</div>
        </div>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-green-100 p-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs text-gray-500 mb-1">عدد العمليات</p>
                <p class="text-2xl font-bold text-green-600">{{ $totalMethodCount }}</p>
            </div>
            <div class="text-3xl">📊</div>
        </div>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-purple-100 p-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs text-gray-500 mb-1">المتوسط لكل عملية</p>
                <p class="text-2xl font-bold text-purple-600">{{ $totalMethodCount > 0 ? number_format($totalMethodAmount / $totalMethodCount, 0) : 0 }}</p>
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

{{-- تفصيل --}}
<div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mb-5">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <h3 class="font-bold text-gray-800 mb-4">توزيع المدفوعات حسب الطريقة</h3>
        <div class="h-80 flex items-center justify-center">
            <canvas id="pmMethodChart"></canvas>
        </div>
    </div>
    <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <h3 class="font-bold text-gray-800 mb-4">تفاصيل كل طريقة دفع</h3>
        <div class="overflow-x-auto">
            <table id="pmTable" class="w-full text-sm">
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
                            @if($totalMethodAmount > 0)
                            <div class="flex items-center gap-2">
                                <div class="flex-1 h-2 bg-gray-200 rounded-full overflow-hidden">
                                    <div class="h-full rounded-full bg-blue-500" style="width:{{ ($data['total'] / $totalMethodAmount * 100) }}%"></div>
                                </div>
                                <span class="text-xs font-bold text-gray-700 min-w-fit">{{ round(($data['total'] / $totalMethodAmount * 100)) }}%</span>
                            </div>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-gray-400">لا توجد بيانات</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- تفاصيل كل عملية دفع فردياً: النزيل، الغرفة، وسند التحويل البنكي إن وُجد --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-5">
    <div class="px-5 py-4 border-b border-gray-100">
        <h3 class="font-bold text-gray-800">تفاصيل عمليات الدفع</h3>
        <p class="text-xs text-gray-400 mt-0.5">كل عملية دفع على حدة — النزيل، الغرفة، ومن استلمها</p>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 whitespace-nowrap">التاريخ</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 whitespace-nowrap">طريقة الدفع</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">النزيل</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 whitespace-nowrap">الغرفة</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 whitespace-nowrap">المبلغ (ر.ي)</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 whitespace-nowrap">استلمها</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">سند التحويل</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($paymentDetails as $p)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 text-gray-500 text-xs whitespace-nowrap">{{ $p->payment_date?->format('d/m/Y H:i') ?? '—' }}</td>
                    <td class="px-4 py-3">
                        <span class="inline-block px-2.5 py-1 rounded-full text-xs font-bold
                            {{ $p->method === 'cash' ? 'bg-green-100 text-green-800' : ($p->method === 'bank_transfer' ? 'bg-blue-100 text-blue-800' : ($p->method === 'pos' ? 'bg-purple-100 text-purple-800' : 'bg-gray-100 text-gray-800')) }}">
                            {{ $methodLabels[$p->method] ?? $p->method }}
                        </span>
                    </td>
                    <td class="px-4 py-3 font-medium text-gray-800">
                        @if($p->reservation && !$p->reservation->trashed())
                        <a href="{{ route('reservations.show', $p->reservation) }}" class="hover:text-blue-600 hover:underline">
                            {{ $p->reservation->guest?->full_name ?? '—' }}
                        </a>
                        @elseif($p->reservation)
                        {{ $p->reservation->guest?->full_name ?? '—' }}
                        <span class="text-xs text-gray-400">(ملغى)</span>
                        @else
                        <span class="text-gray-400">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-gray-600">{{ $p->reservation?->display_room_number ?? '—' }}</td>
                    <td class="px-4 py-3 font-bold text-gray-900">{{ number_format($p->amount, 0) }}</td>
                    <td class="px-4 py-3 text-gray-500 text-xs">{{ $p->receivedBy?->name ?? '—' }}</td>
                    <td class="px-4 py-3 text-xs">
                        @if($p->method === 'bank_transfer')
                            @if($p->bank_transfer_ref)
                            <div class="text-gray-600">رقم السند: <span class="font-mono font-semibold">{{ $p->bank_transfer_ref }}</span></div>
                            @endif
                            @can('payments.bank_receipt')
                            @if($p->bank_receipt_path)
                            <a href="{{ route('payments.receipt', ['file' => $p->bank_receipt_path]) }}" target="_blank"
                               class="inline-flex items-center gap-1 text-blue-600 hover:text-blue-800 hover:underline mt-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 16l4-4a3 3 0 014 0l4 4m0 0l1.5-1.5a3 3 0 014 0L21 19M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                عرض صورة السند
                            </a>
                            @elseif(!$p->bank_transfer_ref)
                            <span class="text-amber-600">لا يوجد سند مرفق</span>
                            @endif
                            @endif
                        @else
                        <span class="text-gray-300">—</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="px-4 py-8 text-center text-gray-400">لا توجد عمليات دفع</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- الاسترجاعات المالية: استرجاع مباشر أو ناتج عن إلغاء حجز — يُخصم من الوردية المفتوحة عند تسجيله --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-5">
    <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
        <div>
            <h3 class="font-bold text-gray-800">الاسترجاعات</h3>
            <p class="text-xs text-gray-400 mt-0.5">مبالغ أُعيدت للنزلاء خلال الفترة (استرجاع مباشر أو بسبب إلغاء حجز)</p>
        </div>
        <div class="text-left">
            <div class="text-xl font-bold text-rose-600">{{ number_format($totalRefundAmount, 0) }} ر.ي</div>
            <div class="text-xs text-gray-400">{{ $refundDetails->count() }} عملية استرجاع</div>
        </div>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 whitespace-nowrap">التاريخ</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">النزيل</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 whitespace-nowrap">الغرفة</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 whitespace-nowrap">المبلغ (ر.ي)</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 whitespace-nowrap">الطريقة</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">السبب</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 whitespace-nowrap">نفّذها</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($refundDetails as $r)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 text-gray-500 text-xs whitespace-nowrap">{{ $r->refunded_at?->format('d/m/Y H:i') ?? '—' }}</td>
                    <td class="px-4 py-3 font-medium text-gray-800">
                        @if($r->reservation && !$r->reservation->trashed())
                        <a href="{{ route('reservations.show', $r->reservation) }}" class="hover:text-blue-600 hover:underline">
                            {{ $r->reservation->guest?->full_name ?? '—' }}
                        </a>
                        @elseif($r->reservation)
                        {{ $r->reservation->guest?->full_name ?? '—' }}
                        <span class="text-xs text-gray-400">(ملغى)</span>
                        @else
                        <span class="text-gray-400">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-gray-600">{{ $r->reservation?->display_room_number ?? '—' }}</td>
                    <td class="px-4 py-3 font-bold text-rose-600">-{{ number_format($r->amount, 0) }}</td>
                    <td class="px-4 py-3 text-gray-600 text-xs">{{ $methodLabels[$r->method] ?? $r->method }}</td>
                    <td class="px-4 py-3 text-gray-600 text-xs max-w-xs truncate" title="{{ $r->reason }}">{{ $r->reason }}</td>
                    <td class="px-4 py-3 text-gray-500 text-xs">{{ $r->processedBy?->name ?? '—' }}</td>
                </tr>
                @empty
                <tr><td colspan="7" class="px-4 py-8 text-center text-gray-400">لا توجد استرجاعات خلال هذه الفترة</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if($dailyByMethod->isNotEmpty())
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
    <h3 class="font-bold text-gray-800 mb-4">اتجاه المدفوعات اليومي</h3>
    <div class="h-96 flex items-center justify-center">
        <canvas id="pmDailyTrendChart"></canvas>
    </div>
</div>
@endif
</div>{{-- end methods --}}
@endif

{{-- ════════════════════════════════════════════════════════
     TAB 4 — نسب الأداء المالي
     ════════════════════════════════════════════════════════ --}}
@if($tab === 'ratios')
<div>
{{-- فلتر --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-5">
    <form method="GET" class="flex gap-2 items-end flex-wrap">
        <input type="hidden" name="tab" value="ratios">
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">الفترة</label>
            <select name="period" class="border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-blue-400">
                <option value="today" {{ $period == 'today' ? 'selected' : '' }}>اليوم</option>
                <option value="week" {{ $period == 'week' ? 'selected' : '' }}>هذا الأسبوع</option>
                <option value="month" {{ $period == 'month' ? 'selected' : '' }}>الشهر الحالي</option>
                <option value="quarter" {{ $period == 'quarter' ? 'selected' : '' }}>الربع الحالي</option>
                <option value="year" {{ $period == 'year' ? 'selected' : '' }}>السنة الحالية</option>
                <option value="all" {{ $period == 'all' ? 'selected' : '' }}>كل الوقت</option>
            </select>
        </div>
        <button type="submit" class="px-4 py-2 text-white rounded-lg text-sm bg-blue-600 hover:bg-blue-700">عرض</button>
    </form>
</div>

{{-- النسب الرئيسية --}}
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-5">
    <div class="bg-white rounded-xl shadow-sm border border-blue-100 p-5">
        <div class="flex items-center justify-between mb-3">
            <h3 class="font-semibold text-gray-700 text-sm">هامش الربح</h3>
            <div class="text-2xl">📈</div>
        </div>
        <p class="text-3xl font-bold text-blue-700">{{ $profitMargin }}%</p>
        <p class="text-xs text-gray-500 mt-2">كم من كل ريال هو ربح؟</p>
        <div class="mt-3 h-2 bg-gray-100 rounded-full overflow-hidden">
            <div class="h-full rounded-full bg-blue-500" style="width:{{ min($profitMargin, 100) }}%"></div>
        </div>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-red-100 p-5">
        <div class="flex items-center justify-between mb-3">
            <h3 class="font-semibold text-gray-700 text-sm">نسبة التكاليف</h3>
            <div class="text-2xl">💰</div>
        </div>
        <p class="text-3xl font-bold text-red-600">{{ $costRatio }}%</p>
        <p class="text-xs text-gray-500 mt-2">المصروفات من الإيراد</p>
        <div class="mt-3 h-2 bg-gray-100 rounded-full overflow-hidden">
            <div class="h-full rounded-full bg-red-500" style="width:{{ min($costRatio, 100) }}%"></div>
        </div>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-green-100 p-5">
        <div class="flex items-center justify-between mb-3">
            <h3 class="font-semibold text-gray-700 text-sm">معدل الإشغال</h3>
            <div class="text-2xl">🏨</div>
        </div>
        <p class="text-3xl font-bold text-green-700">{{ $occupancyRate }}%</p>
        <p class="text-xs text-gray-500 mt-2">استخدام الطاقة الفندقية</p>
        <div class="mt-3 h-2 bg-gray-100 rounded-full overflow-hidden">
            <div class="h-full rounded-full bg-green-500" style="width:{{ $occupancyRate }}%"></div>
        </div>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-purple-100 p-5">
        <div class="flex items-center justify-between mb-3">
            <h3 class="font-semibold text-gray-700 text-sm">متوسط إيراد الغرفة</h3>
            <div class="text-2xl">🔑</div>
        </div>
        <p class="text-3xl font-bold text-purple-700">{{ number_format($revenuePerRoom, 0) }}</p>
        <p class="text-xs text-gray-500 mt-2">ر.ي لكل غرفة</p>
    </div>
</div>

{{-- مؤشرات ثانوية --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-5">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <h3 class="font-semibold text-gray-700 text-sm mb-4">مؤشرات الصحة المالية</h3>
        <div class="space-y-3">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-700">متوسط إيراد الليلة (ADR)</p>
                    <p class="text-xs text-gray-500">متوسط سعر الليلة</p>
                </div>
                <p class="text-lg font-bold text-gray-800">{{ number_format($adr, 0) }} ر.ي</p>
            </div>
            <div class="h-px bg-gray-100"></div>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-700">الإيراد لكل نزيل</p>
                    <p class="text-xs text-gray-500">متوسط إنفاق النزيل</p>
                </div>
                <p class="text-lg font-bold text-gray-800">{{ number_format($revenuePerGuest, 0) }} ر.ي</p>
            </div>
            <div class="h-px bg-gray-100"></div>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-700">متوسط البقاء</p>
                    <p class="text-xs text-gray-500">عدد الليالي المتوسطة</p>
                </div>
                <p class="text-lg font-bold text-gray-800">{{ number_format($avgStay, 1) }} ليلة</p>
            </div>
            <div class="h-px bg-gray-100"></div>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-700">عدد الضيوف</p>
                    <p class="text-xs text-gray-500">في الفترة المحددة</p>
                </div>
                <p class="text-lg font-bold text-gray-800">{{ $guestCount }}</p>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <h3 class="font-semibold text-gray-700 text-sm mb-4">مؤشرات التشغيل</h3>
        <div class="space-y-3">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-700">الإيراد اليومي المتوسط</p>
                    <p class="text-xs text-gray-500">دخل يومي متوسط</p>
                </div>
                <p class="text-lg font-bold text-green-700">{{ number_format($dailyRevenueAvg, 0) }} ر.ي</p>
            </div>
            <div class="h-px bg-gray-100"></div>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-700">المصروف اليومي المتوسط</p>
                    <p class="text-xs text-gray-500">نفقات يومية متوسطة</p>
                </div>
                <p class="text-lg font-bold text-red-600">{{ number_format($dailyExpenseAvg, 0) }} ر.ي</p>
            </div>
            <div class="h-px bg-gray-100"></div>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-700">الربح اليومي المتوسط</p>
                    <p class="text-xs text-gray-500">الصافي اليومي</p>
                </div>
                <p class="text-lg font-bold {{ $dailyProfitAvg >= 0 ? 'text-emerald-700' : 'text-red-700' }}">{{ number_format($dailyProfitAvg, 0) }} ر.ي</p>
            </div>
            <div class="h-px bg-gray-100"></div>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-700">عدد الغرف المحجوزة</p>
                    <p class="text-xs text-gray-500">متوسط يومي</p>
                </div>
                <p class="text-lg font-bold text-blue-700">{{ number_format($avgOccupiedRooms, 1) }}</p>
            </div>
        </div>
    </div>
</div>

{{-- تقييم الأداء العام --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
    <h3 class="font-semibold text-gray-700 text-sm mb-4">تقييم الأداء العام</h3>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="text-center p-4 rounded-lg bg-gradient-to-br from-blue-50 to-blue-100">
            <p class="text-xs text-gray-600 mb-2">تصنيف الربحية</p>
            <div class="text-4xl font-bold text-blue-700">{{ $profitabilityScore }}/10</div>
            <p class="text-xs text-gray-600 mt-2">
                @if($profitabilityScore >= 8) ممتاز 🌟
                @elseif($profitabilityScore >= 6) جيد ✓
                @elseif($profitabilityScore >= 4) متوسط ⚠
                @else يحتاج تحسين ⚡
                @endif
            </p>
        </div>
        <div class="text-center p-4 rounded-lg bg-gradient-to-br from-green-50 to-green-100">
            <p class="text-xs text-gray-600 mb-2">تصنيف الكفاءة</p>
            <div class="text-4xl font-bold text-green-700">{{ $efficiencyScore }}/10</div>
            <p class="text-xs text-gray-600 mt-2">
                @if($efficiencyScore >= 8) ممتاز 🌟
                @elseif($efficiencyScore >= 6) جيد ✓
                @elseif($efficiencyScore >= 4) متوسط ⚠
                @else يحتاج تحسين ⚡
                @endif
            </p>
        </div>
        <div class="text-center p-4 rounded-lg bg-gradient-to-br from-purple-50 to-purple-100">
            <p class="text-xs text-gray-600 mb-2">الصحة المالية</p>
            <div class="text-4xl font-bold text-purple-700">{{ $overallHealth }}/10</div>
            <p class="text-xs text-gray-600 mt-2">
                @if($overallHealth >= 8) صحة ممتازة 🌟
                @elseif($overallHealth >= 6) صحة جيدة ✓
                @elseif($overallHealth >= 4) يحتاج عناية ⚠
                @else حالة حرجة ⚡
                @endif
            </p>
        </div>
    </div>
</div>

{{-- الرؤى والتوصيات --}}
<div class="bg-blue-50 border border-blue-200 rounded-xl p-5 mt-5">
    <h3 class="font-semibold text-blue-900 text-sm mb-3">💡 الرؤى والتوصيات</h3>
    <ul class="space-y-2 text-sm text-blue-800">
        @if($profitMargin < 15)
        <li>⚠️ هامش الربح منخفض. تفحص المصروفات وقم بتحسينها.</li>
        @endif
        @if($occupancyRate < 50)
        <li>⚠️ معدل الإشغال متدني. ركّز على تحسين التسويق والحجوزات.</li>
        @endif
        @if($costRatio > 60)
        <li>⚠️ نسبة التكاليف عالية جداً. قلّل النفقات أو زيّد الإيراد.</li>
        @endif
        @if($profitMargin >= 20 && $occupancyRate >= 70)
        <li>✅ الأداء ممتاز! حافظ على هذا المستوى من الكفاءة.</li>
        @endif
    </ul>
</div>
</div>{{-- end ratios --}}
@endif

</div>{{-- end dir=rtl --}}
@endsection

@push('scripts')

{{-- Revenue Tab Scripts --}}
@if($tab === 'revenue' && $dailyRevenue->isNotEmpty())
<script>
new Chart(document.getElementById('revDailyChart'), {
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

{{-- Expenses Tab Scripts --}}
@if($tab === 'expenses')
<script>
(function() {
    const pieCtx = document.getElementById('expDistributionChart');
    if (pieCtx) {
        new Chart(pieCtx, {
            type: 'doughnut',
            data: {
                labels: @json($expensesByCategory->map(fn($r) => \App\Models\Expense::categoryLabel($r->category))),
                datasets: [{
                    data: @json($expensesByCategory->pluck('total')),
                    backgroundColor: ['#ef4444','#f97316','#f59e0b','#ec4899','#8b5cf6','#6366f1'],
                    borderColor: '#fff', borderWidth: 2,
                }]
            },
            options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
        });
    }
    const barCtx = document.getElementById('expCategoryChart');
    if (barCtx) {
        new Chart(barCtx, {
            type: 'bar',
            data: {
                labels: @json($expensesByCategory->map(fn($r) => \App\Models\Expense::categoryLabel($r->category))),
                datasets: [{
                    label: 'المبلغ (ر.ي)',
                    data: @json($expensesByCategory->pluck('total')),
                    backgroundColor: ['#ef4444','#f97316','#f59e0b','#ec4899','#8b5cf6','#6366f1'],
                    borderRadius: 6,
                }]
            },
            options: {
                indexAxis: 'y', responsive: true,
                plugins: { legend: { position: 'top' } },
                scales: { x: { beginAtZero: true } }
            }
        });
    }
})();

function expExportToExcel() {
    const table = document.getElementById('expensesTable');
    const link = document.createElement('a');
    link.href = 'data:application/vnd.ms-excel,' + encodeURIComponent('<table>' + table.innerHTML + '</table>');
    link.download = 'monthly-expenses-{{ now()->format("d-m-Y") }}.xls';
    link.click();
}
</script>
@endif

{{-- Payment Methods Tab Scripts --}}
@if($tab === 'methods')
<script>
(function() {
    @php
    $pmColorMap = ['cash'=>'#10b981','bank_transfer'=>'#3b82f6','pos'=>'#a855f7','check'=>'#f59e0b','credit_card'=>'#ef4444'];
    $pmBgColors = $byMethod->keys()->map(fn($m) => $pmColorMap[$m] ?? '#808080')->values();
    @endphp

    const pmCtx1 = document.getElementById('pmMethodChart')?.getContext('2d');
    if (pmCtx1) {
        new Chart(pmCtx1, {
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
                    backgroundColor: @json($pmBgColors),
                    borderColor: '#fff', borderWidth: 2,
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: true,
                plugins: {
                    legend: { position: 'bottom', labels: { usePointStyle: true, padding: 15 } },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const val = context.parsed;
                                const pct = (val / {{ $totalMethodAmount ?: 1 }} * 100).toFixed(1);
                                return context.label.split('(')[0] + ': ' + val.toLocaleString() + ' ر.ي (' + pct + '%)';
                            }
                        }
                    }
                }
            }
        });
    }

    @if($dailyByMethod->isNotEmpty())
    @php
    $pmMethodKeys = $byMethod->keys()->toArray();
    @endphp
    const pmCtx2 = document.getElementById('pmDailyTrendChart')?.getContext('2d');
    if (pmCtx2) {
        const pmDates = [
            @foreach($dailyByMethod as $date => $methods)
            '{{ substr($date, 8) }}/{{ substr($date, 5, 2) }}',
            @endforeach
        ];
        const pmBgColorsMap = @json($pmColorMap);
        const pmDatasets = [];
        @foreach($byMethod->keys() as $method)
        pmDatasets.push({
            label: '{{ $methodLabels[$method] ?? $method }}',
            data: [
                @foreach($dailyByMethod as $date => $methods)
                {{ $methods[$method] ?? 0 }},
                @endforeach
            ],
            borderColor: '{{ $pmColorMap[$method] ?? "#808080" }}',
            backgroundColor: '{{ ($pmColorMap[$method] ?? "#808080") }}22',
            borderWidth: 2, fill: true, tension: 0.4
        });
        @endforeach
        new Chart(pmCtx2, {
            type: 'line',
            data: { labels: pmDates, datasets: pmDatasets },
            options: {
                responsive: true, maintainAspectRatio: true,
                plugins: { legend: { position: 'top', labels: { usePointStyle: true, padding: 15 } } },
                scales: { y: { beginAtZero: true, title: { display: true, text: 'المبلغ (ر.ي)' } } }
            }
        });
    }
    @endif

    window.pmExportToExcel = function() {
        const table = document.getElementById('pmTable');
        if (!table) return;
        const rows = table.querySelectorAll('tr');
        const csv = [];
        rows.forEach(row => {
            const cells = [];
            row.querySelectorAll('td, th').forEach(cell => {
                cells.push('"' + cell.textContent.trim().replace(/"/g, '""') + '"');
            });
            csv.push(cells.join(','));
        });
        const link = document.createElement('a');
        link.href = 'data:text/csv;charset=utf-8,%EF%BB%BF' + encodeURIComponent(csv.join('\n'));
        link.download = 'طرق_الدفع_{{ now()->format("d-m-Y") }}.csv';
        link.click();
    };
})();
</script>
@endif

@endpush
