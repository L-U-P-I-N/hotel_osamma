@extends('layouts.app')
@section('title', 'المبالغ المتبقية للنزلاء')
@section('page-title', 'المبالغ المتبقية للنزلاء')
@section('back-url', route('reservations.index'))

@section('content')
<div dir="rtl" class="space-y-5">

{{-- ملخّص الالتزام: يُحسب على كل الأرصدة لا على الصفحة المعروضة --}}
<div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
    <div class="bg-white rounded-xl border border-amber-200 p-4">
        <div class="text-2xl font-black text-amber-700 leading-none">{{ number_format($openTotal, 0) }}</div>
        <div class="text-xs text-gray-500 mt-1">إجمالي المستحق للنزلاء (ر.ي)</div>
    </div>
    <div class="bg-white rounded-xl border border-gray-100 p-4">
        <div class="text-2xl font-black leading-none" style="color:#0F4C75;">{{ $openCount }}</div>
        <div class="text-xs text-gray-500 mt-1">عدد الأرصدة القائمة</div>
    </div>
    <div class="bg-white rounded-xl border border-gray-100 p-4">
        <div class="text-2xl font-black text-green-600 leading-none">{{ number_format($settledSum, 0) }}</div>
        <div class="text-xs text-gray-500 mt-1">ما صُرف للنزلاء سابقاً (ر.ي)</div>
    </div>
</div>

<div class="bg-amber-50 border border-amber-200 rounded-xl px-4 py-3 text-xs text-amber-800">
    كل مبلغ هنا نقدٌ في صندوق الفندق لكنه حقُّ نزيل (ليالٍ دفعها ولم يُقِمها مثلاً). يبقى
    التزاماً على الفندق حتى يُصرف لصاحبه — فيخرج من صندوق الوردية ويظهر في تقرير
    الاسترجاعات — أو يُسقَط بتنازله فيعود إيراداً.
</div>

