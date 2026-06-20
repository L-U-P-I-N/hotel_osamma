@extends('layouts.app')
@section('title', 'تقرير الغرف')
@section('page-title', 'تقرير الغرف')

@section('content')
<div class="space-y-5">

<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
    <div class="flex flex-wrap gap-3 items-end">
    <form method="GET" class="flex flex-wrap gap-3 items-end">
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">من تاريخ</label>
            <input type="date" name="from" value="{{ $from }}" class="border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">إلى تاريخ</label>
            <input type="date" name="to" value="{{ $to }}" class="border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none">
        </div>
        <button type="submit" class="px-4 py-2 text-white rounded-lg text-sm transition" style="background:#0F4C75;">عرض</button>
    </form>
    <div class="mr-auto flex gap-2">
        <a href="{{ route('reports.rooms.pdf', ['from' => $from, 'to' => $to]) }}"
           class="flex items-center gap-2 px-4 py-2 bg-red-600 text-white rounded-lg text-sm hover:bg-red-700 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
            PDF
        </a>
        <a href="{{ route('reports.rooms.excel', ['from' => $from, 'to' => $to]) }}"
           class="flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg text-sm hover:bg-green-700 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
            Excel
        </a>
    </div>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100">
    <div class="px-6 py-4 border-b border-gray-100">
        <h3 class="font-semibold text-gray-700">أداء الغرف</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">رقم الغرفة</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">النوع</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الحالة</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الحجوزات</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الإيرادات (ر.ي)</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @php
                    $statusLabels = ['available'=>'متاحة','occupied'=>'مشغولة','maintenance'=>'صيانة','under_inspection'=>'فحص'];
                    $statusColors = ['available'=>'bg-green-100 text-green-700','occupied'=>'bg-blue-100 text-blue-700','maintenance'=>'bg-red-100 text-red-700','under_inspection'=>'bg-yellow-100 text-yellow-700'];
                @endphp
                @forelse($rooms as $room)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-bold text-gray-800">{{ $room->room_number }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $room->roomType->name ?? '—' }}</td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$room->status] ?? 'bg-gray-100 text-gray-600' }}">
                            {{ $statusLabels[$room->status] ?? $room->status }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-gray-700">{{ $room->total_reservations ?? 0 }}</td>
                    <td class="px-4 py-3 font-semibold {{ ($room->total_revenue ?? 0) > 0 ? 'text-green-700' : 'text-gray-400' }}">
                        {{ number_format($room->total_revenue ?? 0, 0) }}
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="px-4 py-6 text-center text-gray-400">لا توجد بيانات</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
</div>
@endsection
