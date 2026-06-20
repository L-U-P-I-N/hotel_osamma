@extends('layouts.app')
@section('title', 'الحجوزات')
@section('page-title', 'الحجوزات')

@section('content')
<div x-data="{ search: '{{ request('search') }}' }">

<!-- Search & Filters -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-5">
    <form method="GET" class="flex flex-wrap gap-3 items-end">
        <div class="flex-1 min-w-48">
            <label class="block text-xs font-medium text-gray-600 mb-1">بحث</label>
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="اسم النزيل أو رقم الغرفة..."
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">الحالة</label>
            <select name="status" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 outline-none">
                <option value="">جميع الحالات</option>
                <option value="checked_in" {{ request('status')=='checked_in'?'selected':'' }}>مسجل دخول</option>
                <option value="checked_out" {{ request('status')=='checked_out'?'selected':'' }}>مسجل خروج</option>
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">من تاريخ</label>
            <input type="date" name="from" value="{{ request('from') }}"
                   class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 outline-none">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">إلى تاريخ</label>
            <input type="date" name="to" value="{{ request('to') }}"
                   class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 outline-none">
        </div>
        @can('users.manage')
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">الموظف</label>
            <select name="created_by" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 outline-none">
                <option value="">جميع الموظفين</option>
                @foreach($staff as $employee)
                <option value="{{ $employee->id }}" {{ request('created_by')==$employee->id?'selected':'' }}>{{ $employee->name }}</option>
                @endforeach
            </select>
        </div>
        @endcan
        <button type="submit" class="px-4 py-2 bg-primary-800 text-white rounded-lg text-sm hover:bg-primary-700 transition">بحث</button>
        <a href="{{ route('reservations.index') }}" class="px-4 py-2 bg-gray-100 text-gray-600 rounded-lg text-sm hover:bg-gray-200 transition">إعادة تعيين</a>
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
                    $statusColors = ['checked_in'=>'bg-green-100 text-green-800','checked_out'=>'bg-gray-100 text-gray-800'];
                    $payColors = ['unpaid'=>'bg-red-100 text-red-800','partial'=>'bg-yellow-100 text-yellow-800','paid'=>'bg-green-100 text-green-800','deferred'=>'bg-purple-100 text-purple-800'];
                @endphp
                <tr class="hover:bg-blue-50 transition-colors cursor-pointer"
                    onclick="if(!event.target.closest('a,button,form')) window.location='{{ route('reservations.show', $res) }}'">
                    <td class="px-4 py-3 text-gray-400 text-xs">#{{ $res->id }}</td>
                    <td class="px-4 py-3">
                        <div class="font-medium text-gray-800">{{ $res->guest?->full_name ?? '—' }}</div>
                        <div class="text-xs text-gray-400">{{ $res->guest?->nationality ?? '' }}</div>
                    </td>
                    <td class="px-4 py-3 font-semibold text-gray-800">{{ $res->room?->room_number ?? '—' }}</td>
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
                <tr><td colspan="10" class="px-4 py-10 text-center text-gray-400">لا توجد حجوزات</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($reservations->hasPages())
    <div class="px-6 py-4 border-t border-gray-100">
        {{ $reservations->links() }}
    </div>
    @endif
</div>
</div>
@endsection
