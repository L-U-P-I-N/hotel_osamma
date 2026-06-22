@extends('layouts.app')
@section('title', 'كشف حساب النزيل')
@section('page-title', 'كشف حساب النزيل')

@section('content')
<div dir="rtl">

{{-- Header --}}
<div class="flex items-center justify-between mb-5 flex-wrap gap-3">
    <a href="{{ route('reservations.index') }}" class="text-sm text-gray-500 hover:text-gray-700">← الحجوزات</a>
    <a href="{{ route('guests.statement.pdf', $guest) }}" target="_blank"
       class="flex items-center gap-2 px-4 py-2 bg-red-600 text-white rounded-lg text-sm hover:bg-red-700 transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
        طباعة PDF
    </a>
</div>

{{-- Guest Card --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 mb-5">
    <div class="flex items-center gap-4">
        <div class="w-14 h-14 rounded-full flex items-center justify-center text-white text-xl font-bold flex-shrink-0"
             style="background:#0F4C75;">
            {{ mb_substr($guest->full_name, 0, 1) }}
        </div>
        <div class="flex-1">
            <h2 class="text-lg font-bold text-gray-800">{{ $guest->full_name }}</h2>
            <p class="text-sm text-gray-500">{{ $guest->getIdTypeLabel() }}: {{ $guest->id_number }}</p>
            <p class="text-sm text-gray-500">{{ $guest->nationality ?? '—' }}</p>
        </div>
        <div class="grid grid-cols-3 gap-4 text-center">
            <div>
                <p class="text-2xl font-bold text-gray-800">{{ $reservations->count() }}</p>
                <p class="text-xs text-gray-500">إجمالي الحجوزات</p>
            </div>
            <div>
                <p class="text-2xl font-bold text-green-700">{{ number_format($totalPaid, 0) }}</p>
                <p class="text-xs text-gray-500">المدفوع (ر.ي)</p>
            </div>
            <div>
                <p class="text-2xl font-bold {{ $totalBalance > 0 ? 'text-red-600' : 'text-gray-400' }}">{{ number_format($totalBalance, 0) }}</p>
                <p class="text-xs text-gray-500">الرصيد المتبقي (ر.ي)</p>
            </div>
        </div>
    </div>
</div>

@if($totalBalance > 0)
<div class="mb-5 p-4 bg-red-50 border border-red-200 rounded-xl flex items-center gap-3 text-red-800 text-sm">
    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
    <span>يوجد رصيد متبقي غير محصّل بقيمة <strong>{{ number_format($totalBalance, 0) }} ر.ي</strong></span>
</div>
@endif

{{-- Reservations with payments --}}
@foreach($reservations as $res)
@php
    $balance = $res->total_amount - $res->paid_amount;
    $nights  = $res->check_in_date->diffInDays($res->check_out_date);
@endphp
<div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-4">
    {{-- Reservation header --}}
    <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between flex-wrap gap-2">
        <div class="flex items-center gap-3">
            <span class="text-sm font-bold text-gray-700">#{{ $res->id }}</span>
            <span class="text-sm text-gray-500">{{ $res->room->room_number ?? '—' }}</span>
            <span class="text-xs px-2 py-0.5 rounded-full {{ $res->status === 'checked_in' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-600' }}">
                {{ $res->status_label }}
            </span>
        </div>
        <div class="flex items-center gap-4 text-sm text-gray-500">
            <span>{{ $res->check_in_date->format('d/m/Y') }} — {{ $res->check_out_date->format('d/m/Y') }}</span>
            <span class="text-xs text-gray-400">{{ $nights }} ليلة</span>
        </div>
    </div>

    <div class="p-5">
        {{-- Amount summary --}}
        <div class="grid grid-cols-3 gap-4 mb-4">
            <div class="bg-gray-50 rounded-lg p-3 text-center">
                <p class="text-xs text-gray-500 mb-1">إجمالي الحجز</p>
                <p class="font-bold text-gray-800">{{ number_format($res->total_amount, 0) }} ر.ي</p>
            </div>
            <div class="bg-green-50 rounded-lg p-3 text-center">
                <p class="text-xs text-gray-500 mb-1">المدفوع</p>
                <p class="font-bold text-green-700">{{ number_format($res->paid_amount, 0) }} ر.ي</p>
            </div>
            <div class="rounded-lg p-3 text-center {{ $balance > 0 ? 'bg-red-50' : 'bg-gray-50' }}">
                <p class="text-xs text-gray-500 mb-1">المتبقي</p>
                <p class="font-bold {{ $balance > 0 ? 'text-red-600' : 'text-gray-400' }}">{{ number_format($balance, 0) }} ر.ي</p>
            </div>
        </div>

        {{-- Payments list --}}
        @if($res->payments->isNotEmpty())
        <table class="w-full text-sm">
            <thead>
                <tr class="text-xs text-gray-500 border-b border-gray-100">
                    <th class="pb-2 text-right">تاريخ الدفع</th>
                    <th class="pb-2 text-right">طريقة الدفع</th>
                    <th class="pb-2 text-right">المبلغ</th>
                    <th class="pb-2 text-right">الموظف</th>
                    <th class="pb-2"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($res->payments as $pmt)
                <tr>
                    <td class="py-2 text-gray-600">{{ $pmt->payment_date?->format('d/m/Y H:i') ?? '—' }}</td>
                    <td class="py-2">
                        <span class="text-xs px-2 py-0.5 rounded-full bg-blue-50 text-blue-700">
                            {{ match($pmt->method) {
                                'cash'          => 'نقداً',
                                'bank_transfer' => 'تحويل بنكي',
                                'pos'           => 'POS',
                                default         => $pmt->method,
                            } }}
                        </span>
                    </td>
                    <td class="py-2 font-semibold text-gray-800">{{ number_format($pmt->amount, 0) }} ر.ي</td>
                    <td class="py-2 text-gray-500 text-xs">{{ $pmt->receivedBy->name ?? '—' }}</td>
                    <td class="py-2">
                        @can('payments.create')
                        <a href="{{ route('payments.slip', $pmt) }}" target="_blank"
                           class="text-xs px-2 py-1 rounded border border-gray-200 text-gray-500 hover:bg-gray-50">
                            إيصال
                        </a>
                        @endcan
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <p class="text-sm text-gray-400 text-center py-3">لا توجد دفعات مسجلة لهذا الحجز</p>
        @endif
    </div>
</div>
@endforeach

@if($reservations->isEmpty())
<div class="bg-white rounded-xl p-10 text-center text-gray-400">لا توجد حجوزات لهذا النزيل</div>
@endif

</div>
@endsection
