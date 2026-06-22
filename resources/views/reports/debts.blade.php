@extends('layouts.app')
@section('title', 'تقرير الديون')
@section('page-title', 'تقرير الديون')

@section('content')
<div class="space-y-5">

<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 flex gap-2 justify-end">
    <a href="{{ route('reports.debts.pdf') }}"
       class="flex items-center gap-2 px-4 py-2 bg-red-600 text-white rounded-lg text-sm hover:bg-red-700 transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
        تصدير PDF
    </a>
    <a href="{{ route('reports.debts.excel') }}"
       class="flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg text-sm hover:bg-green-700 transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
        تصدير Excel
    </a>
</div>

<div class="rounded-xl p-5 border" style="background:#fef2f2; border-color:#fecaca;">
    <div class="text-xs text-red-500">إجمالي المبالغ غير المحصّلة</div>
    <div class="text-3xl font-bold text-red-700 mt-1">{{ number_format($totalDebt, 2) }} <span class="text-lg font-normal">ر.ي</span></div>
    <div class="text-xs text-red-400 mt-1">{{ $reservations->count() }} حجز به رصيد متبقي</div>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">النزيل</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الغرفة</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الحالة</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الإجمالي</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">المدفوع</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">المتبقي</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">تاريخ الدخول</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">تاريخ الخروج</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($reservations as $res)
                @php $balance = $res->total_amount - $res->paid_amount; @endphp
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-medium text-gray-800">{{ $res->guest->full_name ?? '—' }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $res->display_room_number }}</td>
                    <td class="px-4 py-3">
                        @if($res->status === 'checked_in')
                        <span class="px-2 py-0.5 rounded-full text-xs bg-blue-100 text-blue-700">داخل</span>
                        @else
                        <span class="px-2 py-0.5 rounded-full text-xs bg-gray-100 text-gray-600">خرج</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-gray-700">{{ number_format($res->total_amount, 0) }}</td>
                    <td class="px-4 py-3 text-green-700">{{ number_format($res->paid_amount, 0) }}</td>
                    <td class="px-4 py-3 font-bold text-red-600">{{ number_format($balance, 0) }}</td>
                    <td class="px-4 py-3 text-gray-500 text-xs">
                        {{ $res->check_in_date?->format('d/m/Y') ?? '—' }}
                        @if($res->check_in_time)<br><span class="text-gray-400">{{ $res->check_in_time }}</span>@endif
                    </td>
                    <td class="px-4 py-3 text-gray-500 text-xs">{{ $res->check_out_date?->format('d/m/Y') ?? '—' }}</td>
                    <td class="px-4 py-3">
                        <a href="{{ route('reservations.show', $res->id) }}" class="text-xs px-2 py-1 rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50">تفاصيل</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="9" class="px-4 py-8 text-center text-gray-400">لا توجد ديون مسجلة — ممتاز!</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
</div>
@endsection
