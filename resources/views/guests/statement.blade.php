@extends('layouts.app')
@section('title', 'كشف حساب النزيل')
@section('page-title', 'كشف حساب النزيل')

@section('content')
<div dir="rtl">

{{-- Header --}}
<div class="flex items-center justify-between mb-5 flex-wrap gap-3">
    <a href="{{ route('reservations.expiring') }}" class="text-sm text-gray-500 hover:text-gray-700">← الحجوزات</a>
    <a href="{{ route('guests.statement.pdf', $guest) }}" target="_blank"
       class="flex items-center gap-2 px-4 py-2 bg-red-600 text-white rounded-lg text-sm hover:bg-red-700 transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
        تصدير PDF
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
<div class="mb-5 p-4 bg-red-50 border border-red-200 rounded-xl flex items-center gap-3 text-red-800 text-sm no-print">
    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
    <span>يوجد رصيد متبقي غير محصّل بقيمة <strong>{{ number_format($totalBalance, 0) }} ر.ي</strong></span>
</div>
@endif

{{-- Transaction Timeline --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 mb-5">
    <h3 class="font-bold text-lg text-gray-800 mb-4">سجل جميع العمليات</h3>
    <div class="relative">
        <div class="space-y-3">
            @forelse($allTransactions as $transaction)
            <div class="flex gap-4">
                <div class="flex flex-col items-center">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0
                        {{ $transaction['direction'] === 'out' ? 'bg-green-500' : 'bg-red-500' }}">
                        {{ $transaction['direction'] === 'out' ? '↓' : '↑' }}
                    </div>
                    @if(!$loop->last)
                    <div class="w-0.5 h-12 bg-gray-200 my-2"></div>
                    @endif
                </div>
                <div class="pb-3 flex-1">
                    <div class="flex items-baseline justify-between mb-1">
                        <span class="font-semibold text-gray-700 text-sm">{{ $transaction['description'] }}</span>
                        <span class="font-bold {{ $transaction['direction'] === 'out' ? 'text-green-700' : 'text-red-700' }}">
                            {{ ($transaction['direction'] === 'out' ? '−' : '+') }} {{ number_format($transaction['amount'], 0) }}
                        </span>
                    </div>
                    <div class="flex gap-2 text-xs text-gray-500">
                        <span>{{ $transaction['date']->format('d/m/Y H:i') }}</span>
                        @if($transaction['type'] === 'payment' && $transaction['data']->method)
                        <span>•</span>
                        <span class="px-1.5 py-0.5 rounded-full bg-blue-50 text-blue-600 text-xs">
                            {{ match($transaction['data']->method) {
                                'cash'          => 'نقداً',
                                'bank_transfer' => 'تحويل',
                                'pos'           => 'POS',
                                default         => $transaction['data']->method,
                            } }}
                        </span>
                        @endif
                    </div>
                </div>
            </div>
            @empty
            <p class="text-center text-gray-400 py-4">لا توجد عمليات مسجلة</p>
            @endforelse
        </div>
    </div>
</div>

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
            <span class="text-sm text-gray-500">{{ $res->display_room_number }}</span>
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

<style media="print">
    body {
        margin: 0;
        padding: 1cm;
        font-family: 'Arial', sans-serif;
        background: white;
    }
    .no-print { display: none; }

    .print-header {
        text-align: center;
        margin-bottom: 2cm;
        border-bottom: 2px solid #333;
        padding-bottom: 1cm;
    }

    .print-header h1 {
        margin: 0;
        font-size: 24pt;
    }

    .print-header p {
        margin: 5px 0;
        font-size: 11pt;
    }

    .guest-info {
        margin-bottom: 1cm;
        border: 1px solid #999;
        padding: 0.5cm;
        border-radius: 5px;
    }

    .guest-info-row {
        display: flex;
        justify-content: space-between;
        margin: 5px 0;
        font-size: 11pt;
    }

    .summary-table {
        width: 100%;
        border-collapse: collapse;
        margin: 1cm 0;
    }

    .summary-table td {
        border: 1px solid #999;
        padding: 8px;
        font-size: 11pt;
    }

    .summary-table .label {
        font-weight: bold;
        width: 25%;
    }

    .transactions-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 1cm;
        page-break-inside: avoid;
    }

    .transactions-table th {
        background: #f0f0f0;
        border: 1px solid #999;
        padding: 8px;
        font-weight: bold;
        font-size: 10pt;
        text-align: right;
    }

    .transactions-table td {
        border: 1px solid #ddd;
        padding: 8px;
        font-size: 10pt;
    }

    .page-break {
        page-break-before: always;
        margin-top: 1cm;
    }
</style>
@endsection
