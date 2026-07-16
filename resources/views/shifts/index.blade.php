@extends('layouts.app')
@section('title', 'الوردية')
@section('page-title', 'الوردية')

@section('content')
<div x-data="{ closeModal: {{ $errors->has('actual_amount') ? 'true' : 'false' }}, editWithdrawal: null, reopenModal: null, selectedPayments: [], bulkModal: false, selectedOrphans: [] }">

@if(session('success'))
<div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">{{ session('success') }}</div>
@endif
@if($errors->any())
<div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
    @foreach($errors->all() as $e)
    <p class="text-sm text-red-700">{{ $e }}</p>
    @endforeach
</div>
@endif

@if($activeShift)
{{-- ===== وردية مفتوحة ===== --}}

{{-- رأس الوردية --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-5 flex items-center justify-between flex-wrap gap-3">
    <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center">
            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <div>
            <p class="font-bold text-gray-800">وردية مفتوحة</p>
            <p class="text-xs text-gray-400">
                بدأت {{ $activeShift->started_at->format('H:i') }}
                — {{ $activeShift->shift_date->format('d/m/Y') }}
                @if(auth()->user()->isAdmin()) · {{ $activeShift->user->name }} @endif
            </p>
        </div>
    </div>
    <div class="flex gap-2 flex-wrap">
        <a href="{{ route('shifts.pdf', $activeShift) }}" target="_blank"
           class="flex items-center gap-2 px-4 py-2 bg-red-600 text-white rounded-lg text-sm hover:bg-red-700 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
            تصدير PDF
        </a>
        <button @click="closeModal=true"
                class="flex items-center gap-2 px-4 py-2 bg-gray-800 text-white rounded-lg text-sm hover:bg-gray-700 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            إقفال الوردية
        </button>
    </div>
</div>

{{-- إجماليات الوردية (ريال يمني فقط) --}}
@php
    $recv = $activeShift->total_received_yer;
    $wdr  = $activeShift->total_withdrawals_yer;
    $rfd  = $activeShift->total_refunds_yer;
    $net  = $activeShift->net_balance_yer;
@endphp
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-5">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
        <p class="text-xs text-gray-500 mb-1">المستلمات</p>
        <p class="text-xl font-bold text-green-700">{{ number_format($recv, 0) }} <span class="text-sm font-normal">ر.ي</span></p>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
        <p class="text-xs text-gray-500 mb-1">السحبيات</p>
        <p class="text-xl font-bold text-red-600">{{ number_format($wdr, 0) }} <span class="text-sm font-normal">ر.ي</span></p>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
        <p class="text-xs text-gray-500 mb-1">الاسترجاعات</p>
        <p class="text-xl font-bold text-rose-600">{{ number_format($rfd, 0) }} <span class="text-sm font-normal">ر.ي</span></p>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
        <p class="text-xs text-gray-500 mb-1">الصافي</p>
        <p class="text-xl font-bold {{ $net >= 0 ? 'text-primary-800' : 'text-red-700' }}">{{ number_format($net, 0) }} <span class="text-sm font-normal">ر.ي</span></p>
    </div>
</div>

{{-- المستلمات + السحبيات --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

@php $canReassign = auth()->user()->can('payments.create') || auth()->user()->isAdmin(); @endphp
<div class="bg-white rounded-xl shadow-sm border border-gray-100">
    <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between gap-3 flex-wrap">
        <h3 class="font-semibold text-gray-700">المستلمات ({{ $activeShift->payments->count() }})</h3>
        @if($canReassign)
        {{-- شريط الإجراء الجماعي: يظهر عند تحديد مستلمات --}}
        <div x-show="selectedPayments.length > 0" x-cloak class="flex items-center gap-2">
            <span class="text-xs text-indigo-700 font-medium">محدَّد: <span x-text="selectedPayments.length"></span></span>
            <button type="button" @click="bulkModal = true"
                    class="text-xs px-3 py-1.5 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 transition whitespace-nowrap">
                إقفال المحدد كوردية بتاريخ سابق
            </button>
            <button type="button" @click="selectedPayments = []"
                    class="text-xs px-2 py-1.5 rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-50 transition">
                إلغاء التحديد
            </button>
        </div>
        @endif
    </div>
    @if($activeShift->payments->count() > 0)
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50"><tr>
                @if($canReassign)
                <th class="px-4 py-2 text-center w-10">
                    <input type="checkbox" title="تحديد الكل"
                           @change="selectedPayments = $event.target.checked ? {{ $activeShift->payments->pluck('id')->toJson() }} : []"
                           :checked="selectedPayments.length === {{ $activeShift->payments->count() }}"
                           class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                </th>
                @endif
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">الوقت</th>
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">الغرفة / النزيل</th>
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">المبلغ</th>
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">الطريقة</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($activeShift->payments as $p)
                <tr :class="selectedPayments.includes({{ $p->id }}) ? 'bg-indigo-50' : ''">
                    @if($canReassign)
                    <td class="px-4 py-2 text-center">
                        <input type="checkbox" value="{{ $p->id }}" x-model.number="selectedPayments"
                               class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    </td>
                    @endif
                    <td class="px-4 py-2 text-gray-400 text-xs">{{ $p->payment_date->format('H:i') }}</td>
                    <td class="px-4 py-2 text-gray-700 text-xs">
                        <span class="font-medium">{{ $p->reservation?->display_room_number ?? '—' }}</span>
                        <span class="text-gray-400 mr-1">{{ $p->reservation?->guest?->full_name ?? '' }}</span>
                        @if($p->reservation && $p->reservation->trashed())
                        <span class="inline-block px-1.5 py-0.5 bg-rose-100 text-rose-700 rounded text-[10px] mr-1" title="حجز ملغى — قابله استرجاع">ملغى</span>
                        @endif
                    </td>
                    <td class="px-4 py-2 font-semibold text-green-700 whitespace-nowrap">{{ number_format($p->amount, 0) }} {{ $p->currency }}</td>
                    <td class="px-4 py-2 text-gray-500 text-xs">{{ match($p->method) { 'cash'=>'نقدي','pos'=>'POS','bank_transfer'=>'تحويل', default=>$p->method } }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @if($canReassign)
    <div class="px-5 py-2 border-t border-gray-50 text-xs text-gray-400">
        حدّد المستلمات التي تخصّ يوماً سابقاً ثم اضغط «إقفال المحدد كوردية بتاريخ سابق» لفصلها في وردية مستقلة بتاريخها.
    </div>
    @endif
    @else
    <div class="p-8 text-center text-gray-400 text-sm">لا توجد مستلمات بعد</div>
    @endif
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100">
    <div class="px-5 py-4 border-b border-gray-100">
        <h3 class="font-semibold text-gray-700">السحبيات ({{ $activeShift->withdrawals->count() }})</h3>
    </div>
    @if($activeShift->withdrawals->count() > 0)
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50"><tr>
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">المستلم</th>
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">المبلغ</th>
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">النوع</th>
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">البيان</th>
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">بواسطة</th>
                @canany(['withdrawal.edit','withdrawal.delete'])
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">إجراءات</th>
                @endcanany
            </tr></thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($activeShift->withdrawals as $w)
                <tr class="{{ $w->isExchange() ? 'bg-yellow-50' : '' }}">
                    <td class="px-4 py-2 text-gray-700 text-xs">{{ $w->withdrawn_by_name }}</td>
                    <td class="px-4 py-2 font-semibold text-red-600 whitespace-nowrap">
                        {{ number_format($w->amount, 0) }} {{ $w->currency }}
                        @if($w->isExchange() && $w->exchange_to_amount)
                        <span class="text-xs text-yellow-700 mr-1">← {{ number_format($w->exchange_to_amount, 0) }} {{ $w->exchange_to_currency }}</span>
                        @endif
                    </td>
                    <td class="px-4 py-2">
                        @if($w->isExchange())
                        <span class="px-1.5 py-0.5 bg-yellow-100 text-yellow-700 text-xs rounded">صرف عملة</span>
                        @else
                        <span class="text-gray-400 text-xs">مصروف</span>
                        @endif
                    </td>
                    <td class="px-4 py-2 text-gray-400 text-xs">{{ $w->notes ?? '—' }}</td>
                    <td class="px-4 py-2 text-xs text-gray-500">
                        @if($w->handed_by_name && $w->handed_by_name !== '-')
                        <span class="inline-flex items-center gap-1">
                            <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            {{ $w->handed_by_name }}
                        </span>
                        @else
                        <span class="text-gray-300">—</span>
                        @endif
                    </td>
                    @canany(['withdrawal.edit','withdrawal.delete'])
                    <td class="px-4 py-2">
                        <div class="flex items-center gap-1.5">
                            @can('withdrawal.edit')
                            @if(!$w->isExchange())
                            <button type="button"
                                @click="editWithdrawal = { id: {{ $w->id }}, amount: {{ $w->amount }}, name: '{{ addslashes($w->withdrawn_by_name) }}', notes: '{{ addslashes($w->notes ?? '') }}' }"
                                class="text-xs px-2 py-1 rounded border border-gray-200 text-gray-600 hover:bg-gray-50 transition">
                                تعديل
                            </button>
                            @endif
                            @endcan
                            @can('withdrawal.delete')
                            <form method="POST" action="{{ route('shifts.withdrawal.destroy', $w) }}" onsubmit="return confirm('حذف هذا السحب؟')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-xs px-2 py-1 rounded border border-red-200 text-red-600 hover:bg-red-50 transition">حذف</button>
                            </form>
                            @endcan
                        </div>
                    </td>
                    @endcanany
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
    <div class="p-8 text-center text-gray-400 text-sm">لا توجد سحبيات</div>
    @endif
</div>
</div>

@if($activeShift->refunds->count() > 0)
<div class="bg-white rounded-xl shadow-sm border border-gray-100 mt-5">
    <div class="px-5 py-4 border-b border-gray-100">
        <h3 class="font-semibold text-gray-700">الاسترجاعات ({{ $activeShift->refunds->count() }})</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50"><tr>
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">الوقت</th>
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">النزيل</th>
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">المبلغ</th>
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">الطريقة</th>
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">السبب</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($activeShift->refunds as $r)
                <tr>
                    <td class="px-4 py-2 text-gray-400 text-xs">{{ $r->refunded_at->format('H:i') }}</td>
                    <td class="px-4 py-2 text-gray-700 text-xs">{{ $r->reservation?->guest?->full_name ?? '—' }}</td>
                    <td class="px-4 py-2 font-semibold text-rose-600 whitespace-nowrap">-{{ number_format($r->amount, 0) }} {{ $r->currency }}</td>
                    <td class="px-4 py-2 text-gray-500 text-xs">{{ match($r->method) { 'cash'=>'نقدي','pos'=>'POS','bank_transfer'=>'تحويل', default=>$r->method } }}</td>
                    <td class="px-4 py-2 text-gray-400 text-xs max-w-xs truncate" title="{{ $r->reason }}">{{ $r->reason }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- مستلمات غير مرتبطة بأي وردية — تُضمّ إلى الوردية المفتوحة --}}
@if($orphanPayments->count() > 0)
<div class="bg-white rounded-xl shadow-sm border border-amber-200 mt-5">
    <div class="px-5 py-4 border-b border-amber-100 bg-amber-50 rounded-t-xl flex items-center justify-between gap-3 flex-wrap">
        <div>
            <h3 class="font-semibold text-amber-800">مستلمات غير مرتبطة بوردية ({{ $orphanPayments->count() }})</h3>
            <p class="text-xs text-amber-600 mt-0.5">دفعات لم تُربط بأي وردية — حدّدها واضمّها إلى ورديتك المفتوحة لتظهر عند الإقفال.</p>
        </div>
        <div x-show="selectedOrphans.length > 0" x-cloak class="flex items-center gap-2">
            <span class="text-xs text-amber-700 font-medium">محدَّد: <span x-text="selectedOrphans.length"></span></span>
            <form method="POST" action="{{ route('shifts.attachOrphans') }}" @submit="return confirm('ضمّ المستلمات المحددة إلى ورديتك المفتوحة؟')">
                @csrf @method('PATCH')
                <template x-for="pid in selectedOrphans" :key="pid">
                    <input type="hidden" name="payment_ids[]" :value="pid">
                </template>
                <button type="submit" class="text-xs px-3 py-1.5 rounded-lg bg-amber-600 text-white hover:bg-amber-700 transition whitespace-nowrap">
                    ضمّ المحدد إلى ورديتي
                </button>
            </form>
        </div>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50"><tr>
                <th class="px-4 py-2 text-center w-10">
                    <input type="checkbox" title="تحديد الكل"
                           @change="selectedOrphans = $event.target.checked ? {{ $orphanPayments->pluck('id')->toJson() }} : []"
                           :checked="selectedOrphans.length === {{ $orphanPayments->count() }}"
                           class="rounded border-gray-300 text-amber-600 focus:ring-amber-500">
                </th>
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">تاريخ ووقت المستلمة</th>
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">الغرفة / النزيل</th>
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">المبلغ</th>
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">النوع</th>
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">استلمها</th>
                @if($reassignTargets->isNotEmpty())
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">نقل إلى وردية محدَّدة</th>
                @endif
            </tr></thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($orphanPayments as $op)
                <tr :class="selectedOrphans.includes({{ $op->id }}) ? 'bg-amber-50' : ''">
                    <td class="px-4 py-2 text-center">
                        <input type="checkbox" value="{{ $op->id }}" x-model.number="selectedOrphans"
                               class="rounded border-gray-300 text-amber-600 focus:ring-amber-500">
                    </td>
                    {{-- تاريخ ووقت المستلمة كاملاً (يوم/شهر/سنة) — يحدّد الموظف منه أي
                         وردية تخصّها المستلمة فعلياً قبل نقلها إليها. --}}
                    <td class="px-4 py-2 text-gray-500 text-xs whitespace-nowrap">{{ $op->payment_date->format('d/m/Y H:i') }}</td>
                    <td class="px-4 py-2 text-gray-700 text-xs">
                        <span class="font-medium">{{ $op->reservation?->display_room_number ?? '—' }}</span>
                        <span class="text-gray-400 mr-1">{{ $op->reservation?->guest?->full_name ?? '' }}</span>
                    </td>
                    <td class="px-4 py-2 font-semibold text-green-700 whitespace-nowrap">{{ number_format($op->amount, 0) }} {{ $op->currency }}</td>
                    <td class="px-4 py-2 text-gray-500 text-xs">{{ match($op->type) { 'reservation'=>'حجز','renewal'=>'تجديد', default=>$op->type } }}</td>
                    <td class="px-4 py-2 text-gray-500 text-xs">{{ $op->receivedBy?->name ?? '—' }}</td>
                    @if($reassignTargets->isNotEmpty())
                    <td class="px-4 py-2">
                        <form method="POST" action="{{ route('shifts.reassignPayment', $op) }}" class="flex items-center gap-1.5"
                              onsubmit="return confirm('نقل هذه المستلمة إلى الوردية المحددة؟')">
                            @csrf @method('PATCH')
                            <select name="target_shift_id" required
                                    class="text-xs border border-gray-200 rounded-lg px-2 py-1 focus:ring-1 focus:ring-amber-500 outline-none max-w-[160px]">
                                <option value="">اختر وردية...</option>
                                @foreach($reassignTargets as $rt)
                                <option value="{{ $rt->id }}">
                                    {{ $rt->shift_date->format('d/m/Y') }} — {{ $rt->user?->name }}@if($rt->is_closed) (مقفلة)@endif
                                </option>
                                @endforeach
                            </select>
                            <button type="submit" class="text-xs px-2 py-1 rounded-lg bg-gray-700 text-white hover:bg-gray-800 transition whitespace-nowrap">
                                نقل
                            </button>
                        </form>
                    </td>
                    @endif
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

@else
{{-- ===== لا توجد وردية ===== --}}
<div class="max-w-md mx-auto">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-10 text-center">
        <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <h3 class="text-lg font-bold text-gray-700 mb-2">لا توجد وردية مفتوحة</h3>
        <p class="text-gray-400 text-sm">الوردية تُفتح تلقائياً عند تسجيل الدخول</p>
    </div>
</div>
@endif

{{-- ===== لوحة الأدمن: حالة صناديق جميع الموظفين ===== --}}
@if(auth()->user()->isAdmin() && $allUsersStatus->count() > 0)
<div class="mt-5 bg-white rounded-xl shadow-sm border border-blue-100">
    <div class="px-5 py-3 border-b border-blue-100 bg-blue-50 rounded-t-xl flex items-center justify-between">
        <h3 class="font-semibold text-blue-800 text-sm">حالة صناديق الموظفين</h3>
        <a href="{{ route('reports.shiftsHub', ['tab' => 'deficits']) }}" class="text-xs text-blue-600 hover:underline flex items-center gap-1">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            تقرير العجز التراكمي
        </a>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50"><tr>
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">الموظف</th>
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">حالة الوردية</th>
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">آخر وردية</th>
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">الصافي (ر.ي)</th>
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">الفرق</th>
                <th class="px-4 py-2"></th>
            </tr></thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($allUsersStatus as $us)
                @php $ls = $us['lastShift']; @endphp
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-2 font-medium text-gray-800">{{ $us['user']->name }}</td>
                    <td class="px-4 py-2">
                        @if($us['isActive'])
                        <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-700">مفتوحة</span>
                        @elseif($ls)
                        <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-500">مقفلة</span>
                        @else
                        <span class="text-gray-300 text-xs">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-2 text-gray-500 text-xs">{{ $ls?->shift_date->format('d/m/Y') ?? '—' }}</td>
                    <td class="px-4 py-2 font-medium text-gray-700">
                        @if($ls)
                        {{ number_format($ls->net_balance_yer, 0) }}
                        @else —
                        @endif
                    </td>
                    <td class="px-4 py-2">
                        @if($ls && $ls->shortfall !== null)
                            @php $sf = $ls->shortfall; @endphp
                            <span class="px-2 py-0.5 rounded-full text-xs font-semibold
                                {{ $sf == 0 ? 'bg-green-100 text-green-700' : ($sf < 0 ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700') }}">
                                {{ $sf == 0 ? 'مطابق' : ($sf < 0 ? '▼ '.number_format(abs($sf), 0) : '▲ '.number_format($sf, 0)) }}
                            </span>
                        @else
                            <span class="text-gray-300 text-xs">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-2">
                        @if($ls)
                        <a href="{{ route('shifts.pdf', $ls) }}" target="_blank"
                           class="flex items-center gap-1 px-2 py-1 border border-gray-200 text-gray-500 rounded text-xs hover:bg-gray-50 transition">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                            PDF
                        </a>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- ورديات سابقة --}}
@if($recentShifts->where('is_closed', true)->count() > 0)
<div class="mt-5 bg-white rounded-xl shadow-sm border border-gray-100">
    <div class="px-5 py-3 border-b border-gray-100">
        <h3 class="font-semibold text-gray-700 text-sm">الورديات السابقة</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50"><tr>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">التاريخ</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">البداية</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">النهاية</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">المستلمات (ر.ي)</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">السحبيات (ر.ي)</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الصافي (ر.ي)</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الفعلي (ر.ي)</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الفرق</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">ملاحظات الإقفال</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">إعادة الفتح</th>
                <th class="px-4 py-3"></th>
            </tr></thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($recentShifts->where('is_closed', true) as $s)
                @php
                    $net = $s->net_balance_yer;
                    $reopenEvts = collect($s->close_events ?? [])->filter(fn($e) => ($e['event'] ?? '') === 'reopen');
                @endphp
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 text-gray-600">{{ $s->shift_date->format('d/m/Y') }}</td>
                    <td class="px-4 py-3 text-gray-500 text-xs">{{ $s->started_at->format('H:i') }}</td>
                    <td class="px-4 py-3 text-gray-500 text-xs">{{ $s->ended_at?->format('H:i') ?? '—' }}</td>
                    <td class="px-4 py-3 font-medium text-green-700">{{ number_format($s->total_received_yer, 0) }}</td>
                    <td class="px-4 py-3 font-medium text-red-600">{{ number_format($s->total_withdrawals_yer, 0) }}</td>
                    <td class="px-4 py-3 font-bold {{ $net >= 0 ? 'text-primary-700' : 'text-red-700' }}">
                        {{ number_format($net, 0) }}
                    </td>
                    <td class="px-4 py-3 font-medium text-gray-700">
                        {{ $s->actual_amount !== null ? number_format($s->actual_amount, 0) : '—' }}
                    </td>
                    <td class="px-4 py-3">
                        @if($s->shortfall !== null)
                            @php $sf = $s->shortfall; @endphp
                            <span class="px-2 py-0.5 rounded-full text-xs font-semibold
                                {{ $sf == 0 ? 'bg-green-100 text-green-700' : ($sf < 0 ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700') }}">
                                {{ $sf == 0 ? 'مطابق' : ($sf < 0 ? '▼ '.number_format(abs($sf), 0) : '▲ '.number_format($sf, 0)) }}
                            </span>
                        @else
                            <span class="text-gray-300 text-xs">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        @if($s->notes)
                        <span class="inline-flex items-center gap-1 text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded px-2 py-0.5 max-w-[140px]" title="{{ $s->notes }}">
                            <svg class="w-3 h-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>
                            <span class="truncate">{{ $s->notes }}</span>
                        </span>
                        @else
                        <span class="text-gray-300 text-xs">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        @if($reopenEvts->isNotEmpty())
                            @php $lastReopen = $reopenEvts->last(); @endphp
                            <div class="space-y-1">
                                <span class="inline-flex items-center gap-1 text-xs text-orange-700 bg-orange-50 border border-orange-200 rounded px-2 py-0.5 whitespace-nowrap">
                                    <svg class="w-3 h-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/></svg>
                                    أُعيد فتحه@if($reopenEvts->count() > 1) ({{ $reopenEvts->count() }})@endif
                                </span>
                                <div class="text-xs text-gray-500">{{ $lastReopen['closed_by_name'] ?? '—' }}<span class="text-gray-300 mx-1">·</span>{{ isset($lastReopen['reopened_at']) ? \Carbon\Carbon::parse($lastReopen['reopened_at'])->format('d/m H:i') : '' }}</div>
                                @if(!empty($lastReopen['notes']))
                                <div class="text-xs text-orange-600 max-w-[140px] truncate" title="{{ $lastReopen['notes'] }}">{{ $lastReopen['notes'] }}</div>
                                @endif
                            </div>
                        @else
                            <span class="text-gray-300 text-xs">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-1.5 flex-wrap">
                            <a href="{{ route('shifts.pdf', $s) }}" target="_blank"
                               class="flex items-center gap-1 px-2 py-1 bg-red-600 text-white rounded text-xs hover:bg-red-700 transition">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                تصدير PDF
                            </a>
                            @can('shifts.reopen')
                            @if(!$activeShift)
                            <button type="button"
                                    @click="reopenModal = { id: {{ $s->id }}, url: '{{ route('shifts.reopen', $s) }}', date: '{{ $s->shift_date->format('d/m/Y') }}' }"
                                    class="flex items-center gap-1 px-2 py-1 border border-amber-300 text-amber-700 rounded text-xs hover:bg-amber-50 transition">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/></svg>
                                فتح
                            </button>
                            @else
                            {{-- زر إعادة الفتح مُعطَّل لأن لدى الموظف وردية مفتوحة حالياً — نوضّح
                                 السبب بدل إخفاء الزر بصمت، فلا يبدو الأمر وكأن الصلاحية معطوبة. --}}
                            <span title="لديك وردية مفتوحة حالياً — أقفلها أولاً لتتمكن من إعادة فتح وردية سابقة"
                                  class="flex items-center gap-1 px-2 py-1 border border-gray-200 text-gray-400 rounded text-xs cursor-not-allowed">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/></svg>
                                أقفل ورديتك الحالية أولاً
                            </span>
                            @endif
                            @endcan
                            @if(auth()->user()->isAdmin() && $s->shortfall !== null && $s->shortfall < 0)
                                @if($s->salary_deducted_at)
                                <span class="flex items-center gap-1 px-2 py-1 bg-gray-100 text-gray-400 rounded text-xs" title="تم الخصم بتاريخ {{ $s->salary_deducted_at->format('d/m/Y') }}">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    مخصوم
                                </span>
                                @else
                                <form method="POST" action="{{ route('shifts.deductSalary', $s) }}"
                                      onsubmit="return confirm('هل تريد خصم عجز هذه الوردية ({{ number_format(abs($s->shortfall), 0) }} ر.ي) من راتب الموظف؟')">
                                    @csrf
                                    <button type="submit"
                                            class="flex items-center gap-1 px-2 py-1 border border-red-300 text-red-600 rounded text-xs hover:bg-red-50 transition">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        خصم من الراتب
                                    </button>
                                </form>
                                @endif
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- ورديات موظفين آخرين قابلة لإعادة الفتح — للمدير فقط (صلاحية shifts.reopen
     وحدها تمنح الموظف حق إعادة فتح ورديته هو حصراً، لا وردية أي موظف آخر). --}}
@if(auth()->user()->isAdmin())
@can('shifts.reopen')
@if($reopenableShifts->isNotEmpty())
<div class="mt-5 bg-white rounded-xl shadow-sm border border-gray-100">
    <div class="px-5 py-3 border-b border-gray-100">
        <h3 class="font-semibold text-gray-700 text-sm">إعادة فتح وردية موظف آخر</h3>
        <p class="text-xs text-gray-400 mt-0.5">ورديات مقفلة لموظفين لا يملكون وردية مفتوحة حالياً</p>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50"><tr>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الموظف</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">التاريخ</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">وقت الإقفال</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الصافي (ر.ي)</th>
                <th class="px-4 py-3"></th>
            </tr></thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($reopenableShifts as $s)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-medium text-gray-700">{{ $s->user?->name ?? '—' }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $s->shift_date->format('d/m/Y') }}</td>
                    <td class="px-4 py-3 text-gray-500 text-xs">{{ $s->closed_at?->format('d/m/Y H:i') ?? '—' }}</td>
                    <td class="px-4 py-3 font-bold {{ $s->net_balance_yer >= 0 ? 'text-primary-700' : 'text-red-700' }}">
                        {{ number_format($s->net_balance_yer, 0) }}
                    </td>
                    <td class="px-4 py-3">
                        <button type="button"
                                @click="reopenModal = { id: {{ $s->id }}, url: '{{ route('shifts.reopen', $s) }}', date: '{{ $s->shift_date->format('d/m/Y') }} — {{ $s->user?->name }}' }"
                                class="flex items-center gap-1 px-2 py-1 border border-amber-300 text-amber-700 rounded text-xs hover:bg-amber-50 transition">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/></svg>
                            فتح
                        </button>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
@endcan
@endif

{{-- Modal: تعديل سحب --}}
@can('withdrawal.edit')
<div x-show="editWithdrawal !== null" x-cloak class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4" @click.self="editWithdrawal=null">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-bold text-gray-800">تعديل السحب</h3>
            <button @click="editWithdrawal=null" class="text-gray-400 hover:text-gray-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <template x-if="editWithdrawal">
        <form method="POST" :action="`/shifts/withdrawals/${editWithdrawal.id}`" class="p-6 space-y-4">
            @csrf
            @method('PATCH')
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">المبلغ (ر.ي) *</label>
                <input type="number" name="amount" step="0.01" min="0.01" required
                       :value="editWithdrawal.amount"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 outline-none">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">اسم المستلم *</label>
                <input type="text" name="withdrawn_by_name" required
                       :value="editWithdrawal.name"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 outline-none">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">السبب / البيان</label>
                <input type="text" name="notes"
                       :value="editWithdrawal.notes"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 outline-none">
            </div>
            <div class="flex gap-3">
                <button type="submit" class="flex-1 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-semibold transition">
                    حفظ التعديلات
                </button>
                <button type="button" @click="editWithdrawal=null" class="flex-1 py-2.5 border border-gray-300 text-gray-600 rounded-lg text-sm hover:bg-gray-50 transition">
                    إلغاء
                </button>
            </div>
        </form>
        </template>
    </div>
</div>
@endcan

{{-- Modal: إقفال الوردية --}}
@if($activeShift)
<div x-show="closeModal" x-cloak class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4" @click.self="closeModal=false">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm"
         x-data="{
             systemBalance: {{ $activeShift->net_balance_yer }},
             actualAmount: '',
             get diff() {
                 if (this.actualAmount === '' || this.actualAmount === null) return null;
                 return parseFloat(this.actualAmount) - this.systemBalance;
             }
         }">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between" style="background:#0F4C75; border-radius: 1rem 1rem 0 0;">
            <h3 class="font-bold text-white">إقفال الوردية</h3>
            <button @click="closeModal=false" class="text-white/70 hover:text-white">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form method="POST" action="{{ route('shifts.close') }}" class="p-6 space-y-4">
            @csrf

            {{-- ملخص النظام --}}
            <div class="bg-gray-50 rounded-xl p-4 text-sm space-y-2 border border-gray-100">
                <div class="flex justify-between items-center">
                    <span class="text-gray-500">المستلمات</span>
                    <span class="font-semibold text-green-700">{{ number_format($activeShift->total_received_yer, 0) }} ر.ي</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-500">السحبيات</span>
                    <span class="font-semibold text-red-600">{{ number_format($activeShift->total_withdrawals_yer, 0) }} ر.ي</span>
                </div>
                @if($activeShift->total_refunds_yer > 0)
                <div class="flex justify-between items-center">
                    <span class="text-gray-500">الاسترجاعات</span>
                    <span class="font-semibold text-rose-600">{{ number_format($activeShift->total_refunds_yer, 0) }} ر.ي</span>
                </div>
                @endif
                <div class="flex justify-between items-center border-t border-gray-200 pt-2">
                    <span class="font-semibold text-gray-700">الصافي حسب النظام</span>
                    <span class="font-bold text-lg" style="color:#0F4C75">{{ number_format($activeShift->net_balance_yer, 0) }} ر.ي</span>
                </div>
            </div>

            {{-- المبلغ الفعلي --}}
            <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1.5">
                    المبلغ الفعلي في الصندوق (ر.ي)
                    <span class="text-red-500">*</span>
                </label>
                <input type="number" name="actual_amount" x-model="actualAmount"
                       step="1" min="0" required
                       placeholder="أدخل المبلغ الذي عددته فعلياً..."
                       class="w-full border {{ $errors->has('actual_amount') ? 'border-red-400 bg-red-50' : 'border-gray-300' }} rounded-lg px-3 py-2.5 text-sm outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-100 transition">
                @error('actual_amount')
                <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- عرض الفرق --}}
            <div x-show="diff !== null" x-transition>
                <div class="rounded-xl p-3 text-sm font-medium text-center"
                     :class="{
                         'bg-green-50 border border-green-200 text-green-700': diff === 0,
                         'bg-red-50 border border-red-200 text-red-700': diff < 0,
                         'bg-amber-50 border border-amber-200 text-amber-700': diff > 0
                     }">
                    <template x-if="diff === 0">
                        <span>✓ المبلغ مطابق تماماً</span>
                    </template>
                    <template x-if="diff < 0">
                        <span>نقص في الصندوق: <strong x-text="Math.abs(diff).toLocaleString()"></strong> ر.ي</span>
                    </template>
                    <template x-if="diff > 0">
                        <span>زيادة في الصندوق: <strong x-text="diff.toLocaleString()"></strong> ر.ي</span>
                    </template>
                </div>
            </div>

            {{-- وقت الإقفال الفعلي (اختياري) --}}
            <div x-data="{ showTime: false }">
                <button type="button" @click="showTime = !showTime"
                        class="text-xs text-gray-500 hover:text-gray-700 flex items-center gap-1 mb-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span x-text="showTime ? 'إخفاء وقت الإقفال الفعلي' : 'تحديد وقت إقفال فعلي سابق (وردية منسية)'"></span>
                </button>
                <div x-show="showTime" x-cloak>
                    <input type="datetime-local" name="ended_at"
                           max="{{ now()->format('Y-m-d\TH:i') }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-100 transition">
                    <p class="text-xs text-gray-400 mt-1">اتركه فارغاً لاستخدام الوقت الحالي. استخدمه إذا نسيت إقفال الوردية في وقتها الفعلي.</p>
                </div>
            </div>

            {{-- ملاحظات --}}
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">ملاحظات الإقفال</label>
                <textarea name="notes" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none resize-none focus:border-blue-400 focus:ring-2 focus:ring-blue-100 transition"></textarea>
            </div>

            <button type="submit" class="w-full py-2.5 text-white rounded-lg text-sm font-semibold transition"
                    style="background:#0F4C75;"
                    onclick="return confirm('هل أنت متأكد من إقفال الوردية؟')">
                تأكيد الإقفال
            </button>
        </form>
    </div>
</div>
@endif

{{-- Modal: إعادة فتح الوردية --}}
@can('shifts.reopen')
<div x-show="reopenModal !== null" x-cloak class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4" @click.self="reopenModal=null">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between" style="background:#92400e; border-radius: 1rem 1rem 0 0;">
            <h3 class="font-bold text-white flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/></svg>
                إعادة فتح الوردية
            </h3>
            <button @click="reopenModal=null" class="text-white/70 hover:text-white">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <template x-if="reopenModal">
        <form method="POST" :action="reopenModal.url" class="p-6 space-y-4">
            @csrf
            <div class="bg-amber-50 border border-amber-200 rounded-xl p-3 text-sm text-amber-800">
                <p class="font-semibold mb-1">تنبيه!</p>
                <p class="text-xs leading-relaxed">إعادة الفتح تسمح بالتعديل على الوردية مجدداً. سيتم تسجيل هذه العملية باسم المستخدم والسبب في سجل الوردية للمراجعة.</p>
            </div>
            <div>
                <p class="text-xs text-gray-500 mb-2">الوردية: <span class="font-semibold text-gray-700" x-text="reopenModal.date"></span></p>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1.5">
                    سبب إعادة الفتح <span class="text-red-500">*</span>
                </label>
                <textarea name="reopen_notes" rows="3" required
                          placeholder="اكتب سبب إعادة فتح الوردية..."
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none resize-none focus:border-amber-400 focus:ring-2 focus:ring-amber-100 transition"></textarea>
            </div>
            <div class="flex gap-3">
                <button type="submit"
                        class="flex-1 py-2.5 text-white rounded-lg text-sm font-semibold transition hover:opacity-90"
                        style="background:#92400e;"
                        onclick="return confirm('هل أنت متأكد من إعادة فتح هذه الوردية؟')">
                    تأكيد إعادة الفتح
                </button>
                <button type="button" @click="reopenModal=null"
                        class="flex-1 py-2.5 border border-gray-300 text-gray-600 rounded-lg text-sm hover:bg-gray-50 transition">
                    إلغاء
                </button>
            </div>
        </form>
        </template>
    </div>
</div>
@endcan

{{-- Modal: إقفال المستلمات المحددة كوردية بتاريخ سابق --}}
@if($activeShift && (auth()->user()->can('payments.create') || auth()->user()->isAdmin()))
<div x-show="bulkModal" x-cloak class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4" @click.self="bulkModal=false"
     x-data="{
        get selectedTotal() {
            const map = {{ $activeShift->payments->mapWithKeys(fn($p) => [$p->id => round((float)$p->amount)])->toJson() }};
            return selectedPayments.reduce((s, id) => s + (map[id] || 0), 0);
        },
        actualAmount: ''
     }">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between" style="background:#0F4C75; border-radius: 1rem 1rem 0 0;">
            <h3 class="font-bold text-white flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                إقفال كوردية بتاريخ سابق
            </h3>
            <button @click="bulkModal=false" class="text-white/70 hover:text-white">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form method="POST" action="{{ route('shifts.closePast') }}" class="p-6 space-y-4">
            @csrf
            {{-- المستلمات المحددة تُرسل كحقول مخفية --}}
            <template x-for="pid in selectedPayments" :key="pid">
                <input type="hidden" name="payment_ids[]" :value="pid">
            </template>
            <div class="bg-blue-50 border border-blue-200 rounded-xl p-3 text-xs text-blue-800 leading-relaxed">
                ستُفصَل <strong x-text="selectedPayments.length"></strong> مستلمة (بإجمالي <strong x-text="selectedTotal.toLocaleString()"></strong> ر.ي) في وردية مستقلة بالتاريخ الذي تحدده، وتُقفَل مباشرة. يبقى الباقي في الوردية المفتوحة الحالية.
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1.5">تاريخ الوردية <span class="text-red-500">*</span></label>
                <input type="date" name="shift_date" required max="{{ now()->format('Y-m-d') }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-100 transition">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1.5">المبلغ الفعلي في الصندوق (ر.ي) <span class="text-gray-400 font-normal">— اختياري</span></label>
                <input type="number" name="actual_amount" x-model="actualAmount" step="1" min="0"
                       :placeholder="`الصافي حسب المحدد: ${selectedTotal.toLocaleString()}`"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-100 transition">
                <p class="text-xs text-gray-400 mt-1">اتركه فارغاً إن لم ترغب بتسجيل مطابقة الصندوق لهذه الوردية.</p>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">ملاحظات</label>
                <textarea name="notes" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none resize-none focus:border-blue-400 focus:ring-2 focus:ring-blue-100 transition"></textarea>
            </div>
            <div class="flex gap-3">
                <button type="submit"
                        class="flex-1 py-2.5 text-white rounded-lg text-sm font-semibold transition hover:opacity-90"
                        style="background:#0F4C75;"
                        onclick="return confirm('تأكيد فصل المستلمات المحددة وإقفالها كوردية بالتاريخ المحدد؟')">
                    تأكيد الإقفال
                </button>
                <button type="button" @click="bulkModal=false"
                        class="flex-1 py-2.5 border border-gray-300 text-gray-600 rounded-lg text-sm hover:bg-gray-50 transition">
                    إلغاء
                </button>
            </div>
        </form>
    </div>
</div>
@endif

</div>
@endsection
