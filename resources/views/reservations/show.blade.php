@extends('layouts.app')
@section('title', 'تفاصيل الحجز #' . $reservation->id)
@section('page-title', 'تفاصيل الحجز')
@section('back-url', route('reservations.expiring'))

@push('styles')
<style>
.hero-gradient { background: linear-gradient(135deg, #0B3D63 0%, #0F4C75 60%, #1565a0 100%); }
.stat-card { transition: transform 0.2s ease, box-shadow 0.2s ease; }
.stat-card:hover { transform: translateY(-3px); box-shadow: 0 12px 30px rgba(0,0,0,0.1); }
.timeline-dot { width: 12px; height: 12px; border-radius: 50%; border: 2px solid white; }
.timeline-line { width: 2px; background: linear-gradient(to bottom, #10B981, #EF4444); }
.payment-row:hover td { background: #f8fafc !important; }
.paid-bar { background: linear-gradient(to right, #059669, #10B981); transition: width 0.6s ease; }
.badge-pill { display: inline-flex; align-items: center; gap: 4px; padding: 4px 12px; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; }
</style>
@endpush

@section('content')
@php
    $statusConfig = [
        'confirmed'   => ['label' => 'محجوز',       'color' => '#D97706', 'bg' => '#FEF3C7', 'border' => '#FDE68A', 'dot' => 'bg-amber-400',  'ring' => 'ring-amber-200'],
        'checked_in'  => ['label' => 'مسجل دخول',  'color' => '#059669', 'bg' => '#D1FAE5', 'border' => '#6EE7B7', 'dot' => 'bg-emerald-400','ring' => 'ring-emerald-200'],
        'checked_out' => ['label' => 'مسجل خروج',  'color' => '#6B7280', 'bg' => '#F3F4F6', 'border' => '#D1D5DB', 'dot' => 'bg-gray-400',   'ring' => 'ring-gray-200'],
        'cancelled'   => ['label' => 'ملغى',        'color' => '#DC2626', 'bg' => '#FEE2E2', 'border' => '#FECACA', 'dot' => 'bg-red-400',    'ring' => 'ring-red-200'],
    ];
    $sc = $statusConfig[$reservation->status] ?? $statusConfig['checked_in'];

    $paymentConfig = [
        'unpaid'   => ['label' => 'غير مدفوع',    'color' => '#DC2626', 'bg' => '#FEE2E2'],
        'partial'  => ['label' => 'مدفوع جزئياً', 'color' => '#D97706', 'bg' => '#FEF3C7'],
        'paid'     => ['label' => 'مكتمل الدفع',  'color' => '#059669', 'bg' => '#D1FAE5'],
        'deferred' => ['label' => 'مؤجل',          'color' => '#7C3AED', 'bg' => '#EDE9FE'],
    ];
    $pc = $paymentConfig[$reservation->payment_status] ?? $paymentConfig['unpaid'];

    $pricePerNight = $reservation->nights > 0 ? round($reservation->total_amount / $reservation->nights, 2) : 0;
    // سعر مختلف لليلة الأولى (إن وُجد): نُظهر تفصيلاً واضحاً بدل متوسط الليلة.
    $hasFirstNight = $reservation->first_night_price !== null && $reservation->nights > 1;
    $otherNightPrice = $hasFirstNight
        ? round(($reservation->total_amount - (float) $reservation->first_night_price) / ($reservation->nights - 1), 2)
        : $pricePerNight;
    // سعر ليلة التجديد الافتراضي (قد يختلف عن متوسط سعر الليلة أعلاه) — يُعبّأ
    // مسبقاً في نافذة التجديد ويظل قابلاً للتعديل من الموظف.
    $renewalPrice  = $reservation->effective_renewal_price_per_night;
    $paidPercent   = $reservation->total_amount > 0
        ? min(100, round(($reservation->paid_amount / $reservation->total_amount) * 100))
        : 100;

    $daysLabel = '';
    $daysClass = '';
    if ($reservation->status === 'checked_in' && $reservation->check_out_date) {
        // نحسب الفرق بالأيام الكاملة (تقويمياً) بمقارنة بداية اليوم لتفادي الكسور العشرية
        $diff = (int) round(now()->startOfDay()->diffInDays($reservation->check_out_date->copy()->startOfDay(), false));
        if ($diff < 0)       { $daysLabel = 'تجاوز الموعد بـ ' . abs($diff) . ' يوم'; $daysClass = 'bg-red-600 text-white'; }
        elseif ($diff === 0) { $daysLabel = 'الخروج اليوم'; $daysClass = 'bg-amber-500 text-white'; }
        else                 { $daysLabel = 'يتبقى ' . $diff . ' يوم'; $daysClass = 'bg-emerald-500 text-white'; }
    }
@endphp

<div class="max-w-6xl mx-auto space-y-5" dir="rtl">

{{-- ===== HERO HEADER ===== --}}
<div class="hero-gradient rounded-2xl shadow-lg overflow-hidden">
    <div class="p-6 md:p-8">
        <div class="flex flex-wrap items-start justify-between gap-5">

            {{-- Left: ID + Guest + Room --}}
            <div class="flex flex-col gap-3">
                <div class="flex items-center gap-3 flex-wrap">
                    <span class="text-white/50 text-sm font-mono tracking-widest">#{{ str_pad($reservation->id, 5, '0', STR_PAD_LEFT) }}</span>
                    <span class="badge-pill" style="background:{{ $sc['bg'] }}; color:{{ $sc['color'] }};">
                        <span class="w-1.5 h-1.5 rounded-full inline-block" style="background:{{ $sc['color'] }};"></span>
                        {{ $sc['label'] }}
                    </span>
                    <span class="badge-pill" style="background:{{ $pc['bg'] }}; color:{{ $pc['color'] }};">
                        {{ $pc['label'] }}
                    </span>
                    @if($daysLabel)
                    <span class="badge-pill {{ $daysClass }}">{{ $daysLabel }}</span>
                    @endif
                </div>

                <div>
                    <h1 class="text-2xl md:text-3xl font-bold text-white leading-tight">
                        {{ $reservation->guest?->full_name ?? 'نزيل غير محدد' }}
                    </h1>
                    <div class="flex items-center gap-4 mt-2 text-white/70 text-sm">
                        <span class="flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                            غرفة {{ $reservation->display_room_number }}
                            @if($reservation->suite_booking_type === 'both') <span class="text-amber-300">(A+B)</span> @endif
                            &mdash; {{ $reservation->room_type_label }}
                        </span>
                        <span class="flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                            {{ $reservation->nights }} ليلة
                        </span>
                    </div>
                </div>
            </div>

            {{-- Right: Action Buttons --}}
            <div class="flex items-center gap-2 flex-wrap">
                @can('checkout.process')
                @if($reservation->status === 'checked_in')
                <a href="{{ route('checkout.show', $reservation) }}"
                   class="inline-flex items-center gap-2 px-5 py-2.5 bg-red-500 hover:bg-red-600 text-white text-sm font-semibold rounded-xl transition shadow-md shadow-red-900/30">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    تسجيل الخروج
                </a>
                @endif
                @endcan

                @can('checkin.view')
                @if(in_array($reservation->status, ['confirmed', 'checked_in']))
                <a href="{{ route('reservations.edit', $reservation) }}"
                   class="inline-flex items-center gap-2 px-4 py-2.5 bg-white/15 hover:bg-white/25 text-white text-sm font-medium rounded-xl transition border border-white/20">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                    تعديل
                </a>
                @endif

                @if($reservation->status === 'checked_in')
                <button type="button" onclick="event.stopPropagation(); document.getElementById('renewModal').classList.remove('hidden')"
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-white/15 hover:bg-white/25 text-white text-sm font-medium rounded-xl transition border border-white/20">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    تجديد
                </button>
                <button onclick="document.getElementById('checkinDateModal').classList.remove('hidden')"
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-white/15 hover:bg-white/25 text-white text-sm font-medium rounded-xl transition border border-white/20">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    تعديل تاريخ الوصول
                </button>
                <button onclick="document.getElementById('transferRoomModal').classList.remove('hidden')"
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-amber-500/80 hover:bg-amber-500 text-white text-sm font-medium rounded-xl transition border border-amber-400/30">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                    تغيير الغرفة
                </button>
                @can('checkin.create')
                {{-- تفعيل/إيقاف التجديد التلقائي --}}
                <form method="POST" action="{{ route('reservations.toggleAutoRenew', $reservation) }}" class="inline">
                    @csrf @method('PATCH')
                    <button type="submit"
                            onclick="return confirm(@js($reservation->auto_renew ? 'إيقاف التجديد التلقائي لهذه الإقامة؟' : 'تفعيل التجديد التلقائي؟ سيُحتسب يوم جديد تلقائياً بعد كل ساعة 1 ظهراً ويُضاف للدين.'))"
                            class="inline-flex items-center gap-2 px-4 py-2.5 text-white text-sm font-medium rounded-xl transition border {{ $reservation->auto_renew ? 'bg-emerald-600 hover:bg-emerald-700 border-emerald-400/40' : 'bg-white/15 hover:bg-white/25 border-white/20' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        {{ $reservation->auto_renew ? 'التجديد التلقائي: مُفعّل' : 'تجديد تلقائي' }}
                    </button>
                </form>
                @endcan
                @endif
                @endcan

                @can('checkin.create')
                @if(in_array($reservation->status, ['confirmed', 'checked_in', 'checked_out']) && $reservation->total_amount > 0)
                <button type="button" onclick="document.getElementById('discountModal').classList.remove('hidden')"
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-white/15 hover:bg-white/25 text-white text-sm font-medium rounded-xl transition border border-white/20">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>
                    خصم
                </button>
                @endif
                @endcan

                @can('payments.create')
                @if($reservation->balance > 0 && in_array($reservation->status, ['checked_in', 'checked_out']))
                <button onclick="document.getElementById('paymentModal').classList.remove('hidden')"
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-emerald-500/90 hover:bg-emerald-500 text-white text-sm font-semibold rounded-xl transition border border-emerald-400/30">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                    دفعة
                </button>
                @endif
                @endcan

                @can('checkin.view')
                <a href="{{ route('reservations.invoice', $reservation) }}" target="_blank"
                   class="inline-flex items-center gap-2 px-4 py-2.5 bg-white/15 hover:bg-white/25 text-white text-sm font-medium rounded-xl transition border border-white/20">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    الفاتورة
                </a>
                @endcan

                @can('government.export')
                <a href="{{ route('checkin.exportGov', ['reservation'=>$reservation->id,'format'=>'pdf']) }}"
                   class="inline-flex items-center gap-2 px-4 py-2.5 bg-white/15 hover:bg-white/25 text-white text-sm font-medium rounded-xl transition border border-white/20">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    PDF
                </a>
                @endcan

                @can('checkin.view')
                @if(in_array($reservation->status, ['confirmed', 'checked_in']))
                <button onclick="document.getElementById('cancelReservationModal').classList.remove('hidden')"
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-red-700/50 hover:bg-red-700/80 text-white text-sm font-medium rounded-xl transition border border-red-500/30">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    إلغاء
                </button>
                @endif
                @endcan
            </div>
        </div>
    </div>
</div>

{{-- ===== STATS RIBBON ===== --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-4">
    {{-- Room --}}
    <div class="stat-card bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
        <div class="flex items-center justify-between mb-3">
            <span class="text-xs text-gray-400 font-medium">الغرفة</span>
            <div class="w-8 h-8 rounded-lg bg-blue-50 flex items-center justify-center">
                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
            </div>
        </div>
        <p class="text-3xl font-black text-gray-900">{{ $reservation->display_room_number }}</p>
        <p class="text-xs text-gray-400 mt-1">{{ $reservation->room_type_label }}</p>
    </div>

    {{-- Nights --}}
    <div class="stat-card bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
        <div class="flex items-center justify-between mb-3">
            <span class="text-xs text-gray-400 font-medium">عدد الليالي</span>
            <div class="w-8 h-8 rounded-lg bg-indigo-50 flex items-center justify-center">
                <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
            </div>
        </div>
        <p class="text-3xl font-black text-gray-900">{{ $reservation->nights }}</p>
        <p class="text-xs text-gray-400 mt-1">{{ number_format($pricePerNight, 0) }} {{ $reservation->currency_symbol }}/ليلة</p>
    </div>

    {{-- Total --}}
    <div class="stat-card bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
        <div class="flex items-center justify-between mb-3">
            <span class="text-xs text-gray-400 font-medium">إجمالي الحجز</span>
            <div class="w-8 h-8 rounded-lg bg-amber-50 flex items-center justify-center">
                <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
        </div>
        <p class="text-3xl font-black text-gray-900">{{ number_format($reservation->total_amount, 0) }}</p>
        <p class="text-xs text-gray-400 mt-1">{{ $reservation->currency_symbol }}</p>
    </div>

    {{-- Balance --}}
    <div class="stat-card bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
        <div class="flex items-center justify-between mb-3">
            <span class="text-xs text-gray-400 font-medium">{{ $reservation->balance > 0 ? 'المتبقي' : 'مكتمل' }}</span>
            <div class="w-8 h-8 rounded-lg {{ $reservation->balance > 0 ? 'bg-red-50' : 'bg-emerald-50' }} flex items-center justify-center">
                @if($reservation->balance > 0)
                <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                @else
                <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                @endif
            </div>
        </div>
        <p class="text-3xl font-black {{ $reservation->balance > 0 ? 'text-red-600' : 'text-emerald-600' }}">
            {{ number_format(abs($reservation->balance), 0) }}
        </p>
        <p class="text-xs text-gray-400 mt-1">{{ $reservation->currency_symbol }}</p>
    </div>
</div>

{{-- ===== DATE TIMELINE (horizontal, full width) ===== --}}
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-4">
        {{-- Check-in --}}
        <div class="flex items-center gap-3 flex-1 min-w-0">
            <div class="w-11 h-11 rounded-xl bg-emerald-100 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
            </div>
            <div class="min-w-0">
                <p class="text-xs text-gray-400 font-semibold uppercase tracking-wide">تاريخ الدخول</p>
                <p class="font-bold text-gray-900 text-lg leading-tight">{{ $reservation->check_in_date?->format('d/m/Y') ?? '—' }}</p>
                @if($reservation->check_in_time)
                <p class="text-xs text-emerald-600 font-medium">{{ $reservation->check_in_time }}</p>
                @endif
            </div>
        </div>

        {{-- Connector + nights --}}
        <div class="flex items-center gap-2 flex-1 justify-center">
            <div class="hidden sm:block flex-1 h-0.5 bg-gradient-to-l from-emerald-200 via-gray-200 to-red-200 rounded-full"></div>
            <span class="px-3 py-1 rounded-full bg-gray-800 text-white text-xs font-black shrink-0 whitespace-nowrap">{{ $reservation->nights }} {{ $reservation->nights == 1 ? 'ليلة' : 'ليالٍ' }}</span>
            <div class="hidden sm:block flex-1 h-0.5 bg-gradient-to-l from-emerald-200 via-gray-200 to-red-200 rounded-full"></div>
        </div>

        {{-- Check-out --}}
        <div class="flex items-center gap-3 flex-1 min-w-0 sm:justify-end">
            <div class="w-11 h-11 rounded-xl bg-red-100 flex items-center justify-center shrink-0 sm:order-2">
                <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
            </div>
            <div class="min-w-0 sm:text-left sm:order-1">
                <p class="text-xs text-gray-400 font-semibold uppercase tracking-wide">تاريخ الخروج</p>
                <p class="font-bold text-gray-900 text-lg leading-tight">{{ $reservation->check_out_date?->format('d/m/Y') ?? '—' }}</p>
                @if($daysLabel && $reservation->status === 'checked_in')
                <span class="inline-flex items-center gap-1 mt-1 px-2 py-0.5 text-xs font-bold rounded-lg {{ $daysClass }}">{{ $daysLabel }}</span>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- ===== MAIN GRID ===== --}}
<div class="grid grid-cols-1 lg:grid-cols-3 gap-5 items-start">

    {{-- ===== LEFT COLUMN ===== --}}
    <div class="lg:col-span-2 space-y-5">

        {{-- Guest Card --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-50 flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-blue-600 flex items-center justify-center shadow-sm">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                </div>
                <h3 class="font-bold text-gray-800">بيانات النزيل الرئيسي</h3>
            </div>
            <div class="p-6">
                <div class="flex flex-col md:flex-row gap-6">
                    <div class="flex-1 grid grid-cols-2 sm:grid-cols-3 gap-x-6 gap-y-3.5 text-sm">
                        <div>
                            <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">الاسم الكامل</p>
                            <p class="font-bold text-gray-900 text-base">{{ $reservation->guest?->full_name ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">الجنسية</p>
                            <p class="text-gray-700">{{ $reservation->guest?->nationality ?: '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">المهنة</p>
                            <p class="text-gray-700">{{ $reservation->guest?->occupation ?: '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">نوع الهوية</p>
                            <p class="text-gray-700">{{ $reservation->guest?->getIdTypeLabel() ?? '—' }}</p>
                        </div>
                        @can('guests.sensitive')
                        <div>
                            <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">رقم الهوية</p>
                            <p class="font-mono font-semibold text-gray-800 tracking-wider">{{ $reservation->guest?->id_number ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">رقم الجوال</p>
                            <p class="font-mono text-gray-800">{{ $reservation->guest?->phone ?: '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">جهة الإصدار</p>
                            <p class="text-gray-700">{{ $reservation->guest?->id_issuer ?: '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">تاريخ الإصدار</p>
                            <p class="text-gray-700">{{ $reservation->guest?->id_issue_date?->format('d/m/Y') ?: '—' }}</p>
                        </div>
                        @endcan
                        <div>
                            <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">جهة القدوم</p>
                            <p class="text-gray-700">{{ $reservation->origin ?: '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">الغرض من الزيارة</p>
                            <p class="text-gray-700">{{ $reservation->purpose ?: '—' }}</p>
                        </div>
                    </div>

                    {{-- ID Image --}}
                    @if($reservation->guest?->id_image_path)
                    <div class="md:w-44 shrink-0" id="guestIdImageBox">
                        <p class="text-xs text-gray-400 mb-2 text-center font-medium">صورة الهوية</p>
                        @php $ext = strtolower(pathinfo($reservation->guest->id_image_path, PATHINFO_EXTENSION)); @endphp
                        @if($ext === 'pdf')
                        <a href="{{ route('guests.idImage', $reservation->guest) }}" target="_blank"
                           class="flex flex-col items-center justify-center w-full h-36 border-2 border-dashed border-gray-200 rounded-2xl hover:border-blue-400 transition bg-red-50 group">
                            <svg class="w-10 h-10 text-red-400 group-hover:text-red-500" fill="currentColor" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8l-6-6zm-1 1.5L18.5 9H13V3.5zM8 17h8v-1H8v1zm0-3h8v-1H8v1zm0-3h5v-1H8v1z"/></svg>
                            <span class="text-xs text-red-500 mt-2 font-semibold">ملف PDF</span>
                            <span class="text-xs text-gray-400">اضغط للعرض</span>
                        </a>
                        @else
                        <a href="{{ route('guests.idImage', $reservation->guest) }}" target="_blank">
                            <img src="{{ route('guests.idImage', $reservation->guest) }}"
                                 alt="هوية النزيل"
                                 class="w-full h-36 object-cover rounded-2xl border border-gray-200 hover:opacity-90 transition cursor-zoom-in shadow-sm"
                                 onerror="document.getElementById('guestIdImageBox').innerHTML='<div class=\'flex flex-col items-center justify-center h-36 border-2 border-dashed border-gray-200 rounded-2xl bg-gray-50 text-gray-400 text-xs text-center p-3\'><svg class=\'w-8 h-8 mb-2\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z\'></path></svg>الصورة غير متاحة</div>'">
                        </a>
                        @endif
                    </div>
                    @endif
                </div>

                @if($reservation->notes)
                <div class="mt-5 p-4 bg-amber-50 border border-amber-100 rounded-xl text-sm text-amber-800 flex gap-3">
                    <svg class="w-5 h-5 text-amber-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>
                    <div>
                        <span class="font-semibold text-xs text-amber-600 block mb-0.5">ملاحظات</span>
                        {{ $reservation->notes }}
                    </div>
                </div>
                @endif
            </div>
        </div>

        {{-- Companions --}}
        @if($reservation->companions->count() > 0)
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-50 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-violet-600 flex items-center justify-center shadow-sm">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                    <h3 class="font-bold text-gray-800">المرافقون</h3>
                </div>
                <span class="px-3 py-1 bg-violet-100 text-violet-700 text-xs font-bold rounded-full">
                    {{ $reservation->companions->count() }}
                </span>
            </div>
            <div class="divide-y divide-gray-50">
                @foreach($reservation->companions as $idx => $c)
                <div class="p-6">
                    <div class="flex flex-col md:flex-row gap-5">
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="w-8 h-8 rounded-full bg-violet-100 text-violet-700 text-sm font-black flex items-center justify-center shrink-0">
                                    {{ $idx + 1 }}
                                </div>
                                <span class="font-bold text-gray-900">{{ $c->full_name }}</span>
                                <span class="badge-pill bg-violet-50 text-violet-600">
                                    {{ $c->getRelationshipLabel() }}
                                </span>
                            </div>
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-x-6 gap-y-3 text-sm">
                                <div>
                                    <p class="text-xs text-gray-400 uppercase tracking-wide mb-0.5">الجنسية</p>
                                    <p class="text-gray-700">{{ $c->nationality ?: '—' }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-400 uppercase tracking-wide mb-0.5">نوع الهوية</p>
                                    <p class="text-gray-700">{{ $c->getIdTypeLabel() ?? '—' }}</p>
                                </div>
                                @can('guests.sensitive')
                                <div>
                                    <p class="text-xs text-gray-400 uppercase tracking-wide mb-0.5">رقم الهوية</p>
                                    <p class="font-mono font-semibold text-gray-800">{{ $c->id_number ?? '—' }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-400 uppercase tracking-wide mb-0.5">جهة الإصدار</p>
                                    <p class="text-gray-700">{{ $c->id_issuer ?: '—' }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-400 uppercase tracking-wide mb-0.5">تاريخ الإصدار</p>
                                    <p class="text-gray-700">{{ $c->id_issue_date?->format('d/m/Y') ?: '—' }}</p>
                                </div>
                                @endcan
                            </div>
                        </div>

                        <div class="flex gap-3 md:flex-col md:w-36 shrink-0">
                            @if($c->id_image_path)
                            <div class="flex-1 md:flex-none">
                                <p class="text-xs text-gray-400 mb-1.5 text-center font-medium">صورة الهوية</p>
                                @php $cExt = strtolower(pathinfo($c->id_image_path, PATHINFO_EXTENSION)); @endphp
                                @if($cExt === 'pdf')
                                <a href="{{ route('companions.idImage', $c) }}" target="_blank"
                                   class="flex flex-col items-center justify-center w-full h-24 border-2 border-dashed border-gray-200 rounded-xl hover:border-blue-400 transition bg-red-50">
                                    <svg class="w-7 h-7 text-red-400" fill="currentColor" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8l-6-6zm-1 1.5L18.5 9H13V3.5z"/></svg>
                                    <span class="text-xs text-red-400 mt-1 font-medium">PDF</span>
                                </a>
                                @else
                                <a href="{{ route('companions.idImage', $c) }}" target="_blank">
                                    <img src="{{ route('companions.idImage', $c) }}" alt="هوية المرافق"
                                         class="w-full h-24 object-cover rounded-xl border border-gray-200 hover:opacity-90 transition cursor-zoom-in shadow-sm">
                                </a>
                                @endif
                            </div>
                            @endif
                            @if($c->marriage_doc_path)
                            <div class="flex-1 md:flex-none">
                                <p class="text-xs text-gray-400 mb-1.5 text-center font-medium">عقد الزواج</p>
                                @php $mExt = strtolower(pathinfo($c->marriage_doc_path, PATHINFO_EXTENSION)); @endphp
                                @if($mExt === 'pdf')
                                <a href="{{ route('companions.marriageDoc', $c) }}" target="_blank"
                                   class="flex flex-col items-center justify-center w-full h-24 border-2 border-dashed border-gray-200 rounded-xl hover:border-pink-400 transition bg-pink-50">
                                    <svg class="w-7 h-7 text-pink-400" fill="currentColor" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8l-6-6zm-1 1.5L18.5 9H13V3.5z"/></svg>
                                    <span class="text-xs text-pink-400 mt-1 font-medium">PDF</span>
                                </a>
                                @else
                                <a href="{{ route('companions.marriageDoc', $c) }}" target="_blank">
                                    <img src="{{ route('companions.marriageDoc', $c) }}" alt="عقد الزواج"
                                         class="w-full h-24 object-cover rounded-xl border border-gray-200 hover:opacity-90 transition cursor-zoom-in shadow-sm">
                                </a>
                                @endif
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Payments History --}}
        @if($reservation->payments->count() > 0)
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-50 flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-emerald-600 flex items-center justify-center shadow-sm">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
                <h3 class="font-bold text-gray-800">سجل المدفوعات</h3>
                <span class="mr-auto px-2.5 py-0.5 bg-emerald-50 text-emerald-700 text-xs font-bold rounded-full">
                    {{ $reservation->payments->count() }} دفعة
                </span>
            </div>

            {{-- Desktop Table --}}
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50/80 text-xs text-gray-500 font-semibold border-b border-gray-100">
                            <th class="px-5 py-3 text-right font-semibold">التاريخ</th>
                            <th class="px-5 py-3 text-right font-semibold">المبلغ</th>
                            <th class="px-5 py-3 text-right font-semibold">الطريقة</th>
                            <th class="px-5 py-3 text-right font-semibold">النوع</th>
                            <th class="px-5 py-3 text-right font-semibold">ملاحظة</th>
                            <th class="px-5 py-3 text-right font-semibold">استلم بواسطة</th>
                            <th class="px-5 py-3 text-right font-semibold">السند</th>
                            @can('payments.create')
                            <th class="px-5 py-3 text-right font-semibold"></th>
                            @endcan
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($reservation->payments as $p)
                        @php
                            $typeLabel = match($p->type) {
                                'reservation'  => ['label' => 'دفعة حجز',    'class' => 'bg-blue-50 text-blue-700'],
                                'renewal'      => ['label' => 'دفعة تجديد',  'class' => 'bg-amber-50 text-amber-700'],
                                'compensation' => ['label' => 'تعويض أضرار', 'class' => 'bg-red-50 text-red-700'],
                                'extra_service'=> ['label' => 'خدمة إضافية', 'class' => 'bg-purple-50 text-purple-700'],
                                default        => ['label' => $p->type,       'class' => 'bg-gray-50 text-gray-600'],
                            };
                            $methodClass = match($p->method) {
                                'cash'          => 'bg-emerald-50 text-emerald-700',
                                'pos'           => 'bg-blue-50 text-blue-700',
                                'bank_transfer' => 'bg-purple-50 text-purple-700',
                                default         => 'bg-gray-50 text-gray-600',
                            };
                            $methodLabel = match($p->method) { 'cash'=>'نقدي', 'pos'=>'POS', 'bank_transfer'=>'تحويل', default=>$p->method };
                        @endphp
                        <tr class="payment-row transition-colors">
                            <td class="px-5 py-3.5 text-gray-500 text-xs whitespace-nowrap">{{ $p->payment_date->format('d/m/Y') }}<br><span class="text-gray-400">{{ $p->payment_date->format('H:i') }}</span></td>
                            <td class="px-5 py-3.5 font-bold text-emerald-700 whitespace-nowrap text-base">
                                {{ number_format($p->amount, 0) }}
                                <span class="text-xs font-normal text-gray-400">{{ $reservation->currency_symbol }}</span>
                            </td>
                            <td class="px-5 py-3.5">
                                <span class="badge-pill {{ $methodClass }}">{{ $methodLabel }}</span>
                            </td>
                            <td class="px-5 py-3.5">
                                <span class="badge-pill {{ $typeLabel['class'] }}">{{ $typeLabel['label'] }}</span>
                            </td>
                            <td class="px-5 py-3.5 max-w-[160px]">
                                @if($p->notes)
                                <span class="text-xs bg-blue-50 text-blue-600 rounded-lg px-2 py-1">{{ $p->notes }}</span>
                                @else
                                <span class="text-gray-300 text-xs">—</span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-gray-600 text-sm">{{ $p->receivedBy?->name ?? '—' }}</td>
                            <td class="px-5 py-3.5">
                                @if($p->bank_receipt_path)
                                @can('payments.bank_receipt')
                                <a href="{{ route('payments.receipt', ['file' => $p->bank_receipt_path]) }}" target="_blank"
                                   class="inline-flex items-center gap-1 text-blue-600 hover:text-blue-800 text-xs font-semibold hover:underline">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    عرض
                                </a>
                                @endcan
                                @else
                                <span class="text-gray-300 text-xs">—</span>
                                @endif
                            </td>
                            @can('payments.create')
                            <td class="px-5 py-3.5">
                                <button type="button"
                                        onclick="openEditPaymentModal({{ $p->id }}, {{ $p->amount }})"
                                        class="inline-flex items-center gap-1 text-gray-400 hover:text-blue-600 text-xs font-semibold hover:underline">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    تصحيح
                                </button>
                            </td>
                            @endcan
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Mobile View --}}
            <div class="md:hidden space-y-3 p-4">
                @foreach($reservation->payments as $p)
                @php
                    $typeLabel = match($p->type) {
                        'reservation'  => ['label' => 'دفعة حجز',    'class' => 'bg-blue-50 text-blue-700'],
                        'renewal'      => ['label' => 'دفعة تجديد',  'class' => 'bg-amber-50 text-amber-700'],
                        'compensation' => ['label' => 'تعويض أضرار', 'class' => 'bg-red-50 text-red-700'],
                        'extra_service'=> ['label' => 'خدمة إضافية', 'class' => 'bg-purple-50 text-purple-700'],
                        default        => ['label' => $p->type,       'class' => 'bg-gray-50 text-gray-600'],
                    };
                @endphp
                <div class="bg-gray-50 rounded-xl p-4 space-y-2.5">
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-gray-400">{{ $p->payment_date->format('d/m/Y H:i') }}</span>
                        <span class="font-bold text-emerald-700 text-lg">{{ number_format($p->amount, 0) }} {{ $reservation->currency_symbol }}</span>
                    </div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="badge-pill {{ $p->method === 'cash' ? 'bg-emerald-50 text-emerald-700' : ($p->method === 'pos' ? 'bg-blue-50 text-blue-700' : 'bg-purple-50 text-purple-700') }}">
                            {{ match($p->method) { 'cash'=>'نقدي', 'pos'=>'POS', 'bank_transfer'=>'تحويل', default=>$p->method } }}
                        </span>
                        <span class="badge-pill {{ $typeLabel['class'] }}">{{ $typeLabel['label'] }}</span>
                    </div>
                    <div class="text-xs text-gray-500">استلمه: <span class="font-medium text-gray-700">{{ $p->receivedBy?->name ?? '—' }}</span></div>
                    @can('payments.create')
                    <button type="button"
                            onclick="openEditPaymentModal({{ $p->id }}, {{ $p->amount }})"
                            class="inline-flex items-center gap-1 text-blue-600 text-xs font-semibold hover:underline">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        تصحيح المبلغ
                    </button>
                    @endcan
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Extra Charges --}}
        @if($reservation->extraCharges->count() > 0)
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-50 flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-red-500 flex items-center justify-center shadow-sm">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <h3 class="font-bold text-gray-800">الرسوم الإضافية</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50/80 text-xs text-gray-500 font-semibold border-b border-gray-100">
                            <th class="px-5 py-3 text-right">التاريخ</th>
                            <th class="px-5 py-3 text-right">النوع</th>
                            <th class="px-5 py-3 text-right">الوصف</th>
                            <th class="px-5 py-3 text-right">المبلغ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($reservation->extraCharges as $charge)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-5 py-3.5 text-gray-500">{{ $charge->charge_date->format('d/m/Y') }}</td>
                            <td class="px-5 py-3.5 text-gray-600">{{ $charge->type }}</td>
                            <td class="px-5 py-3.5 text-gray-500 text-sm">{{ $charge->description ?: '—' }}</td>
                            <td class="px-5 py-3.5 font-bold text-red-600">
                                {{ number_format($charge->amount, 0) }} {{ $reservation->currency_symbol }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        {{-- Room Inspection --}}
        @if($reservation->status === 'checked_out' && $reservation->roomInspections->count() > 0)
        @php $insp = $reservation->roomInspections->first(); @endphp
        <div class="bg-white rounded-2xl shadow-sm border {{ $insp->has_damage ? 'border-red-200' : 'border-emerald-100' }} overflow-hidden">
            <div class="px-6 py-4 border-b {{ $insp->has_damage ? 'border-red-100' : 'border-emerald-50' }} flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl {{ $insp->has_damage ? 'bg-red-500' : 'bg-emerald-500' }} flex items-center justify-center shadow-sm">
                    @if($insp->has_damage)
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    @else
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    @endif
                </div>
                <h3 class="font-bold text-gray-800">تقرير فحص الغرفة</h3>
                <span class="mr-auto text-xs text-gray-400">{{ $insp->inspection_date?->format('d/m/Y H:i') }}</span>
            </div>
            <div class="p-6 space-y-3 text-sm">
                @if($insp->has_damage)
                <div class="flex items-center gap-2 text-red-700 font-semibold">
                    <span class="w-2 h-2 rounded-full bg-red-500 inline-block"></span>
                    تم تسجيل أضرار في الغرفة
                </div>
                @if($insp->damage_description)
                <p class="text-gray-600 bg-red-50 rounded-xl p-4 text-sm leading-relaxed">{{ $insp->damage_description }}</p>
                @endif
                @if($insp->compensation_amount > 0)
                <div class="flex justify-between items-center bg-orange-50 border border-orange-100 rounded-xl p-4">
                    <span class="text-orange-800 font-semibold text-sm">مبلغ التعويض</span>
                    <span class="font-black text-orange-700 text-lg">{{ number_format($insp->compensation_amount, 0) }} {{ $reservation->currency_symbol }}</span>
                </div>
                <div class="flex items-center gap-1.5 text-sm">
                    <span class="w-2.5 h-2.5 rounded-full {{ $insp->compensation_status === 'paid' ? 'bg-emerald-500' : 'bg-amber-500' }} inline-block"></span>
                    <span class="{{ $insp->compensation_status === 'paid' ? 'text-emerald-700 font-semibold' : 'text-amber-700' }}">
                        {{ $insp->compensation_status === 'paid' ? 'التعويض مدفوع' : 'التعويض معلق' }}
                    </span>
                </div>
                @endif
                @if($insp->images->count() > 0)
                <div>
                    <p class="text-xs text-gray-400 mb-2 font-medium">صور الأضرار ({{ $insp->images->count() }})</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach($insp->images as $img)
                        <a href="{{ Storage::disk('private')->url($img->image_path) }}" target="_blank"
                           class="w-20 h-20 rounded-xl overflow-hidden border border-gray-200 block hover:opacity-90 transition shadow-sm">
                            <img src="{{ asset('storage/' . $img->image_path) }}" alt="صورة ضرر"
                                 class="w-full h-full object-cover" onerror="this.parentElement.style.display='none'">
                        </a>
                        @endforeach
                    </div>
                </div>
                @endif
                @else
                <div class="flex items-center gap-3 text-emerald-700 font-semibold">
                    <span class="w-3 h-3 rounded-full bg-emerald-500 inline-block"></span>
                    الغرفة بحالة جيدة — لا توجد أضرار
                </div>
                @endif
            </div>
        </div>
        @endif

    </div>

    {{-- ===== RIGHT COLUMN ===== --}}
    <div class="space-y-5 lg:sticky lg:top-6 self-start">

        {{-- Financial Summary Card --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-50 flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-amber-500 flex items-center justify-center shadow-sm">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h3 class="font-bold text-gray-800">الملخص المالي</h3>
            </div>
            <div class="p-5 space-y-3 text-sm">
                @if($hasFirstNight)
                <div class="flex justify-between items-center">
                    <span class="text-gray-500">سعر الليلة الأولى</span>
                    <span class="font-semibold text-purple-700">{{ number_format($reservation->first_night_price, 0) }} {{ $reservation->currency_symbol }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-500">سعر باقي الليالي ({{ $reservation->nights - 1 }})</span>
                    <span class="font-semibold text-gray-800">{{ number_format($otherNightPrice, 0) }} {{ $reservation->currency_symbol }}</span>
                </div>
                @else
                <div class="flex justify-between items-center">
                    <span class="text-gray-500">سعر الليلة</span>
                    <span class="font-semibold text-gray-800">{{ number_format($pricePerNight, 0) }} {{ $reservation->currency_symbol }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-500">عدد الليالي</span>
                    <span class="font-semibold text-gray-800">{{ $reservation->nights }}</span>
                </div>
                @endif
                @if($reservation->discount_amount > 0)
                <div class="flex justify-between items-center text-emerald-600">
                    <span>خصم {{ $reservation->discount_reason ? '(' . $reservation->discount_reason . ')' : '' }}</span>
                    <span class="font-semibold">- {{ number_format($reservation->discount_amount, 0) }} {{ $reservation->currency_symbol }}</span>
                </div>
                @endif
                @if($reservation->extraCharges->count() > 0)
                <div class="flex justify-between items-center text-red-600">
                    <span>رسوم إضافية</span>
                    <span class="font-semibold">+ {{ number_format($reservation->extraCharges->sum('amount'), 0) }} {{ $reservation->currency_symbol }}</span>
                </div>
                @endif
                <div class="border-t border-gray-100 pt-3">
                    <div class="flex justify-between items-center">
                        <span class="font-bold text-gray-800">الإجمالي</span>
                        <span class="font-black text-gray-900 text-lg">{{ number_format($reservation->total_amount, 0) }} <span class="text-sm font-normal text-gray-400">{{ $reservation->currency_symbol }}</span></span>
                    </div>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-500">المدفوع</span>
                    <span class="font-semibold text-emerald-600">{{ number_format($reservation->paid_amount, 0) }} {{ $reservation->currency_symbol }}</span>
                </div>

                {{-- Progress Bar --}}
                @if($reservation->total_amount > 0)
                <div>
                    <div class="flex justify-between items-center mb-1.5">
                        <span class="text-xs text-gray-400">نسبة السداد</span>
                        <span class="text-xs font-bold {{ $paidPercent >= 100 ? 'text-emerald-600' : 'text-gray-600' }}">{{ $paidPercent }}%</span>
                    </div>
                    <div class="w-full bg-gray-100 rounded-full h-2">
                        <div class="paid-bar h-2 rounded-full" style="width: {{ $paidPercent }}%"></div>
                    </div>
                </div>
                @endif

                <div class="bg-gray-50 rounded-xl p-4 flex justify-between items-center mt-2">
                    <span class="font-bold {{ $reservation->balance > 0 ? 'text-red-700' : 'text-emerald-700' }} text-sm">
                        {{ $reservation->balance > 0 ? 'المتبقي' : 'مكتمل الدفع' }}
                    </span>
                    <span class="font-black text-xl {{ $reservation->balance > 0 ? 'text-red-600' : 'text-emerald-600' }}">
                        {{ number_format(abs($reservation->balance), 0) }}
                        <span class="text-sm font-normal text-gray-400">{{ $reservation->currency_symbol }}</span>
                    </span>
                </div>
            </div>
        </div>

        {{-- Meta Info Card --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 space-y-3.5 text-sm">
            <div class="flex justify-between items-center">
                <span class="text-gray-400 text-xs font-medium">سُجِّل بواسطة</span>
                <span class="text-gray-700 font-semibold text-sm">{{ $reservation->createdBy?->name ?? '—' }}</span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-gray-400 text-xs font-medium">تاريخ الإنشاء</span>
                <span class="text-gray-600 text-xs">{{ $reservation->created_at?->format('d/m/Y H:i') ?? '—' }}</span>
            </div>
            @if($reservation->status === 'checked_in')
            <div class="flex justify-between items-center">
                <span class="text-gray-400 text-xs font-medium">التجديد التلقائي</span>
                @if($reservation->auto_renew)
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">
                    مُفعّل · يوم جديد بعد 1 ظهراً
                </span>
                @else
                <span class="text-gray-500 text-xs">متوقّف</span>
                @endif
            </div>
            @endif
            @if($reservation->adminApproval)
            <div class="flex justify-between items-center">
                <span class="text-gray-400 text-xs font-medium">اعتمد الآجل</span>
                <span class="text-gray-700 font-semibold text-sm">{{ $reservation->adminApproval->name }}</span>
            </div>
            @endif
        </div>

    </div>
</div>

{{-- ===== BACK LINK ===== --}}
<div class="pt-1">
    <a href="{{ route('reservations.expiring') }}"
       class="inline-flex items-center gap-2 px-5 py-2.5 text-sm text-gray-600 border border-gray-200 rounded-xl hover:bg-gray-50 hover:border-gray-300 transition font-medium">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        العودة للقائمة
    </a>
</div>

</div>

{{-- ===== PAYMENT MODAL ===== --}}
@can('payments.create')
@if($reservation->balance > 0)
<div id="paymentModal"
     class="{{ $errors->any() ? '' : 'hidden' }} fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 p-4"
     onclick="if(event.target===this) this.classList.add('hidden')">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg" onclick="event.stopPropagation()">
        <div class="hero-gradient px-6 py-5 rounded-t-2xl flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-white/20 flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                </div>
                <h3 class="font-bold text-white text-lg">إضافة دفعة</h3>
            </div>
            <button onclick="document.getElementById('paymentModal').classList.add('hidden')"
                    class="w-8 h-8 flex items-center justify-center text-white/70 hover:text-white hover:bg-white/20 rounded-lg transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form method="POST" action="{{ route('payments.store') }}" enctype="multipart/form-data"
              class="p-6 space-y-4" x-data="{ payMethod: '{{ old('method', 'cash') }}' }">
            @csrf
            <input type="hidden" name="reservation_id" value="{{ $reservation->id }}">
            <input type="hidden" name="currency" value="YER">

            <div class="bg-red-50 border border-red-100 rounded-xl p-4 flex items-center justify-between">
                <div>
                    <p class="text-xs text-red-500 font-medium mb-0.5">الرصيد المتبقي</p>
                    <p class="font-black text-red-700 text-2xl">{{ number_format($reservation->balance, 0) }} <span class="text-sm font-normal">{{ $reservation->currency_symbol }}</span></p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-red-100 flex items-center justify-center">
                    <svg class="w-6 h-6 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>

            @if($errors->any())
            <div class="bg-red-50 border border-red-200 rounded-xl p-3 text-sm text-red-700">
                @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
            </div>
            @endif

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">المبلغ <span class="text-red-500">*</span></label>
                <input type="number" name="amount" step="0.01" min="0.01" max="{{ $reservation->balance }}"
                       value="{{ old('amount') }}" placeholder="{{ number_format($reservation->balance, 0) }}" required
                       class="w-full border {{ $errors->has('amount') ? 'border-red-400 bg-red-50' : 'border-gray-300' }} rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-blue-500 outline-none transition">
                <p class="text-xs text-gray-400 mt-1.5">الحد الأقصى: {{ number_format($reservation->balance, 0) }} {{ $reservation->currency_symbol }}</p>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">ملاحظة <span class="text-gray-400 font-normal">(اختياري)</span></label>
                <input type="text" name="notes" value="{{ old('notes') }}"
                       placeholder="مثال: دفع 100 ر.س بسعر صرف 400 = 40,000 ر.ي"
                       class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-blue-500 outline-none transition">
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">طريقة الدفع</label>
                <select name="method" x-model="payMethod"
                        class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-blue-500 outline-none transition">
                    <option value="cash">نقدي</option>
                    <option value="pos">شبكة POS</option>
                    <option value="bank_transfer">تحويل بنكي</option>
                </select>
            </div>

            <div x-show="payMethod === 'bank_transfer'" class="bg-blue-50 border border-blue-100 rounded-xl p-4 space-y-3">
                <p class="text-xs text-blue-600 font-semibold">يجب تقديم واحد على الأقل: صورة السند أو رقم المرجع</p>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">صورة سند التحويل</label>
                    <input type="file" name="bank_receipt" accept="image/*,.pdf"
                           class="w-full text-sm text-gray-600 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:bg-blue-100 file:text-blue-700 hover:file:bg-blue-200">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">رقم مرجع التحويل</label>
                    <input type="text" name="bank_transfer_ref" value="{{ old('bank_transfer_ref') }}"
                           placeholder="TRF-20240101-001"
                           class="w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
            </div>

            <div class="flex gap-3 pt-1">
                <button type="submit"
                        class="flex-1 py-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-bold text-sm transition shadow-sm">
                    تسجيل الدفعة
                </button>
                <button type="button" onclick="document.getElementById('paymentModal').classList.add('hidden')"
                        class="px-6 py-3 border border-gray-300 text-gray-700 rounded-xl text-sm hover:bg-gray-50 transition font-medium">
                    إلغاء
                </button>
            </div>
        </form>
    </div>
</div>
@endif
@endcan

@can('payments.create')
{{-- Correct Payment Amount Modal --}}
<div id="editPaymentModal" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 p-4"
     onclick="if(event.target===this) this.classList.add('hidden')">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md" onclick="event.stopPropagation()">
        <div class="hero-gradient px-6 py-5 rounded-t-2xl flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-white/20 flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                </div>
                <h3 class="font-bold text-white text-lg">تصحيح مبلغ الدفعة</h3>
            </div>
            <button type="button" onclick="document.getElementById('editPaymentModal').classList.add('hidden')"
                    class="w-8 h-8 flex items-center justify-center text-white/70 hover:text-white hover:bg-white/20 rounded-lg transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form id="editPaymentForm" method="POST" class="p-6 space-y-4">
            @csrf
            @method('PATCH')
            <div class="bg-amber-50 border border-amber-100 rounded-xl p-4 text-sm text-amber-800">
                استخدم هذا فقط لتصحيح مبلغ أُدخل بالخطأ (مثلاً اختيار دفع كامل بدل جزئي أو العكس).
                سيُحدَّث المبلغ المدفوع الإجمالي للحجز وحالة الدفع تلقائياً.
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">المبلغ الصحيح <span class="text-red-500">*</span></label>
                <input type="number" id="editPaymentAmount" name="amount" step="0.01" min="0.01" required
                       class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-blue-500 outline-none transition">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">سبب التصحيح <span class="text-red-500">*</span></label>
                <input type="text" name="correction_reason" required maxlength="255"
                       placeholder="مثال: تم اختيار دفع كامل بالخطأ، الزبون دفع جزئياً فقط"
                       class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-blue-500 outline-none transition">
            </div>
            <div class="flex gap-3 pt-1">
                <button type="submit"
                        class="flex-1 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-bold text-sm transition shadow-sm">
                    تأكيد التصحيح
                </button>
                <button type="button" onclick="document.getElementById('editPaymentModal').classList.add('hidden')"
                        class="px-6 py-3 border border-gray-300 text-gray-700 rounded-xl text-sm hover:bg-gray-50 transition font-medium">
                    إلغاء
                </button>
            </div>
        </form>
    </div>
</div>
<script>
    function openEditPaymentModal(paymentId, currentAmount) {
        const base = @json(route('payments.update', ['payment' => 0]));
        document.getElementById('editPaymentForm').action = base.replace(/\/0$/, '/' + paymentId);
        document.getElementById('editPaymentAmount').value = currentAmount;
        document.getElementById('editPaymentModal').classList.remove('hidden');
    }
</script>
@endcan

{{-- ===== DISCOUNT MODAL ===== --}}
@can('checkin.create')
@if(in_array($reservation->status, ['confirmed', 'checked_in', 'checked_out']) && $reservation->total_amount > 0)
<div id="discountModal" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 p-4 overflow-y-auto"
     onclick="if(event.target===this) this.classList.add('hidden')">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md max-h-[90vh] overflow-y-auto my-auto" onclick="event.stopPropagation()">
        <div class="hero-gradient px-6 py-5 rounded-t-2xl flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-white/20 flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>
                </div>
                <h3 class="font-bold text-white text-lg">تطبيق خصم</h3>
            </div>
            <button onclick="document.getElementById('discountModal').classList.add('hidden')"
                    class="w-8 h-8 flex items-center justify-center text-white/70 hover:text-white hover:bg-white/20 rounded-lg transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form method="POST" action="{{ route('reservations.applyDiscount', $reservation) }}"
              x-data="{ dtype: '{{ $reservation->discount_type ?: 'fixed' }}', dvalue: {{ (float)($reservation->discount_value ?: 0) }}, total: {{ (float)$reservation->total_amount }} }"
              class="p-6 space-y-4">
            @csrf
            <div class="bg-gray-50 rounded-xl p-4 text-sm flex justify-between items-center">
                <span class="text-gray-400 text-xs font-medium">الإجمالي الحالي</span>
                <strong class="text-gray-800">{{ number_format($reservation->total_amount, 0) }} {{ $reservation->currency_symbol }}</strong>
            </div>

            @if($reservation->discount_amount > 0)
            <div class="flex items-start gap-2 rounded-xl bg-blue-50 border border-blue-200 p-3 text-xs text-blue-800">
                <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>يوجد خصم مطبّق حالياً بقيمة <strong>{{ number_format($reservation->discount_amount, 0) }} {{ $reservation->currency_symbol }}</strong>. تطبيق خصم جديد يستبدله ويُحسب على الإجمالي الحالي.</span>
            </div>
            @endif

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">نوع الخصم <span class="text-red-500">*</span></label>
                <div class="grid grid-cols-2 gap-2">
                    <label :class="dtype === 'fixed' ? 'bg-blue-50 border-blue-500 text-blue-700' : 'border-gray-300 text-gray-600'"
                           class="flex items-center justify-center gap-2 border rounded-xl px-4 py-3 text-sm font-medium cursor-pointer transition">
                        <input type="radio" name="discount_type" value="fixed" x-model="dtype" class="hidden">
                        مبلغ ثابت (ر.ي)
                    </label>
                    <label :class="dtype === 'percent' ? 'bg-blue-50 border-blue-500 text-blue-700' : 'border-gray-300 text-gray-600'"
                           class="flex items-center justify-center gap-2 border rounded-xl px-4 py-3 text-sm font-medium cursor-pointer transition">
                        <input type="radio" name="discount_type" value="percent" x-model="dtype" class="hidden">
                        نسبة مئوية (%)
                    </label>
                </div>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    <span x-show="dtype === 'fixed'">قيمة الخصم (ر.ي)</span>
                    <span x-show="dtype === 'percent'">نسبة الخصم (%)</span>
                    <span class="text-red-500">*</span>
                </label>
                <input type="number" name="discount_value" x-model.number="dvalue" min="0" step="0.01" required
                       :max="dtype === 'percent' ? 100 : total"
                       class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
            </div>

            {{-- ملخص حي --}}
            <div class="bg-emerald-50 border border-emerald-100 rounded-xl p-4 space-y-1.5 text-sm">
                <div class="flex justify-between items-center text-emerald-700">
                    <span>قيمة الخصم</span>
                    <strong x-text="Math.round((dtype === 'percent' ? total * Math.min(dvalue,100)/100 : Math.min(dvalue, total)) || 0).toLocaleString() + ' {{ $reservation->currency_symbol }}'"></strong>
                </div>
                <div class="flex justify-between items-center border-t border-emerald-100 pt-1.5">
                    <span class="font-bold text-gray-800">الإجمالي بعد الخصم</span>
                    <strong class="text-gray-900" x-text="Math.max(0, Math.round(total - ((dtype === 'percent' ? total * Math.min(dvalue,100)/100 : Math.min(dvalue, total)) || 0))).toLocaleString() + ' {{ $reservation->currency_symbol }}'"></strong>
                </div>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">سبب الخصم <span class="text-gray-400 font-normal">(اختياري)</span></label>
                <input type="text" name="discount_reason" maxlength="255" value="{{ $reservation->discount_reason }}"
                       placeholder="مثال: نزيل دائم، عرض خاص..."
                       class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
            </div>

            <div class="flex gap-2 pt-1">
                <button type="submit"
                        class="flex-1 px-4 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm font-bold transition">
                    تطبيق الخصم
                </button>
                <button type="button" onclick="document.getElementById('discountModal').classList.add('hidden')"
                        class="px-5 py-3 border border-gray-300 text-gray-600 rounded-xl text-sm hover:bg-gray-50 transition">
                    إلغاء
                </button>
            </div>
        </form>
    </div>
</div>
@endif
@endcan

{{-- ===== MODALS (checked_in only) ===== --}}
@if($reservation->status === 'checked_in')
@can('checkin.view')

{{-- Renewal Modal --}}
<div id="renewModal" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 p-4 overflow-y-auto"
     onclick="if(event.target===this) this.classList.add('hidden')">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto my-auto" onclick="event.stopPropagation()">
        <div class="hero-gradient px-6 py-5 rounded-t-2xl flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-white/20 flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                </div>
                <h3 class="font-bold text-white text-lg">تجديد الإقامة</h3>
            </div>
            <button onclick="document.getElementById('renewModal').classList.add('hidden')"
                    class="w-8 h-8 flex items-center justify-center text-white/70 hover:text-white hover:bg-white/20 rounded-lg transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form method="POST" action="{{ route('reservations.renew', $reservation) }}"
              x-data="renewForm()" class="p-6 space-y-3">
            @csrf
            <div class="grid grid-cols-2 gap-3 bg-gray-50 rounded-xl p-4 text-sm">
                <div>
                    <span class="text-gray-400 text-xs block mb-0.5 font-medium">تاريخ الدخول</span>
                    <strong class="text-gray-800">{{ $reservation->check_in_date?->format('d/m/Y') ?? '—' }}</strong>
                </div>
                <div>
                    <span class="text-gray-400 text-xs block mb-0.5 font-medium">الخروج الحالي</span>
                    <strong class="text-gray-800">{{ $reservation->check_out_date?->format('d/m/Y') ?? '—' }}</strong>
                </div>
                <div>
                    <span class="text-gray-400 text-xs block mb-0.5 font-medium">المدة الحالية</span>
                    <strong class="text-gray-800">{{ $reservation->nights }} {{ $reservation->nights == 1 ? 'ليلة' : 'ليالٍ' }}</strong>
                </div>
                <div>
                    <span class="text-gray-400 text-xs block mb-0.5 font-medium">الرصيد المتبقي حالياً</span>
                    <strong class="{{ $reservation->balance > 0 ? 'text-red-600' : 'text-emerald-600' }}">{{ number_format($reservation->balance, 0) }} ر.ي</strong>
                </div>
            </div>

            {{-- مدخلان مترابطان: عدد الليالي ↔ تاريخ الخروج (تعديل أحدهما يحدّث الآخر) --}}
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">عدد الليالي الإضافية <span class="text-red-500">*</span></label>
                    <input type="number" min="1" step="1" x-model.number="extraNights" @input="fromNights()"
                           placeholder="0"
                           class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">تاريخ الخروج الجديد <span class="text-red-500">*</span></label>
                    <input type="date" name="new_check_out_date" x-model="newDate"
                           min="{{ $reservation->check_out_date?->copy()->addDay()->format('Y-m-d') ?? '' }}"
                           @if($renewMaxCheckout) max="{{ $renewMaxCheckout->format('Y-m-d') }}" @endif
                           @change="fromDate()" required
                           class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
            </div>

            @if($renewMaxCheckout && $renewNextArrival)
            {{-- تنبيه: الغرفة محجوزة لنزيل قادم، مع يوم فاصل للتنظيف قبل وصوله --}}
            <div class="flex items-start gap-2 rounded-xl bg-amber-50 border border-amber-200 p-3 text-xs text-amber-800">
                <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>هذه الغرفة محجوزة لنزيل قادم يصل بتاريخ <strong>{{ $renewNextArrival->format('Y/m/d') }}</strong>، ويجب ترك يوم فاصل للتنظيف — فأقصى تاريخ خروج متاح للتجديد هو <strong>{{ $renewMaxCheckout->format('Y/m/d') }}</strong>.</span>
            </div>
            @endif

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">سعر ليلة التجديد <span class="text-gray-400 font-normal">(قابل للتعديل)</span></label>
                <div class="relative">
                    <input type="number" name="renewal_price_per_night" min="0" step="0.01"
                           x-model.number="pricePerNight" @input="calc()"
                           class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-xs text-gray-400 pointer-events-none">ر.ي / ليلة</span>
                </div>
                <p class="text-xs text-gray-400 mt-1">سيُحفَظ هذا السعر كسعر تجديد افتراضي للحجز.</p>
            </div>

            <div x-show="extraNights > 0" class="grid grid-cols-3 gap-3">
                <div class="bg-blue-50 border border-blue-100 rounded-xl p-3 text-center">
                    <div class="text-2xl font-black text-blue-700" x-text="extraNights"></div>
                    <div class="text-xs text-blue-500 mt-0.5 font-medium">ليالٍ إضافية</div>
                </div>
                <div class="bg-emerald-50 border border-emerald-100 rounded-xl p-3 text-center">
                    <div class="text-lg font-black text-emerald-700" x-text="formatNum(extraAmount)"></div>
                    <div class="text-xs text-emerald-500 mt-0.5 font-medium">مبلغ إضافي (ر.ي)</div>
                </div>
                <div class="bg-gray-50 border border-gray-200 rounded-xl p-3 text-center">
                    <div class="text-lg font-black text-gray-800" x-text="formatNum(newTotal)"></div>
                    <div class="text-xs text-gray-500 mt-0.5 font-medium">الإجمالي الجديد (ر.ي)</div>
                </div>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">دفعة مقدمة <span class="text-gray-400 font-normal">(اختياري)</span></label>
                <div class="grid grid-cols-2 gap-3">
                    <input type="number" name="advance_payment" min="0" step="0.01" placeholder="0"
                           x-model.number="advancePayment" @input="calc()"
                           class="border border-gray-300 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                    <select name="payment_method" class="border border-gray-300 rounded-xl px-3 py-3 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                        <option value="cash">نقدي</option>
                        <option value="pos">شبكة POS</option>
                        <option value="bank_transfer">تحويل بنكي</option>
                    </select>
                </div>
            </div>

            {{-- ملخص المطلوب على النزيل بعد التجديد والدفعة --}}
            <div x-show="extraNights > 0" class="rounded-xl border p-4 text-sm"
                 :class="remaining > 0 ? 'bg-red-50 border-red-200' : 'bg-emerald-50 border-emerald-200'">
                <div class="flex items-center justify-between">
                    <span class="text-gray-600">المدفوع سابقاً</span>
                    <strong class="text-gray-800">{{ number_format($reservation->paid_amount, 0) }} ر.ي</strong>
                </div>
                <div class="flex items-center justify-between mt-1" x-show="advancePayment > 0">
                    <span class="text-gray-600">الدفعة الآن</span>
                    <strong class="text-emerald-700" x-text="'+ ' + formatNum(advancePayment) + ' ر.ي'"></strong>
                </div>
                <div class="flex items-center justify-between mt-2 pt-2 border-t"
                     :class="remaining > 0 ? 'border-red-200' : 'border-emerald-200'">
                    <span class="font-semibold" :class="remaining > 0 ? 'text-red-700' : 'text-emerald-700'"
                          x-text="remaining > 0 ? 'المتبقي على النزيل' : 'مسدّد بالكامل'"></span>
                    <strong class="text-lg" :class="remaining > 0 ? 'text-red-700' : 'text-emerald-700'"
                            x-text="formatNum(remaining) + ' ر.ي'"></strong>
                </div>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">ملاحظة الدفع <span class="text-gray-400 font-normal">(اختياري)</span></label>
                <input type="text" name="payment_notes" placeholder="مثال: دفع 100 ر.س بسعر صرف 400"
                       class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">ملاحظات التجديد</label>
                <input type="text" name="notes" placeholder="سبب التجديد..."
                       class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
            </div>

            <div class="flex gap-3 pt-1">
                <button type="submit" :disabled="extraNights <= 0"
                        class="flex-1 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-bold text-sm transition shadow-sm disabled:opacity-40">
                    تأكيد التجديد
                </button>
                <button type="button" onclick="document.getElementById('renewModal').classList.add('hidden')"
                        class="px-6 py-3 border border-gray-300 text-gray-700 rounded-xl text-sm hover:bg-gray-50 transition font-medium">
                    إلغاء
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Edit Check-in Date Modal --}}
<div id="checkinDateModal" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 p-4"
     onclick="if(event.target===this) this.classList.add('hidden')">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md" onclick="event.stopPropagation()">
        <div class="hero-gradient px-6 py-5 rounded-t-2xl flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-white/20 flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </div>
                <h3 class="font-bold text-white text-lg">تعديل تاريخ الوصول</h3>
            </div>
            <button onclick="document.getElementById('checkinDateModal').classList.add('hidden')"
                    class="w-8 h-8 flex items-center justify-center text-white/70 hover:text-white hover:bg-white/20 rounded-lg transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form method="POST" action="{{ route('reservations.updateCheckInDate', $reservation) }}"
              x-data="{ newCheckIn: '{{ $reservation->check_in_date?->format('Y-m-d') }}' }" class="p-6 space-y-4">
            @csrf @method('PATCH')
            <div class="bg-blue-50 border border-blue-100 rounded-xl p-4 text-sm text-blue-800">
                لتأجيل موعد وصول نزيل دفع مسبقاً — سيُزاح تاريخ الخروج بنفس عدد الأيام
                تلقائياً حتى تبقى مدة الإقامة والمبلغ المدفوع كما هما دون تغيير.
            </div>
            <div class="grid grid-cols-2 gap-3 bg-gray-50 rounded-xl p-4 text-sm">
                <div>
                    <span class="text-gray-400 text-xs block mb-0.5 font-medium">تاريخ الدخول الحالي</span>
                    <strong class="text-gray-800">{{ $reservation->check_in_date?->format('d/m/Y') ?? '—' }}</strong>
                </div>
                <div>
                    <span class="text-gray-400 text-xs block mb-0.5 font-medium">تاريخ الخروج الحالي</span>
                    <strong class="text-gray-800">{{ $reservation->check_out_date?->format('d/m/Y') ?? '—' }}</strong>
                </div>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">تاريخ الوصول الجديد <span class="text-red-500">*</span></label>
                <input type="date" name="new_check_in_date" x-model="newCheckIn" required
                       class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
            </div>
            <div class="flex gap-3 pt-1">
                <button type="submit"
                        class="flex-1 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-bold text-sm transition shadow-sm">
                    تأكيد التعديل
                </button>
                <button type="button" onclick="document.getElementById('checkinDateModal').classList.add('hidden')"
                        class="px-6 py-3 border border-gray-300 text-gray-700 rounded-xl text-sm hover:bg-gray-50 transition font-medium">
                    إلغاء
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Transfer Room Modal --}}
<div id="transferRoomModal" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 p-4"
     onclick="if(event.target===this) this.classList.add('hidden')">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md" onclick="event.stopPropagation()">
        <div class="hero-gradient px-6 py-5 rounded-t-2xl flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-white/20 flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                </div>
                <h3 class="font-bold text-white text-lg">تغيير الغرفة</h3>
            </div>
            <button onclick="document.getElementById('transferRoomModal').classList.add('hidden')"
                    class="w-8 h-8 flex items-center justify-center text-white/70 hover:text-white hover:bg-white/20 rounded-lg transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form method="POST" action="{{ route('reservations.transferRoom', $reservation) }}">
            @csrf
            <div class="p-6 space-y-4">
                <div class="bg-amber-50 border border-amber-100 rounded-xl p-4 text-sm text-amber-800">
                    <p class="font-semibold">الغرفة الحالية: <span class="font-black text-amber-900">{{ $reservation->display_room_number }}</span></p>
                    <p class="text-xs mt-1.5 text-amber-600">ستنتقل الغرفة الحالية إلى وضع <strong>تحت الفحص</strong> بعد النقل</p>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">الغرفة / الجناح الجديد <span class="text-red-500">*</span></label>
                    <select name="new_room_selection" id="transferRoomSelect" required onchange="updateTransferPreview(this)"
                            class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                        <option value="">اختر غرفة أو جناحاً متاحاً...</option>
                        @foreach($transferOptions as $opt)
                        <option value="{{ $opt['value'] }}" data-price="{{ $opt['price'] }}">
                            {{ $opt['label'] }} — {{ number_format($opt['price'], 0) }} ر.ي/ليلة
                        </option>
                        @endforeach
                    </select>
                    @if($transferOptions->isEmpty())
                    <p class="text-xs text-red-500 mt-1.5 font-medium">لا توجد غرف متاحة حالياً</p>
                    @endif
                </div>
                @php
                    $trNights    = max(1, $reservation->nights);
                    $trStayed    = (int) min(max($reservation->check_in_date->diffInDays(today(), false), 0), $trNights);
                    $trRemaining = $trNights - $trStayed;
                    $trOldPpn    = round((float) $reservation->total_amount / $trNights, 2);
                @endphp
                <div id="transferPricePreview" class="hidden bg-blue-50 border border-blue-100 rounded-xl p-4 text-sm text-blue-800 space-y-1">
                    <p>الليالي المتبقية: <strong>{{ $trRemaining }}</strong> ليلة × <strong id="tpNewPrice">0</strong> ر.ي
                       (الليالي الماضية {{ $trStayed }} تبقى بالسعر الحالي {{ number_format($trOldPpn, 0) }} ر.ي)</p>
                    <p>الإجمالي الجديد للحجز: <strong id="tpNewTotal" class="text-blue-900">0</strong> ر.ي
                       <span class="text-xs">(بدلاً من {{ number_format($reservation->total_amount, 0) }} ر.ي)</span></p>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">سبب التغيير</label>
                    <textarea name="notes" rows="2" placeholder="مثال: مشكلة في تكييف الغرفة..."
                              class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-blue-500 outline-none resize-none"></textarea>
                </div>
            </div>
            <div class="flex gap-3 px-6 pb-6">
                <button type="submit" {{ $transferOptions->isEmpty() ? 'disabled' : '' }}
                        class="flex-1 py-3 bg-amber-500 hover:bg-amber-600 text-white rounded-xl text-sm font-bold transition shadow-sm disabled:opacity-50">
                    تأكيد النقل
                </button>
                <button type="button" onclick="document.getElementById('transferRoomModal').classList.add('hidden')"
                        class="flex-1 py-3 border border-gray-300 text-gray-700 rounded-xl text-sm hover:bg-gray-50 transition font-medium">
                    إلغاء
                </button>
            </div>
        </form>
    </div>
</div>

@endcan
@endif

{{-- Cancel Reservation Modal — متاحة أياً كانت حالة الحجز (محجوز/مسجل دخول)، لذا خارج غلاف "مسجل دخول فقط" أعلاه.
     سبب الإلغاء إجباري حتى تبقى بياناته قابلة للمراجعة --}}
@can('checkin.view')
@if(in_array($reservation->status, ['confirmed', 'checked_in']))
<div id="cancelReservationModal" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 p-4"
     onclick="if(event.target===this) this.classList.add('hidden')">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md" onclick="event.stopPropagation()">
        <div class="px-6 py-5 rounded-t-2xl flex items-center justify-between" style="background:#b91c1c;">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-white/20 flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </div>
                <h3 class="font-bold text-white text-lg">إلغاء الحجز</h3>
            </div>
            <button onclick="document.getElementById('cancelReservationModal').classList.add('hidden')"
                    class="w-8 h-8 flex items-center justify-center text-white/70 hover:text-white hover:bg-white/20 rounded-lg transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form method="POST" action="{{ route('reservations.cancel', $reservation) }}">
            @csrf @method('PATCH')
            <div class="p-6 space-y-4">
                <div class="bg-amber-50 border border-amber-100 rounded-xl p-4 text-sm text-amber-800">
                    سيتم إلغاء الحجز وتحرير الغرفة، لكن بيانات النزيل والدفعات وأي متأخرات تبقى محفوظة
                    ويمكن مراجعتها لاحقاً في تقرير أسباب إلغاء الحجوزات.
                </div>
                @if((float) $reservation->balance > 0)
                <div class="bg-red-50 border border-red-100 rounded-xl p-3 text-sm text-red-700 font-semibold">
                    تنبيه: على هذا الحجز متأخرات بقيمة {{ number_format($reservation->balance, 0) }} {{ $reservation->currency_symbol }}
                </div>
                @endif
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">سبب الإلغاء <span class="text-red-500">*</span></label>
                    <textarea name="cancellation_reason" rows="3" required maxlength="500"
                              placeholder="مثال: النزيل غيّر رأيه، خطأ في تسجيل الحجز، ظروف طارئة..."
                              class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-red-400 outline-none resize-none"></textarea>
                </div>
                @if((float) $reservation->paid_amount > 0)
                <div class="bg-blue-50 border border-blue-100 rounded-xl p-4 space-y-3">
                    <p class="text-sm font-semibold text-blue-800">
                        على هذا الحجز مبلغ مدفوع {{ number_format($reservation->paid_amount, 0) }} {{ $reservation->currency_symbol }} — يجب تسجيل استرجاعه
                    </p>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">مبلغ الاسترجاع <span class="text-red-500">*</span></label>
                        <input type="number" name="refund_amount" step="1" min="1" max="{{ $reservation->paid_amount }}"
                               value="{{ $reservation->paid_amount }}" required
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">طريقة الاسترجاع <span class="text-red-500">*</span></label>
                        <select name="refund_method" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option value="cash">نقداً</option>
                            <option value="bank_transfer">تحويل بنكي</option>
                            <option value="pos">POS</option>
                        </select>
                    </div>
                </div>
                @endif
            </div>
            <div class="flex gap-3 px-6 pb-6">
                <button type="submit"
                        class="flex-1 py-3 bg-red-700 hover:bg-red-800 text-white rounded-xl text-sm font-bold transition shadow-sm">
                    تأكيد الإلغاء
                </button>
                <button type="button" onclick="document.getElementById('cancelReservationModal').classList.add('hidden')"
                        class="flex-1 py-3 border border-gray-300 text-gray-700 rounded-xl text-sm hover:bg-gray-50 transition font-medium">
                    تراجع
                </button>
            </div>
        </form>
    </div>
</div>
@endif
@endcan

@push('scripts')
<script>
// معاينة السعر عند تغيير الغرفة: الليالي الماضية بالسعر القديم + المتبقية بسعر الاختيار الجديد
const TR_STAYED    = {{ $trStayed ?? 0 }};
const TR_REMAINING = {{ $trRemaining ?? 0 }};
const TR_OLD_PPN   = {{ $trOldPpn ?? 0 }};
function updateTransferPreview(sel) {
    const box = document.getElementById('transferPricePreview');
    const opt = sel.options[sel.selectedIndex];
    const newPpn = parseFloat(opt?.dataset?.price || 0);
    if (!sel.value || newPpn <= 0) { box.classList.add('hidden'); return; }
    const newTotal = TR_STAYED * TR_OLD_PPN + TR_REMAINING * newPpn;
    document.getElementById('tpNewPrice').textContent = newPpn.toLocaleString('ar-YE');
    document.getElementById('tpNewTotal').textContent = newTotal.toLocaleString('ar-YE');
    box.classList.remove('hidden');
}

function renewForm() {
    return {
        newDate: '',
        extraNights: 0,
        extraAmount: 0,
        newTotal: 0,
        advancePayment: 0,
        remaining: 0,
        pricePerNight: {{ $renewalPrice }},
        currentOut: '{{ $reservation->check_out_date?->format('Y-m-d') ?? '' }}',
        currentTotal: {{ (float) $reservation->total_amount }},
        paidAmount: {{ (float) $reservation->paid_amount }},
        // أقصى تاريخ خروج مسموح به (وصول نزيل قادم) — أو '' إن لا يوجد قيد
        maxOut: '{{ $renewMaxCheckout?->format('Y-m-d') ?? '' }}',

        // أداة: تحويل نص التاريخ (Y-m-d) إلى كائن تاريخ عند منتصف الليل محلياً
        parseDate(s) { const [y, m, d] = s.split('-').map(Number); return new Date(y, m - 1, d); },
        addDays(base, n) {
            const d = this.parseDate(base); d.setDate(d.getDate() + n);
            const p = (x) => String(x).padStart(2, '0');
            return `${d.getFullYear()}-${p(d.getMonth() + 1)}-${p(d.getDate())}`;
        },
        // أقصى عدد ليالٍ إضافية مسموح به وفق قيد النزيل القادم (∞ إن لا قيد)
        maxNights() {
            if (!this.maxOut) return Infinity;
            return Math.max(0, Math.round((this.parseDate(this.maxOut) - this.parseDate(this.currentOut)) / 86400000));
        },

        // المستخدم عدّل عدد الليالي → نحسب تاريخ الخروج الجديد منه (مع احترام الحد الأقصى)
        fromNights() {
            let n = Math.max(0, Math.floor(this.extraNights || 0));
            n = Math.min(n, this.maxNights());
            this.extraNights = n;
            this.newDate = n > 0 ? this.addDays(this.currentOut, n) : '';
            this.calc();
        },
        // المستخدم عدّل تاريخ الخروج → نحسب عدد الليالي منه (مع احترام الحد الأقصى)
        fromDate() {
            if (!this.newDate) { this.extraNights = 0; this.calc(); return; }
            if (this.maxOut && this.newDate > this.maxOut) { this.newDate = this.maxOut; }
            const diff = Math.round((this.parseDate(this.newDate) - this.parseDate(this.currentOut)) / 86400000);
            this.extraNights = Math.max(0, diff);
            this.calc();
        },
        calc() {
            this.extraAmount = this.extraNights * (parseFloat(this.pricePerNight) || 0);
            this.newTotal    = this.currentTotal + this.extraAmount;
            this.remaining   = Math.max(0, this.newTotal - this.paidAmount - (parseFloat(this.advancePayment) || 0));
        },
        formatNum(n) { return (parseFloat(n)||0).toLocaleString('ar-YE'); },
    }
}
</script>
@endpush

@endsection
