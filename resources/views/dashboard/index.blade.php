@extends('layouts.app')
@section('title', 'لوحة التحكم')
@section('page-title', 'لوحة التحكم')

@section('content')
@php
    $overdueCount = $expiringGuests->filter(fn($r) => $r->check_out_date->startOfDay()->lt(now()->startOfDay()))->count();
    $todayCount   = $expiringGuests->filter(fn($r) => $r->check_out_date->isToday())->count();
@endphp

{{-- ── شريط KPI الرئيسي ── --}}
<div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-4">

    @can('rooms.view')
    <a href="{{ route('rooms.index', ['status' => 'available']) }}"
       class="flex items-center gap-3 bg-white rounded-xl border border-gray-100 shadow-sm px-4 py-3 hover:border-green-300 transition">
        <div class="w-10 h-10 rounded-lg bg-green-100 flex items-center justify-center flex-shrink-0">
            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div>
            <p class="text-2xl font-bold text-green-700 leading-none">{{ $availableRooms }}</p>
            <p class="text-xs text-gray-500 mt-0.5">غرفة متاحة</p>
        </div>
    </a>
    @endcan

    @can('rooms.view')
    <a href="{{ route('rooms.index', ['status' => 'occupied']) }}"
       class="flex items-center gap-3 bg-white rounded-xl border border-gray-100 shadow-sm px-4 py-3 hover:border-red-300 transition">
        <div class="w-10 h-10 rounded-lg bg-red-100 flex items-center justify-center flex-shrink-0">
            <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
        </div>
        <div>
            <p class="text-2xl font-bold text-red-700 leading-none">{{ $occupiedRooms }}</p>
            <p class="text-xs text-gray-500 mt-0.5">غرفة مشغولة</p>
        </div>
    </a>
    @endcan

    <div class="flex items-center gap-3 bg-white rounded-xl border border-gray-100 shadow-sm px-4 py-3">
        <div class="w-10 h-10 rounded-lg bg-orange-100 flex items-center justify-center flex-shrink-0">
            <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
        </div>
        <div>
            <p class="text-2xl font-bold text-orange-700 leading-none">{{ $todayDepartures }}</p>
            <p class="text-xs text-gray-500 mt-0.5">مغادرة اليوم</p>
        </div>
    </div>

    @can('reports.view')
    <div class="flex items-center gap-3 bg-white rounded-xl border border-gray-100 shadow-sm px-4 py-3">
        <div class="w-10 h-10 rounded-lg bg-amber-100 flex items-center justify-center flex-shrink-0">
            <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div>
            <p class="text-lg font-bold text-amber-700 leading-none">{{ number_format($todayRevenue, 0) }}</p>
            <p class="text-xs text-gray-500 mt-0.5">إيراد اليوم · ر.ي</p>
        </div>
    </div>
    @endcan

</div>

