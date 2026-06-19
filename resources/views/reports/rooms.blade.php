@extends('layouts.app')
@section('title', 'تقرير الغرف')
@section('page-title', 'تقرير الغرف')

@section('content')
<div class="space-y-5">

<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
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
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">عدد الحجوزات</th>
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
                <tr><td colspan="5" class="px-4 py-6 text-center text-gray-400">لا توجد غرف</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
</div>
@endsection
