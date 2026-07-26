@extends('layouts.app')
@section('title', 'الحجوزات')
@section('page-title', 'الحجوزات')

@section('content')
<div x-data="{ search: '{{ request('search') }}' }">

<!-- Search & Filters -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-5">
    <form method="GET" class="flex flex-wrap gap-3 items-end">
        <div class="flex flex-col gap-1 flex-1 min-w-48">
            <label class="text-xs font-medium text-gray-500">بحث</label>
            {{-- بحث تلقائي أثناء الكتابة: يُرسَل النموذج بعد توقّف الموظف عن الكتابة
                 نصف ثانية، دون الحاجة لضغط زر البحث أو Enter. --}}
            <input type="text" name="search" value="{{ request('search') }}" x-model="search"
                   x-on:input.debounce.500ms="$el.form.requestSubmit()"
                   autofocus
                   x-init="$el.focus(); $el.setSelectionRange($el.value.length, $el.value.length)"
                   placeholder="اسم النزيل أو رقم الغرفة أو رقم الحجز..."
                   class="border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400 transition bg-white">
        </div>
        <div class="flex flex-col gap-1">
            <label class="text-xs font-medium text-gray-500">الحالة</label>
            <select name="status" onchange="this.form.submit()"
                    class="border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400 transition bg-white min-w-[140px]">
                <option value="">جميع الحالات</option>
                <option value="confirmed" {{ request('status')=='confirmed'?'selected':'' }}>محجوز</option>
                <option value="checked_in" {{ request('status')=='checked_in'?'selected':'' }}>مسجل دخول</option>
                <option value="checked_out" {{ request('status')=='checked_out'?'selected':'' }}>مسجل خروج</option>
            </select>
        </div>
        <div class="flex flex-col gap-1">
            <label class="text-xs font-medium text-gray-500">من تاريخ</label>
            <input type="date" name="from" value="{{ request('from') }}" onchange="this.form.submit()"
                   class="border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400 transition bg-white">
        </div>
        <div class="flex flex-col gap-1">
            <label class="text-xs font-medium text-gray-500">إلى تاريخ</label>
            <input type="date" name="to" value="{{ request('to') }}" onchange="this.form.submit()"
                   class="border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400 transition bg-white">
        </div>
        @can('users.manage')
        <div class="flex flex-col gap-1">
            <label class="text-xs font-medium text-gray-500">الموظف</label>
            <select name="created_by" onchange="this.form.submit()"
                    class="border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400 transition bg-white min-w-[140px]">
                <option value="">جميع الموظفين</option>
                @foreach($staff as $employee)
                <option value="{{ $employee->id }}" {{ request('created_by')==$employee->id?'selected':'' }}>{{ $employee->name }}</option>
                @endforeach
            </select>
        </div>
        @endcan
        <button type="submit" class="sr-only">بحث</button>
        @if(request()->hasAny(['search','status','from','to','created_by']))
        <a href="{{ route('reservations.index') }}"
           class="flex items-center gap-1.5 px-3 py-2 text-xs text-gray-500 border border-gray-200 rounded-lg hover:bg-gray-50 transition self-end">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            مسح
        </a>
        @endif
    </form>
</div>

