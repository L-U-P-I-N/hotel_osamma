@extends('layouts.app')
@section('title', 'تقرير أعمار الديون')
@section('page-title', 'تقرير أعمار الديون')

@section('content')
<div dir="rtl">

{{-- Summary Cards --}}
<div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-5">
    <div class="bg-white rounded-xl shadow-sm border border-red-100 p-4 text-center">
        <p class="text-xs text-gray-500 mb-1">إجمالي الديون</p>
        <p class="text-xl font-bold text-red-700">{{ number_format($totalDebt, 0) }}</p>
        <p class="text-xs text-gray-400">ر.ي</p>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-yellow-100 p-4 text-center">
        <p class="text-xs text-gray-500 mb-1">جارية (≤30 يوم)</p>
        <p class="text-xl font-bold text-yellow-600">{{ number_format($buckets['current']['total'], 0) }}</p>
        <p class="text-xs text-gray-400">{{ $buckets['current']['count'] }} حجز</p>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-orange-100 p-4 text-center">
        <p class="text-xs text-gray-500 mb-1">31–60 يوم</p>
        <p class="text-xl font-bold text-orange-600">{{ number_format($buckets['30_60']['total'], 0) }}</p>
        <p class="text-xs text-gray-400">{{ $buckets['30_60']['count'] }} حجز</p>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-red-100 p-4 text-center">
        <p class="text-xs text-gray-500 mb-1">61–90 يوم</p>
        <p class="text-xl font-bold text-red-600">{{ number_format($buckets['60_90']['total'], 0) }}</p>
        <p class="text-xs text-gray-400">{{ $buckets['60_90']['count'] }} حجز</p>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-red-200 p-4 text-center">
        <p class="text-xs text-gray-500 mb-1">+90 يوم</p>
        <p class="text-xl font-bold text-red-800">{{ number_format($buckets['over_90']['total'], 0) }}</p>
        <p class="text-xs text-gray-400">{{ $buckets['over_90']['count'] }} حجز</p>
    </div>
</div>

{{-- Detailed table --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-100">
    <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
        <h3 class="font-semibold text-gray-700 text-sm">تفاصيل الديون مصنّفة حسب العمر</h3>
        <span class="text-xs text-gray-400">اليوم: {{ now()->format('d/m/Y') }}</span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">النزيل</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الغرفة</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">تاريخ الخروج</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">أيام منذ الخروج</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الإجمالي</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">المدفوع</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">المتبقي</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">التصنيف</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($reservations as $res)
                @php
                    $balance  = $res->total_amount - $res->paid_amount;
                    $refDate  = $res->actual_check_out ?? $res->check_out_date;
                    $daysAgo  = $refDate ? now()->startOfDay()->diffInDays($refDate->startOfDay()) : 0;
                    if ($daysAgo <= 30)      { $badge = ['جارية', 'bg-yellow-100 text-yellow-700']; }
                    elseif ($daysAgo <= 60)  { $badge = ['31–60 يوم', 'bg-orange-100 text-orange-700']; }
                    elseif ($daysAgo <= 90)  { $badge = ['61–90 يوم', 'bg-red-100 text-red-600']; }
                    else                     { $badge = ['+90 يوم', 'bg-red-200 text-red-800']; }
                @endphp
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-medium text-gray-800">{{ $res->guest->full_name ?? '—' }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $res->room->room_number ?? '—' }}</td>
                    <td class="px-4 py-3 text-gray-500 text-xs">{{ $refDate?->format('d/m/Y') ?? '—' }}</td>
                    <td class="px-4 py-3 text-center">
                        <span class="font-bold {{ $daysAgo > 60 ? 'text-red-600' : 'text-gray-700' }}">{{ $daysAgo }}</span>
                    </td>
                    <td class="px-4 py-3 text-gray-700">{{ number_format($res->total_amount, 0) }}</td>
                    <td class="px-4 py-3 text-green-700">{{ number_format($res->paid_amount, 0) }}</td>
                    <td class="px-4 py-3 font-bold text-red-600">{{ number_format($balance, 0) }}</td>
                    <td class="px-4 py-3">
                        <span class="text-xs px-2 py-0.5 rounded-full {{ $badge[1] }}">{{ $badge[0] }}</span>
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex gap-1.5">
                            <a href="{{ route('reservations.show', $res) }}" class="text-xs px-2 py-1 rounded border border-gray-200 text-gray-600 hover:bg-gray-50">تفاصيل</a>
                            <a href="{{ route('guests.statement', $res->guest) }}" class="text-xs px-2 py-1 rounded border border-blue-200 text-blue-600 hover:bg-blue-50">كشف حساب</a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="9" class="px-4 py-8 text-center text-gray-400">لا توجد ديون — ممتاز!</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

</div>
@endsection