{{-- ── KPIs المالية المتقدمة (للمدير/المالك) ── --}}
@can('reports.view')
<div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-4">

    {{-- الأرباح الصافية اليوم --}}
    <div class="bg-white rounded-xl border shadow-sm px-4 py-3 {{ $todayNetProfit >= 0 ? 'border-emerald-200' : 'border-red-200' }}">
        <div class="flex items-center gap-2 mb-1">
            <div class="w-8 h-8 rounded-lg {{ $todayNetProfit >= 0 ? 'bg-emerald-100' : 'bg-red-100' }} flex items-center justify-center flex-shrink-0">
                <svg class="w-4 h-4 {{ $todayNetProfit >= 0 ? 'text-emerald-600' : 'text-red-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
            </div>
            <p class="text-xs text-gray-500">صافي الربح اليوم</p>
        </div>
        <p class="text-xl font-bold {{ $todayNetProfit >= 0 ? 'text-emerald-700' : 'text-red-700' }}">
            {{ $todayNetProfit >= 0 ? '+' : '' }}{{ number_format($todayNetProfit, 0) }}
        </p>
        <p class="text-xs text-gray-400 mt-0.5">إيراد: {{ number_format($todayRevenue,0) }} — مصروف: {{ number_format($todayExpenses,0) }}</p>
    </div>

    {{-- معدل الإشغال --}}
    <div class="bg-white rounded-xl border border-blue-200 shadow-sm px-4 py-3">
        <div class="flex items-center gap-2 mb-1">
            <div class="w-8 h-8 rounded-lg bg-blue-100 flex items-center justify-center flex-shrink-0">
                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            </div>
            <p class="text-xs text-gray-500">معدل الإشغال</p>
        </div>
        <p class="text-xl font-bold text-blue-700">{{ $occupancyRate }}%</p>
        <div class="mt-1.5 h-1.5 bg-gray-100 rounded-full overflow-hidden">
            <div class="h-full rounded-full {{ $occupancyRate >= 70 ? 'bg-emerald-500' : ($occupancyRate >= 40 ? 'bg-amber-400' : 'bg-red-400') }}"
                 style="width:{{ $occupancyRate }}%"></div>
        </div>
        <p class="text-xs text-gray-400 mt-0.5">{{ $occupiedRooms }}/{{ $totalRooms }} غرفة</p>
    </div>

    {{-- متوسط سعر الليلة ADR --}}
    <div class="bg-white rounded-xl border border-purple-200 shadow-sm px-4 py-3">
        <div class="flex items-center gap-2 mb-1">
            <div class="w-8 h-8 rounded-lg bg-purple-100 flex items-center justify-center flex-shrink-0">
                <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
            </div>
            <p class="text-xs text-gray-500">متوسط سعر الليلة (ADR)</p>
        </div>
        <p class="text-xl font-bold text-purple-700">{{ number_format($adr, 0) }}</p>
        <p class="text-xs text-gray-400 mt-0.5">ر.ي / ليلة اليوم</p>
    </div>

    {{-- الديون المستحقة --}}
    @if($debtReservations > 0)
    <a href="{{ route('reports.agedDebts') }}"
       class="bg-white rounded-xl border border-rose-300 shadow-sm px-4 py-3 hover:bg-rose-50 transition">
        <div class="flex items-center gap-2 mb-1">
            <div class="w-8 h-8 rounded-lg bg-rose-100 flex items-center justify-center flex-shrink-0">
                <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </div>
            <p class="text-xs text-gray-500">ديون مستحقة</p>
        </div>
        <p class="text-xl font-bold text-rose-700">{{ number_format($totalOutstandingDebt, 0) }}</p>
        <p class="text-xs text-rose-500 mt-0.5 font-medium">{{ $debtReservations }} حجز · ر.ي</p>
    </a>
    @else
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm px-4 py-3">
        <div class="flex items-center gap-2 mb-1">
            <div class="w-8 h-8 rounded-lg bg-gray-100 flex items-center justify-center flex-shrink-0">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <p class="text-xs text-gray-500">ديون مستحقة</p>
        </div>
        <p class="text-xl font-bold text-gray-400">0</p>
        <p class="text-xs text-green-600 mt-0.5 font-medium">لا توجد ديون ✓</p>
    </div>
    @endif

</div>
@endcan

{{-- ── الأزرار السريعة ── --}}
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 mb-4">

    @can('checkin.create')
    <a href="{{ route('checkin.create') }}"
       class="flex items-center gap-3 rounded-xl p-4 text-white transition hover:opacity-90 shadow-sm"
       style="background:#0F4C75;">
        <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center flex-shrink-0">
            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
        </div>
        <div>
            <p class="font-bold text-sm">تسجيل دخول</p>
            <p class="text-xs text-white/70">نزيل موجود الآن</p>
        </div>
    </a>
    @endcan

    @can('checkin.create')
    <a href="{{ route('checkin.create', ['mode' => 'reserve']) }}"
       class="flex items-center gap-3 rounded-xl p-4 text-white transition hover:opacity-90 shadow-sm bg-blue-600">
        <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center flex-shrink-0">
            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
        </div>
        <div>
            <p class="font-bold text-sm">حجز مسبق</p>
            <p class="text-xs text-white/70">النزيل سيصل لاحقاً</p>
        </div>
    </a>
    @endcan

    @can('checkin.view')
    <a href="{{ route('reservations.expiring') }}"
       class="flex items-center gap-3 rounded-xl p-4 transition hover:opacity-90 shadow-sm
              {{ $overdueCount > 0 ? 'bg-red-600 text-white' : 'bg-orange-500 text-white' }}">
        <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center flex-shrink-0">
            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div>
            <p class="font-bold text-sm">النزلاء المسجلون</p>
            <p class="text-xs text-white/80">
                {{ $expiringGuests->count() }} نزيل
                @if($overdueCount > 0) — <span class="font-bold">{{ $overdueCount }} متأخر</span>@endif
            </p>
        </div>
    </a>
    @endcan

    @can('settlement.view')
    <a href="{{ route('settlement.index') }}"
       class="flex items-center gap-3 rounded-xl p-4 text-white transition hover:opacity-90 shadow-sm bg-emerald-600">
        <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center flex-shrink-0">
            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
        </div>
        <div>
            <p class="font-bold text-sm">التسوية النقدية</p>
            <p class="text-xs text-white/70">حساب الوردية</p>
        </div>
    </a>
    @endcan

