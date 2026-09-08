@extends('layouts.app')
@section('title', 'كشف الحسابات')
@section('page-title', 'كشف الحسابات')

@section('content')
<div class="space-y-5" dir="rtl">

    <div>
        <h2 class="text-2xl font-black text-gray-800">كشف الحسابات</h2>
        <p class="text-gray-500 text-sm mt-1">
            ابحث بالاسم عن أي شخص له علاقة مالية بالفندق — نزيل، مرافق، موظف، أو مستخدم نظام —
            وافتح كشف حسابه الكامل (الحجوزات، الفواتير، الدفعات، الراتب والسحوبات، أو حركة الصندوق).
        </p>
    </div>

    <form method="GET" action="{{ route('reports.accountSearch') }}" class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <div class="flex gap-2">
            <input type="text" name="q" value="{{ $q }}" autofocus
                   placeholder="اكتب اسم النزيل، المرافق، الموظف، أو المستخدم..."
                   class="flex-1 border border-gray-200 rounded-lg px-4 py-2.5 text-sm outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400 bg-white">
            <button type="submit" class="px-6 py-2.5 text-white rounded-lg text-sm font-semibold" style="background:#0F4C75;">
                بحث
            </button>
        </div>
    </form>

    @if($q !== '' && mb_strlen($q) < 2)
    <p class="text-sm text-amber-600 bg-amber-50 border border-amber-200 rounded-xl px-4 py-3">اكتب حرفين على الأقل للبحث.</p>
    @elseif($q !== '')
        @php
            $totalResults = $guests->count() + $companions->count() + $employees->count() + $users->count();
        @endphp

        @if($totalResults === 0)
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 py-16 text-center text-gray-400">
            لا توجد نتائج مطابقة لـ «{{ $q }}»
        </div>
        @else

        {{-- النزلاء --}}
        @if($guests->isNotEmpty())
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-5 py-3 border-b border-gray-100 bg-gray-50 flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                <h3 class="font-semibold text-gray-700 text-sm">نزلاء ({{ $guests->count() }})</h3>
            </div>
            <div class="divide-y divide-gray-50">
                @foreach($guests as $guest)
                <a href="{{ route('guests.statement', $guest) }}"
                   class="flex items-center justify-between gap-3 px-5 py-3 hover:bg-gray-50 transition">
                    <div>
                        <div class="font-semibold text-gray-800 text-sm">{{ $guest->full_name }}</div>
                        <div class="text-xs text-gray-400 mt-0.5">{{ $guest->nationality ?? '—' }}</div>
                    </div>
                    <span class="text-xs text-blue-600 font-semibold whitespace-nowrap">فتح كشف الحساب ←</span>
                </a>
                @endforeach
            </div>
        </div>
        @endif

        {{-- المرافقون --}}
        @if($companions->isNotEmpty())
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-5 py-3 border-b border-gray-100 bg-gray-50 flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-violet-500"></span>
                <h3 class="font-semibold text-gray-700 text-sm">مرافقون ({{ $companions->count() }})</h3>
            </div>
            <div class="divide-y divide-gray-50">
                @foreach($companions as $companion)
                @php $mainGuest = $companion->reservation?->guest; @endphp
                <div class="flex items-center justify-between gap-3 px-5 py-3">
                    <div>
                        <div class="font-semibold text-gray-800 text-sm">
                            {{ $companion->full_name }}
                            <span class="text-xs text-gray-400 font-normal">({{ $companion->getRelationshipLabel() }})</span>
                        </div>
                        <div class="text-xs text-gray-400 mt-0.5">
                            مرافق ضمن حجز {{ $mainGuest?->full_name ?? '—' }}
                            @if($companion->reservation)
                                — غرفة {{ $companion->reservation->display_room_number }}
                            @endif
                        </div>
                    </div>
                    @if($mainGuest)
                    <a href="{{ route('guests.statement', $mainGuest) }}"
                       class="text-xs text-violet-600 font-semibold whitespace-nowrap hover:underline">
                        كشف حساب النزيل الرئيسي ←
                    </a>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- الموظفون --}}
        @if($employees->isNotEmpty())
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-5 py-3 border-b border-gray-100 bg-gray-50 flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                <h3 class="font-semibold text-gray-700 text-sm">موظفون ({{ $employees->count() }})</h3>
            </div>
            <div class="divide-y divide-gray-50">
                @foreach($employees as $employee)
                <a href="{{ route('employees.statement', $employee) }}"
                   class="flex items-center justify-between gap-3 px-5 py-3 hover:bg-gray-50 transition">
                    <div>
                        <div class="font-semibold text-gray-800 text-sm">{{ $employee->name }}</div>
                        <div class="text-xs text-gray-400 mt-0.5">{{ $employee->position ?? '—' }}</div>
                    </div>
                    <span class="text-xs text-emerald-600 font-semibold whitespace-nowrap">فتح كشف الحساب ←</span>
                </a>
                @endforeach
            </div>
        </div>
        @endif

        {{-- مستخدمو النظام --}}
        @if($users->isNotEmpty())
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-5 py-3 border-b border-gray-100 bg-gray-50 flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                <h3 class="font-semibold text-gray-700 text-sm">مستخدمو النظام ({{ $users->count() }})</h3>
            </div>
            <div class="divide-y divide-gray-50">
                @foreach($users as $user)
                <a href="{{ route('users.statement', $user) }}"
                   class="flex items-center justify-between gap-3 px-5 py-3 hover:bg-gray-50 transition">
                    <div>
                        <div class="font-semibold text-gray-800 text-sm">{{ $user->name }}</div>
                        <div class="text-xs text-gray-400 mt-0.5">{{ '@' . $user->username }}</div>
                    </div>
                    <span class="text-xs text-amber-600 font-semibold whitespace-nowrap">فتح كشف الصندوق ←</span>
                </a>
                @endforeach
            </div>
        </div>
        @endif

        @endif
    @endif
</div>
@endsection
