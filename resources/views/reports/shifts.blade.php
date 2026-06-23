@extends('layouts.app')
@section('title', 'تقرير الورديات')
@section('page-title', 'تقرير الورديات')

@section('content')
<div class="space-y-5" dir="rtl">

{{-- ─── شريط الفلتر والتصدير ─── --}}
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 px-5 py-4">
    <form method="GET" class="flex flex-wrap items-end gap-4">
        <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">من تاريخ</label>
            <input type="date" name="from" value="{{ $from }}" onchange="this.form.submit()"
                   class="border border-gray-200 rounded-xl px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400 transition bg-white">
        </div>
        <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">إلى تاريخ</label>
            <input type="date" name="to" value="{{ $to }}" onchange="this.form.submit()"
                   class="border border-gray-200 rounded-xl px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400 transition bg-white">
        </div>
        <button type="submit" class="sr-only">عرض</button>

        <div class="flex items-center gap-2 me-auto">
            <span class="text-xs text-gray-400 bg-gray-50 px-3 py-2 rounded-xl border border-gray-100">
                {{ \Carbon\Carbon::parse($from)->format('d/m/Y') }} — {{ \Carbon\Carbon::parse($to)->format('d/m/Y') }}
            </span>
            <a href="{{ route('reports.shifts.pdf', ['from' => $from, 'to' => $to]) }}"
               class="inline-flex items-center gap-1.5 px-3 py-2 bg-red-600 text-white text-xs rounded-xl hover:bg-red-700 transition font-semibold">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                تصدير PDF
            </a>
            <a href="{{ route('reports.shifts.excel', ['from' => $from, 'to' => $to]) }}"
               class="inline-flex items-center gap-1.5 px-3 py-2 bg-green-600 text-white text-xs rounded-xl hover:bg-green-700 transition font-semibold">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Excel
            </a>
        </div>
    </form>
</div>

@if($shifts->isEmpty())
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 py-20 text-center">
    <div class="w-16 h-16 rounded-2xl bg-gray-100 flex items-center justify-center mx-auto mb-4">
        <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
    </div>
    <p class="text-sm font-medium text-gray-400">لا توجد ورديات في هذه الفترة</p>
</div>
@else

{{-- ─── قائمة الورديات ─── --}}
<div class="space-y-4">
@foreach($shifts as $shift)
@php
    $payments    = $shift->payments->sortBy(fn($p) => $p->reservation?->display_room_number ?? '');
    $withdrawals = $shift->withdrawals->sortBy('withdrawal_date');
    $expenses    = $withdrawals->where('withdrawal_type', '!=', 'currency_exchange');
    $exchanges   = $withdrawals->where('withdrawal_type', 'currency_exchange');

    $empName = $shift->user?->name ?? '—';
    $empColors = ['#0F4C75','#065f46','#92400e','#6d28d9','#be123c','#0369a1'];
    $empColor  = $empColors[abs(crc32($empName)) % count($empColors)];