{{-- فلترة --}}
<form method="GET" class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
    <div class="flex flex-wrap gap-3 items-end">
        <div class="flex flex-col gap-1 flex-1 min-w-48">
            <label class="text-xs font-medium text-gray-500">بحث باسم النزيل أو رقم الغرفة أو رقم الحجز</label>
            <input type="text" name="search" value="{{ $search }}" placeholder="اسم النزيل / رقم الغرفة / رقم الحجز…"
                   class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400 transition bg-white">
        </div>
        <div class="flex flex-col gap-1">
            <label class="text-xs font-medium text-gray-500">الحالة</label>
            <select name="status" onchange="this.form.submit()"
                    class="border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none bg-white min-w-[160px]">
                <option value="all" {{ $status === 'all' ? 'selected' : '' }}>الكل</option>
                @foreach(\App\Models\GuestCredit::STATUS_LABELS as $key => $label)
                <option value="{{ $key }}" {{ $status === $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="px-4 py-2 text-white rounded-lg text-sm font-medium self-end" style="background:#0F4C75;">بحث</button>
        @if($search !== '' || $status !== \App\Models\GuestCredit::STATUS_OPEN)
        <a href="{{ route('guest-credits.index') }}"
           class="inline-flex items-center gap-1.5 px-4 py-2 border border-gray-300 text-gray-600 rounded-lg text-sm hover:bg-gray-50 transition self-end">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            إلغاء الفلترة
        </a>
        @endif
    </div>
</form>

{{-- الجدول --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-100">
    <div class="overflow-x-auto">
        <table class="w-full text-sm" dir="rtl">
            <thead class="bg-gray-50 border-b border-gray-100">
                <tr>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الحجز</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">النزيل</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الغرفة</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">المبلغ</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">السبب</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الحالة</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">سُجّل بواسطة</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">إجراءات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($credits as $credit)
                @php
                    $statusCls = [
                        \App\Models\GuestCredit::STATUS_OPEN      => 'bg-amber-100 text-amber-800',
                        \App\Models\GuestCredit::STATUS_SETTLED   => 'bg-green-100 text-green-800',
                        \App\Models\GuestCredit::STATUS_CANCELLED => 'bg-gray-100 text-gray-700',
                    ][$credit->status] ?? 'bg-gray-100 text-gray-700';
                @endphp
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-4 py-3 text-xs">
                        <a href="{{ route('reservations.show', $credit->reservation_id) }}"
                           class="font-mono font-semibold hover:underline" style="color:#0F4C75;">#{{ $credit->reservation_id }}</a>
                    </td>
                    <td class="px-4 py-3 font-medium text-gray-800">{{ $credit->guest?->full_name ?? '—' }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $credit->reservation?->room?->room_number ?? '—' }}</td>
                    <td class="px-4 py-3 font-bold {{ $credit->is_open ? 'text-amber-700' : 'text-gray-500' }}">
                        {{ number_format($credit->amount, 0) }} ر.ي
                    </td>
                    <td class="px-4 py-3 text-xs text-gray-600 max-w-[16rem]">
                        {{ $credit->reason }}
                        @if($credit->settlement_notes)
                        <div class="text-[11px] text-gray-400 mt-0.5">{{ $credit->settlement_notes }}</div>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ $statusCls }}">{{ $credit->status_label }}</span>
                        @if($credit->settled_at)
                        <div class="text-[11px] text-gray-400 mt-0.5">{{ $credit->settled_at->format('d/m/Y H:i') }}</div>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-xs text-gray-500">
                        {{ $credit->createdBy?->name ?? '—' }}
                        @if($credit->settledBy)
                        <div class="text-[11px] text-gray-400">أنهاه: {{ $credit->settledBy->name }}</div>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        @can('guest_credits.manage')
                        @if($credit->is_open)
                        <div x-data="{ pane: null }" class="space-y-2">
                            <div class="flex items-center gap-2">
                                <button type="button" @click="pane = pane === 'settle' ? null : 'settle'"
                                        class="text-xs px-3 py-1.5 rounded-lg bg-green-600 hover:bg-green-700 text-white font-medium transition">صرف للنزيل</button>
                                <button type="button" @click="pane = pane === 'cancel' ? null : 'cancel'"
                                        class="text-xs px-3 py-1.5 rounded-lg border border-gray-300 text-gray-600 hover:bg-gray-50 transition">إسقاط</button>
                            </div>

                            <form x-show="pane === 'settle'" x-cloak method="POST"
                                  action="{{ route('guest-credits.settle', $credit) }}"
                                  onsubmit="return confirm('صرف {{ number_format($credit->amount, 0) }} ر.ي للنزيل من صندوق الوردية؟')"
                                  class="flex flex-wrap items-end gap-2 bg-green-50 border border-green-200 rounded-lg p-2.5">
                                @csrf
                                <div class="flex flex-col gap-1">
                                    <label class="text-[11px] font-semibold text-gray-600">طريقة الصرف *</label>
                                    <select name="settlement_method" required
                                            class="border border-gray-300 rounded-lg px-2 py-1.5 text-xs outline-none bg-white">
                                        <option value="cash">نقدي</option>
                                        <option value="pos">POS</option>
                                        <option value="bank_transfer">تحويل بنكي</option>
                                    </select>
                                </div>
                                <div class="flex flex-col gap-1">
                                    <label class="text-[11px] font-semibold text-gray-600">ملاحظة</label>
                                    <input type="text" name="settlement_notes" maxlength="500" placeholder="اختياري"
                                           class="border border-gray-300 rounded-lg px-2 py-1.5 text-xs w-40 outline-none bg-white">
                                </div>
                                <button type="submit" class="px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white rounded-lg text-xs font-bold transition">تأكيد الصرف</button>
                            </form>

                            <form x-show="pane === 'cancel'" x-cloak method="POST"
                                  action="{{ route('guest-credits.cancel', $credit) }}"
                                  onsubmit="return confirm('إسقاط هذا الرصيد؟ سيعود المبلغ إيراداً للفندق.')"
                                  class="flex flex-wrap items-end gap-2 bg-gray-50 border border-gray-200 rounded-lg p-2.5">
                                @csrf
                                <div class="flex flex-col gap-1">
                                    <label class="text-[11px] font-semibold text-gray-600">سبب الإسقاط *</label>
                                    <input type="text" name="reason" required maxlength="500" placeholder="مثال: تنازل النزيل عن المبلغ"
                                           class="border border-gray-300 rounded-lg px-2 py-1.5 text-xs w-56 outline-none bg-white">
                                </div>
                                <button type="submit" class="px-3 py-1.5 bg-gray-700 hover:bg-gray-800 text-white rounded-lg text-xs font-bold transition">تأكيد الإسقاط</button>
                            </form>
                        </div>
                        @else
                        <span class="text-xs text-gray-400">—</span>
                        @endif
                        @else
                        <span class="text-xs text-gray-400">لا صلاحية</span>
                        @endcan
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-4 py-12 text-center text-gray-400 text-sm">
                        <svg class="w-10 h-10 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        لا توجد مبالغ متبقية للنزلاء بهذا الفلتر
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($credits->hasPages())
    <div class="px-5 py-3 border-t border-gray-100">
        <x-pagination-info :items="$credits" />
        {{ $credits->links() }}
    </div>
    @endif
</div>

</div>
@endsection