<!-- Table -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100">
    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
        <h3 class="font-semibold text-gray-700">قائمة الحجوزات
            <span class="mr-2 text-sm font-normal text-gray-400">({{ $reservations->total() }} حجز)</span>
        </h3>
        @can('checkin.create')
        <div class="flex items-center gap-2">
            <a href="{{ route('checkin.create') }}"
               class="flex items-center gap-2 px-4 py-2 bg-primary-800 text-white rounded-lg text-sm hover:bg-primary-700 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
                تسجيل الدخول
            </a>
        </div>
        @endcan
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-100">
                <tr>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">#</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">النزيل</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الغرفة</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الدخول</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الخروج</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الإجمالي</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الدفع</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الحالة</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">بواسطة</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">إجراءات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($reservations as $res)
                @php
                    $statusColors = ['confirmed'=>'bg-blue-100 text-blue-800','checked_in'=>'bg-green-100 text-green-800','checked_out'=>'bg-gray-100 text-gray-800'];
                    $payColors = ['unpaid'=>'bg-red-100 text-red-800','partial'=>'bg-yellow-100 text-yellow-800','paid'=>'bg-green-100 text-green-800','deferred'=>'bg-purple-100 text-purple-800'];
                @endphp
                <tr class="hover:bg-blue-50 transition-colors cursor-pointer select-none"
                    onclick="if(!event.target.closest('a,button,form')) window.location='{{ route('reservations.show', $res) }}'"
                    onmousedown="if(!event.target.closest('a,button,form')) event.preventDefault()">
                    <td class="px-4 py-3 text-gray-400 text-xs">#{{ $res->id }}</td>
                    <td class="px-4 py-3">
                        <div class="font-medium text-gray-800">{{ $res->guest?->full_name ?? '—' }}</div>
                        <div class="text-xs text-gray-400">{{ $res->guest?->nationality ?? '' }}</div>
                    </td>
                    <td class="px-4 py-3 font-semibold text-gray-800">{{ $res->display_room_number }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $res->check_in_date?->format('d/m/Y') ?? '—' }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $res->check_out_date?->format('d/m/Y') ?? '—' }}</td>
                    <td class="px-4 py-3 font-medium text-gray-800">{{ number_format($res->total_amount, 0) }}</td>
                    <td class="px-4 py-3">
                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ $payColors[$res->payment_status] ?? 'bg-gray-100 text-gray-700' }}">
                            {{ $res->payment_status_label }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$res->status] ?? 'bg-gray-100 text-gray-700' }}">
                            {{ $res->status_label }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-xs text-gray-500">{{ $res->createdBy?->name ?? '—' }}</td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2 flex-wrap">
                            <a href="{{ route('reservations.show', $res) }}" class="text-xs font-medium" style="color:#0F4C75;">عرض</a>
                            @if($res->status === 'confirmed')
                            @can('checkin.create')
                            <form method="POST" action="{{ route('reservations.checkin', $res) }}" onsubmit="return confirm('تسجيل الدخول للحجز #{{ $res->id }}؟')">
                                @csrf @method('PATCH')
                                <button type="submit" class="text-xs font-medium text-green-600 hover:text-green-800">تسجيل الدخول</button>
                            </form>
                            @endcan
                            @endif
                            @can('checkin.view')
                            @if($res->status === 'checked_in')
                            <a href="{{ route('reservations.edit', $res) }}" class="text-xs font-medium text-gray-600 hover:text-gray-800">تعديل</a>
                            @endif
                            @if($res->status === 'checked_in')
                            <form method="POST" action="{{ route('reservations.cancel', $res) }}" onsubmit="return confirm('حذف الحجز #{{ $res->id }} نهائياً؟')">
                                @csrf @method('PATCH')
                                <button type="submit" class="text-xs font-medium text-red-500 hover:text-red-700">إلغاء</button>
                            </form>
                            @endif
                            @endcan
                            @if($res->status === 'checked_in')
                            @can('checkout.process')
                            <a href="{{ route('checkout.show', $res) }}" class="text-xs font-medium text-red-600 hover:text-red-800">خروج</a>
                            @endcan
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="10" class="px-4 py-10">
                    <x-empty-state
                        icon="📭"
                        title="لا توجد حجوزات"
                        message="ابدأ بإنشاء حجزة جديدة"
                        action_text="إنشاء حجزة"
                        action_url="{{ route('checkin.create') }}"
                    />
                </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($reservations->hasPages())
    <div class="px-6 py-4 border-t border-gray-100">
        <x-pagination-info :items="$reservations" />
        {{ $reservations->links() }}
    </div>
    @endif
</div>
</div>
@endsection
