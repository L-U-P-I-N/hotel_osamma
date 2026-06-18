@extends('layouts.app')
@section('title', 'تم تسجيل الخروج — الحجز #' . $reservation->id)
@section('page-title', 'تسجيل الخروج')

@section('content')
@php
    $inspection = $reservation->roomInspections->first();
    $lastPayments = $reservation->payments->sortByDesc('payment_date')->take(3);
    $totalPaid = $reservation->payments->sum('amount');
@endphp

<div class="max-w-3xl mx-auto space-y-5">

{{-- ===== SUCCESS BANNER ===== --}}
<div class="bg-white rounded-2xl shadow-sm border border-green-200 overflow-hidden">
    <div class="h-1.5 bg-gradient-to-l from-green-400 to-green-600"></div>
    <div class="p-6 flex items-center gap-5">
        <div class="w-16 h-16 rounded-2xl bg-green-100 flex items-center justify-center shrink-0">
            <svg class="w-9 h-9 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <div>
            <h2 class="text-xl font-bold text-gray-800">تم تسجيل الخروج بنجاح</h2>
            <p class="text-gray-500 text-sm mt-0.5">
                {{ $reservation->guest?->full_name }} — غرفة {{ $reservation->room?->room_number }}
                &nbsp;·&nbsp; {{ now()->format('d/m/Y H:i') }}
            </p>
        </div>
    </div>
</div>

