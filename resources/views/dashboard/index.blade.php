@extends('layouts.app')
@section('title', 'لوحة التحكم')
@section('page-title', 'لوحة التحكم')

@section('content')
@php
    $overdueCount        = $overdueGuests->count();
    $strictOverdueCount  = $overdueGuests->filter(fn($r) => $r->check_out_date->copy()->startOfDay()->lt(now()->startOfDay()))->count();
    $todayLeaveCount     = $overdueCount - $strictOverdueCount;
    $headerBg            = $strictOverdueCount > 0 ? 'bg-red-600'    : 'bg-orange-500';
    $headerBorder        = $strictOverdueCount > 0 ? 'border-red-300' : 'border-orange-300';
    $iconBg              = $strictOverdueCount > 0 ? 'bg-red-500'     : 'bg-orange-400';
    $subtitleColor       = $strictOverdueCount > 0 ? 'text-red-200'   : 'text-orange-100';
@endphp

{{-- ══════════════════════════════════════════════════════
     لوحة تحكم بصفحة واحدة — بدون تمرير للصفحة، التمرير الوحيد
     داخل كارد "النزلاء المسجلون"
     ══════════════════════════════════════════════════════ --}}
<div class="h-full flex flex-col gap-3 min-h-0">

{{-- ── تنبيه النزلاء المتأخرين (قائمة قابلة للتمرير داخلياً) ── --}}
@if($overdueCount > 0)
@canany(['checkin.view', 'checkout.process', 'checkin.create'])
<div class="flex-shrink-0" x-data="{ renewOpen: null }">
    <div class="rounded-xl overflow-hidden shadow border {{ $headerBorder }}">

        {{-- Header --}}
        <div class="flex items-center gap-3 px-5 py-2.5 {{ $headerBg }} text-white">
            <div class="w-7 h-7 rounded-full {{ $iconBg }} flex items-center justify-center flex-shrink-0 animate-pulse">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="flex-1 min-w-0">
                <p class="font-bold text-sm truncate">
                    @if($strictOverdueCount > 0 && $todayLeaveCount > 0)
                        {{ $strictOverdueCount }} {{ $strictOverdueCount === 1 ? 'متأخر' : 'متأخرون' }}
                        &nbsp;·&nbsp;
                        {{ $todayLeaveCount }} {{ $todayLeaveCount === 1 ? 'مغادرة اليوم' : 'مغادرات اليوم' }}
                    @elseif($strictOverdueCount > 0)
                        {{ $strictOverdueCount }} {{ $strictOverdueCount === 1 ? 'نزيل تجاوز' : 'نزلاء تجاوزوا' }} موعد المغادرة
                    @else
                        {{ $todayLeaveCount }} {{ $todayLeaveCount === 1 ? 'نزيل موعد مغادرته اليوم' : 'نزلاء موعد مغادرتهم اليوم' }}
                    @endif
                </p>
                <p class="text-xs {{ $subtitleColor }}">يرجى اختيار تجديد الإقامة أو تسجيل المغادرة لكل نزيل</p>
            </div>
            <a href="{{ route('reservations.expiring') }}" class="text-xs bg-white/20 hover:bg-white/30 px-3 py-1.5 rounded-lg transition font-medium whitespace-nowrap">
                عرض الكل
            </a>
        </div>

        {{-- Guest list (scrollable) --}}
        <div class="divide-y divide-red-100 bg-white max-h-44 overflow-y-auto slim-scroll">
            @foreach($overdueGuests as $res)
            @php $daysOver = (int) now()->startOfDay()->diffInDays($res->check_out_date->copy()->startOfDay(), false) * -1; @endphp
            <div>
                {{-- Guest row --}}
                <div class="px-5 py-2.5 flex items-center gap-3 flex-wrap sm:flex-nowrap">
                    <div class="w-9 h-9 rounded-full bg-red-100 flex items-center justify-center flex-shrink-0 font-bold text-red-700 text-sm">
                        {{ mb_substr($res->guest?->full_name ?? '؟', 0, 1) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-semibold text-gray-800 text-sm truncate">{{ $res->guest?->full_name ?? '—' }}</p>
                        <p class="text-xs text-gray-500">
                            غرفة {{ $res->display_room_number }}
                            &nbsp;•&nbsp; دخل: {{ $res->check_in_date->format('d/m/Y') }}
                            &nbsp;•&nbsp; انتهى: {{ $res->check_out_date->format('d/m/Y') }}
                        </p>
                    </div>
                    <span class="px-2.5 py-1 rounded-full text-xs font-bold flex-shrink-0 {{ $daysOver === 0 ? 'bg-orange-100 text-orange-700' : 'bg-red-100 text-red-700' }}">
                        {{ $daysOver === 0 ? 'موعد المغادرة اليوم' : 'متأخر '.$daysOver.' '.($daysOver === 1 ? 'يوم' : 'أيام') }}
                    </span>
                    <div class="flex items-center gap-2 flex-shrink-0">
                        @can('checkin.create')
                        <button
                            @click="renewOpen === {{ $res->id }} ? renewOpen = null : renewOpen = {{ $res->id }}"
                            :class="renewOpen === {{ $res->id }} ? 'bg-green-800' : 'bg-green-600 hover:bg-green-700'"
                            class="inline-flex items-center gap-1.5 text-xs px-3 py-1.5 rounded-lg text-white transition font-semibold whitespace-nowrap">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            تجديد الإقامة
                        </button>
                        @endcan
                        @can('checkout.process')
                        <a href="{{ route('checkout.show', $res) }}"
                           class="inline-flex items-center gap-1.5 text-xs px-3 py-1.5 rounded-lg bg-red-600 hover:bg-red-700 text-white transition font-semibold whitespace-nowrap">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7"/></svg>
                            تسجيل المغادرة
                        </a>
                        @endcan
                        <a href="{{ route('reservations.show', $res) }}"
                           class="inline-flex items-center text-xs px-2.5 py-1.5 rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-50 transition whitespace-nowrap">
                            تفاصيل
                        </a>
                    </div>
                </div>

                {{-- Inline renew form --}}
                @can('checkin.create')
                <div x-show="renewOpen === {{ $res->id }}" x-cloak
                     class="px-5 pb-4 pt-3 bg-green-50 border-t border-green-100">
                    <p class="text-xs font-semibold text-green-800 mb-3">تجديد إقامة: {{ $res->guest?->full_name }}</p>
                    <form method="POST" action="{{ route('reservations.renew', $res) }}"
                          class="flex items-end gap-3 flex-wrap">
                        @csrf
                        <div class="flex flex-col gap-1">
                            <label class="text-xs font-medium text-gray-600">تاريخ الخروج الجديد *</label>
                            <input type="date" name="new_check_out_date" required
                                   min="{{ now()->addDay()->toDateString() }}"
                                   value="{{ now()->addDay()->toDateString() }}"
                                   class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-green-400 outline-none bg-white">
                        </div>
                        <div class="flex flex-col gap-1">
                            <label class="text-xs font-medium text-gray-600">دفعة مقدمة (ر.ي)</label>
                            <input type="number" name="advance_payment" min="0" step="0.01" placeholder="0"
                                   class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm w-32 focus:ring-2 focus:ring-green-400 outline-none bg-white">
                        </div>
                        <div class="flex flex-col gap-1">
                            <label class="text-xs font-medium text-gray-600">طريقة الدفع</label>
                            <select name="payment_method"
                                    class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-green-400 outline-none bg-white">
                                <option value="cash">نقداً</option>
                                <option value="pos">POS</option>
                                <option value="bank_transfer">تحويل بنكي</option>
                            </select>
                        </div>
                        <div class="flex flex-col gap-1">
                            <label class="text-xs font-medium text-gray-600">ملاحظات</label>
                            <input type="text" name="notes" placeholder="سبب التجديد..."
                                   class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm w-40 focus:ring-2 focus:ring-green-400 outline-none bg-white">
                        </div>
                        <div class="flex gap-2">
                            <button type="submit"
                                    class="px-4 py-1.5 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm font-semibold transition">
                                تأكيد التجديد
                            </button>
                            <button type="button" @click="renewOpen = null"
                                    class="px-4 py-1.5 border border-gray-300 text-gray-600 rounded-lg text-sm hover:bg-gray-50 transition">
                                إلغاء
                            </button>
                        </div>
                    </form>
                </div>
                @endcan
            </div>
            @endforeach
        </div>
    </div>
</div>
@endcanany
@endif

{{-- ── التنبيهات المهمة (مضغوطة في سطر واحد) ── --}}
@if(!empty($alerts))
<div class="flex-shrink-0 flex flex-wrap gap-2">
    @foreach($alerts as $alert)
    <div class="flex items-center gap-2 px-3 py-2 rounded-lg border-r-4 {{
        $alert['type'] === 'danger' ? 'bg-red-50 border-red-400 text-red-900' :
        ($alert['type'] === 'warning' ? 'bg-amber-50 border-amber-400 text-amber-900' :
        'bg-blue-50 border-blue-400 text-blue-900')
    }}">
        <span class="text-lg flex-shrink-0">{{ $alert['icon'] }}</span>
        <div class="min-w-0">
            <p class="font-semibold text-xs leading-tight">{{ $alert['title'] }}</p>
            <p class="text-xs opacity-90 leading-tight">{{ $alert['message'] }}</p>
        </div>
    </div>
    @endforeach
