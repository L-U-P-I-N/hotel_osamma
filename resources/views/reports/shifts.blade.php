@extends('layouts.app')
@section('title', 'تقرير الورديات')
@section('page-title', 'تقرير الورديات')

@section('content')
<div class="space-y-4">

{{-- Filter --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
    <form method="GET" class="flex flex-wrap items-end gap-3">
        <div class="flex flex-col gap-1">
            <label class="text-xs font-medium text-gray-500">من</label>
            <input type="date" name="from" value="{{ $from }}" onchange="this.form.submit()"
                   class="border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400 transition bg-white">
        </div>
        <div class="flex flex-col gap-1">
            <label class="text-xs font-medium text-gray-500">إلى</label>
            <input type="date" name="to" value="{{ $to }}" onchange="this.form.submit()"
                   class="border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400 transition bg-white">
        </div>
        <button type="submit" class="sr-only">عرض</button>
        <div class="mr-auto flex items-center gap-2">
            <span class="text-xs text-gray-400">{{ \Carbon\Carbon::parse($from)->format('d/m/Y') }} — {{ \Carbon\Carbon::parse($to)->format('d/m/Y') }}</span>
            <a href="{{ route('reports.shifts.pdf', ['from' => $from, 'to' => $to]) }}"
               class="inline-flex items-center gap-1.5 px-3 py-2 bg-red-600 text-white text-xs rounded-lg hover:bg-red-700 transition font-medium">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                PDF
            </a>
            <a href="{{ route('reports.shifts.excel', ['from' => $from, 'to' => $to]) }}"
               class="inline-flex items-center gap-1.5 px-3 py-2 bg-green-600 text-white text-xs rounded-lg hover:bg-green-700 transition font-medium">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Excel
            </a>
        </div>
    </form>
</div>

@if($shifts->isEmpty())
<div class="bg-white rounded-xl shadow-sm border border-gray-100 py-16 text-center text-gray-400">
    <svg class="w-12 h-12 mx-auto mb-3 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
    </svg>
    <p class="text-sm">لا توجد ورديات في هذه الفترة</p>
</div>
@else

{{-- Shifts List --}}
<div class="space-y-4">
@foreach($shifts as $shift)
@php
    $payments = $shift->payments->sortBy(fn($p) => $p->reservation?->display_room_number ?? '');
    $withdrawals = $shift->withdrawals->sortBy('withdrawal_date');
    $expenses = $withdrawals->where('withdrawal_type', '!=', 'currency_exchange');
    $exchanges = $withdrawals->where('withdrawal_type', 'currency_exchange');
@endphp

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden" x-data="{ open: true }">

    {{-- Shift Header --}}
    <div class="px-5 py-4 border-b border-gray-100 flex items-center gap-4 cursor-pointer select-none" @click="open=!open">
        <div class="flex-1 flex items-center gap-4 flex-wrap">
            <div>
                <div class="text-xs text-gray-400 mb-0.5">التاريخ</div>
                <div class="font-bold text-gray-800">{{ $shift->shift_date->format('d/m/Y') }}</div>
            </div>
            <div class="h-8 w-px bg-gray-100"></div>
            <div>
                <div class="text-xs text-gray-400 mb-0.5">الموظف</div>
                <div class="font-semibold text-gray-700">{{ $shift->user?->name ?? '—' }}</div>
            </div>
            <div class="h-8 w-px bg-gray-100"></div>
            <div>
                <div class="text-xs text-gray-400 mb-0.5">الوردية</div>
                <div class="text-sm text-gray-600">
                    {{ $shift->started_at?->format('H:i') ?? '—' }}
                    @if($shift->ended_at) — {{ $shift->ended_at->format('H:i') }} @endif
                </div>
            </div>
            <div class="h-8 w-px bg-gray-100"></div>

            {{-- Currency Totals --}}
            @foreach([['YER','ر.ي','blue'], ['SAR','ر.س','green'], ['USD','$','purple']] as [$cur, $sym, $color])
            @if($shift->{"total_received_$cur"} > 0 || $shift->{"total_withdrawals_$cur"} > 0)
            <div class="text-center">
                <div class="text-xs text-gray-400 mb-0.5">{{ $cur }}</div>
                <div class="text-sm font-bold text-{{ $color }}-700">
                    {{ number_format($shift->{"total_received_$cur"}, 0) }}
                    <span class="text-xs font-normal text-gray-400">{{ $sym }}</span>
                </div>
            </div>
            @endif
            @endforeach

            {{-- Status Badge --}}
            <span class="px-2.5 py-1 rounded-full text-xs font-medium {{ $shift->is_closed ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700' }}">
                {{ $shift->is_closed ? 'مغلقة' : 'مفتوحة' }}
            </span>
        </div>

        <svg :class="open ? 'rotate-180' : ''" class="w-4 h-4 text-gray-400 flex-shrink-0 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
        </svg>
    </div>

    {{-- Shift Body --}}
    <div x-show="open" x-cloak>
        <div class="grid grid-cols-1 lg:grid-cols-2 divide-y lg:divide-y-0 lg:divide-x lg:divide-x-reverse divide-gray-100">

            {{-- Revenues by Room --}}
            <div class="p-5">
                <h4 class="font-semibold text-gray-700 text-sm mb-3 flex items-center gap-2">
                    <span class="w-6 h-6 rounded-lg bg-green-100 flex items-center justify-center">
                        <svg class="w-3.5 h-3.5 text-green-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                    </span>
                    الإيرادات المستلمة
                    <span class="text-xs text-gray-400 font-normal">({{ $payments->count() }} دفعة)</span>
                </h4>

                @if($payments->isEmpty())
                <p class="text-xs text-gray-400 text-center py-4">لا توجد دفعات في هذه الوردية</p>
                @else
                <div class="space-y-1.5">
                    @foreach($payments as $p)
                    <div class="flex items-center justify-between bg-gray-50 rounded-lg px-3 py-2">
                        <div class="flex items-center gap-2.5">
                            <span class="font-bold text-primary-800 text-xs w-14">
                                غرفة {{ $p->reservation?->display_room_number ?? '—' }}
                            </span>
                            <span class="text-gray-500 text-xs">
                                {{ $p->reservation?->guest?->full_name ?? '' }}
                            </span>
                            @if($p->type === 'compensation')
                            <span class="px-1.5 py-0.5 bg-red-100 text-red-700 rounded text-xs">تعويض</span>
                            @endif
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs px-1.5 py-0.5 rounded
                                {{ $p->method === 'cash' ? 'bg-green-100 text-green-700' : ($p->method === 'pos' ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700') }}">
                                {{ match($p->method) { 'cash' => 'نقدي', 'pos' => 'POS', 'bank_transfer' => 'تحويل', default => $p->method } }}
                            </span>
                            <span class="font-bold text-green-700 text-xs whitespace-nowrap">
                                {{ number_format($p->amount, 0) }}
                                <span class="font-normal text-gray-400">{{ $p->currency }}</span>
                            </span>
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>

            {{-- Withdrawals & Expenses --}}
            <div class="p-5 space-y-4">

                {{-- Expenses / Withdrawals --}}
                @if($expenses->isNotEmpty())
                <div>
                    <h4 class="font-semibold text-gray-700 text-sm mb-3 flex items-center gap-2">
                        <span class="w-6 h-6 rounded-lg bg-red-100 flex items-center justify-center">
                            <svg class="w-3.5 h-3.5 text-red-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        </span>
                        السحبيات والمصروفات
                    </h4>
                    <div class="space-y-1.5">
                        @foreach($expenses as $w)
                        <div class="flex items-center justify-between bg-red-50 rounded-lg px-3 py-2">
                            <div>
                                <span class="font-medium text-gray-700 text-xs">{{ $w->withdrawn_by_name ?? '—' }}</span>
                                @if($w->notes)
                                <span class="text-gray-400 text-xs mr-1">— {{ $w->notes }}</span>
                                @endif
                            </div>
                            <span class="font-bold text-red-700 text-xs whitespace-nowrap">
                                {{ number_format($w->amount, 0) }}
                                <span class="font-normal text-gray-400">{{ $w->currency }}</span>
                            </span>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- Currency Exchanges --}}
                @if($exchanges->isNotEmpty())
                <div>
                    <h4 class="font-semibold text-gray-700 text-sm mb-3 flex items-center gap-2">
                        <span class="w-6 h-6 rounded-lg bg-purple-100 flex items-center justify-center">
                            <svg class="w-3.5 h-3.5 text-purple-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                        </span>
                        صرف العملة
                    </h4>
                    <div class="space-y-1.5">
                        @foreach($exchanges as $w)
                        <div class="flex items-center justify-between bg-purple-50 rounded-lg px-3 py-2">
                            <span class="text-gray-700 text-xs">
                                {{ number_format($w->amount, 0) }} {{ $w->currency }}
                                @if($w->exchange_to_amount)
                                → {{ number_format($w->exchange_to_amount, 0) }} {{ $w->exchange_to_currency }}
                                @endif
                                @if($w->notes) — <span class="text-gray-400">{{ $w->notes }}</span> @endif
                            </span>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                @if($expenses->isEmpty() && $exchanges->isEmpty())
                <p class="text-xs text-gray-400 text-center py-4">لا توجد سحبيات أو صرف عملة</p>
                @endif
            </div>
        </div>

        {{-- Financial Summary Footer --}}
        <div class="px-5 py-4 bg-gray-50 border-t border-gray-100">
            <div class="grid grid-cols-3 gap-4 text-center">
                @foreach([['YER','ر.ي'], ['SAR','ر.س'], ['USD','$']] as [$cur, $sym])
                @if($shift->{"total_received_$cur"} > 0 || $shift->{"total_withdrawals_$cur"} > 0)
                <div class="bg-white rounded-xl border border-gray-100 p-3 shadow-sm">
                    <div class="text-xs text-gray-500 mb-2 font-medium">{{ $cur }}</div>
                    <div class="space-y-1 text-xs">
                        <div class="flex justify-between">
                            <span class="text-gray-400">إجمالي المستلم</span>
                            <span class="font-semibold text-green-700">
                                {{ number_format($shift->{"total_received_$cur"}, 0) }} {{ $sym }}
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-400">مجموع السحبيات</span>
                            <span class="font-semibold text-red-600">
                                {{ number_format($shift->{"total_withdrawals_$cur"}, 0) }} {{ $sym }}
                            </span>
                        </div>
                        <div class="flex justify-between border-t border-gray-100 pt-1 mt-1">
                            <span class="font-semibold text-gray-600">المتبقي</span>
                            @php $net = $shift->{"total_received_$cur"} - $shift->{"total_withdrawals_$cur"}; @endphp
                            <span class="font-bold {{ $net >= 0 ? 'text-green-700' : 'text-red-700' }}">
                                {{ number_format($net, 0) }} {{ $sym }}
                            </span>
                        </div>
                    </div>
                </div>
                @endif
                @endforeach
            </div>

            @if($shift->notes)
            <div class="mt-3 bg-yellow-50 border border-yellow-100 rounded-lg px-4 py-2 text-xs text-yellow-800">
                <strong>ملاحظات:</strong> {{ $shift->notes }}
            </div>
            @endif
        </div>
    </div>
</div>
@endforeach
</div>

{{-- Pagination --}}
@if($shifts->hasPages())
<div class="bg-white rounded-xl shadow-sm border border-gray-100 px-5 py-3">
    {{ $shifts->links() }}
</div>
@endif
@endif

</div>
@endsection
