@extends('layouts.app')
@section('title', 'النزلاء المسجلون - ترتيب حسب الخروج')
@section('page-title', 'النزلاء المسجلون')

@push('styles')
<style>
    /* صفّ النزيل المُغادِر: رمادي خفيف في الوضع النهاري، وشفاف يندمج مع
       البطاقة في الوضع الليلي (بدل الأبيض). الـ hover يبقى كحركة لطيفة. */
    .row-departed { background-color: rgba(249,250,251,.7); }
    html.dark .row-departed { background-color: transparent !important; }
    /* صفّ "اليوم" (كان غير مُعالَج في الوضع الليلي فيظهر برتقالياً فاتحاً) */
    html.dark .bg-orange-50 { background-color: rgba(249,115,22,.10) !important; }
</style>
@endpush

@section('content')
<div dir="rtl">

<!-- Header -->
<div class="flex items-center justify-between mb-4">
    <div>
        <p class="text-sm text-gray-500">
            @php $st = $status ?? 'all'; @endphp
            {{ $st === 'checked_out' ? 'إجمالي المغادرين' : ($st === 'all' ? 'إجمالي النزلاء' : 'إجمالي المسجلين') }}:
            <strong>{{ $total }}</strong>
            @if($st !== 'checked_out')
            @if($overdueCount > 0)
            — <span class="text-red-600 font-semibold">{{ $overdueCount }} متأخر</span>
            @endif
            @if($todayCount > 0)
            — <span class="text-orange-600 font-semibold">{{ $todayCount }} خروجهم اليوم</span>
            @endif
            @endif
        </p>
    </div>
    <a href="{{ route('dashboard') }}" class="flex items-center gap-2 px-4 py-2 border border-gray-300 text-gray-600 rounded-lg text-sm hover:bg-gray-50 transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        لوحة التحكم
    </a>
</div>

<!-- Filters -->
<form method="GET" action="{{ route('reservations.expiring') }}" id="filters" class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-5">
    <div class="flex flex-wrap gap-3 items-end">

        {{-- Search: name or room --}}
        <div class="flex flex-col gap-1 flex-1 min-w-48">
            <label class="text-xs font-medium text-gray-500">بحث باسم النزيل أو رقم الغرفة</label>
            <div class="relative">
                <span class="absolute inset-y-0 right-3 flex items-center pointer-events-none">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </span>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="اسم النزيل أو رقم الغرفة... (اضغط Enter للبحث)"
                       class="w-full border border-gray-200 rounded-lg pr-9 pl-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400 transition bg-white">
            </div>
        </div>

        {{-- Check-in date --}}
        <div class="flex flex-col gap-1">
            <label class="text-xs font-medium text-gray-500">تاريخ الدخول</label>
            <input type="date" name="check_in_date" value="{{ request('check_in_date') }}"
                   onchange="this.form.submit()"
                   class="border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400 transition bg-white">
        </div>

        {{-- Check-out date --}}
        <div class="flex flex-col gap-1">
            <label class="text-xs font-medium text-gray-500">تاريخ المغادرة</label>
            <input type="date" name="check_out_date" value="{{ request('check_out_date') }}"
                   onchange="this.form.submit()"
                   class="border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400 transition bg-white">
        </div>

        {{-- Status --}}
        <div class="flex flex-col gap-1">
            <label class="text-xs font-medium text-gray-500">الحالة</label>
            <select name="status"
                    onchange="this.form.submit()"
                    class="border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400 transition bg-white">
                <option value="all"         {{ ($status ?? 'all') === 'all'         ? 'selected' : '' }}>الكل</option>
                <option value="checked_in"  {{ ($status ?? '') === 'checked_in'  ? 'selected' : '' }}>المقيمون حالياً</option>
                <option value="checked_out" {{ ($status ?? '') === 'checked_out' ? 'selected' : '' }}>المغادرون</option>
            </select>
        </div>

        <button type="submit" class="px-4 py-2 text-white rounded-lg text-sm font-medium self-end" style="background:#0F4C75;">بحث</button>

        @if(request()->hasAny(['search','check_in_date','check_out_date']) || (request('status') && request('status') !== 'all'))
        <a href="{{ route('reservations.expiring') }}"
           class="inline-flex items-center gap-1.5 px-4 py-2 border border-gray-300 text-gray-600 rounded-lg text-sm hover:bg-gray-50 transition self-end">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            إلغاء الفلترة
        </a>
        @endif
    </div>