@endphp

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden" x-data="{ open: true }">

    {{-- ─── رأس الوردية ─── --}}
    <div class="px-5 py-4 flex items-center gap-4 cursor-pointer select-none hover:bg-gray-50/60 transition"
         @click="open = !open">

        {{-- صورة الموظف والاسم --}}
        <div class="flex items-center gap-3 min-w-0">
            <div class="w-11 h-11 rounded-xl flex items-center justify-center text-white font-bold text-base flex-shrink-0 shadow-sm"
                 style="background: {{ $empColor }};">
                {{ mb_strtoupper(mb_substr($empName, 0, 1)) }}
            </div>
            <div>
                <div class="font-bold text-gray-900 text-sm">{{ $empName }}</div>
                <div class="flex items-center gap-2 mt-0.5 text-xs text-gray-400">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    {{ $shift->shift_date->format('d/m/Y') }}
                    <span class="w-1 h-1 rounded-full bg-gray-300 inline-block"></span>
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    {{ $shift->started_at?->format('H:i') ?? '—' }}
                    @if($shift->ended_at) — {{ $shift->ended_at->format('H:i') }} @endif
                </div>
            </div>
        </div>

        {{-- فاصل --}}
        <div class="h-10 w-px bg-gray-100 hidden md:block"></div>

        {{-- الإجماليات المالية --}}
        <div class="flex items-center gap-5 flex-wrap">
            @foreach([['YER','ر.ي','green'], ['SAR','ر.س','blue'], ['USD','$','purple']] as [$cur, $sym, $clr])
            @php $net = $shift->{"total_received_$cur"} - $shift->{"total_withdrawals_$cur"}; @endphp
            @if($shift->{"total_received_$cur"} > 0 || $shift->{"total_withdrawals_$cur"} > 0)
            <div class="text-center">
                <div class="text-xs text-gray-400 mb-0.5">صافي {{ $sym }}</div>
                <div class="font-black text-sm {{ $net >= 0 ? 'text-green-700' : 'text-red-700' }}">
                    {{ number_format($net, 0) }}
                </div>
            </div>
            @endif
            @endforeach
        </div>

        {{-- حالة الوردية وزر التوسيع --}}
        <div class="flex items-center gap-3 me-0 ms-auto">
            <span class="px-2.5 py-1 rounded-full text-xs font-semibold
                {{ $shift->is_closed ? 'bg-gray-100 text-gray-600' : 'bg-amber-100 text-amber-700' }}">
                {{ $shift->is_closed ? 'مغلقة' : 'مفتوحة' }}
            </span>
            <svg :class="open ? 'rotate-180' : ''" class="w-4 h-4 text-gray-400 flex-shrink-0 transition-transform duration-200"
                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
            </svg>
        </div>
    </div>

    {{-- ─── جسم الوردية ─── --}}
    <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">

        <div class="border-t border-gray-100">
            <div class="grid grid-cols-1 lg:grid-cols-2 divide-y lg:divide-y-0 lg:divide-x lg:divide-x-reverse divide-gray-100">

                {{-- ─── الإيرادات ─── --}}
                <div class="p-5">
                    <div class="flex items-center justify-between mb-4">
                        <h4 class="flex items-center gap-2 font-bold text-gray-700 text-sm">
                            <span class="w-7 h-7 rounded-xl bg-green-100 flex items-center justify-center flex-shrink-0">
                                <svg class="w-3.5 h-3.5 text-green-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </span>
                            الإيرادات المستلمة
                        </h4>
                        <span class="text-xs text-gray-500 bg-gray-100 px-2.5 py-1 rounded-lg font-semibold">
                            {{ $payments->count() }} دفعة
                        </span>
                    </div>

                    @if($payments->isEmpty())
                    <div class="flex flex-col items-center justify-center py-8 text-gray-300">
                        <svg class="w-9 h-9 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <p class="text-xs">لا توجد دفعات</p>
                    </div>
                    @else
                    <div class="space-y-2">
                        @foreach($payments as $p)
                        <div class="flex items-center justify-between rounded-xl border border-gray-100 bg-gray-50/70 px-4 py-2.5 gap-3">
                            {{-- يسار: رقم الغرفة والنزيل --}}
                            <div class="flex items-center gap-3 min-w-0 flex-1">
                                <span class="flex-shrink-0 font-black text-xs text-white px-2 py-1 rounded-lg"
                                      style="background:#0F4C75; min-width:2.5rem; text-align:center;">
                                    {{ $p->reservation?->display_room_number ?? '—' }}
                                </span>
                                <div class="min-w-0">
                                    <span class="text-gray-700 text-xs font-medium block truncate">
                                        {{ $p->reservation?->guest?->full_name ?? '—' }}
                                    </span>
                                    @if($p->type === 'compensation')
                                    <span class="inline-block mt-0.5 px-1.5 py-0.5 bg-red-100 text-red-700 rounded text-xs">تعويض</span>
                                    @endif
                                </div>
                            </div>
                            {{-- يمين: الطريقة والمبلغ --}}
                            <div class="flex items-center gap-2 flex-shrink-0">
                                <span class="text-xs px-2 py-0.5 rounded-lg font-medium
                                    {{ $p->method === 'cash' ? 'bg-green-100 text-green-700' : ($p->method === 'pos' ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700') }}">
                                    {{ match($p->method) { 'cash' => 'نقدي', 'pos' => 'POS', 'bank_transfer' => 'تحويل', default => $p->method } }}
                                </span>
                                <span class="font-black text-green-700 text-sm whitespace-nowrap">
                                    {{ number_format($p->amount, 0) }}
                                    <span class="text-xs font-normal text-gray-400">{{ $p->currency }}</span>
                                </span>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @endif
                </div>

                {{-- ─── السحبيات وصرف العملة ─── --}}
                <div class="p-5 space-y-5">

                    {{-- السحبيات والمصروفات --}}
                    <div>
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="flex items-center gap-2 font-bold text-gray-700 text-sm">
                                <span class="w-7 h-7 rounded-xl bg-red-100 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-3.5 h-3.5 text-red-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                                    </svg>
                                </span>
                                السحبيات والمصروفات
                            </h4>
                            @if($expenses->isNotEmpty())
                            <span class="text-xs text-gray-500 bg-gray-100 px-2.5 py-1 rounded-lg font-semibold">
                                {{ $expenses->count() }} عملية
                            </span>
                            @endif
                        </div>

                        @if($expenses->isEmpty())
                        <div class="flex flex-col items-center justify-center py-6 text-gray-300">
                            <svg class="w-8 h-8 mb-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 12H4"/>
                            </svg>
                            <p class="text-xs">لا توجد سحبيات</p>
                        </div>
                        @else
                        <div class="space-y-2">
                            @foreach($expenses as $w)
                            <div class="flex items-start justify-between rounded-xl border border-red-100 bg-red-50/60 px-4 py-2.5 gap-3">
                                <div class="min-w-0 flex-1">
                                    <p class="font-semibold text-gray-800 text-xs">{{ $w->withdrawn_by_name ?? '—' }}</p>
                                    @if($w->notes)
                                    <p class="text-gray-500 text-xs mt-0.5">{{ $w->notes }}</p>
                                    @endif
                                    @if($w->handed_by_name && $w->handed_by_name !== '-')
                                    <p class="text-gray-400 text-xs mt-0.5 flex items-center gap-1">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                        بواسطة: {{ $w->handed_by_name }}
                                    </p>
                                    @endif
                                </div>
                                <span class="font-black text-red-700 text-sm whitespace-nowrap flex-shrink-0">
                                    {{ number_format($w->amount, 0) }}
                                    <span class="text-xs font-normal text-gray-400">{{ $w->currency }}</span>
                                </span>
                            </div>
                            @endforeach
                        </div>
                        @endif
                    </div>

                    {{-- صرف العملة --}}
                    @if($exchanges->isNotEmpty())
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <h4 class="flex items-center gap-2 font-bold text-gray-700 text-sm">
                                <span class="w-7 h-7 rounded-xl bg-purple-100 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-3.5 h-3.5 text-purple-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                                    </svg>
                                </span>
                                صرف العملة
                            </h4>
                            <span class="text-xs text-gray-500 bg-gray-100 px-2.5 py-1 rounded-lg font-semibold">{{ $exchanges->count() }}</span>
                        </div>
                        <div class="space-y-2">
                            @foreach($exchanges as $w)
                            <div class="rounded-xl border border-purple-100 bg-purple-50/60 px-4 py-2.5">
                                <div class="flex items-center gap-2 text-sm">
                                    <span class="font-bold text-gray-800">{{ number_format($w->amount, 0) }} <span class="text-xs text-gray-500">{{ $w->currency }}</span></span>
                                    @if($w->exchange_to_amount)
                                    {{-- سهم RTL: من اليسار الى اليمين --}}
                                    <svg class="w-4 h-4 text-purple-400 flex-shrink-0 rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                                    </svg>
                                    <span class="font-bold text-purple-700">{{ number_format($w->exchange_to_amount, 0) }} <span class="text-xs text-gray-500">{{ $w->exchange_to_currency }}</span></span>
                                    @endif
                                </div>
                                @if($w->notes)
                                <p class="text-gray-400 text-xs mt-1">{{ $w->notes }}</p>
                                @endif
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- ─── ملخص مالي للوردية ─── --}}
        <div class="border-t border-gray-100 bg-gray-50/80 px-5 py-4">
            @php
                $activeCurrencies = collect([
                    ['YER','ر.ي'],
                    ['SAR','ر.س'],
                    ['USD','$'],
                ])->filter(fn($c) => $shift->{"total_received_{$c[0]}"} > 0 || $shift->{"total_withdrawals_{$c[0]}"} > 0);
            @endphp

            <div class="grid gap-3"
                 style="grid-template-columns: repeat({{ max(1, $activeCurrencies->count()) }}, minmax(0,1fr))">
                @foreach($activeCurrencies as [$cur, $sym])
                @php
                    $recv = $shift->{"total_received_$cur"};
                    $wdr  = $shift->{"total_withdrawals_$cur"};
                    $net  = $recv - $wdr;
                @endphp
                <div class="bg-white rounded-xl border border-gray-100 px-4 py-3 shadow-sm">
                    {{-- رأس البطاقة --}}
                    <div class="flex items-center justify-between mb-2.5">
                        <span class="text-xs font-bold text-gray-500 uppercase tracking-wide">{{ $cur }}</span>
                        <span class="text-sm font-black {{ $net >= 0 ? 'text-green-700' : 'text-red-700' }}">
                            {{ number_format($net, 0) }}
                            <span class="text-xs font-normal text-gray-400">{{ $sym }}</span>
                        </span>
                    </div>
                    {{-- تفاصيل --}}
                    <div class="space-y-1.5 text-xs border-t border-gray-100 pt-2.5">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-400 flex items-center gap-1">
                                <span class="w-1.5 h-1.5 rounded-full bg-green-400 inline-block"></span>
                                المستلم
                            </span>
                            <span class="font-bold text-green-700">{{ number_format($recv, 0) }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-400 flex items-center gap-1">
                                <span class="w-1.5 h-1.5 rounded-full bg-red-400 inline-block"></span>
                                السحب
                            </span>
                            <span class="font-bold text-red-600">{{ number_format($wdr, 0) }}</span>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            @if($shift->notes)
            <div class="mt-3 flex items-start gap-2.5 bg-yellow-50 border border-yellow-100 rounded-xl px-4 py-2.5">
                <svg class="w-3.5 h-3.5 text-yellow-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="text-xs text-yellow-800">{{ $shift->notes }}</span>
            </div>
            @endif
        </div>

    </div>{{-- end shift body --}}
</div>{{-- end shift card --}}
@endforeach
</div>

{{-- ─── ترقيم الصفحات ─── --}}
@if($shifts->hasPages())
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 px-5 py-3">
    {{ $shifts->links() }}
</div>
@endif

@endif

</div>
@endsection
