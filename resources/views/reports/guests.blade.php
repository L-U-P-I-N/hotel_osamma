@extends('layouts.app')
@section('title', 'تقرير الضيوف')
@section('page-title', 'تقرير الضيوف')

@section('content')
<div class="space-y-5">

<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
    <div class="flex flex-wrap gap-3 items-end">
    <form method="GET" class="flex flex-wrap gap-3 items-end">
        <div class="flex flex-col gap-1">
            <label class="text-xs font-medium text-gray-500">من تاريخ</label>
            <input type="date" name="from" value="{{ $from }}" onchange="this.form.submit()"
                   class="border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400 transition bg-white">
        </div>
        <div class="flex flex-col gap-1">
            <label class="text-xs font-medium text-gray-500">إلى تاريخ</label>
            <input type="date" name="to" value="{{ $to }}" onchange="this.form.submit()"
                   class="border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400 transition bg-white">
        </div>
        <button type="submit" class="sr-only">عرض</button>
    </form>
    <div class="mr-auto flex gap-2">
        <a href="{{ route('reports.guests.pdf', ['from' => $from, 'to' => $to]) }}"
           class="flex items-center gap-2 px-4 py-2 bg-red-600 text-white rounded-lg text-sm hover:bg-red-700 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
            PDF
        </a>
        <a href="{{ route('reports.guests.excel', ['from' => $from, 'to' => $to]) }}"
           class="flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg text-sm hover:bg-green-700 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
            Excel
        </a>
    </div>
    </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <div class="text-xs text-gray-500">إجمالي الضيوف المسجلين</div>
        <div class="text-3xl font-bold mt-1" style="color:#0F4C75;">{{ $totalGuests }}</div>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <div class="text-xs text-gray-500">ضيوف جدد في الفترة</div>
        <div class="text-3xl font-bold text-green-600 mt-1">{{ $newGuests }}</div>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <div class="text-xs text-gray-500">ضيوف متكررون في الفترة</div>
        <div class="text-3xl font-bold text-blue-600 mt-1">{{ $returningGuests }}</div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-700">الجنسيات (أعلى 10)</h3>
        </div>
        <div class="divide-y divide-gray-50">
            @forelse($byNationality as $nat)
            <div class="px-6 py-3 flex items-center justify-between">
                <span class="text-sm text-gray-700">{{ $nat->nationality ?: 'غير محدد' }}</span>
                <span class="text-sm font-bold" style="color:#0F4C75;">{{ $nat->count }}</span>
            </div>
            @empty
            <div class="px-6 py-6 text-center text-gray-400 text-sm">لا توجد بيانات</div>
            @endforelse
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-700">الضيوف الأكثر حجزاً في الفترة</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50"><tr>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الاسم</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">عدد الحجوزات</th>
                </tr></thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($topGuests as $g)
                    <tr>
                        <td class="px-4 py-3 font-medium text-gray-800">{{ $g->full_name }}</td>
                        <td class="px-4 py-3 font-bold text-blue-600">{{ $g->period_reservations }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="2" class="px-4 py-6 text-center text-gray-400">لا توجد بيانات</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
</div>
@endsection
