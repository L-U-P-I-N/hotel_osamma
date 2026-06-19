@extends('layouts.app')
@section('title', 'لوحة التحكم')
@section('page-title', 'لوحة التحكم')

@section('content')
@php
    $overdueCount = $expiringGuests->filter(fn($r) => $r->check_out_date->startOfDay()->lt(now()->startOfDay()))->count();
    $todayCount   = $expiringGuests->filter(fn($r) => $r->check_out_date->isToday())->count();
@endphp

{{-- ── شريط الأرقام السريعة ── --}}
<div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-5">

    @can('rooms.view')
    <a href="{{ route('rooms.index', ['status' => 'available']) }}"
       class="flex items-center gap-3 bg-white rounded-xl border border-gray-100 shadow-sm px-4 py-3 hover:border-green-300 transition group">
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
       class="flex items-center gap-3 bg-white rounded-xl border border-gray-100 shadow-sm px-4 py-3 hover:border-red-300 transition group">
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

{{-- ── الأزرار السريعة ── --}}
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 mb-5">

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

{{-- ── جدول النزلاء المسجلين (الأقرب للمغادرة) ── --}}
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
            @if($todayRevenue > 0)
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

{{-- ── شريط حالة الغرف السفلي (للمدير) ── --}}
@can('rooms.view')
<div class="mt-4 flex flex-wrap gap-2">
    @php
        $pills = [
            ['label'=>'إجمالي', 'count'=>$totalRooms, 'color'=>'bg-gray-100 text-gray-700', 'url'=>route('rooms.index')],
            ['label'=>'متاحة',  'count'=>$roomStatusCounts['available']??0,  'color'=>'bg-green-100 text-green-700', 'url'=>route('rooms.index',['status'=>'available'])],
            ['label'=>'مشغولة', 'count'=>$roomStatusCounts['occupied']??0,   'color'=>'bg-red-100 text-red-700',   'url'=>route('rooms.index',['status'=>'occupied'])],
            ['label'=>'محجوزة', 'count'=>$roomStatusCounts['reserved']??0,   'color'=>'bg-blue-100 text-blue-700', 'url'=>route('rooms.index',['status'=>'reserved'])],
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