</div>
@endif

{{-- ── شريط الأرقام السريعة (مضغوط) ── --}}
<div class="flex-shrink-0 grid grid-cols-2 sm:grid-cols-4 gap-2">

    @can('rooms.view')
    <a href="{{ route('rooms.index', ['status' => 'available']) }}"
       class="flex items-center gap-3 bg-white rounded-xl border border-gray-100 shadow-sm px-3 py-2.5 hover:border-green-300 transition">
        <div class="w-9 h-9 rounded-lg bg-green-100 flex items-center justify-center flex-shrink-0">
            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div>
            <p class="text-xl font-bold text-green-700 leading-none">{{ $availableRooms }}</p>
            <p class="text-xs text-gray-500 mt-0.5">غرفة متاحة</p>
        </div>
    </a>
    @endcan

    @can('rooms.view')
    <a href="{{ route('rooms.index', ['status' => 'occupied']) }}"
       class="flex items-center gap-3 bg-white rounded-xl border border-gray-100 shadow-sm px-3 py-2.5 hover:border-red-300 transition">
        <div class="w-9 h-9 rounded-lg bg-red-100 flex items-center justify-center flex-shrink-0">
            <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
        </div>
        <div>
            <p class="text-xl font-bold text-red-700 leading-none">{{ $occupiedRooms }}</p>
            <p class="text-xs text-gray-500 mt-0.5">غرفة مشغولة</p>
        </div>
    </a>
    @endcan

    <div class="flex items-center gap-3 bg-white rounded-xl border border-gray-100 shadow-sm px-3 py-2.5">
        <div class="w-9 h-9 rounded-lg bg-orange-100 flex items-center justify-center flex-shrink-0">
            <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
        </div>
        <div>
            <p class="text-xl font-bold text-orange-700 leading-none">{{ $todayDepartures }}</p>
            <p class="text-xs text-gray-500 mt-0.5">مغادرة اليوم</p>
        </div>
    </div>

    @can('reports.view')
    <div class="flex items-center gap-3 bg-white rounded-xl border border-gray-100 shadow-sm px-3 py-2.5">
        <div class="w-9 h-9 rounded-lg bg-amber-100 flex items-center justify-center flex-shrink-0">
            <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div>
            <p class="text-base font-bold text-amber-700 leading-none">{{ number_format($todayRevenue, 0) }}</p>
            <p class="text-xs text-gray-500 mt-0.5">إيراد اليوم · ر.ي</p>
        </div>
    </div>
    @endcan