</div>

{{-- ── الحجوزات القادمة + الرسم البياني ── --}}
@can('reports.view')
<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-4">

    {{-- الحجوزات القادمة (7 أيام) --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-semibold text-gray-800 text-sm">الوصولات القادمة (7 أيام)</h3>
            <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700">{{ $upcomingArrivals->count() }}</span>
        </div>
        @if($upcomingArrivals->isEmpty())
        <div class="py-8 text-center text-gray-400 text-sm">لا توجد حجوزات قادمة</div>
        @else
        <div class="divide-y divide-gray-50 max-h-60 overflow-y-auto">
            @foreach($upcomingArrivals as $res)
            <div class="px-5 py-2.5 flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-800">{{ $res->guest?->full_name ?? '—' }}</p>
                    <p class="text-xs text-gray-400">غرفة {{ $res->room?->room_number ?? '—' }}</p>
                </div>
                <div class="text-left">
                    <p class="text-xs font-semibold text-blue-600">{{ $res->check_in_date->format('d/m') }}</p>
                    @php $daysUntil = now()->diffInDays($res->check_in_date, false); @endphp
                    <p class="text-xs text-gray-400">
                        {{ $daysUntil == 0 ? 'اليوم' : ($daysUntil == 1 ? 'غداً' : 'بعد '.$daysUntil.' أيام') }}
                    </p>
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>

    {{-- رسم بياني آخر 7 أيام --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
        <h3 class="font-semibold text-gray-800 text-sm mb-3">الإيراد والمصروف (آخر 7 أيام)</h3>
        <canvas id="trendChart" height="120"></canvas>
    </div>

</div>
@endcan

{{-- ── جدول النزلاء المسجلين ── --}}
@can('checkin.view')
<div class="bg-white rounded-xl shadow-sm border border-gray-100">
    <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
        <div class="flex items-center gap-2">
            <h3 class="font-semibold text-gray-800">النزلاء المسجلون</h3>
            @if($expiringGuests->count() > 0)
            <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-primary-100 text-primary-700">{{ $expiringGuests->count() }}</span>
            @endif
            @if($overdueCount > 0)
            <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-700">{{ $overdueCount }} متأخر</span>
            @endif
        </div>
        <div class="flex items-center gap-3">
            @if($monthlyRevenue > 0)
            <span class="text-xs text-gray-400">الشهر: <strong class="text-gray-700">{{ number_format($monthlyRevenue, 0) }} ر.ي</strong></span>
            @endif
            @can('rooms.view')
            <a href="{{ route('rooms.index') }}" class="text-xs text-gray-400 hover:text-primary-600 transition">حالة الغرف</a>
            @endcan
        </div>
    </div>

    @if($expiringGuests->isEmpty())
    <div class="py-12 text-center text-gray-400">
        <svg class="w-10 h-10 mx-auto mb-3 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
        <p class="text-sm">لا يوجد نزلاء مسجلون حالياً</p>
        @can('checkin.create')
        <a href="{{ route('checkin.create') }}" class="inline-block mt-3 text-sm text-primary-600 hover:underline">+ تسجيل دخول نزيل جديد</a>
        @endcan
    </div>
    @else
    <div class="overflow-x-auto">
        <table class="w-full text-sm" dir="rtl">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">النزيل</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الغرفة</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الدخول</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الخروج</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">المدة</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الرصيد</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">إجراءات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($expiringGuests as $res)
                @php
                    $daysLeft = (int) now()->startOfDay()->diffInDays($res->check_out_date->copy()->startOfDay(), false);
                    if ($daysLeft < 0) {
                        $rowCls  = 'bg-red-50 hover:bg-red-100';
                        $badgeCls = 'bg-red-100 text-red-700';
                        $badgeTxt = 'متأخر ' . abs($daysLeft) . ' ' . (abs($daysLeft) === 1 ? 'يوم' : 'أيام');
                    } elseif ($daysLeft === 0) {
                        $rowCls  = 'bg-orange-50 hover:bg-orange-100';
                        $badgeCls = 'bg-orange-100 text-orange-700';
                        $badgeTxt = 'اليوم';
                    } elseif ($daysLeft === 1) {
                        $rowCls  = 'bg-yellow-50 hover:bg-yellow-100';
                        $badgeCls = 'bg-yellow-100 text-yellow-700';
                        $badgeTxt = 'غداً';
                    } else {
                        $rowCls  = 'hover:bg-gray-50';
                        $badgeCls = 'bg-gray-100 text-gray-600';
                        $badgeTxt = $daysLeft . ' أيام';
                    }
                    $balance = (float)$res->total_amount - (float)$res->paid_amount;
                @endphp
                <tr class="{{ $rowCls }} transition-colors">
                    <td class="px-4 py-3">
                        <a href="{{ route('reservations.show', $res) }}" class="font-semibold text-gray-800 hover:text-primary-700 transition">
                            {{ $res->guest?->full_name ?? '—' }}
                        </a>
                    </td>
                    <td class="px-4 py-3 font-medium text-gray-700">{{ $res->room?->room_number ?? '—' }}</td>
                    <td class="px-4 py-3 text-gray-500 text-xs">{{ $res->check_in_date->format('d/m/Y') }}</td>
                    <td class="px-4 py-3 text-gray-600 font-medium text-xs">{{ $res->check_out_date->format('d/m/Y') }}</td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-1 rounded-full text-xs font-semibold {{ $badgeCls }}">{{ $badgeTxt }}</span>
                    </td>
                    <td class="px-4 py-3">
                        @if($balance > 0)
                        <span class="text-xs font-bold text-rose-600">{{ number_format($balance, 0) }} ر.ي</span>
                        @else
                        <span class="text-xs text-green-600">مسوّى</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-1.5">
                            @can('checkout.process')
                            <a href="{{ route('checkout.show', $res) }}"
                               class="text-xs px-2.5 py-1.5 rounded-lg bg-red-600 text-white hover:bg-red-700 transition font-medium whitespace-nowrap">خروج</a>
                            @endcan
                            @can('checkin.create')
                            <a href="{{ route('reservations.show', $res) }}#renew"
                               class="text-xs px-2.5 py-1.5 rounded-lg bg-green-600 text-white hover:bg-green-700 transition font-medium whitespace-nowrap">تجديد</a>
                            @endcan
                            <a href="{{ route('reservations.show', $res) }}"
                               class="text-xs px-2.5 py-1.5 rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-50 transition whitespace-nowrap">تفاصيل</a>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>
@endcan

{{-- ── شريط حالة الغرف السفلي ── --}}
@can('rooms.view')
<div class="mt-4 flex flex-wrap gap-2">
    @php
        $pills = [
            ['label'=>'إجمالي', 'count'=>$totalRooms, 'color'=>'bg-gray-100 text-gray-700', 'url'=>route('rooms.index')],
            ['label'=>'متاحة',  'count'=>$roomStatusCounts['available']??0,  'color'=>'bg-green-100 text-green-700', 'url'=>route('rooms.index',['status'=>'available'])],
            ['label'=>'مشغولة', 'count'=>$roomStatusCounts['occupied']??0,   'color'=>'bg-red-100 text-red-700',   'url'=>route('rooms.index',['status'=>'occupied'])],
            ['label'=>'صيانة',  'count'=>$roomStatusCounts['maintenance']??0,'color'=>'bg-gray-200 text-gray-600', 'url'=>route('rooms.index',['status'=>'maintenance'])],
            ['label'=>'فحص',    'count'=>$roomStatusCounts['under_inspection']??0,'color'=>'bg-amber-100 text-amber-700','url'=>route('rooms.index',['status'=>'under_inspection'])],
        ];
    @endphp
    @foreach($pills as $pill)
    <a href="{{ $pill['url'] }}"
       class="flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium {{ $pill['color'] }} hover:opacity-80 transition">
        {{ $pill['label'] }}
        <span class="font-bold">{{ $pill['count'] }}</span>
    </a>
    @endforeach
</div>
@endcan

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
(function() {
    const ctx = document.getElementById('trendChart');
    if (!ctx) return;
    const labels   = @json($trendDays->pluck('label'));
    const revenue  = @json($trendDays->pluck('revenue'));
    const expenses = @json($trendDays->pluck('expense'));
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels,
            datasets: [
                { label: 'إيراد', data: revenue, backgroundColor: 'rgba(16,185,129,.7)', borderRadius: 4 },
                { label: 'مصروف', data: expenses, backgroundColor: 'rgba(239,68,68,.6)', borderRadius: 4 },
            ]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'bottom', labels: { font: { size: 11 } } } },
            scales: {
                y: { beginAtZero: true, ticks: { callback: v => v.toLocaleString('ar') } }
            }
        }
    });
})();
</script>
@endpush
