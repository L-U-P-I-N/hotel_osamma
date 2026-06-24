@extends('layouts.app')
@section('title', 'تفاصيل الحجز #' . $reservation->id)
@section('page-title', 'تفاصيل الحجز')
@section('back-url', route('reservations.index'))

@section('content')
@php
    $statusColors = [
        'checked_in'  => ['bg' => 'bg-green-500', 'light' => 'bg-green-50 border-green-200', 'text' => 'text-green-700', 'badge' => 'bg-green-100 text-green-800'],
        'checked_out' => ['bg' => 'bg-gray-400',  'light' => 'bg-gray-50 border-gray-200',  'text' => 'text-gray-600',  'badge' => 'bg-gray-100 text-gray-700'],
    ];
    $sc = $statusColors[$reservation->status] ?? $statusColors['checked_in'];
    $pc = ['unpaid'=>'bg-red-100 text-red-800','partial'=>'bg-amber-100 text-amber-800','paid'=>'bg-green-100 text-green-800','deferred'=>'bg-purple-100 text-purple-800'];
    $pricePerNight = $reservation->nights > 0 ? round($reservation->total_amount / $reservation->nights, 2) : 0;
@endphp

<div class="max-w-5xl mx-auto space-y-5">

{{-- ===== HEADER BAR ===== --}}
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="h-1.5 {{ $sc['bg'] }}"></div>
    <div class="p-5 flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl {{ $sc['bg'] }} flex items-center justify-center shadow-sm">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
            </div>
            <div>
                <p class="text-xs text-gray-400 font-medium">رقم الحجز</p>
                <h2 class="text-xl font-bold text-gray-800">#{{ $reservation->id }}</h2>
            </div>
            <span class="px-3 py-1 rounded-full text-sm font-medium {{ $sc['badge'] }}">
                {{ $reservation->status_label }}
            </span>
            <span class="px-3 py-1 rounded-full text-sm font-medium {{ $pc[$reservation->payment_status] ?? '' }}">
                {{ $reservation->payment_status_label }}
            </span>
        </div>

        {{-- Action Buttons --}}
        <div class="flex items-center gap-2 flex-wrap justify-end">
            {{-- PRIMARY: Checkout --}}
            @can('checkout.process')
            @if($reservation->status === 'checked_in')
            <a href="{{ route('checkout.show', $reservation) }}"
               class="inline-flex items-center gap-1.5 px-6 py-2.5 bg-red-600 text-white text-sm font-semibold rounded-xl hover:bg-red-700 transition shadow-sm">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                تسجيل الخروج
            </a>
            @endif
            @endcan

            {{-- Secondary Actions --}}
            @can('checkin.view')
            @if($reservation->status === 'checked_in')
            <a href="{{ route('reservations.edit', $reservation) }}"
               class="inline-flex items-center gap-1.5 px-4 py-2.5 border border-primary-600 text-primary-700 text-sm rounded-xl hover:bg-primary-50 transition font-medium">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                تعديل
            </a>

            <button onclick="document.getElementById('renewModal').classList.remove('hidden')"
                    class="inline-flex items-center gap-1.5 px-4 py-2.5 bg-blue-600 text-white text-sm rounded-xl hover:bg-blue-700 transition font-medium">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                تجديد
            </button>

            <button onclick="document.getElementById('transferRoomModal').classList.remove('hidden')"
                    class="inline-flex items-center gap-1.5 px-4 py-2.5 bg-amber-500 text-white text-sm rounded-xl hover:bg-amber-600 transition font-medium">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                تغيير الغرفة
            </button>
            @endif
            @endcan

            {{-- Payment Button --}}
            @can('payments.create')
            @if($reservation->balance > 0 && in_array($reservation->status, ['checked_in', 'checked_out']))
            <button onclick="document.getElementById('paymentModal').classList.remove('hidden')"
                    class="inline-flex items-center gap-1.5 px-4 py-2.5 bg-green-600 text-white text-sm rounded-xl hover:bg-green-700 transition font-medium">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                إضافة دفعة
            </button>
            @endif
            @endcan

            {{-- Export Button --}}
            @can('government.export')
            <a href="{{ route('checkin.exportGov', ['reservation'=>$reservation->id,'format'=>'pdf']) }}"
               class="inline-flex items-center gap-1.5 px-4 py-2.5 bg-red-600 text-white text-sm rounded-xl hover:bg-red-700 transition font-medium">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                تصدير PDF
            </a>
            @endcan

            {{-- Destructive Actions: Cancel --}}
            @can('checkin.view')
            @if($reservation->status === 'checked_in')
            <button onclick="deleteModal.open(document.getElementById('cancelForm'), 'هل أنت متأكد من إلغاء هذا الحجز نهائياً؟')"
                    class="inline-flex items-center gap-1.5 px-4 py-2.5 border-2 border-red-300 text-red-600 text-sm rounded-xl hover:bg-red-50 transition font-medium">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                إلغاء الحجز
            </button>
            <form id="cancelForm" method="POST" action="{{ route('reservations.cancel', $reservation) }}" class="hidden">
                @csrf @method('PATCH')
            </form>
            @endif
            @endcan
        </div>
    </div>
