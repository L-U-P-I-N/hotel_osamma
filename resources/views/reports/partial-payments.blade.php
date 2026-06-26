@extends('layouts.app')
@section('title', 'تقرير الدفعات الجزئية')
@section('page-title', 'تقرير الدفعات الجزئية')

@section('content')
<div dir="rtl">

{{-- ─── فلاتر البحث ─── --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 mb-6">
    <form method="GET" action="{{ route('reports.partialPayments') }}" class="flex flex-wrap items-end gap-4">

        <div class="flex-1 min-w-[140px]">
            <label class="block text-xs font-semibold text-gray-500 mb-1.5">من تاريخ</label>
            <input type="date" name="from" value="{{ $from }}"
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
        </div>

        <div class="flex-1 min-w-[140px]">
            <label class="block text-xs font-semibold text-gray-500 mb-1.5">إلى تاريخ</label>
            <input type="date" name="to" value="{{ $to }}"
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
        </div>

        <div class="flex-1 min-w-[180px]">
            <label class="block text-xs font-semibold text-gray-500 mb-1.5">اسم النزيل</label>
            <input type="text" name="search" value="{{ $search }}" placeholder="ابحث باسم النزيل..."
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
        </div>

        <div class="min-w-[160px]">
            <label class="block text-xs font-semibold text-gray-500 mb-1.5">عرض</label>
            <select name="status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                <option value="partial" {{ $status === 'partial' ? 'selected' : '' }}>الجزئية فقط (غير مكتملة)</option>
                <option value="all"     {{ $status === 'all'     ? 'selected' : '' }}>كل الحجوزات التي لها دفعات</option>
            </select>
        </div>

        <div class="flex gap-2">
            <button type="submit"
                    class="flex items-center gap-2 px-5 py-2 bg-blue-600 text-white rounded-lg text-sm font-semibold hover:bg-blue-700 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z"/></svg>
                تصفية
            </button>
            <a href="{{ route('reports.partialPayments') }}"
               class="flex items-center gap-2 px-4 py-2 border border-gray-300 text-gray-600 rounded-lg text-sm hover:bg-gray-50 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                إعادة ضبط
            </a>
        </div>
    </form>
</div>

{{-- ─── بطاقات الإحصاء ─── --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <div class="flex items-center gap-3 mb-3">
            <div class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center">
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            </div>
            <span class="text-xs font-semibold text-gray-500">إجمالي الحجوزات</span>
        </div>
        <div class="text-2xl font-black text-gray-800">{{ number_format($totalReservations) }}</div>
        <div class="text-xs text-gray-400 mt-1">حجز في الفترة المحددة</div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <div class="flex items-center gap-3 mb-3">
            <div class="w-10 h-10 bg-green-100 rounded-xl flex items-center justify-center">
                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <span class="text-xs font-semibold text-gray-500">إجمالي المحصَّل</span>
        </div>
        <div class="text-2xl font-black text-green-700">{{ number_format($totalCollected, 0) }}</div>
        <div class="text-xs text-gray-400 mt-1">ريال يمني</div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <div class="flex items-center gap-3 mb-3">
            <div class="w-10 h-10 bg-red-100 rounded-xl flex items-center justify-center">
                <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </div>
            <span class="text-xs font-semibold text-gray-500">المتبقي غير المحصَّل</span>
        </div>
        <div class="text-2xl font-black text-red-600">{{ number_format($totalRemaining, 0) }}</div>
        <div class="text-xs text-gray-400 mt-1">ريال يمني</div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <div class="flex items-center gap-3 mb-3">
            <div class="w-10 h-10 bg-purple-100 rounded-xl flex items-center justify-center">
                <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/></svg>
            </div>
            <span class="text-xs font-semibold text-gray-500">إجمالي عمليات الدفع</span>
        </div>
        <div class="text-2xl font-black text-purple-700">{{ number_format($totalTransactions) }}</div>
        <div class="text-xs text-gray-400 mt-1">عملية دفع مسجلة</div>
    </div>

</div>

{{-- ─── الجدول الرئيسي ─── --}}
@if($reservations->isEmpty())
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-16 text-center">
    <svg class="w-14 h-14 mx-auto mb-4 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
    <p class="text-gray-400 text-sm">لا توجد حجوزات بدفعات جزئية في الفترة المحددة</p>
</div>
@else
<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden" x-data="{ open: null }">

    {{-- عنوان الجدول --}}
    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
        <div class="flex items-center gap-2">
            <h2 class="font-bold text-gray-800">سجل الدفعات</h2>
            <span class="px-2.5 py-0.5 bg-blue-100 text-blue-700 rounded-full text-xs font-bold">{{ $totalReservations }} حجز</span>
        </div>
        <p class="text-xs text-gray-400">انقر على أي صف لعرض تفاصيل الدفعات</p>
    </div>

    {{-- Desktop table --}}
    <div class="hidden md:block overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-100 text-xs font-bold text-gray-500 uppercase tracking-wide">
                    <th class="text-right px-4 py-3">النزيل والغرفة</th>
                    <th class="text-right px-4 py-3">تاريخ الدخول</th>
                    <th class="text-right px-4 py-3">تاريخ الخروج</th>
                    <th class="text-right px-4 py-3">الإجمالي</th>
                    <th class="text-right px-4 py-3">المدفوع</th>
                    <th class="text-right px-4 py-3">المتبقي</th>
                    <th class="text-center px-4 py-3">الدفعات</th>
                    <th class="text-right px-4 py-3">آخر دفعة</th>
                    <th class="text-center px-4 py-3">الحالة</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($reservations as $res)
                @php
                    $balance      = $res->total_amount - $res->paid_amount;
                    $pctPaid      = $res->total_amount > 0 ? round(($res->paid_amount / $res->total_amount) * 100) : 0;
                    $lastPayment  = $res->payments->last();
                    $payCount     = $res->payments->count();
                @endphp
                {{-- Main row --}}
                <tr class="hover:bg-gray-50 cursor-pointer transition"
                    @click="open = (open === {{ $res->id }}) ? null : {{ $res->id }}">
                    <td class="px-4 py-3">
                        <div class="font-semibold text-gray-800">{{ $res->guest?->full_name ?? '—' }}</div>
                        <div class="text-xs text-gray-400 mt-0.5">غرفة {{ $res->room?->room_number }} · {{ $res->room?->roomType?->name }}</div>
                    </td>
                    <td class="px-4 py-3 text-gray-600 whitespace-nowrap">
                        {{ $res->check_in_date?->format('d/m/Y') ?? '—' }}
                    </td>
                    <td class="px-4 py-3 text-gray-600 whitespace-nowrap">
                        {{ ($res->actual_check_out ?? $res->check_out_date)?->format('d/m/Y') ?? '—' }}
                    </td>
                    <td class="px-4 py-3 font-semibold text-gray-800 whitespace-nowrap">
                        {{ number_format($res->total_amount, 0) }} <span class="text-xs text-gray-400 font-normal">ر.ي</span>
                    </td>
                    <td class="px-4 py-3">
                        <div class="font-semibold text-green-700 whitespace-nowrap">{{ number_format($res->paid_amount, 0) }} <span class="text-xs text-gray-400 font-normal">ر.ي</span></div>
                        {{-- Progress bar --}}
                        <div class="mt-1 h-1.5 w-24 bg-gray-200 rounded-full overflow-hidden">
                            <div class="h-full rounded-full transition-all"
                                 style="width: {{ $pctPaid }}%; background: {{ $pctPaid >= 100 ? '#10B981' : ($pctPaid >= 50 ? '#3B82F6' : '#F59E0B') }}"></div>
                        </div>
                        <div class="text-xs text-gray-400 mt-0.5">{{ $pctPaid }}%</div>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap">
                        @if($balance > 0)
                            <span class="font-bold text-red-600">{{ number_format($balance, 0) }} <span class="text-xs font-normal">ر.ي</span></span>
                        @else
                            <span class="text-green-600 font-semibold text-xs">مكتمل ✓</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-center">
                        <span class="inline-flex items-center justify-center w-8 h-8 rounded-full text-xs font-black
                            {{ $payCount >= 3 ? 'bg-purple-100 text-purple-700' : ($payCount >= 2 ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-600') }}">
                            {{ $payCount }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-gray-500 text-xs whitespace-nowrap">
                        @if($lastPayment)
                            <div class="font-medium text-gray-700">{{ $lastPayment->payment_date?->format('d/m/Y') }}</div>
                            <div class="text-gray-400">{{ $lastPayment->payment_date?->format('H:i') }}</div>
                        @else
                            —
                        @endif
                    </td>
                    <td class="px-4 py-3 text-center">
                        @if($res->payment_status === 'paid')
                            <span class="px-2.5 py-1 bg-green-100 text-green-700 rounded-full text-xs font-bold">مدفوع</span>
                        @elseif($res->payment_status === 'partial')
                            <span class="px-2.5 py-1 bg-amber-100 text-amber-700 rounded-full text-xs font-bold">جزئي</span>
                        @else
                            <span class="px-2.5 py-1 bg-gray-100 text-gray-600 rounded-full text-xs font-bold">{{ $res->payment_status }}</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-center">
                        <svg class="w-4 h-4 text-gray-400 mx-auto transition-transform duration-200"
                             :class="open === {{ $res->id }} ? 'rotate-180' : ''"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </td>
                </tr>

                {{-- Expandable payment history --}}
                <tr x-show="open === {{ $res->id }}" x-transition style="display:none">
                    <td colspan="10" class="px-0 py-0">
                        <div class="bg-blue-50 border-t border-b border-blue-100 px-6 py-4">

                            @if($res->payments->isEmpty())
                            <p class="text-sm text-gray-400 text-center py-2">لا توجد دفعات مسجلة لهذا الحجز</p>
                            @else
                            <div class="mb-3 flex items-center gap-2">
                                <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                                <span class="text-xs font-bold text-blue-700">سجل الدفعات — {{ $payCount }} عملية دفع</span>
                            </div>

                            <div class="overflow-hidden rounded-xl border border-blue-200 bg-white">
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr class="bg-blue-600 text-white text-xs">
                                            <th class="text-right px-4 py-2.5 font-semibold">#</th>
                                            <th class="text-right px-4 py-2.5 font-semibold">التاريخ والوقت</th>
                                            <th class="text-right px-4 py-2.5 font-semibold">المبلغ</th>
                                            <th class="text-right px-4 py-2.5 font-semibold">طريقة الدفع</th>
                                            <th class="text-right px-4 py-2.5 font-semibold">استلم بواسطة</th>
                                            <th class="text-right px-4 py-2.5 font-semibold">ملاحظات</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        @php $runningTotal = 0; @endphp
                                        @foreach($res->payments as $i => $pay)
                                        @php $runningTotal += $pay->amount; @endphp
                                        <tr class="{{ $i % 2 === 0 ? 'bg-white' : 'bg-gray-50' }}">
                                            <td class="px-4 py-3">
                                                <span class="w-6 h-6 rounded-full bg-blue-100 text-blue-700 text-xs font-black flex items-center justify-center">{{ $i + 1 }}</span>
                                            </td>
                                            <td class="px-4 py-3">
                                                <div class="font-semibold text-gray-800">{{ $pay->payment_date?->format('d/m/Y') }}</div>
                                                <div class="text-xs text-gray-400">{{ $pay->payment_date?->format('H:i') }}</div>
                                            </td>
                                            <td class="px-4 py-3">
                                                <div class="font-bold text-green-700">{{ number_format($pay->amount, 0) }} ر.ي</div>
                                                <div class="text-xs text-gray-400">المجموع: {{ number_format($runningTotal, 0) }} ر.ي</div>
                                            </td>
                                            <td class="px-4 py-3">
                                                @if($pay->method === 'cash')
                                                    <span class="flex items-center gap-1.5 text-gray-700">
                                                        <span class="w-2 h-2 rounded-full bg-green-500 flex-shrink-0"></span>
                                                        نقدي
                                                    </span>
                                                @elseif($pay->method === 'bank_transfer')
                                                    <span class="flex items-center gap-1.5 text-blue-700">
                                                        <span class="w-2 h-2 rounded-full bg-blue-500 flex-shrink-0"></span>
                                                        تحويل بنكي
                                                        @if($pay->bank_transfer_ref)
                                                            <span class="text-xs text-gray-400">({{ $pay->bank_transfer_ref }})</span>
                                                        @endif
                                                    </span>
                                                @else
                                                    <span class="text-gray-600">{{ $pay->method }}</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 text-gray-600">
                                                {{ $pay->receivedBy?->name ?? '—' }}
                                            </td>
                                            <td class="px-4 py-3 text-gray-500 text-xs">
                                                {{ $pay->notes ?: '—' }}
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr class="bg-gray-50 border-t-2 border-gray-200 font-bold text-sm">
                                            <td colspan="2" class="px-4 py-3 text-gray-600">الإجمالي المحصَّل</td>
                                            <td class="px-4 py-3 text-green-700">{{ number_format($res->paid_amount, 0) }} ر.ي</td>
                                            <td colspan="3" class="px-4 py-3">
                                                @if($balance > 0)
                                                    <span class="text-red-600">متبقي: {{ number_format($balance, 0) }} ر.ي</span>
                                                @else
                                                    <span class="text-green-600">✓ مكتمل الدفع</span>
                                                @endif
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                            @endif

                            {{-- رابط صفحة الحجز --}}
                            <div class="mt-3 flex justify-end">
                                <a href="{{ route('reservations.show', $res->id) }}"
                                   class="flex items-center gap-1.5 text-xs text-blue-600 hover:text-blue-800 font-semibold transition">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                    فتح صفحة الحجز الكاملة
                                </a>
                            </div>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Mobile cards --}}
    <div class="md:hidden divide-y divide-gray-100" x-data="{ open: null }">
        @foreach($reservations as $res)
        @php
            $balance     = $res->total_amount - $res->paid_amount;
            $pctPaid     = $res->total_amount > 0 ? round(($res->paid_amount / $res->total_amount) * 100) : 0;
            $lastPayment = $res->payments->last();
            $payCount    = $res->payments->count();
        @endphp
        <div class="p-4">
            <div class="flex items-start justify-between cursor-pointer" @click="open = (open === {{ $res->id }}) ? null : {{ $res->id }}">
                <div class="flex-1">
                    <div class="font-bold text-gray-800 text-sm">{{ $res->guest?->full_name ?? '—' }}</div>
                    <div class="text-xs text-gray-400 mt-0.5">غرفة {{ $res->room?->room_number }} · {{ $res->check_in_date?->format('d/m/Y') }}</div>
                    <div class="flex items-center gap-3 mt-2">
                        <span class="text-xs text-green-700 font-semibold">{{ number_format($res->paid_amount, 0) }} ر.ي مدفوع</span>
                        @if($balance > 0)
                            <span class="text-xs text-red-600 font-semibold">{{ number_format($balance, 0) }} ر.ي متبقي</span>
                        @endif
                    </div>
                    <div class="mt-1.5 h-1.5 w-32 bg-gray-200 rounded-full overflow-hidden">
                        <div class="h-full rounded-full" style="width: {{ $pctPaid }}%; background: {{ $pctPaid >= 100 ? '#10B981' : ($pctPaid >= 50 ? '#3B82F6' : '#F59E0B') }}"></div>
                    </div>
                </div>
                <div class="flex flex-col items-end gap-1.5 mr-3">
                    <span class="px-2 py-0.5 text-xs font-bold rounded-full
                        {{ $res->payment_status === 'paid' ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700' }}">
                        {{ $res->payment_status === 'paid' ? 'مكتمل' : 'جزئي' }}
                    </span>
                    <span class="text-xs text-purple-600 font-bold">{{ $payCount }} دفعات</span>
                </div>
            </div>

            <div x-show="open === {{ $res->id }}" x-transition class="mt-3 pt-3 border-t border-gray-100 space-y-2">
                @foreach($res->payments as $i => $pay)
                <div class="flex items-center justify-between bg-gray-50 rounded-lg px-3 py-2.5">
                    <div>
                        <div class="text-xs font-bold text-gray-700">دفعة {{ $i + 1 }}</div>
                        <div class="text-xs text-gray-400 mt-0.5">{{ $pay->payment_date?->format('d/m/Y H:i') }}</div>
                        <div class="text-xs text-gray-500">{{ $pay->method === 'cash' ? 'نقدي' : 'تحويل بنكي' }}</div>
                    </div>
                    <div class="text-right">
                        <div class="text-sm font-bold text-green-700">{{ number_format($pay->amount, 0) }} ر.ي</div>
                        @if($pay->receivedBy)
                            <div class="text-xs text-gray-400">{{ $pay->receivedBy->name }}</div>
                        @endif
                    </div>
                </div>
                @endforeach
                <a href="{{ route('reservations.show', $res->id) }}"
                   class="flex items-center justify-center gap-1.5 w-full py-2 text-xs text-blue-600 border border-blue-200 rounded-lg hover:bg-blue-50 transition font-semibold mt-1">
                    عرض الحجز كاملاً
                </a>
            </div>
        </div>
        @endforeach
    </div>

</div>
@endif

</div>
@endsection
