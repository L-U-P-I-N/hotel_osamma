@extends('layouts.app')
@section('title', 'أداء الموظفين')
@section('page-title', 'تقرير أداء الموظفين')

@section('content')
<div class="space-y-5">

{{-- Filter Bar --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
    <form method="GET" class="flex flex-wrap gap-3 items-end justify-between">
        <div class="flex flex-wrap gap-3 items-end">
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
        </div>
        <div class="flex gap-2 items-end">
            <a href="{{ route('reports.staff.pdf', ['from' => $from, 'to' => $to]) }}"
               class="inline-flex items-center gap-1.5 px-3 py-2 bg-red-600 text-white text-xs rounded-lg hover:bg-red-700 transition font-medium">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                PDF
            </a>
            <a href="{{ route('reports.staff.excel', ['from' => $from, 'to' => $to]) }}"
               class="inline-flex items-center gap-1.5 px-3 py-2 bg-green-600 text-white text-xs rounded-lg hover:bg-green-700 transition font-medium">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Excel
            </a>
        </div>
    </form>
</div>

{{-- Summary Row --}}
<div class="grid grid-cols-3 gap-3">
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 text-center">
        <div class="text-2xl font-bold" style="color:#0F4C75">{{ $staffData->sum('checkins') }}</div>
        <div class="text-xs text-gray-500 mt-0.5">إجمالي الحجوزات</div>
    </div>
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 text-center">
        <div class="text-2xl font-bold text-green-600">{{ number_format($staffData->sum('revenue'), 0) }}</div>
        <div class="text-xs text-gray-500 mt-0.5">إجمالي المستلم (ر.ي)</div>
    </div>
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 text-center">
        <div class="text-2xl font-bold text-blue-600">{{ $staffData->where('checkins', '>', 0)->count() }}</div>
        <div class="text-xs text-gray-500 mt-0.5">موظف نشط في الفترة</div>
    </div>
</div>

{{-- Per-employee expandable cards --}}
@forelse($staffData->sortByDesc('checkins') as $row)
<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden" x-data="{ open: false }">

    {{-- Header row --}}
    <div class="px-5 py-4 flex items-center justify-between cursor-pointer select-none hover:bg-gray-50 transition"
         @click="open = !open">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full flex items-center justify-center text-white font-bold text-sm flex-shrink-0"
                 style="background:#0F4C75;">
                {{ mb_substr($row['user']->name, 0, 1) }}
            </div>
            <div>
                <div class="font-semibold text-gray-800">{{ $row['user']->name }}</div>
                <div class="text-xs text-gray-400 flex items-center gap-1 mt-0.5">
                    @if($row['user']->employee_id)
                        <span>{{ $row['user']->employee_id }}</span>
                        <span>•</span>
                    @endif
                    @if($row['user']->roles->first())
                        <span style="color:#0F4C75;">{{ $row['user']->roles->first()->name }}</span>
                    @endif
                </div>
            </div>
        </div>

        <div class="flex items-center gap-6">
            <div class="text-center min-w-[60px]">
                <div class="text-xl font-bold text-gray-800">{{ $row['checkins'] }}</div>
                <div class="text-xs text-gray-400">حجز</div>
            </div>
            <div class="text-center min-w-[60px]">
                <div class="text-xl font-bold text-indigo-600">{{ $row['checked_out'] }}</div>
                <div class="text-xs text-gray-400">مغادرة</div>
            </div>
            <div class="text-center min-w-[100px]">
                <div class="text-xl font-bold text-green-600">{{ number_format($row['revenue'], 0) }}</div>
                <div class="text-xs text-gray-400">ر.ي مستلم</div>
            </div>
            <svg class="w-5 h-5 text-gray-400 transition-transform duration-200 flex-shrink-0"
                 :class="{ 'rotate-180': open }"
                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </div>
    </div>

    {{-- Expandable reservations table --}}
    <div x-show="open" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
         class="border-t border-gray-100">
        @if($row['reservations']->isEmpty())
        <div class="px-5 py-8 text-center text-sm text-gray-400">لا توجد حجوزات في هذه الفترة</div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2.5 text-right text-gray-500 font-medium whitespace-nowrap">#</th>
                        <th class="px-4 py-2.5 text-right text-gray-500 font-medium whitespace-nowrap">الغرفة</th>
                        <th class="px-4 py-2.5 text-right text-gray-500 font-medium whitespace-nowrap">النوع</th>
                        <th class="px-4 py-2.5 text-right text-gray-500 font-medium whitespace-nowrap">النزيل</th>
                        <th class="px-4 py-2.5 text-right text-gray-500 font-medium whitespace-nowrap">تاريخ الدخول</th>
                        <th class="px-4 py-2.5 text-right text-gray-500 font-medium whitespace-nowrap">تاريخ الخروج</th>
                        <th class="px-4 py-2.5 text-right text-gray-500 font-medium whitespace-nowrap">إجمالي الحجز</th>
                        <th class="px-4 py-2.5 text-right text-gray-500 font-medium whitespace-nowrap">المدفوع</th>
                        <th class="px-4 py-2.5 text-right text-gray-500 font-medium whitespace-nowrap">حالة الدفع</th>
                        <th class="px-4 py-2.5 text-right text-gray-500 font-medium whitespace-nowrap">الحالة</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($row['reservations'] as $res)
                    @php
                        $statusInfo = match($res->status) {
                            'checked_in'  => ['label' => 'مقيم',   'class' => 'bg-green-100 text-green-700'],
                            'checked_out' => ['label' => 'غادر',   'class' => 'bg-gray-100 text-gray-600'],
                            default       => ['label' => $res->status, 'class' => 'bg-gray-100 text-gray-600'],
                        };
                        $payInfo = match($res->payment_status ?? 'unpaid') {
                            'paid'     => ['label' => 'مدفوع',  'class' => 'bg-green-100 text-green-700'],
                            'partial'  => ['label' => 'جزئي',   'class' => 'bg-amber-100 text-amber-700'],
                            'deferred' => ['label' => 'مؤجل',   'class' => 'bg-purple-100 text-purple-700'],
                            default    => ['label' => 'غير مدفوع', 'class' => 'bg-red-100 text-red-700'],
                        };
                        $paidAmount = $res->payments->sum('amount');
                    @endphp
                    <tr class="hover:bg-blue-50 transition">
                        <td class="px-4 py-2.5 text-gray-400">#{{ $res->id }}</td>
                        <td class="px-4 py-2.5 font-bold whitespace-nowrap" style="color:#0F4C75">
                            {{ $res->room?->room_number ?? '—' }}
                        </td>
                        <td class="px-4 py-2.5 text-gray-500 whitespace-nowrap">
                            {{ $res->room?->roomType?->name ?? '—' }}
                        </td>
                        <td class="px-4 py-2.5 font-medium text-gray-800 whitespace-nowrap">
                            <a href="{{ route('reservations.show', $res) }}" class="hover:underline hover:text-blue-600">
                                {{ $res->guest?->full_name ?? '—' }}
                            </a>
                        </td>
                        <td class="px-4 py-2.5 text-gray-600 whitespace-nowrap">
                            {{ $res->check_in_date?->format('d/m/Y') ?? '—' }}
                        </td>
                        <td class="px-4 py-2.5 text-gray-600 whitespace-nowrap">
                            {{ $res->check_out_date?->format('d/m/Y') ?? '—' }}
                        </td>
                        <td class="px-4 py-2.5 font-semibold text-gray-800 whitespace-nowrap">
                            {{ number_format($res->total_amount, 0) }}
                        </td>
                        <td class="px-4 py-2.5 font-semibold text-green-700 whitespace-nowrap">
                            {{ number_format($paidAmount, 0) }}
                        </td>
                        <td class="px-4 py-2.5 whitespace-nowrap">
                            <span class="px-2 py-0.5 rounded-full font-medium text-xs {{ $payInfo['class'] }}">
                                {{ $payInfo['label'] }}
                            </span>
                        </td>
                        <td class="px-4 py-2.5 whitespace-nowrap">
                            <span class="px-2 py-0.5 rounded-full font-medium text-xs {{ $statusInfo['class'] }}">
                                {{ $statusInfo['label'] }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-gray-50 border-t border-gray-200">
                    <tr>
                        <td colspan="6" class="px-4 py-2.5 text-right text-gray-500 font-semibold text-xs">الإجمالي</td>
                        <td class="px-4 py-2.5 font-bold text-gray-800 text-xs whitespace-nowrap">
                            {{ number_format($row['reservations']->sum('total_amount'), 0) }} ر.ي
                        </td>
                        <td class="px-4 py-2.5 font-bold text-green-700 text-xs whitespace-nowrap">
                            {{ number_format($row['revenue'], 0) }} ر.ي
                        </td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        @endif
    </div>
</div>
@empty
<div class="bg-white rounded-xl border border-gray-100 py-14 text-center text-gray-400">
    <svg class="w-10 h-10 mx-auto mb-3 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
    </svg>
    <p class="text-sm">لا يوجد موظفون نشطون</p>
</div>
@endforelse

</div>
@endsection