</div>

{{-- ===== SUMMARY RIBBON ===== --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-4">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 text-center">
        <p class="text-xs text-gray-400 mb-1">الغرفة</p>
        <p class="text-2xl font-bold text-primary-800">{{ $reservation->display_room_number }}</p>
        <p class="text-xs text-gray-500 mt-0.5">
            {{ $reservation->room?->roomType?->name ?? '' }}
            @if($reservation->suite_booking_type === 'both')
                <span class="text-primary-600">(A+B)</span>
            @endif
        </p>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 text-center">
        <p class="text-xs text-gray-400 mb-1">عدد الليالي</p>
        <p class="text-2xl font-bold text-gray-800">{{ $reservation->nights }}</p>
        <p class="text-xs text-gray-500 mt-0.5">{{ number_format($pricePerNight, 0) }} {{ $reservation->currency_symbol }}/ليلة</p>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 text-center">
        <p class="text-xs text-gray-400 mb-1">إجمالي الحجز</p>
        <p class="text-2xl font-bold text-gray-800">{{ number_format($reservation->total_amount, 0) }}</p>
        <p class="text-xs text-gray-500 mt-0.5">{{ $reservation->currency_symbol }}</p>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 text-center">
        <p class="text-xs text-gray-400 mb-1">المتبقي</p>
        <p class="text-2xl font-bold {{ $reservation->balance > 0 ? 'text-red-600' : 'text-green-600' }}">
            {{ number_format($reservation->balance, 0) }}
        </p>
        <p class="text-xs text-gray-500 mt-0.5">{{ $reservation->currency_symbol }}</p>
    </div>
</div>

{{-- ===== MAIN GRID ===== --}}
<div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

    {{-- LEFT: Guest Info --}}
    <div class="lg:col-span-2 space-y-5">

        {{-- Guest Card --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-50 flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg bg-primary-100 flex items-center justify-center">
                    <svg class="w-4 h-4 text-primary-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                </div>
                <h3 class="font-semibold text-gray-800">بيانات النزيل الرئيسي</h3>
            </div>
            <div class="p-5">
                <div class="flex flex-col md:flex-row gap-5">
                    {{-- Info Fields --}}
                    <div class="flex-1 grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3 text-sm">
                        <div>
                            <p class="text-xs text-gray-400 mb-0.5">الاسم الكامل</p>
                            <p class="font-semibold text-gray-800">{{ $reservation->guest?->full_name ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 mb-0.5">الجنسية</p>
                            <p class="text-gray-700">{{ $reservation->guest?->nationality ?: '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 mb-0.5">المهنة</p>
                            <p class="text-gray-700">{{ $reservation->guest?->occupation ?: '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 mb-0.5">نوع الهوية</p>
                            <p class="text-gray-700">{{ $reservation->guest?->getIdTypeLabel() ?? '—' }}</p>
                        </div>
                        @can('guests.sensitive')
                        <div>
                            <p class="text-xs text-gray-400 mb-0.5">رقم الهوية</p>
                            <p class="font-mono text-gray-800">{{ $reservation->guest?->id_number ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 mb-0.5">رقم الجوال</p>
                            <p class="font-mono text-gray-800">{{ $reservation->guest?->phone ?: '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 mb-0.5">جهة الإصدار</p>
                            <p class="text-gray-700">{{ $reservation->guest?->id_issuer ?: '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 mb-0.5">تاريخ الإصدار</p>
                            <p class="text-gray-700">{{ $reservation->guest?->id_issue_date?->format('d/m/Y') ?: '—' }}</p>
                        </div>
                        @endcan
                        <div>
                            <p class="text-xs text-gray-400 mb-0.5">جهة القدوم</p>
                            <p class="text-gray-700">{{ $reservation->origin ?: '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 mb-0.5">الغرض من الزيارة</p>
                            <p class="text-gray-700">{{ $reservation->purpose ?: '—' }}</p>
                        </div>
                    </div>

                    {{-- ID Image --}}
                    @can('guests.sensitive')
                    @if($reservation->guest?->id_image_path)
                    <div class="md:w-48 shrink-0" id="guestIdImageBox">
                        <p class="text-xs text-gray-400 mb-2 text-center">صورة الهوية</p>
                        @php
                            $ext = strtolower(pathinfo($reservation->guest->id_image_path, PATHINFO_EXTENSION));
                        @endphp
                        @if($ext === 'pdf')
                        <a href="{{ route('guests.idImage', $reservation->guest) }}" target="_blank"
                           class="flex flex-col items-center justify-center w-full h-36 border-2 border-dashed border-gray-200 rounded-xl hover:border-primary-400 transition bg-red-50 group">
                            <svg class="w-10 h-10 text-red-400 group-hover:text-red-500" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8l-6-6zm-1 1.5L18.5 9H13V3.5zM8 17h8v-1H8v1zm0-3h8v-1H8v1zm0-3h5v-1H8v1z"/>
                            </svg>
                            <span class="text-xs text-red-500 mt-2 font-medium">ملف PDF</span>
                            <span class="text-xs text-gray-400">اضغط للعرض</span>
                        </a>
                        @else
                        <a href="{{ route('guests.idImage', $reservation->guest) }}" target="_blank">
                            <img src="{{ route('guests.idImage', $reservation->guest) }}"
                                 alt="هوية النزيل"
                                 class="w-full h-36 object-cover rounded-xl border border-gray-200 hover:opacity-90 transition cursor-zoom-in shadow-sm"
                                 onerror="document.getElementById('guestIdImageBox').innerHTML='<div class=\'flex flex-col items-center justify-center h-36 border-2 border-dashed border-gray-200 rounded-xl bg-gray-50 text-gray-400 text-xs text-center p-3\'><svg class=\'w-8 h-8 mb-2\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z\'></path></svg>الصورة غير متاحة</div>'">
                        </a>
                        @endif
                    </div>
                    @endif
                    @endcan
                </div>

                @if($reservation->notes)
                <div class="mt-4 p-3 bg-amber-50 border border-amber-100 rounded-xl text-sm text-amber-800">
                    <span class="font-medium text-xs text-amber-600 block mb-0.5">ملاحظات</span>
                    {{ $reservation->notes }}
                </div>
                @endif
            </div>
        </div>

        {{-- Companions --}}
        @if($reservation->companions->count() > 0)
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-50 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-violet-100 flex items-center justify-center">
                        <svg class="w-4 h-4 text-violet-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                    <h3 class="font-semibold text-gray-800">المرافقون</h3>
                </div>
                <span class="px-2.5 py-0.5 bg-violet-100 text-violet-700 text-xs font-semibold rounded-full">
                    {{ $reservation->companions->count() }}
                </span>
            </div>
            <div class="divide-y divide-gray-50">
                @foreach($reservation->companions as $idx => $c)
                <div class="p-5">
                    <div class="flex flex-col md:flex-row gap-5">
                        {{-- Companion Info --}}
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-3">
                                <span class="w-6 h-6 rounded-full bg-violet-100 text-violet-700 text-xs font-bold flex items-center justify-center">
                                    {{ $idx + 1 }}
                                </span>
                                <span class="font-semibold text-gray-800">{{ $c->full_name }}</span>
                                <span class="px-2 py-0.5 bg-violet-50 text-violet-600 text-xs rounded-full">
                                    {{ $c->getRelationshipLabel() }}
                                </span>
                            </div>
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-x-6 gap-y-2 text-sm">
                                <div>
                                    <p class="text-xs text-gray-400 mb-0.5">الجنسية</p>
                                    <p class="text-gray-700">{{ $c->nationality ?: '—' }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-400 mb-0.5">نوع الهوية</p>
                                    <p class="text-gray-700">{{ $c->getIdTypeLabel() ?? '—' }}</p>
                                </div>
                                @can('guests.sensitive')
                                <div>
                                    <p class="text-xs text-gray-400 mb-0.5">رقم الهوية</p>
                                    <p class="font-mono text-gray-800">{{ $c->id_number ?? '—' }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-400 mb-0.5">جهة الإصدار</p>
                                    <p class="text-gray-700">{{ $c->id_issuer ?: '—' }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-400 mb-0.5">تاريخ الإصدار</p>
                                    <p class="text-gray-700">{{ $c->id_issue_date?->format('d/m/Y') ?: '—' }}</p>
                                </div>
                                @endcan
                            </div>
                        </div>

                        {{-- Companion Images --}}
                        @can('guests.sensitive')
                        <div class="flex gap-3 md:flex-col md:w-36 shrink-0">
                            @if($c->id_image_path)
                            <div class="flex-1 md:flex-none">
                                <p class="text-xs text-gray-400 mb-1.5 text-center">صورة الهوية</p>
                                @php $cExt = strtolower(pathinfo($c->id_image_path, PATHINFO_EXTENSION)); @endphp
                                @if($cExt === 'pdf')
                                <a href="{{ route('companions.idImage', $c) }}" target="_blank"
                                   class="flex flex-col items-center justify-center w-full h-24 border-2 border-dashed border-gray-200 rounded-xl hover:border-primary-400 transition bg-red-50 group">
                                    <svg class="w-7 h-7 text-red-400" fill="currentColor" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8l-6-6zm-1 1.5L18.5 9H13V3.5z"/></svg>
                                    <span class="text-xs text-red-400 mt-1">PDF</span>
                                </a>
                                @else
                                <a href="{{ route('companions.idImage', $c) }}" target="_blank">
                                    <img src="{{ route('companions.idImage', $c) }}"
                                         alt="هوية المرافق"
                                         class="w-full h-24 object-cover rounded-xl border border-gray-200 hover:opacity-90 transition cursor-zoom-in shadow-sm">
                                </a>
                                @endif
                            </div>
                            @endif
                            @if($c->marriage_doc_path)
                            <div class="flex-1 md:flex-none">
                                <p class="text-xs text-gray-400 mb-1.5 text-center">عقد الزواج</p>
                                @php $mExt = strtolower(pathinfo($c->marriage_doc_path, PATHINFO_EXTENSION)); @endphp
                                @if($mExt === 'pdf')
                                <a href="{{ route('companions.marriageDoc', $c) }}" target="_blank"
                                   class="flex flex-col items-center justify-center w-full h-24 border-2 border-dashed border-gray-200 rounded-xl hover:border-pink-400 transition bg-pink-50 group">
                                    <svg class="w-7 h-7 text-pink-400" fill="currentColor" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8l-6-6zm-1 1.5L18.5 9H13V3.5z"/></svg>
                                    <span class="text-xs text-pink-400 mt-1">PDF</span>
                                </a>
                                @else
                                <a href="{{ route('companions.marriageDoc', $c) }}" target="_blank">
                                    <img src="{{ route('companions.marriageDoc', $c) }}"
                                         alt="عقد الزواج"
                                         class="w-full h-24 object-cover rounded-xl border border-gray-200 hover:opacity-90 transition cursor-zoom-in shadow-sm">
                                </a>
                                @endif
                            </div>
                            @endif
                        </div>
                        @endcan
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Payments History --}}
        @if($reservation->payments->count() > 0)
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-50 flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg bg-green-100 flex items-center justify-center">
                    <svg class="w-4 h-4 text-green-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
                <h3 class="font-semibold text-gray-800">سجل المدفوعات</h3>
            </div>
            {{-- Desktop Table View --}}
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 text-xs text-gray-500 font-medium">
                            <th class="px-4 py-3 text-right">التاريخ</th>
                            <th class="px-4 py-3 text-right">المبلغ</th>
                            <th class="px-4 py-3 text-right">الطريقة</th>
                            <th class="px-4 py-3 text-right">سبب الدفع</th>
                            <th class="px-4 py-3 text-right">ملاحظة</th>
                            <th class="px-4 py-3 text-right">استلم بواسطة</th>
                            <th class="px-4 py-3 text-right">السند</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($reservation->payments as $p)
                        @php
                            $typeLabel = match($p->type) {
                                'reservation'  => ['label' => 'دفعة حجز',   'class' => 'bg-blue-50 text-blue-700'],
                                'renewal'      => ['label' => 'دفعة تجديد', 'class' => 'bg-amber-50 text-amber-700'],
                                'compensation' => ['label' => 'تعويض أضرار','class' => 'bg-red-50 text-red-700'],
                                'extra_service'=> ['label' => 'خدمة إضافية','class' => 'bg-purple-50 text-purple-700'],
                                default        => ['label' => $p->type,      'class' => 'bg-gray-50 text-gray-600'],
                            };
                        @endphp
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-4 py-3 text-gray-600 whitespace-nowrap">{{ $p->payment_date->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-3 font-bold text-green-700 whitespace-nowrap">
                                {{ number_format($p->amount, 2) }}
                                <span class="text-xs font-normal text-gray-400">ر.ي</span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium
                                    {{ $p->method === 'cash' ? 'bg-green-50 text-green-700' : ($p->method === 'pos' ? 'bg-blue-50 text-blue-700' : 'bg-purple-50 text-purple-700') }}">
                                    {{ match($p->method) { 'cash'=>'نقدي', 'pos'=>'POS', 'bank_transfer'=>'تحويل', default=>$p->method } }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $typeLabel['class'] }}">
                                    {{ $typeLabel['label'] }}
                                </span>
                            </td>
                            <td class="px-4 py-3 max-w-[180px]">
                                @if($p->notes)
                                <span class="text-xs bg-blue-50 text-blue-700 rounded px-1.5 py-0.5">💱 {{ $p->notes }}</span>
                                @else
                                <span class="text-gray-300 text-xs">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-600">{{ $p->receivedBy?->name ?? '—' }}</td>
                            <td class="px-4 py-3">
                                @if($p->bank_receipt_path)
                                @can('payments.bank_receipt')
                                <a href="{{ route('payments.receipt', ['file' => $p->bank_receipt_path]) }}" target="_blank"
                                   class="text-primary-600 hover:underline text-xs font-medium">عرض</a>
                                @endcan
                                @else
                                <span class="text-gray-300 text-xs">—</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Mobile Card View --}}
            <div class="md:hidden space-y-3 p-4">
                @foreach($reservation->payments as $p)
                @php
                    $typeLabel = match($p->type) {
                        'reservation'  => ['label' => 'دفعة حجز',   'class' => 'bg-blue-50 text-blue-700'],
                        'renewal'      => ['label' => 'دفعة تجديد', 'class' => 'bg-amber-50 text-amber-700'],
                        'compensation' => ['label' => 'تعويض أضرار','class' => 'bg-red-50 text-red-700'],
                        'extra_service'=> ['label' => 'خدمة إضافية','class' => 'bg-purple-50 text-purple-700'],
                        default        => ['label' => $p->type,      'class' => 'bg-gray-50 text-gray-600'],
                    };
                @endphp
                <div class="border border-gray-200 rounded-lg p-3 space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-gray-400">{{ $p->payment_date->format('d/m/Y H:i') }}</span>
                        <span class="font-bold text-green-700">{{ number_format($p->amount, 2) }} ر.ي</span>
                    </div>
                    <div class="flex items-center justify-between gap-2">
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $p->method === 'cash' ? 'bg-green-50 text-green-700' : ($p->method === 'pos' ? 'bg-blue-50 text-blue-700' : 'bg-purple-50 text-purple-700') }}">
                            {{ match($p->method) { 'cash'=>'نقدي', 'pos'=>'POS', 'bank_transfer'=>'تحويل', default=>$p->method } }}
                        </span>
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $typeLabel['class'] }}">
                            {{ $typeLabel['label'] }}
                        </span>
                    </div>
                    <div class="text-xs text-gray-600">من: {{ $p->receivedBy?->name ?? '—' }}</div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Extra Charges --}}
        @if($reservation->extraCharges->count() > 0)
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-50 flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg bg-red-100 flex items-center justify-center">
                    <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <h3 class="font-semibold text-gray-800">الرسوم الإضافية</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 text-xs text-gray-500 font-medium">
                            <th class="px-4 py-3 text-right">التاريخ</th>
                            <th class="px-4 py-3 text-right">النوع</th>
                            <th class="px-4 py-3 text-right">الوصف</th>
                            <th class="px-4 py-3 text-right">المبلغ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($reservation->extraCharges as $charge)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-4 py-3 text-gray-600">{{ $charge->charge_date->format('d/m/Y') }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $charge->type }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $charge->description ?: '—' }}</td>
                            <td class="px-4 py-3 font-medium text-red-600">
                                {{ number_format($charge->amount, 0) }} {{ $reservation->currency_symbol }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        {{-- Room Inspection (shown after checkout) --}}
        @if($reservation->status === 'checked_out' && $reservation->roomInspections->count() > 0)
        @php $insp = $reservation->roomInspections->first(); @endphp
        <div class="bg-white rounded-2xl shadow-sm border {{ $insp->has_damage ? 'border-red-200' : 'border-green-100' }} overflow-hidden">
            <div class="px-5 py-4 border-b {{ $insp->has_damage ? 'border-red-100' : 'border-green-50' }} flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg {{ $insp->has_damage ? 'bg-red-100' : 'bg-green-100' }} flex items-center justify-center">
                    @if($insp->has_damage)
                    <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    @else
                    <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    @endif
                </div>
                <h3 class="font-semibold text-gray-800">تقرير فحص الغرفة</h3>
                <span class="mr-auto text-xs text-gray-400">{{ $insp->inspection_date?->format('d/m/Y H:i') }}</span>
            </div>
            <div class="p-5 space-y-3 text-sm">
                @if($insp->has_damage)
                <div class="flex items-center gap-2 text-red-700 font-medium">
                    <span class="w-2 h-2 rounded-full bg-red-500"></span>
                    تم تسجيل أضرار في الغرفة
                </div>
                @if($insp->damage_description)
                <p class="text-gray-600 bg-red-50 rounded-xl p-3 text-xs">{{ $insp->damage_description }}</p>
                @endif
                @if($insp->compensation_amount > 0)
                <div class="flex justify-between items-center bg-orange-50 rounded-xl p-3">
                    <span class="text-orange-800 text-xs font-medium">مبلغ التعويض</span>
                    <span class="font-bold text-orange-700">{{ number_format($insp->compensation_amount, 0) }} {{ $reservation->currency_symbol }}</span>
                </div>
                <div class="flex items-center gap-1.5 text-xs">
                    <span class="w-2 h-2 rounded-full {{ $insp->compensation_status === 'paid' ? 'bg-green-500' : 'bg-amber-500' }}"></span>
                    <span class="{{ $insp->compensation_status === 'paid' ? 'text-green-700' : 'text-amber-700' }}">
                        {{ $insp->compensation_status === 'paid' ? 'التعويض مدفوع' : 'التعويض معلق' }}
                    </span>
                </div>
                @endif
                @if($insp->images->count() > 0)
                <div>
                    <p class="text-xs text-gray-400 mb-2">صور الأضرار ({{ $insp->images->count() }})</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach($insp->images as $img)
                        <a href="{{ Storage::disk('private')->url($img->image_path) }}" target="_blank"
                           class="w-16 h-16 rounded-lg overflow-hidden border border-gray-200 block">
                            <img src="{{ asset('storage/' . $img->image_path) }}" alt="صورة ضرر"
                                 class="w-full h-full object-cover" onerror="this.parentElement.style.display='none'">
                        </a>
                        @endforeach
                    </div>
                </div>
                @endif
                @else
                <div class="flex items-center gap-2 text-green-700 font-medium">
                    <span class="w-2 h-2 rounded-full bg-green-500"></span>
                    الغرفة بحالة جيدة — لا توجد أضرار
                </div>
                @endif
            </div>
        </div>
        @endif

    </div>

    {{-- RIGHT: Reservation Details + Financial --}}
    <div class="space-y-5">

        {{-- Reservation Dates Card --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-50 flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg bg-blue-100 flex items-center justify-center">
                    <svg class="w-4 h-4 text-blue-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <h3 class="font-semibold text-gray-800">تواريخ الحجز</h3>
            </div>
            <div class="p-5 space-y-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-green-100 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400">تاريخ الدخول</p>
                        <p class="font-semibold text-gray-800">{{ $reservation->check_in_date?->format('d/m/Y') ?? '—' }}</p>
                        @if($reservation->check_in_time)
                        <p class="text-xs text-gray-500">{{ $reservation->check_in_time }}</p>
                        @endif
                    </div>
                </div>
                <div class="border-r-2 border-dashed border-gray-200 h-4 mr-5"></div>
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-red-100 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400">تاريخ الخروج</p>
                        <p class="font-semibold text-gray-800">{{ $reservation->check_out_date?->format('d/m/Y') ?? '—' }}</p>
                        @if($reservation->status === 'checked_in' && $reservation->check_out_date?->isPast())
                        <span class="text-xs text-red-500 font-medium">متأخر!</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Financial Card --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-50 flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg bg-amber-100 flex items-center justify-center">
                    <svg class="w-4 h-4 text-amber-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h3 class="font-semibold text-gray-800">الملخص المالي</h3>
            </div>
            <div class="p-5 space-y-3 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-500">سعر الليلة</span>
                    <span class="font-medium">{{ number_format($pricePerNight, 0) }} {{ $reservation->currency_symbol }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">عدد الليالي</span>
                    <span class="font-medium">{{ $reservation->nights }}</span>
                </div>
                @if($reservation->extraCharges->count() > 0)
                <div class="flex justify-between">
                    <span class="text-gray-500">رسوم إضافية</span>
                    <span class="font-medium text-red-600">+{{ number_format($reservation->extraCharges->sum('amount'), 0) }} {{ $reservation->currency_symbol }}</span>
                </div>
                @endif
                <div class="border-t border-gray-100 pt-3 flex justify-between">
                    <span class="text-gray-700 font-medium">الإجمالي</span>
                    <span class="font-bold text-gray-800">{{ number_format($reservation->total_amount, 2) }} {{ $reservation->currency_symbol }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">المدفوع</span>
                    <span class="font-medium text-green-600">{{ number_format($reservation->paid_amount, 2) }} {{ $reservation->currency_symbol }}</span>
                </div>
                <div class="flex justify-between bg-gray-50 rounded-xl p-3 mt-1">
                    <span class="font-semibold {{ $reservation->balance > 0 ? 'text-red-700' : 'text-green-700' }}">
                        {{ $reservation->balance > 0 ? 'المتبقي' : 'مكتمل الدفع' }}
                    </span>
                    <span class="font-bold text-lg {{ $reservation->balance > 0 ? 'text-red-600' : 'text-green-600' }}">
                        {{ number_format(abs($reservation->balance), 2) }} {{ $reservation->currency_symbol }}
                    </span>
                </div>
            </div>
        </div>

        {{-- Meta Info Card --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 space-y-3 text-sm">
            <div class="flex justify-between">
                <span class="text-gray-400">سُجِّل بواسطة</span>
                <span class="text-gray-700 font-medium">{{ $reservation->createdBy?->name ?? '—' }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-400">تاريخ الإنشاء</span>
                <span class="text-gray-700">{{ $reservation->created_at?->format('d/m/Y H:i') ?? '—' }}</span>
            </div>
            @if($reservation->adminApproval)
            <div class="flex justify-between">
                <span class="text-gray-400">اعتمد الآجل</span>
                <span class="text-gray-700">{{ $reservation->adminApproval->name }}</span>
            </div>
            @endif
        </div>

    </div>
</div>

{{-- ===== ADD PAYMENT MODAL ===== --}}
@can('payments.create')
@if($reservation->balance > 0)
<div id="paymentModal"
     class="{{ $errors->any() ? '' : 'hidden' }} fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 p-4"
     onclick="if(event.target===this) this.classList.add('hidden')">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg" onclick="event.stopPropagation()">
        <div class="p-5 border-b border-gray-100 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg bg-green-100 flex items-center justify-center">
                    <svg class="w-4 h-4 text-green-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                </div>
                <h3 class="font-bold text-gray-800 text-lg">إضافة دفعة</h3>
            </div>
            <button onclick="document.getElementById('paymentModal').classList.add('hidden')"
                    class="w-8 h-8 flex items-center justify-center text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form method="POST" action="{{ route('payments.store') }}" enctype="multipart/form-data"
              class="p-5 space-y-4" x-data="{ payMethod: '{{ old('method', 'cash') }}' }">
            @csrf
            <input type="hidden" name="reservation_id" value="{{ $reservation->id }}">

            {{-- Balance summary --}}
            <div class="bg-red-50 border border-red-200 rounded-xl p-3 flex items-center justify-between text-sm">
                <span class="text-red-700 font-medium">الرصيد المتبقي</span>
                <span class="font-bold text-red-800 text-base">{{ number_format($reservation->balance, 2) }} {{ $reservation->currency_symbol }}</span>
            </div>

            @if($errors->any())
            <div class="bg-red-50 border border-red-200 rounded-xl p-3 text-sm text-red-700">
                @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
            </div>
            @endif

            <input type="hidden" name="currency" value="YER">
            <div class="grid grid-cols-1 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">المبلغ <span class="text-red-500">*</span></label>
                    <input type="number" name="amount" step="0.01" min="0.01"
                           max="{{ $reservation->balance }}"
                           value="{{ old('amount') }}"
                           placeholder="{{ number_format($reservation->balance, 2) }}" required
                           class="w-full border {{ $errors->has('amount') ? 'border-red-400' : 'border-gray-300' }} rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 outline-none">
                    <p class="text-xs text-gray-400 mt-1">الحد الأقصى: {{ number_format($reservation->balance, 2) }} {{ $reservation->currency_symbol }}</p>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">ملاحظة (اختياري)</label>
                    <input type="text" name="notes" value="{{ old('notes') }}"
                           placeholder="مثال: دفع 100 ر.س بسعر صرف 400 = 40,000 ر.ي"
                           class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 outline-none">
                </div>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1.5">طريقة الدفع</label>
                <select name="method" x-model="payMethod"
                        class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 outline-none">
                    <option value="cash">نقدي</option>
                    <option value="pos">شبكة POS</option>
                    <option value="bank_transfer">تحويل بنكي</option>
                </select>
            </div>

            <div x-show="payMethod === 'bank_transfer'" class="bg-blue-50 border border-blue-200 rounded-xl p-4 space-y-3">
                <p class="text-xs text-blue-700 font-medium">يجب تقديم واحد على الأقل: صورة السند أو رقم المرجع</p>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">صورة سند التحويل</label>
                    <input type="file" name="bank_receipt" accept="image/*,.pdf"
                           class="w-full text-sm text-gray-600 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:bg-blue-100 file:text-blue-700 hover:file:bg-blue-200">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">رقم مرجع التحويل</label>
                    <input type="text" name="bank_transfer_ref" value="{{ old('bank_transfer_ref') }}"
                           placeholder="مثال: TRF-20240101-001"
                           class="w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 outline-none">
                </div>
            </div>

            <div class="flex gap-3 pt-1">
                <button type="submit"
                        class="flex-1 py-2.5 bg-green-600 text-white rounded-xl font-semibold text-sm hover:bg-green-700 transition">
                    تسجيل الدفعة
                </button>
                <button type="button" onclick="document.getElementById('paymentModal').classList.add('hidden')"
                        class="px-6 py-2.5 border border-gray-300 text-gray-700 rounded-xl text-sm hover:bg-gray-50 transition">
                    إلغاء
                </button>
            </div>
        </form>
    </div>
</div>
@endif
@endcan

{{-- ===== BACK LINK ===== --}}
<div>
    <a href="{{ route('reservations.index') }}"
       class="inline-flex items-center gap-1.5 px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded-xl hover:bg-gray-50 transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        العودة للقائمة
    </a>
</div>

</div>

{{-- ===== MODALS ===== --}}
@if($reservation->status === 'checked_in')
@can('checkin.view')

{{-- Renewal Modal --}}
<div id="renewModal" class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 p-4"
     onclick="if(event.target===this) this.classList.add('hidden')">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg" onclick="event.stopPropagation()">
        <div class="p-5 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-bold text-gray-800 text-lg">تجديد الإقامة</h3>
            <button onclick="document.getElementById('renewModal').classList.add('hidden')"
                    class="w-8 h-8 flex items-center justify-center text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form method="POST" action="{{ route('reservations.renew', $reservation) }}"
              x-data="renewForm()" class="p-5 space-y-4">
            @csrf
            <div class="grid grid-cols-2 gap-3 bg-gray-50 rounded-xl p-4 text-sm">
                <div><span class="text-gray-400 text-xs block mb-0.5">تاريخ الدخول</span><strong>{{ $reservation->check_in_date?->format('d/m/Y') ?? '—' }}</strong></div>
                <div><span class="text-gray-400 text-xs block mb-0.5">الخروج الحالي</span><strong>{{ $reservation->check_out_date?->format('d/m/Y') ?? '—' }}</strong></div>
                <div><span class="text-gray-400 text-xs block mb-0.5">سعر الليلة</span><strong>{{ number_format($pricePerNight, 0) }} {{ $reservation->currency_symbol }}</strong></div>
                <div><span class="text-gray-400 text-xs block mb-0.5">الرصيد المتبقي</span><strong class="{{ $reservation->balance > 0 ? 'text-red-600' : 'text-green-600' }}">{{ number_format($reservation->balance, 0) }} ر.ي</strong></div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">تاريخ الخروج الجديد <span class="text-red-500">*</span></label>
                <input type="date" name="new_check_out_date" x-model="newDate"
                       min="{{ $reservation->check_out_date?->addDay()->format('Y-m-d') ?? '' }}"
                       @change="calcExtra()" required
                       class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 outline-none">
            </div>
            <div x-show="extraNights > 0" class="grid grid-cols-2 gap-3">
                <div class="bg-blue-50 rounded-xl p-3 text-center">
                    <div class="text-xl font-bold text-blue-700" x-text="extraNights"></div>
                    <div class="text-xs text-blue-500 mt-0.5">ليالٍ إضافية</div>
                </div>
                <div class="bg-green-50 rounded-xl p-3 text-center">
                    <div class="text-lg font-bold text-green-700" x-text="formatNum(extraAmount)"></div>
                    <div class="text-xs text-green-500 mt-0.5">مبلغ إضافي</div>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">دفعة مقدمة (اختياري)</label>
                <div class="grid grid-cols-2 gap-3">
                    <input type="number" name="advance_payment" min="0" step="0.01" placeholder="0"
                           class="border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 outline-none">
                    <select name="payment_method" class="border border-gray-300 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 outline-none">
                        <option value="cash">نقدي</option>
                        <option value="pos">شبكة POS</option>
                        <option value="bank_transfer">تحويل بنكي</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">ملاحظة الدفع (اختياري)</label>
                <input type="text" name="payment_notes" placeholder="مثال: دفع 100 ر.س بسعر صرف 400 = 40,000 ر.ي"
                       class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">ملاحظات التجديد</label>
                <input type="text" name="notes" placeholder="سبب التجديد..."
                       class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 outline-none">
            </div>
            <div class="flex gap-3 pt-1">
                <button type="submit" :disabled="extraNights <= 0"
                        class="flex-1 py-2.5 bg-blue-600 text-white rounded-xl font-semibold text-sm hover:bg-blue-700 transition disabled:opacity-40">
                    تأكيد التجديد
                </button>
                <button type="button" onclick="document.getElementById('renewModal').classList.add('hidden')"
                        class="px-6 py-2.5 border border-gray-300 text-gray-700 rounded-xl text-sm hover:bg-gray-50 transition">
                    إلغاء
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Transfer Room Modal --}}
<div id="transferRoomModal" class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 p-4"
     onclick="if(event.target===this) this.classList.add('hidden')">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between p-5 border-b border-gray-100">
            <h3 class="text-lg font-bold text-gray-800">تغيير الغرفة</h3>
            <button onclick="document.getElementById('transferRoomModal').classList.add('hidden')"
                    class="w-8 h-8 flex items-center justify-center text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form method="POST" action="{{ route('reservations.transferRoom', $reservation) }}">
            @csrf
            <div class="p-5 space-y-4">
                <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 text-sm text-amber-800">
                    <p class="font-medium">الغرفة الحالية: <span class="font-bold text-amber-900">{{ $reservation->display_room_number }}</span></p>
                    <p class="text-xs mt-1 text-amber-600">ستنتقل الغرفة الحالية إلى وضع <strong>تحت الفحص</strong> بعد النقل</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">الغرفة الجديدة <span class="text-red-500">*</span></label>
                    <select name="new_room_id" required
                            class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 outline-none">
                        <option value="">اختر غرفة متاحة...</option>
                        @foreach($availableRooms as $room)
                        <option value="{{ $room->id }}">
                            غرفة {{ $room->room_number }} — الطابق {{ $room->floor }}
                            @if($room->roomType) ({{ $room->roomType->name }}) @endif
                        </option>
                        @endforeach
                    </select>
                    @if($availableRooms->isEmpty())
                    <p class="text-xs text-red-500 mt-1">لا توجد غرف متاحة حالياً</p>
                    @endif
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">سبب التغيير</label>
                    <textarea name="notes" rows="2" placeholder="مثال: مشكلة في تكييف الغرفة..."
                              class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 outline-none resize-none"></textarea>
                </div>
            </div>
            <div class="flex gap-3 px-5 pb-5">
                <button type="submit" {{ $availableRooms->isEmpty() ? 'disabled' : '' }}
                        class="flex-1 py-2.5 bg-amber-500 text-white rounded-xl text-sm font-semibold hover:bg-amber-600 transition disabled:opacity-50">
                    تأكيد النقل
                </button>
                <button type="button" onclick="document.getElementById('transferRoomModal').classList.add('hidden')"
                        class="flex-1 py-2.5 border border-gray-300 text-gray-700 rounded-xl text-sm hover:bg-gray-50 transition">
                    إلغاء
                </button>
            </div>
        </form>
    </div>
</div>

@endcan
@endif

@push('scripts')
<script>
function renewForm() {
    return {
        newDate: '',
        extraNights: 0,
        extraAmount: 0,
        pricePerNight: {{ $pricePerNight }},
        currentOut: '{{ $reservation->check_out_date?->format('Y-m-d') ?? '' }}',
        calcExtra() {
            if (!this.newDate) return;
            const d1 = new Date(this.currentOut), d2 = new Date(this.newDate);
            this.extraNights = Math.max(0, Math.floor((d2 - d1) / 86400000));
            this.extraAmount = this.extraNights * this.pricePerNight;
        },
        formatNum(n) { return (parseFloat(n)||0).toLocaleString('ar-YE'); },
    }
}
</script>
@endpush

@endsection
