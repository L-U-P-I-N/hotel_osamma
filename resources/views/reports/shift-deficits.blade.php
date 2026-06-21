@extends('layouts.app')
@section('title', 'تقرير العجز التراكمي')
@section('page-title', 'تقرير العجز التراكمي للموظفين')

@section('content')
<div dir="rtl">

{{-- فلاتر التاريخ --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-5">
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
        <button type="submit" class="px-4 py-2 text-sm text-white rounded-lg transition" style="background:#0F4C75;">عرض</button>
    </form>
</div>

@if(session('success'))
<div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">{{ session('success') }}</div>
@endif
@if($errors->any())
<div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
    @foreach($errors->all() as $e)<p class="text-sm text-red-700">{{ $e }}</p>@endforeach
</div>
@endif

@if($summary->isEmpty())
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-10 text-center text-gray-400">
    لا توجد ورديات مقفلة في هذه الفترة
</div>
@else

{{-- ملخص الإجماليات --}}
@php
    $grandTotalDeficit  = $summary->sum('total_deficit');
    $grandTotalSurplus  = $summary->sum('total_surplus');
    $grandTotalReceived = $summary->sum('total_received');
    $grandDeficitCount  = $summary->sum('deficit_count');
    $grandDeductedCount = $summary->sum('deducted_count');
@endphp
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-5">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
        <p class="text-xs text-gray-500 mb-1">إجمالي المستلمات</p>
        <p class="text-lg font-bold text-green-700">{{ number_format($grandTotalReceived, 0) }} <span class="text-sm font-normal">ر.ي</span></p>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-red-100 p-4">
        <p class="text-xs text-gray-500 mb-1">إجمالي العجوزات</p>
        <p class="text-lg font-bold text-red-700">{{ number_format($grandTotalDeficit, 0) }} <span class="text-sm font-normal">ر.ي</span></p>
        <p class="text-xs text-gray-400 mt-0.5">{{ $grandDeficitCount }} وردية بعجز</p>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-amber-100 p-4">
        <p class="text-xs text-gray-500 mb-1">إجمالي الزيادات</p>
        <p class="text-lg font-bold text-amber-700">{{ number_format($grandTotalSurplus, 0) }} <span class="text-sm font-normal">ر.ي</span></p>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
        <p class="text-xs text-gray-500 mb-1">عجوزات مخصومة</p>
        <p class="text-lg font-bold text-primary-800">{{ $grandDeductedCount }} / {{ $grandDeficitCount }}</p>
        <p class="text-xs text-gray-400 mt-0.5">من مجموع ورديات العجز</p>
    </div>
</div>

{{-- جدول تفاصيل الموظفين --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-100">
    <div class="px-5 py-3 border-b border-gray-100">
        <h3 class="font-semibold text-gray-700 text-sm">تفاصيل العجز لكل موظف</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50"><tr>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الموظف</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">عدد الورديات</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">إجمالي المستلمات</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">إجمالي السحبيات</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 text-red-600">مجموع العجز</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 text-amber-600">مجموع الزيادة</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">ورديات العجز</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">المخصوم</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($summary as $row)
                <tr x-data="{ open: false }" class="hover:bg-gray-50">
                    <td class="px-4 py-3">
                        <button @click="open=!open" class="flex items-center gap-2 font-medium text-gray-800">
                            <svg :class="open ? 'rotate-90' : ''" class="w-3.5 h-3.5 text-gray-400 transition-transform flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            {{ $row['user']->name }}
                        </button>
                    </td>
                    <td class="px-4 py-3 text-gray-600">{{ $row['shift_count'] }}</td>
                    <td class="px-4 py-3 font-medium text-green-700">{{ number_format($row['total_received'], 0) }} ر.ي</td>
                    <td class="px-4 py-3 font-medium text-gray-600">{{ number_format($row['total_withdrawn'], 0) }} ر.ي</td>
                    <td class="px-4 py-3 font-bold {{ $row['total_deficit'] > 0 ? 'text-red-700' : 'text-gray-300' }}">
                        {{ $row['total_deficit'] > 0 ? number_format($row['total_deficit'], 0).' ر.ي' : '—' }}
                    </td>
                    <td class="px-4 py-3 font-medium {{ $row['total_surplus'] > 0 ? 'text-amber-700' : 'text-gray-300' }}">
                        {{ $row['total_surplus'] > 0 ? number_format($row['total_surplus'], 0).' ر.ي' : '—' }}
                    </td>
                    <td class="px-4 py-3 text-gray-600">{{ $row['deficit_count'] }}</td>
                    <td class="px-4 py-3">
                        @if($row['deficit_count'] > 0)
                        <span class="px-2 py-0.5 rounded-full text-xs font-semibold
                            {{ $row['deducted_count'] == $row['deficit_count'] ? 'bg-green-100 text-green-700' : 'bg-orange-100 text-orange-700' }}">
                            {{ $row['deducted_count'] }} / {{ $row['deficit_count'] }}
                        </span>
                        @else
                        <span class="text-gray-300 text-xs">—</span>
                        @endif
                    </td>
                </tr>

                {{-- الورديات التفصيلية لكل موظف --}}
                <tr x-show="open" x-cloak>
                    <td colspan="8" class="bg-gray-50 px-6 py-3">
                        <table class="w-full text-xs">
                            <thead><tr class="text-gray-400">
                                <th class="py-1 text-right">التاريخ</th>
                                <th class="py-1 text-right">المستلمات</th>
                                <th class="py-1 text-right">السحبيات</th>
                                <th class="py-1 text-right">الصافي</th>
                                <th class="py-1 text-right">الفعلي</th>
                                <th class="py-1 text-right">الفرق</th>
                                <th class="py-1 text-right">الخصم</th>
                                <th class="py-1"></th>
                            </tr></thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach($row['shifts'] as $sh)
                                @php $net = $sh->total_received_yer - $sh->total_withdrawals_yer; @endphp
                                <tr>
                                    <td class="py-1.5 text-gray-600">{{ $sh->shift_date->format('d/m/Y') }}</td>
                                    <td class="py-1.5 text-green-700 font-medium">{{ number_format($sh->total_received_yer, 0) }}</td>
                                    <td class="py-1.5 text-red-600">{{ number_format($sh->total_withdrawals_yer, 0) }}</td>
                                    <td class="py-1.5 font-medium {{ $net >= 0 ? 'text-primary-700' : 'text-red-700' }}">{{ number_format($net, 0) }}</td>
                                    <td class="py-1.5 text-gray-600">{{ $sh->actual_amount !== null ? number_format($sh->actual_amount, 0) : '—' }}</td>
                                    <td class="py-1.5">
                                        @if($sh->shortfall !== null)
                                            @php $sf = $sh->shortfall; @endphp
                                            <span class="font-semibold {{ $sf == 0 ? 'text-green-600' : ($sf < 0 ? 'text-red-700' : 'text-amber-700') }}">
                                                {{ $sf == 0 ? 'مطابق' : ($sf < 0 ? '▼ '.number_format(abs($sf), 0) : '▲ '.number_format($sf, 0)) }}
                                            </span>
                                        @else —
                                        @endif
                                    </td>
                                    <td class="py-1.5">
                                        @if($sh->shortfall !== null && $sh->shortfall < 0)
                                            @if($sh->salary_deducted_at)
                                            <span class="text-green-600 font-medium">✓ مخصوم</span>
                                            @else
                                            <form method="POST" action="{{ route('shifts.deductSalary', $sh) }}"
                                                  onsubmit="return confirm('خصم {{ number_format(abs($sh->shortfall), 0) }} ر.ي من راتب {{ $row['user']->name }}؟')">
                                                @csrf
                                                <button type="submit" class="px-2 py-0.5 border border-red-300 text-red-600 rounded text-xs hover:bg-red-50 transition">
                                                    خصم من الراتب
                                                </button>
                                            </form>
                                            @endif
                                        @else
                                            <span class="text-gray-300">—</span>
                                        @endif
                                    </td>
                                    <td class="py-1.5">
                                        <a href="{{ route('shifts.pdf', $sh) }}" target="_blank"
                                           class="text-gray-400 hover:text-gray-600 text-xs">PDF</a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

</div>
@endsection
