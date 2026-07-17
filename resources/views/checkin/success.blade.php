@extends('layouts.app')
@section('title', 'تم التسجيل')
@section('page-title', 'تم تسجيل الدخول')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 text-center mb-6">
        <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <h1 class="text-2xl font-bold text-gray-800 mb-2">تم تسجيل الدخول بنجاح</h1>
        <p class="text-gray-500">رقم الحجز: <span class="font-bold text-primary-800">#{{ $reservation->id }}</span></p>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-5">
        <h2 class="font-semibold text-gray-700 mb-4">ملخص الحجز</h2>
        <div class="grid grid-cols-2 gap-3 text-sm">
            <div><span class="text-gray-500">النزيل:</span> <span class="font-medium">{{ $reservation->guest?->full_name ?? '—' }}</span></div>
            <div><span class="text-gray-500">الغرفة:</span> <span class="font-medium">{{ $reservation->display_room_number }}</span></div>
            <div><span class="text-gray-500">الدخول:</span> <span class="font-medium">{{ $reservation->check_in_date?->format('d/m/Y') ?? '—' }}</span></div>
            <div><span class="text-gray-500">الخروج:</span> <span class="font-medium">{{ $reservation->check_out_date?->format('d/m/Y') ?? '—' }}</span></div>
            <div><span class="text-gray-500">المرافقون:</span> <span class="font-medium">{{ $reservation->companions->count() }}</span></div>
            <div><span class="text-gray-500">الإجمالي:</span> <span class="font-bold text-primary-800">{{ number_format($reservation->total_amount, 2) }} {{ $reservation->currency_symbol }}</span></div>
        </div>
    </div>

    <div class="flex flex-wrap gap-3 justify-center">
        @can('government.export')
        <a href="{{ route('checkin.exportGov', ['reservation'=>$reservation->id, 'format'=>'pdf']) }}"
           class="flex items-center gap-2 px-5 py-2.5 bg-red-600 text-white rounded-lg text-sm hover:bg-red-700 transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            تصدير PDF للجهات الحكومية
        </a>
        <a href="{{ route('checkin.exportGov', ['reservation'=>$reservation->id, 'format'=>'excel']) }}"
           class="flex items-center gap-2 px-5 py-2.5 bg-green-600 text-white rounded-lg text-sm hover:bg-green-700 transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            تصدير Excel
        </a>
        @endcan
        <a href="{{ route('checkin.create') }}"
           class="flex items-center gap-2 px-5 py-2.5 bg-primary-800 text-white rounded-lg text-sm hover:bg-primary-700 transition">
            حجز جديد
        </a>
        @can('checkin.view')
        <a href="{{ route('reservations.show', $reservation) }}"
           class="flex items-center gap-2 px-5 py-2.5 border border-gray-300 text-gray-700 rounded-lg text-sm hover:bg-gray-50 transition">
            عرض الحجز
        </a>
        @endcan
    </div>
</div>
@endsection