</div>

{{-- ══════════════════════════════════════════════════════
     المنطقة الرئيسية: تملأ بقية الارتفاع
     يسار: كارد النزلاء (تمرير داخلي) · يمين: إجراءات + مؤشرات + مخطط
     ══════════════════════════════════════════════════════ --}}
<div class="flex-1 grid grid-cols-1 lg:grid-cols-3 gap-3 min-h-0">

    {{-- ── كارد النزلاء المسجلون (التمرير الوحيد) ── --}}
    @can('checkin.view')
    <div class="lg:col-span-2 flex flex-col min-h-0 bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between flex-shrink-0">
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
        <div class="flex-1 flex flex-col items-center justify-center text-center text-gray-400 min-h-0">
            <svg class="w-10 h-10 mx-auto mb-3 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            <p class="text-sm">لا يوجد نزلاء مسجلون حالياً</p>
            @can('checkin.create')
            <a href="{{ route('checkin.create') }}" class="inline-block mt-3 text-sm text-primary-600 hover:underline">+ تسجيل دخول نزيل جديد</a>
            @endcan
        </div>
        @else
        <div class="flex-1 overflow-y-auto min-h-0 slim-scroll">
            <table class="w-full text-sm" dir="rtl">
                <thead class="bg-gray-50 sticky top-0 z-10">
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
                        <td class="px-4 py-2.5">
                            <a href="{{ route('reservations.show', $res) }}" class="font-semibold text-gray-800 hover:text-primary-700 transition">
                                {{ $res->guest?->full_name ?? '—' }}
                            </a>
                        </td>
                        <td class="px-4 py-2.5 font-medium text-gray-700">{{ $res->display_room_number }}</td>
                        <td class="px-4 py-2.5 text-gray-500 text-xs">{{ $res->check_in_date->format('d/m/Y') }}</td>
                        <td class="px-4 py-2.5 text-gray-600 font-medium text-xs">{{ $res->check_out_date->format('d/m/Y') }}</td>
                        <td class="px-4 py-2.5">
                            <span class="px-2 py-1 rounded-full text-xs font-semibold {{ $badgeCls }}">{{ $badgeTxt }}</span>
                        </td>
                        <td class="px-4 py-2.5">
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

    {{-- ── العمود الجانبي: إجراءات + مؤشرات + مخطط + غرف ── --}}
    <div class="flex flex-col gap-3 min-h-0">

        {{-- الأزرار السريعة --}}
        <div class="flex-shrink-0 space-y-2">
            @can('checkin.create')
            <a href="{{ route('checkin.create') }}"
               class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-white transition hover:opacity-90 shadow-sm"
               style="background:#0F4C75;">
                <div class="w-8 h-8 bg-white/20 rounded-lg flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
                </div>
                <div>
                    <p class="font-bold text-sm">تسجيل دخول</p>
                    <p class="text-xs text-white/70">نزيل موجود الآن</p>
                </div>
            </a>
            @endcan

            <div class="grid grid-cols-2 gap-2">
                @can('checkin.view')
                <a href="{{ route('reservations.expiring') }}"
                   class="flex items-center gap-2 rounded-xl px-3 py-2.5 transition hover:opacity-90 shadow-sm
                          {{ $overdueCount > 0 ? 'bg-red-600 text-white' : 'bg-orange-500 text-white' }}">
                    <div class="w-8 h-8 bg-white/20 rounded-lg flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div class="min-w-0">
                        <p class="font-bold text-xs leading-tight">النزلاء المسجلون</p>
                        <p class="text-xs text-white/80 leading-tight">
                            {{ $expiringGuests->count() }} نزيل@if($overdueCount > 0) · <span class="font-bold">{{ $overdueCount }} متأخر</span>@endif
                        </p>
                    </div>
                </a>
                @endcan

                @can('settlement.view')
                <a href="{{ route('settlement.index') }}"
                   class="flex items-center gap-2 rounded-xl px-3 py-2.5 text-white transition hover:opacity-90 shadow-sm bg-emerald-600">
                    <div class="w-8 h-8 bg-white/20 rounded-lg flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    </div>
                    <div class="min-w-0">
                        <p class="font-bold text-xs leading-tight">التسوية النقدية</p>
                        <p class="text-xs text-white/70 leading-tight">حساب الوردية</p>
                    </div>
                </a>
                @endcan
            </div>
        </div>

        {{-- مؤشرات مضغوطة: الإشغال + المصروفات --}}
        <div class="flex-shrink-0 grid grid-cols-2 gap-2">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-3">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-xs text-gray-500">معدل الإشغال</p>
                    <span class="text-lg">📊</span>
                </div>
                <p class="text-2xl font-bold text-primary-600 leading-none mb-2">{{ $occupancyRateToday }}%</p>
                <div class="w-full bg-gray-100 rounded-full h-1.5">
                    <div class="bg-primary-600 h-1.5 rounded-full transition-all duration-300" style="width: {{ $occupancyRateToday }}%"></div>
                </div>
                <p class="text-xs text-gray-400 mt-1.5">{{ $occupiedRooms }} من {{ $totalRooms }} غرفة</p>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-3">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-xs text-gray-500">مصروفات اليوم</p>
                    <span class="text-lg">💸</span>
                </div>
                <p class="text-xl font-bold {{ $todayExpenses > $todayRevenue ? 'text-red-600' : 'text-gray-700' }} leading-none mb-2">{{ number_format($todayExpenses, 0) }} <span class="text-xs font-normal">ر.ي</span></p>
                @if($todayRevenue > 0)
                <div class="flex items-center justify-between text-xs pt-1.5 border-t border-gray-100">
                    <span class="text-gray-500">الإيراد: {{ number_format($todayRevenue, 0) }}</span>
                    <span class="font-semibold {{ $todayExpenses > $todayRevenue ? 'text-red-600' : 'text-green-600' }}">
                        {{ $todayExpenses > $todayRevenue ? '−' : '+' }}{{ number_format(abs($todayRevenue - $todayExpenses), 0) }}
                    </span>
                </div>
                @endif
            </div>
        </div>

        {{-- مخطط إيرادات الأسبوع (يملأ المساحة المتبقية) --}}
        @if($weeklyRevenue)
        <div class="flex-1 min-h-0 bg-white rounded-xl shadow-sm border border-gray-100 p-3 flex flex-col">
            <div class="flex items-center justify-between mb-2 flex-shrink-0">
                <div>
                    <h3 class="font-semibold text-gray-800 text-sm">إيرادات الأسبوع</h3>
                    <p class="text-xs text-gray-400">آخر 7 أيام</p>
                </div>
                <span class="text-lg">📈</span>
            </div>
            <div class="flex-1 min-h-0 relative">
                <canvas id="weeklyRevenueChart"></canvas>
            </div>
        </div>
        @endif

        {{-- شريط حالة الغرف --}}
        @can('rooms.view')
        <div class="flex-shrink-0 flex flex-wrap gap-2">
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

    </div>
</div>
</div>

{{-- ── مخطط إيرادات الأسبوع (script) ── --}}
@if($weeklyRevenue)
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('weeklyRevenueChart');
        if (ctx) {
            const weeklyData = @json($weeklyRevenue);
            const dates = weeklyData.map(d => d.date);
            const revenues = weeklyData.map(d => d.revenue);

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: dates,
                    datasets: [{
                        label: 'الإيراد اليومي (ر.ي)',
                        data: revenues,
                        borderColor: '#0F4C75',
                        backgroundColor: 'rgba(15, 76, 117, 0.1)',
                        tension: 0.4,
                        fill: true,
                        pointRadius: 4,
                        pointBackgroundColor: '#0F4C75',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointHoverRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                maxTicksLimit: 5,
                                callback: function(value) {
                                    return value.toLocaleString('ar-SA');
                                }
                            }
                        }
                    }
                }
            });
        }
    });
</script>
@endif

@endsection