</form>

<!-- Table -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100">
    <div class="overflow-x-auto">
        <table class="w-full text-sm" dir="rtl">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">#</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">النزيل</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الغرفة</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">تاريخ الدخول</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">تاريخ الخروج</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">المدة المتبقية</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">المبلغ المتبقي</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">إجراءات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100" x-data="{ renewOpen: null }">
                @forelse($reservations as $res)
                @php
                    $isCheckedOut = $res->status === 'checked_out';
                    $daysLeft = (int) now()->startOfDay()->diffInDays($res->check_out_date->copy()->startOfDay(), false);
                    if ($isCheckedOut) {
                        $badgeCls = 'bg-gray-100 text-gray-600 border border-gray-200';
                        $badgeLabel = 'غادر';
                        $rowCls = 'row-departed';
                    } elseif ($daysLeft < 0) {
                        $badgeCls = 'bg-red-100 text-red-700 border border-red-200';
                        $badgeLabel = 'متأخر ' . abs($daysLeft) . ' ' . (abs($daysLeft) === 1 ? 'يوم' : 'أيام');
                        $rowCls = 'bg-red-50';
                    } elseif ($daysLeft === 0) {
                        $badgeCls = 'bg-orange-100 text-orange-700 border border-orange-200';
                        $badgeLabel = 'اليوم';
                        $rowCls = 'bg-orange-50';
                    } elseif ($daysLeft === 1) {
                        $badgeCls = 'bg-yellow-100 text-yellow-700 border border-yellow-200';
                        $badgeLabel = 'غداً';
                        $rowCls = 'bg-yellow-50';
                    } elseif ($daysLeft <= 3) {
                        $badgeCls = 'bg-amber-100 text-amber-700 border border-amber-200';
                        $badgeLabel = $daysLeft . ' أيام';
                        $rowCls = '';
                    } else {
                        $badgeCls = 'bg-blue-50 text-blue-600 border border-blue-100';
                        $badgeLabel = $daysLeft . ' أيام';
                        $rowCls = '';
                    }
                    $balance = (float)$res->total_amount - (float)$res->paid_amount;
                @endphp
                {{-- Guest row --}}
                <tr class="{{ $rowCls }} hover:bg-gray-50 transition-colors">
                    <td class="px-4 py-3 text-gray-500 font-mono text-xs">#{{ $res->id }}</td>
                    <td class="px-4 py-3">
                        <a href="{{ route('reservations.show', $res) }}" class="font-semibold text-gray-800 hover:text-primary-700 transition">
                            {{ $res->guest?->full_name ?? '—' }}
                        </a>
                        @if($res->guest?->phone)
                        <div class="text-xs text-gray-400 mt-0.5">{{ $res->guest->phone }}</div>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <span class="font-medium text-gray-800">{{ $res->display_room_number }}</span>
                        <div class="text-xs text-gray-400">{{ $res->room_type_label }}</div>
                    </td>
                    <td class="px-4 py-3 text-gray-600 text-sm">{{ $res->check_in_date->format('d/m/Y') }}</td>
                    <td class="px-4 py-3 text-gray-600 text-sm font-medium">{{ $res->check_out_date->format('d/m/Y') }}</td>
                    <td class="px-4 py-3">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold {{ $badgeCls }}">
                            @if(!$isCheckedOut && $daysLeft < 0)
                            <svg class="w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                            @endif
                            {{ $badgeLabel }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        @if($balance > 0)
                        <span class="text-red-600 font-semibold text-sm">{{ number_format($balance, 0) }} ر.ي</span>
                        @else
                        <span class="text-green-600 text-xs">مسدد</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2 flex-wrap">
                            @unless($isCheckedOut)
                            @can('checkout.process')
                            <a href="{{ route('checkout.show', $res) }}"
                               class="inline-flex items-center gap-1 text-xs px-3 py-1.5 rounded-lg bg-red-600 text-white hover:bg-red-700 transition font-medium">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7"/></svg>
                                مغادرة
                            </a>
                            @endcan
                            @can('checkin.create')
                            <button
                                @click="renewOpen === {{ $res->id }} ? renewOpen = null : renewOpen = {{ $res->id }}"
                                :class="renewOpen === {{ $res->id }} ? 'bg-green-800 ring-2 ring-green-400' : 'bg-green-600 hover:bg-green-700'"
                                class="inline-flex items-center gap-1 text-xs px-3 py-1.5 rounded-lg text-white transition font-medium">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                تجديد
                            </button>
                            @endcan
                            @endunless
                            <a href="{{ route('reservations.show', $res) }}"
                               class="inline-flex items-center gap-1 text-xs px-3 py-1.5 rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50 transition">
                                تفاصيل
                            </a>
                        </div>
                    </td>
                </tr>
                {{-- Inline renewal form row --}}
                @can('checkin.create')
                <tr x-show="renewOpen === {{ $res->id }}" x-cloak class="bg-green-50">
                    <td colspan="8" class="px-6 py-4 border-t border-green-200">
                        <div class="flex items-center gap-2 mb-3">
                            <svg class="w-4 h-4 text-green-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            <span class="text-sm font-semibold text-green-800">تجديد إقامة: {{ $res->guest?->full_name }}</span>
                            <span class="text-xs text-green-600">— الغرفة {{ $res->display_room_number }} — تاريخ الخروج الحالي: {{ $res->check_out_date->format('d/m/Y') }}</span>
                        </div>
                        <form method="POST" action="{{ route('reservations.renew', $res) }}"
                              class="flex items-end gap-3 flex-wrap">
                            @csrf
                            <div class="flex flex-col gap-1">
                                <label class="text-xs font-medium text-gray-600">تاريخ الخروج الجديد *</label>
                                <input type="date" name="new_check_out_date" required
                                       min="{{ now()->addDay()->toDateString() }}"
                                       value="{{ now()->addDay()->toDateString() }}"
                                       class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-green-400 outline-none bg-white">
                            </div>
                            <div class="flex flex-col gap-1">
                                <label class="text-xs font-medium text-gray-600">دفعة مقدمة (ر.ي)</label>
                                <input type="number" name="advance_payment" min="0" step="0.01" placeholder="0"
                                       class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm w-32 focus:ring-2 focus:ring-green-400 outline-none bg-white">
                            </div>
                            <div class="flex flex-col gap-1">
                                <label class="text-xs font-medium text-gray-600">طريقة الدفع</label>
                                <select name="payment_method"
                                        class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-green-400 outline-none bg-white">
                                    <option value="cash">نقداً</option>
                                    <option value="pos">POS</option>
                                    <option value="bank_transfer">تحويل بنكي</option>
                                </select>
                            </div>
                            <div class="flex flex-col gap-1">
                                <label class="text-xs font-medium text-gray-600">ملاحظات</label>
                                <input type="text" name="notes" placeholder="سبب التجديد..."
                                       class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm w-44 focus:ring-2 focus:ring-green-400 outline-none bg-white">
                            </div>
                            <div class="flex gap-2">
                                <button type="submit"
                                        class="px-4 py-1.5 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm font-semibold transition">
                                    تأكيد التجديد
                                </button>
                                <button type="button" @click="renewOpen = null"
                                        class="px-4 py-1.5 border border-gray-300 text-gray-600 rounded-lg text-sm hover:bg-white transition">
                                    إلغاء
                                </button>
                            </div>
                        </form>
                    </td>
                </tr>
                @endcan
                @empty
                <tr>
                    <td colspan="8" class="px-4 py-12 text-center text-gray-400">
                        <svg class="w-10 h-10 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        لا يوجد نزلاء مسجلون حالياً
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-5 py-3 border-t border-gray-100">
        {{ $reservations->links() }}
    </div>
</div>

</div>
@endsection