{{-- ===== SUMMARY GRID ===== --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-4">
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 text-center">
        <p class="text-xs text-gray-400 mb-1">الغرفة</p>
        <p class="text-2xl font-bold text-primary-800">{{ $reservation->room?->room_number }}</p>
        <p class="text-xs text-gray-500">{{ $reservation->room?->roomType?->name }}</p>
    </div>
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 text-center">
        <p class="text-xs text-gray-400 mb-1">الليالي</p>
        <p class="text-2xl font-bold text-gray-800">{{ $reservation->nights }}</p>
        <p class="text-xs text-gray-500">{{ $reservation->check_in_date?->format('d/m') }} — {{ $reservation->check_out_date?->format('d/m') }}</p>
    </div>
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 text-center">
        <p class="text-xs text-gray-400 mb-1">الإجمالي</p>
        <p class="text-xl font-bold text-gray-800">{{ number_format($reservation->total_amount, 0) }}</p>
        <p class="text-xs text-gray-500">{{ $reservation->currency_symbol }}</p>
    </div>
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 text-center">
        <p class="text-xs text-gray-400 mb-1">الرصيد المتبقي</p>
        <p class="text-xl font-bold {{ $reservation->balance > 0 ? 'text-red-600' : 'text-green-600' }}">
            {{ number_format($reservation->balance, 0) }}
        </p>
        <p class="text-xs text-gray-500">{{ $reservation->currency_symbol }}</p>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-5">

    {{-- Financial Breakdown --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-50 flex items-center gap-2">
            <div class="w-7 h-7 rounded-lg bg-green-100 flex items-center justify-center">
                <svg class="w-3.5 h-3.5 text-green-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            </div>
            <h3 class="font-semibold text-gray-800 text-sm">الملخص المالي</h3>
        </div>
        <div class="p-5 space-y-3 text-sm">
            <div class="flex justify-between">
                <span class="text-gray-500">إجمالي الحجز</span>
                <span class="font-medium">{{ number_format($reservation->total_amount, 2) }} {{ $reservation->currency_symbol }}</span>
            </div>
            @foreach($reservation->payments->groupBy('type') as $type => $payments)
            <div class="flex justify-between">
                <span class="text-gray-500">
                    {{ match($type) { 'reservation'=>'مدفوعات الحجز', 'compensation'=>'تعويض أضرار', default=>$type } }}
                </span>
                <span class="font-medium text-green-600">
                    {{ number_format($payments->sum('amount'), 2) }} {{ $reservation->currency_symbol }}
                </span>
            </div>
            @endforeach
            <div class="border-t border-gray-100 pt-3 flex justify-between">
                <span class="font-semibold {{ $reservation->balance > 0 ? 'text-red-700' : 'text-green-700' }}">
                    {{ $reservation->balance > 0 ? 'رصيد معلق' : 'الحساب مسوَّى' }}
                </span>
                <span class="font-bold text-lg {{ $reservation->balance > 0 ? 'text-red-600' : 'text-green-600' }}">
                    {{ number_format(abs($reservation->balance), 2) }} {{ $reservation->currency_symbol }}
                </span>
            </div>
        </div>
    </div>

    {{-- Room Inspection --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-50 flex items-center gap-2">
            <div class="w-7 h-7 rounded-lg {{ $inspection?->has_damage ? 'bg-red-100' : 'bg-green-100' }} flex items-center justify-center">
                @if($inspection?->has_damage)
                <svg class="w-3.5 h-3.5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                @else
                <svg class="w-3.5 h-3.5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                @endif
            </div>
            <h3 class="font-semibold text-gray-800 text-sm">حالة الغرفة</h3>
        </div>
        <div class="p-5 text-sm">
            @if($inspection?->has_damage)
            <div class="space-y-3">
                <div class="flex items-center gap-2 text-red-700 font-medium">
                    <span class="w-2 h-2 rounded-full bg-red-500"></span>
                    تم تسجيل أضرار في الغرفة
                </div>
                @if($inspection->damage_description)
                <p class="text-gray-600 text-xs bg-red-50 rounded-xl p-3">{{ $inspection->damage_description }}</p>
                @endif
                @if($inspection->compensation_amount > 0)
                <div class="flex justify-between items-center bg-orange-50 rounded-xl p-3">
                    <span class="text-orange-800 text-xs font-medium">مبلغ التعويض</span>
                    <span class="font-bold text-orange-700">{{ number_format($inspection->compensation_amount, 0) }} ر.ي</span>
                </div>
                <div class="flex items-center gap-1.5 text-xs">
                    <span class="w-2 h-2 rounded-full {{ $inspection->compensation_status === 'paid' ? 'bg-green-500' : 'bg-amber-500' }}"></span>
                    <span class="{{ $inspection->compensation_status === 'paid' ? 'text-green-700' : 'text-amber-700' }}">
                        {{ $inspection->compensation_status === 'paid' ? 'التعويض مدفوع' : 'التعويض معلق' }}
                    </span>
                </div>
                <p class="text-xs text-blue-600 bg-blue-50 rounded-xl p-2 flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    تم تسجيل الأضرار تلقائياً في المصروفات
                </p>
                @endif
            </div>
            @else
            <div class="flex items-center gap-2 text-green-700 font-medium">
                <span class="w-2 h-2 rounded-full bg-green-500"></span>
                الغرفة بحالة جيدة — لا توجد أضرار
            </div>
            @endif
            <div class="mt-4 pt-3 border-t border-gray-100 text-xs text-gray-500 flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                الغرفة الآن في وضع <strong class="text-amber-600 mx-1">تحت الفحص</strong>
            </div>
        </div>
    </div>
</div>

{{-- Recent Payments --}}
@if($lastPayments->count() > 0)
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="px-5 py-4 border-b border-gray-50">
        <h3 class="font-semibold text-gray-800 text-sm">آخر المدفوعات</h3>
    </div>
    <div class="divide-y divide-gray-50">
        @foreach($lastPayments as $p)
        <div class="px-5 py-3 flex items-center justify-between text-sm">
            <div class="flex items-center gap-3">
                <span class="w-2 h-2 rounded-full bg-green-400"></span>
                <span class="text-gray-600">{{ $p->payment_date->format('d/m/Y H:i') }}</span>
                <span class="px-2 py-0.5 rounded-full text-xs
                    {{ $p->method === 'cash' ? 'bg-green-50 text-green-700' : ($p->method === 'pos' ? 'bg-blue-50 text-blue-700' : 'bg-purple-50 text-purple-700') }}">
                    {{ match($p->method) { 'cash'=>'نقدي', 'pos'=>'POS', 'bank_transfer'=>'تحويل', default=>$p->method } }}
                </span>
            </div>
            <span class="font-bold text-green-700">{{ number_format($p->amount, 2) }} {{ $p->currency }}</span>
        </div>
        @endforeach
    </div>
</div>
@endif

{{-- Actions --}}
<div class="flex flex-wrap items-center gap-3">
    <a href="{{ route('reservations.show', $reservation) }}"
       class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary-800 text-white rounded-xl text-sm font-semibold hover:bg-primary-700 transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
        عرض تفاصيل الحجز
    </a>
    <a href="{{ route('reservations.index') }}"
       class="inline-flex items-center gap-2 px-5 py-2.5 border border-gray-300 text-gray-600 rounded-xl text-sm hover:bg-gray-50 transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
        قائمة الحجوزات
    </a>
    @can('checkin.create')
    <a href="{{ route('checkin.create') }}"
       class="inline-flex items-center gap-2 px-5 py-2.5 border border-green-300 text-green-700 rounded-xl text-sm hover:bg-green-50 transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        تسجيل دخول جديد
    </a>
    @endcan
</div>

</div>
@endsection
