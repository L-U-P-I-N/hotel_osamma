@extends('layouts.app')
@section('title', 'تقارير الورديات والصناديق')
@section('page-title', 'الورديات والصناديق')

@section('content')
<div class="space-y-5" dir="rtl">

{{-- Session alerts --}}
@if(session('success'))
<div class="p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">{{ session('success') }}</div>
@endif
@if($errors->any())
<div class="p-4 bg-red-50 border border-red-200 rounded-lg">
    @foreach($errors->all() as $e)<p class="text-sm text-red-700">{{ $e }}</p>@endforeach
</div>
@endif

{{-- ─── Tab bar ─── --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-100">
    <div class="flex border-b border-gray-100 overflow-x-auto">
        @php
        $tabs = [
            'shifts'   => ['الورديات',      'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z', 'blue'],
            'deficits' => ['عجوزات الصناديق','M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z', 'red'],
            'daily'    => ['الإغلاق اليومي','M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z', 'green'],
        ];
        @endphp
        @foreach($tabs as $key => [$label, $icon, $color])
        @php
        $isActive = $tab === $key;
        $colorMap = ['blue' => 'border-blue-600 text-blue-700 bg-blue-50/50', 'red' => 'border-red-500 text-red-700 bg-red-50/50', 'green' => 'border-green-600 text-green-700 bg-green-50/50'];
        $params = ['tab' => $key, 'from' => $from, 'to' => $to, 'date' => $date];
        @endphp
        <a href="{{ route('reports.shiftsHub', $params) }}"
           class="flex items-center gap-2 px-5 py-3.5 text-sm font-semibold whitespace-nowrap transition
               {{ $isActive ? 'border-b-2 '.$colorMap[$color] : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50' }}">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $icon }}"/></svg>
            {{ $label }}
        </a>
        @endforeach
    </div>

{{-- ══════════════════════════════════════════ TAB 1: الورديات ══════════════════════════════════════════ --}}
@if($tab === 'shifts')
<div class="p-4">
    <form method="GET" action="{{ route('reports.shiftsHub') }}" class="flex flex-wrap items-end gap-4 mb-4">
        <input type="hidden" name="tab" value="shifts">
        <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-500">من تاريخ</label>
            <input type="date" name="from" value="{{ $from }}" onchange="this.form.submit()"
                   class="border border-gray-200 rounded-xl px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400 transition bg-white">
        </div>
        <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-500">إلى تاريخ</label>
            <input type="date" name="to" value="{{ $to }}" onchange="this.form.submit()"
                   class="border border-gray-200 rounded-xl px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400 transition bg-white">
        </div>
        <div class="flex items-center gap-2 me-auto">
            <a href="{{ route('reports.shifts.pdf', ['from' => $from, 'to' => $to]) }}"
               class="inline-flex items-center gap-1.5 px-3 py-2 bg-red-600 text-white text-xs rounded-xl hover:bg-red-700 transition font-semibold">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                PDF
            </a>
            <a href="{{ route('reports.shifts.excel', ['from' => $from, 'to' => $to]) }}"
               class="inline-flex items-center gap-1.5 px-3 py-2 bg-green-600 text-white text-xs rounded-xl hover:bg-green-700 transition font-semibold">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Excel
            </a>
        </div>
    </form>

    @if($shifts && $shifts->isEmpty())
    <div class="py-16 text-center text-gray-400">
        <svg class="w-12 h-12 mx-auto mb-3 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <p class="text-sm font-medium">لا توجد ورديات في هذه الفترة</p>
    </div>
    @else
    <div class="space-y-4">
    @foreach($shifts as $shift)
    @php
        $payments    = $shift->groupedPayments()->sortBy(fn($p) => $p->reservation?->display_room_number ?? '');
        $withdrawals = $shift->withdrawals->sortBy('withdrawal_date');
        $expenses    = $withdrawals->where('withdrawal_type', '!=', 'currency_exchange');
        $exchanges   = $withdrawals->where('withdrawal_type', 'currency_exchange');
        $empName  = $shift->user?->name ?? '—';
        $empColors = ['#0F4C75','#065f46','#92400e','#6d28d9','#be123c','#0369a1'];
        $empColor  = $empColors[abs(crc32($empName)) % count($empColors)];
    @endphp
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden" x-data="{ open: true }">
        <div class="px-5 py-4 flex items-center gap-4 cursor-pointer select-none hover:bg-gray-50/60 transition" @click="open=!open">
            <div class="flex items-center gap-3 min-w-0">
                <div class="w-11 h-11 rounded-xl flex items-center justify-center text-white font-bold text-base flex-shrink-0 shadow-sm" style="background:{{ $empColor }};">
                    {{ mb_strtoupper(mb_substr($empName, 0, 1)) }}
                </div>
                <div>
                    <div class="font-bold text-gray-900 text-sm">{{ $empName }}</div>
                    <div class="flex items-center gap-2 mt-0.5 text-xs text-gray-400">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        {{ $shift->shift_date->format('d/m/Y') }}
                        <span class="w-1 h-1 rounded-full bg-gray-300 inline-block"></span>
                        {{ $shift->started_at?->format('H:i') ?? '—' }}
                        @if($shift->ended_at) — {{ $shift->ended_at->format('H:i') }} @endif
                    </div>
                </div>
            </div>
            <div class="h-10 w-px bg-gray-100 hidden md:block"></div>
            <div class="flex items-center gap-5 flex-wrap">
                @foreach([['YER','ر.ي','green'],['SAR','ر.س','blue'],['USD','$','purple']] as [$cur,$sym,$clr])
                @php $net = $shift->{"total_received_$cur"} - $shift->{"total_withdrawals_$cur"}; @endphp
                @if($shift->{"total_received_$cur"} > 0 || $shift->{"total_withdrawals_$cur"} > 0)
                <div class="text-center">
                    <div class="text-xs text-gray-400 mb-0.5">صافي {{ $sym }}</div>
                    <div class="font-black text-sm {{ $net >= 0 ? 'text-green-700' : 'text-red-700' }}">{{ number_format($net, 0) }}</div>
                </div>
                @endif
                @endforeach
            </div>
            <div class="flex items-center gap-3 ms-auto">
                <span class="px-2.5 py-1 rounded-full text-xs font-semibold {{ $shift->is_closed ? 'bg-gray-100 text-gray-600' : 'bg-amber-100 text-amber-700' }}">
                    {{ $shift->is_closed ? 'مغلقة' : 'مفتوحة' }}
                </span>
                <svg :class="open?'rotate-180':''" class="w-4 h-4 text-gray-400 flex-shrink-0 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
            </div>
        </div>
        <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
            <div class="border-t border-gray-100">
                <div class="grid grid-cols-1 lg:grid-cols-2 divide-y lg:divide-y-0 lg:divide-x lg:divide-x-reverse divide-gray-100">
                    <div class="p-5">
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="flex items-center gap-2 font-bold text-gray-700 text-sm">
                                <span class="w-7 h-7 rounded-xl bg-green-100 flex items-center justify-center flex-shrink-0"><svg class="w-3.5 h-3.5 text-green-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></span>
                                الإيرادات المستلمة
                            </h4>
                            <span class="text-xs text-gray-500 bg-gray-100 px-2.5 py-1 rounded-lg font-semibold">{{ $payments->count() }} دفعة</span>
                        </div>
                        @if($payments->isEmpty())
                        <div class="flex flex-col items-center justify-center py-8 text-gray-300">
                            <svg class="w-9 h-9 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            <p class="text-xs">لا توجد دفعات</p>
                        </div>
                        @else
                        <div class="space-y-2">
                            @foreach($payments as $p)
                            <div class="flex items-center justify-between rounded-xl border border-gray-100 bg-gray-50/70 px-4 py-2.5 gap-3">
                                <div class="flex items-center gap-3 min-w-0 flex-1">
                                    <span class="flex-shrink-0 font-black text-xs text-white px-2 py-1 rounded-lg" style="background:#0F4C75; min-width:2.5rem; text-align:center;">{{ $p->reservation?->display_room_number ?? '—' }}</span>
                                    <div class="min-w-0">
                                        <span class="text-gray-700 text-xs font-medium block truncate">{{ $p->reservation?->guest?->full_name ?? '—' }}</span>
                                        @if($p->type === 'compensation')<span class="inline-block mt-0.5 px-1.5 py-0.5 bg-red-100 text-red-700 rounded text-xs">تعويض</span>@endif
                                    </div>
                                </div>
                                <div class="flex items-center gap-2 flex-shrink-0">
                                    <span class="text-xs px-2 py-0.5 rounded-lg font-medium {{ $p->method==='cash'?'bg-green-100 text-green-700':($p->method==='pos'?'bg-blue-100 text-blue-700':'bg-purple-100 text-purple-700') }}">
                                        {{ match($p->method){'cash'=>'نقدي','pos'=>'POS','bank_transfer'=>'تحويل',default=>$p->method} }}
                                    </span>
                                    <span class="font-black text-green-700 text-sm whitespace-nowrap">{{ number_format($p->amount, 0) }} <span class="text-xs font-normal text-gray-400">{{ $p->currency }}</span></span>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        @endif
                    </div>
                    <div class="p-5 space-y-5">
                        <div>
                            <div class="flex items-center justify-between mb-4">
                                <h4 class="flex items-center gap-2 font-bold text-gray-700 text-sm">
                                    <span class="w-7 h-7 rounded-xl bg-red-100 flex items-center justify-center flex-shrink-0"><svg class="w-3.5 h-3.5 text-red-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg></span>
                                    السحبيات والمصروفات
                                </h4>
                                @if($expenses->isNotEmpty())<span class="text-xs text-gray-500 bg-gray-100 px-2.5 py-1 rounded-lg font-semibold">{{ $expenses->count() }} عملية</span>@endif
                            </div>
                            @if($expenses->isEmpty())
                            <div class="flex flex-col items-center justify-center py-6 text-gray-300"><svg class="w-8 h-8 mb-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 12H4"/></svg><p class="text-xs">لا توجد سحبيات</p></div>
                            @else
                            <div class="space-y-2">
                                @foreach($expenses as $w)
                                <div class="flex items-start justify-between rounded-xl border border-red-100 bg-red-50/60 px-4 py-2.5 gap-3">
                                    <div class="min-w-0 flex-1">
                                        <p class="font-semibold text-gray-800 text-xs">{{ $w->withdrawn_by_name ?? '—' }}</p>
                                        @if($w->notes)<p class="text-gray-500 text-xs mt-0.5">{{ $w->notes }}</p>@endif
                                    </div>
                                    <span class="font-black text-red-700 text-sm whitespace-nowrap flex-shrink-0">{{ number_format($w->amount, 0) }} <span class="text-xs font-normal text-gray-400">{{ $w->currency }}</span></span>
                                </div>
                                @endforeach
                            </div>
                            @endif
                        </div>
                        @if($exchanges->isNotEmpty())
                        <div>
                            <h4 class="flex items-center gap-2 font-bold text-gray-700 text-sm mb-3">
                                <span class="w-7 h-7 rounded-xl bg-purple-100 flex items-center justify-center flex-shrink-0"><svg class="w-3.5 h-3.5 text-purple-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg></span>
                                صرف العملة
                            </h4>
                            <div class="space-y-2">
                                @foreach($exchanges as $w)
                                <div class="rounded-xl border border-purple-100 bg-purple-50/60 px-4 py-2.5">
                                    <div class="flex items-center gap-2 text-sm">
                                        <span class="font-bold text-gray-800">{{ number_format($w->amount, 0) }} <span class="text-xs text-gray-500">{{ $w->currency }}</span></span>
                                        @if($w->exchange_to_amount)<svg class="w-4 h-4 text-purple-400 flex-shrink-0 rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                                        <span class="font-bold text-purple-700">{{ number_format($w->exchange_to_amount, 0) }} <span class="text-xs text-gray-500">{{ $w->exchange_to_currency }}</span></span>@endif
                                    </div>
                                    @if($w->notes)<p class="text-gray-400 text-xs mt-1">{{ $w->notes }}</p>@endif
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            <div class="border-t border-gray-100 bg-gray-50/80 px-5 py-4">
                @php
                $activeCurrencies = collect([['YER','ر.ي'],['SAR','ر.س'],['USD','$']])->filter(fn($c) => $shift->{"total_received_{$c[0]}"} > 0 || $shift->{"total_withdrawals_{$c[0]}"} > 0);
                @endphp
                <div class="grid gap-3" style="grid-template-columns: repeat({{ max(1,$activeCurrencies->count()) }}, minmax(0,1fr))">
                    @foreach($activeCurrencies as [$cur,$sym])
                    @php $recv=$shift->{"total_received_$cur"}; $wdr=$shift->{"total_withdrawals_$cur"}; $net=$recv-$wdr; @endphp
                    <div class="bg-white rounded-xl border border-gray-100 px-4 py-3 shadow-sm">
                        <div class="flex items-center justify-between mb-2.5">
                            <span class="text-xs font-bold text-gray-500 uppercase tracking-wide">{{ $cur }}</span>
                            <span class="text-sm font-black {{ $net>=0?'text-green-700':'text-red-700' }}">{{ number_format($net,0) }} <span class="text-xs font-normal text-gray-400">{{ $sym }}</span></span>
                        </div>
                        <div class="space-y-1.5 text-xs border-t border-gray-100 pt-2.5">
                            <div class="flex justify-between items-center"><span class="text-gray-400 flex items-center gap-1"><span class="w-1.5 h-1.5 rounded-full bg-green-400 inline-block"></span>المستلم</span><span class="font-bold text-green-700">{{ number_format($recv,0) }}</span></div>
                            <div class="flex justify-between items-center"><span class="text-gray-400 flex items-center gap-1"><span class="w-1.5 h-1.5 rounded-full bg-red-400 inline-block"></span>السحب</span><span class="font-bold text-red-600">{{ number_format($wdr,0) }}</span></div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @if($shift->notes)
                <div class="mt-3 flex items-start gap-2.5 bg-yellow-50 border border-yellow-100 rounded-xl px-4 py-2.5">
                    <svg class="w-3.5 h-3.5 text-yellow-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span class="text-xs text-yellow-800">{{ $shift->notes }}</span>
                </div>
                @endif
            </div>
        </div>
    </div>
    @endforeach
    </div>
    @if($shifts && $shifts->hasPages())
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 px-5 py-3 mt-4">{{ $shifts->links() }}</div>
    @endif
    @endif
</div>
@endif

{{-- ══════════════════════════════════════════ TAB 2: عجوزات الصناديق ══════════════════════════════════════════ --}}
@if($tab === 'deficits')
<div class="p-4">
    <form method="GET" action="{{ route('reports.shiftsHub') }}" class="flex flex-wrap gap-3 items-end mb-4">
        <input type="hidden" name="tab" value="deficits">
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
        <button type="submit" class="px-4 py-2 text-sm text-white rounded-lg transition" style="background:#0F4C75;">عرض</button>
    </form>

    @if($summary && $summary->isEmpty())
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-10 text-center text-gray-400">لا توجد ورديات مقفلة في هذه الفترة</div>
    @else
    @php
        $grandTotalDeficit  = $summary->sum('total_deficit');
        $grandTotalSurplus  = $summary->sum('total_surplus');
        $grandTotalReceived = $summary->sum('total_received');
        $grandDeficitCount  = $summary->sum('deficit_count');
        $grandDeductedCount = $summary->sum('deducted_count');
    @endphp
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-5">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
            <p class="text-xs text-gray-500 mb-1">إجمالي المستلمات</p>
            <p class="text-lg font-bold text-green-700">{{ number_format($grandTotalReceived, 0) }} <span class="text-sm font-normal">ر.ي</span></p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-red-100 p-4">
            <p class="text-xs text-gray-500 mb-1">إجمالي العجوزات</p>
            <p class="text-lg font-bold text-red-700">{{ number_format($grandTotalDeficit, 0) }} <span class="text-sm font-normal">ر.ي</span></p>
            <p class="text-xs text-gray-400 mt-0.5">{{ $grandDeficitCount }} وردية بعجز</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-amber-100 p-4">
            <p class="text-xs text-gray-500 mb-1">إجمالي الزيادات</p>
            <p class="text-lg font-bold text-amber-700">{{ number_format($grandTotalSurplus, 0) }} <span class="text-sm font-normal">ر.ي</span></p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
            <p class="text-xs text-gray-500 mb-1">عجوزات مخصومة</p>
            <p class="text-lg font-bold" style="color:#0F4C75;">{{ $grandDeductedCount }} / {{ $grandDeficitCount }}</p>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="px-5 py-3 border-b border-gray-100">
            <h3 class="font-semibold text-gray-700 text-sm">تفاصيل العجز لكل موظف</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50"><tr>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الموظف</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">عدد الورديات</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">إجمالي المستلمات</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">إجمالي السحبيات</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-red-600">مجموع العجز</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-amber-600">مجموع الزيادة</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">ورديات العجز</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">المخصوم</th>
                </tr></thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($summary as $row)
                    <tr x-data="{ open: false }" class="hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <button @click="open=!open" class="flex items-center gap-2 font-medium text-gray-800">
                                <svg :class="open?'rotate-90':''" class="w-3.5 h-3.5 text-gray-400 transition-transform flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                {{ $row['user']->name }}
                            </button>
                        </td>
                        <td class="px-4 py-3 text-gray-600">{{ $row['shift_count'] }}</td>
                        <td class="px-4 py-3 font-medium text-green-700">{{ number_format($row['total_received'], 0) }} ر.ي</td>
                        <td class="px-4 py-3 font-medium text-gray-600">{{ number_format($row['total_withdrawn'], 0) }} ر.ي</td>
                        <td class="px-4 py-3 font-bold {{ $row['total_deficit'] > 0 ? 'text-red-700' : 'text-gray-300' }}">{{ $row['total_deficit'] > 0 ? number_format($row['total_deficit'], 0).' ر.ي' : '—' }}</td>
                        <td class="px-4 py-3 font-medium {{ $row['total_surplus'] > 0 ? 'text-amber-700' : 'text-gray-300' }}">{{ $row['total_surplus'] > 0 ? number_format($row['total_surplus'], 0).' ر.ي' : '—' }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $row['deficit_count'] }}</td>
                        <td class="px-4 py-3">
                            @if($row['deficit_count'] > 0)
                            <span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ $row['deducted_count'] == $row['deficit_count'] ? 'bg-green-100 text-green-700' : 'bg-orange-100 text-orange-700' }}">
                                {{ $row['deducted_count'] }} / {{ $row['deficit_count'] }}
                            </span>
                            @else
                            <span class="text-gray-300 text-xs">—</span>
                            @endif
                        </td>
                    </tr>
                    <tr x-show="open" x-cloak>
                        <td colspan="8" class="bg-gray-50 px-6 py-3">
                            <table class="w-full text-xs">
                                <thead><tr class="text-gray-400">
                                    <th class="py-1 text-right">التاريخ</th>
                                    <th class="py-1 text-right">المستلمات</th>
                                    <th class="py-1 text-right">السحبيات</th>
                                    <th class="py-1 text-right">الصافي</th>
                                    <th class="py-1 text-right">الفعلي</th>
                                    <th class="py-1 text-right">الفرق</th>
                                    <th class="py-1 text-right">الخصم</th>
                                    <th class="py-1"></th>
                                </tr></thead>
                                <tbody class="divide-y divide-gray-200">
                                    @foreach($row['shifts'] as $sh)
                                    @php $net = $sh->net_balance_yer; @endphp
                                    <tr>
                                        <td class="py-1.5 text-gray-600">{{ $sh->shift_date->format('d/m/Y') }}</td>
                                        <td class="py-1.5 text-green-700 font-medium">{{ number_format($sh->total_received_yer, 0) }}</td>
                                        <td class="py-1.5 text-red-600">{{ number_format($sh->total_withdrawals_yer, 0) }}</td>
                                        <td class="py-1.5 font-medium {{ $net >= 0 ? 'text-primary-700' : 'text-red-700' }}">{{ number_format($net, 0) }}</td>
                                        <td class="py-1.5 text-gray-600">{{ $sh->actual_amount !== null ? number_format($sh->actual_amount, 0) : '—' }}</td>
                                        <td class="py-1.5">
                                            @if($sh->shortfall !== null)
                                            @php $sf = $sh->shortfall; @endphp
                                            <span class="font-semibold {{ $sf==0?'text-green-600':($sf<0?'text-red-700':'text-amber-700') }}">
                                                {{ $sf==0?'مطابق':($sf<0?'▼ '.number_format(abs($sf),0):'▲ '.number_format($sf,0)) }}
                                            </span>
                                            @else —
                                            @endif
                                        </td>
                                        <td class="py-1.5">
                                            @if($sh->shortfall !== null && $sh->shortfall < 0)
                                            @if($sh->salary_deducted_at)
                                            <span class="text-green-600 font-medium">✓ مخصوم</span>
                                            @else
                                            <form method="POST" action="{{ route('shifts.deductSalary', $sh) }}" onsubmit="return confirm('خصم {{ number_format(abs($sh->shortfall), 0) }} ر.ي من راتب {{ $row['user']->name }}؟')">
                                                @csrf
                                                <button type="submit" class="px-2 py-0.5 border border-red-300 text-red-600 rounded text-xs hover:bg-red-50 transition">خصم من الراتب</button>
                                            </form>
                                            @endif
                                            @else
                                            <span class="text-gray-300">—</span>
                                            @endif
                                        </td>
                                        <td class="py-1.5">
                                            <a href="{{ route('shifts.pdf', $sh) }}" target="_blank" class="inline-flex items-center gap-1 px-2 py-1 bg-red-600 text-white rounded text-xs hover:bg-red-700 transition">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                                PDF
                                            </a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>
@endif

{{-- ══════════════════════════════════════════ TAB 3: الإغلاق اليومي ══════════════════════════════════════════ --}}
@if($tab === 'daily')
<div class="p-4">
    <form method="GET" action="{{ route('reports.shiftsHub') }}" class="flex flex-wrap items-end gap-3 mb-4">
        <input type="hidden" name="tab" value="daily">
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">التاريخ</label>
            <input type="date" name="date" value="{{ $date }}"
                   class="border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none focus:border-blue-400">
        </div>
        <button type="submit" class="px-4 py-2 text-white rounded-lg text-sm" style="background:#0F4C75;">عرض</button>
        <a href="{{ route('reports.dailyClose.pdf', ['date' => $date]) }}"
           class="px-4 py-2 bg-red-600 text-white rounded-lg text-sm hover:bg-red-700 flex items-center gap-2 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
            PDF
        </a>
    </form>

    <h2 class="text-base font-bold text-gray-700 mb-4">
        تقرير إغلاق يوم {{ \Carbon\Carbon::parse($date)->isoFormat('dddd، D MMMM Y') }}
    </h2>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-5">
        <div class="bg-white rounded-xl shadow-sm border border-green-100 p-4">
            <p class="text-xs text-gray-500 mb-1">إجمالي الإيرادات</p>
            <p class="text-2xl font-bold text-green-700">{{ number_format($totalRevenue, 0) }}</p>
            <p class="text-xs text-gray-400">ر.ي</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-red-100 p-4">
            <p class="text-xs text-gray-500 mb-1">إجمالي المصروفات</p>
            <p class="text-2xl font-bold text-red-600">{{ number_format($totalExpenses, 0) }}</p>
            <p class="text-xs text-gray-400">ر.ي</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-blue-100 p-4">
            <p class="text-xs text-gray-500 mb-1">صافي اليوم</p>
            <p class="text-2xl font-bold {{ $netDay >= 0 ? 'text-blue-700' : 'text-red-600' }}">{{ number_format($netDay, 0) }}</p>
            <p class="text-xs text-gray-400">ر.ي</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
            <p class="text-xs text-gray-500 mb-1">عدد الدفعات</p>
            <p class="text-2xl font-bold text-gray-700">{{ $paymentCount }}</p>
            <p class="text-xs text-gray-400">{{ $reservationCount }} حجز</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-5">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100">
            <div class="px-5 py-3 border-b border-gray-100"><h3 class="font-semibold text-gray-700 text-sm">الإيرادات حسب طريقة الدفع</h3></div>
            <table class="w-full text-sm">
                <thead class="bg-gray-50"><tr>
                    <th class="px-4 py-2.5 text-right text-xs font-medium text-gray-500">الطريقة</th>
                    <th class="px-4 py-2.5 text-right text-xs font-medium text-gray-500">عدد الدفعات</th>
                    <th class="px-4 py-2.5 text-right text-xs font-medium text-gray-500">المبلغ (ر.ي)</th>
                </tr></thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($revenueByMethod as $row)
                    <tr>
                        <td class="px-4 py-2.5">
                            <span class="text-xs px-2 py-0.5 rounded-full {{ $row->method==='cash'?'bg-green-100 text-green-700':($row->method==='bank_transfer'?'bg-blue-100 text-blue-700':'bg-purple-100 text-purple-700') }}">
                                {{ match($row->method){'cash'=>'نقداً','bank_transfer'=>'تحويل بنكي','pos'=>'POS',default=>$row->method} }}
                            </span>
                        </td>
                        <td class="px-4 py-2.5 text-gray-600">{{ $row->count }}</td>
                        <td class="px-4 py-2.5 font-semibold text-gray-800">{{ number_format($row->total, 0) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="3" class="px-4 py-6 text-center text-gray-400">لا توجد إيرادات لهذا اليوم</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100">
            <div class="px-5 py-3 border-b border-gray-100"><h3 class="font-semibold text-gray-700 text-sm">المصروفات حسب الفئة</h3></div>
            <table class="w-full text-sm">
                <thead class="bg-gray-50"><tr>
                    <th class="px-4 py-2.5 text-right text-xs font-medium text-gray-500">الفئة</th>
                    <th class="px-4 py-2.5 text-right text-xs font-medium text-gray-500">العدد</th>
                    <th class="px-4 py-2.5 text-right text-xs font-medium text-gray-500">المبلغ (ر.ي)</th>
                </tr></thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($expensesByCategory as $row)
                    <tr>
                        <td class="px-4 py-2.5 text-gray-700">{{ \App\Models\Expense::categoryLabel($row->category) }}</td>
                        <td class="px-4 py-2.5 text-gray-600">{{ $row->count }}</td>
                        <td class="px-4 py-2.5 font-semibold text-red-600">{{ number_format($row->total, 0) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="3" class="px-4 py-6 text-center text-gray-400">لا توجد مصروفات لهذا اليوم</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-5">
        <div class="px-5 py-3 border-b border-gray-100"><h3 class="font-semibold text-gray-700 text-sm">ورديات اليوم</h3></div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50"><tr>
                    <th class="px-4 py-2.5 text-right text-xs font-medium text-gray-500">الموظف</th>
                    <th class="px-4 py-2.5 text-right text-xs font-medium text-gray-500">البدء</th>
                    <th class="px-4 py-2.5 text-right text-xs font-medium text-gray-500">الإقفال</th>
                    <th class="px-4 py-2.5 text-right text-xs font-medium text-gray-500">مستلمات</th>
                    <th class="px-4 py-2.5 text-right text-xs font-medium text-gray-500">سحبيات</th>
                    <th class="px-4 py-2.5 text-right text-xs font-medium text-gray-500">الصافي</th>
                    <th class="px-4 py-2.5 text-right text-xs font-medium text-gray-500">عجز/فائض</th>
                    <th class="px-4 py-2.5 text-right text-xs font-medium text-gray-500">الحالة</th>
                </tr></thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($dailyShifts as $shift)
                    @php $net = $shift->net_balance_yer; @endphp
                    <tr>
                        <td class="px-4 py-2.5 font-medium text-gray-800">{{ $shift->user->name ?? '—' }}</td>
                        <td class="px-4 py-2.5 text-gray-500 text-xs">{{ $shift->started_at?->format('H:i') }}</td>
                        <td class="px-4 py-2.5 text-gray-500 text-xs">{{ $shift->closed_at?->format('H:i') ?? '—' }}</td>
                        <td class="px-4 py-2.5 text-green-700">{{ number_format($shift->total_received_yer, 0) }}</td>
                        <td class="px-4 py-2.5 text-red-600">{{ number_format($shift->total_withdrawals_yer, 0) }}</td>
                        <td class="px-4 py-2.5 font-semibold" style="color:#0F4C75;">{{ number_format($net, 0) }}</td>
                        <td class="px-4 py-2.5 font-semibold {{ $shift->shortfall < 0 ? 'text-red-600' : 'text-gray-400' }}">{{ $shift->shortfall != 0 ? number_format($shift->shortfall, 0) : '—' }}</td>
                        <td class="px-4 py-2.5">
                            @if($shift->is_closed)
                            <span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-600">مقفلة</span>
                            @else
                            <span class="text-xs px-2 py-0.5 rounded-full bg-green-100 text-green-700">مفتوحة</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="px-4 py-6 text-center text-gray-400">لا توجد ورديات لهذا اليوم</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <h3 class="font-semibold text-gray-700 text-sm mb-4">تسوية الكاش (ريال يمني)</h3>
        <div class="space-y-2 text-sm">
            <div class="flex justify-between items-center py-2 border-b border-gray-100">
                <span class="text-gray-600">إجمالي المستلمات نقداً</span>
                <span class="font-semibold text-green-700">{{ number_format($cashRevenue, 0) }} ر.ي</span>
            </div>
            <div class="flex justify-between items-center py-2 border-b border-gray-100">
                <span class="text-gray-600">إجمالي المصروفات نقداً</span>
                <span class="font-semibold text-red-600">{{ number_format($cashExpenses, 0) }} ر.ي</span>
            </div>
            <div class="flex justify-between items-center py-2 border-b border-gray-100">
                <span class="text-gray-600">السحبيات من الصناديق</span>
                <span class="font-semibold text-red-600">{{ number_format($totalWithdrawals, 0) }} ر.ي</span>
            </div>
            <div class="flex justify-between items-center py-3 bg-blue-50 rounded-lg px-3 mt-2">
                <span class="font-bold text-gray-700">الرصيد النقدي المتوقع</span>
                <span class="text-xl font-bold" style="color:#0F4C75;">{{ number_format($expectedCash, 0) }} ر.ي</span>
            </div>
        </div>
    </div>
</div>
@endif

</div>{{-- end tabs container --}}
</div>{{-- end main --}}
@endsection
