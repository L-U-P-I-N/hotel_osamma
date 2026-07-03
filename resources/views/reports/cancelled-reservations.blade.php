@extends('layouts.app')
@section('title', 'أسباب إلغاء الحجوزات')
@section('page-title', 'أسباب إلغاء الحجوزات')

@section('content')
<div class="space-y-5" dir="rtl">

    <div class="flex flex-wrap items-end gap-3 justify-between">
        <form method="GET" action="{{ route('reports.cancelledReservations') }}" class="flex gap-2 items-end flex-wrap">
            <div class="flex flex-col gap-1">
                <label class="text-xs font-medium text-gray-500">من</label>
                <input type="date" name="from" value="{{ $from }}"
                       class="border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400 bg-white">
            </div>
            <div class="flex flex-col gap-1">
                <label class="text-xs font-medium text-gray-500">إلى</label>
                <input type="date" name="to" value="{{ $to }}"
                       class="border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400 bg-white">
            </div>
            <div class="flex flex-col gap-1">
                <label class="text-xs font-medium text-gray-500">بحث باسم النزيل</label>
                <input type="text" name="search" value="{{ $search }}" placeholder="اسم النزيل..."
                       class="border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400 bg-white">
            </div>
            <button type="submit" class="px-4 py-2 text-white rounded-lg text-sm font-medium" style="background:#0F4C75;">فلترة</button>
            <a href="{{ route('reports.cancelledReservations') }}" class="px-3 py-2 text-xs text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50">إعادة تعيين</a>
        </form>
        <a href="{{ route('reports.cancelledReservations.pdf', ['from' => $from, 'to' => $to, 'search' => $search]) }}"
           class="flex items-center gap-2 px-4 py-2 bg-red-600 text-white rounded-lg text-sm hover:bg-red-700 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
            تصدير PDF
        </a>
    </div>

    <div class="grid grid-cols-2 gap-4">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 text-center">
            <div class="text-2xl font-bold" style="color:#0F4C75;">{{ $totalCancelled }}</div>
            <div class="text-xs text-gray-500 mt-0.5">حجز ملغى في الفترة</div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-red-100 p-4 text-center">
            <div class="text-2xl font-bold text-red-600">{{ number_format($totalUnpaidBalance, 0) }}</div>
            <div class="text-xs text-gray-500 mt-0.5">متأخرات لم تُحصَّل قبل الإلغاء (ر.ي)</div>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100">
            <h3 class="font-semibold text-gray-700 text-sm">
                الحجوزات الملغاة
                <span class="text-gray-400 font-normal mr-2 text-xs">({{ $reservations->total() }} حجز)</span>
            </h3>
        </div>

        @if($reservations->isEmpty())
        <div class="py-12 text-center text-gray-400 text-sm">لا توجد حجوزات ملغاة في هذه الفترة</div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-100">
                    <tr>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 whitespace-nowrap">تاريخ الإلغاء</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 whitespace-nowrap">اسم النزيل</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 whitespace-nowrap">الغرفة</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 whitespace-nowrap">تاريخ الحجز الأصلي</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">سبب الإلغاء</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 whitespace-nowrap">ألغاه</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 whitespace-nowrap">المتأخرات وقت الإلغاء</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($reservations as $res)
                    @php $balance = max(0, (float)$res->total_amount - (float)$res->paid_amount); @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-gray-600 whitespace-nowrap">{{ $res->cancelled_at?->format('d/m/Y H:i') ?? '—' }}</td>
                        <td class="px-4 py-3 font-medium text-gray-800">{{ $res->guest?->full_name ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $res->display_room_number }}</td>
                        <td class="px-4 py-3 text-gray-500 text-xs whitespace-nowrap">
                            {{ $res->check_in_date?->format('d/m/Y') ?? '—' }} — {{ $res->check_out_date?->format('d/m/Y') ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-gray-700">{{ $res->cancellation_reason }}</td>
                        <td class="px-4 py-3 text-gray-500 text-xs whitespace-nowrap">{{ $res->cancelledBy?->name ?? '—' }}</td>
                        <td class="px-4 py-3 font-bold {{ $balance > 0 ? 'text-red-600' : 'text-gray-400' }}">
                            {{ $balance > 0 ? number_format($balance, 0) . ' ر.ي' : '—' }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="px-5 py-3 border-t border-gray-100">
            {{ $reservations->links() }}
        </div>
        @endif
    </div>

</div>
@endsection
