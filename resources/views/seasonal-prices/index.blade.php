@extends('layouts.app')
@section('title', 'الأسعار الموسمية')
@section('page-title', 'الأسعار الموسمية')

@section('content')
@if(session('success'))
<div class="mb-4 p-3 bg-green-50 border border-green-200 rounded-lg text-sm text-green-700">{{ session('success') }}</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
    {{-- نموذج إضافة --}}
    @can('rooms.edit')
    <div class="lg:col-span-1">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <h3 class="font-semibold text-gray-800 mb-4">إضافة سعر موسمي</h3>
            <form action="{{ route('seasonal-prices.store') }}" method="POST" class="space-y-3">
                @csrf
                @if($errors->any())
                <div class="p-3 bg-red-50 border border-red-200 rounded-lg text-xs text-red-700">
                    @foreach($errors->all() as $e)<p>{{ $e }}</p>@endforeach
                </div>
                @endif

                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">اسم الموسم *</label>
                    <input type="text" name="name" value="{{ old('name') }}" placeholder="مثال: موسم رمضان"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">نوع الغرفة (فارغ = كل الغرف)</label>
                    <select name="room_type_id" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
                        <option value="">كل أنواع الغرف</option>
                        @foreach($roomTypes as $rt)
                        <option value="{{ $rt->id }}">{{ $rt->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">من تاريخ *</label>
                        <input type="date" name="from_date" value="{{ old('from_date') }}"
                               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">إلى تاريخ *</label>
                        <input type="date" name="to_date" value="{{ old('to_date') }}"
                               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
                    </div>
                </div>

                <div x-data="{ mode: '{{ old('price_mode','fixed') }}' }">
                    <label class="block text-xs font-medium text-gray-600 mb-1">نوع التسعير *</label>
                    <select name="price_mode" x-model="mode" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
                        <option value="fixed">سعر ثابت</option>
                        <option value="multiplier">معامل ضرب (نسبة)</option>
                    </select>

                    <div x-show="mode === 'fixed'" class="mt-2">
                        <label class="block text-xs text-gray-500 mb-1">السعر (ر.ي)</label>
                        <input type="number" name="price_yer" value="{{ old('price_yer') }}" step="1"
                               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
                    </div>
                    <div x-show="mode === 'multiplier'" class="mt-2">
                        <label class="block text-xs text-gray-500 mb-1">المعامل (مثال: 1.5 = زيادة 50%)</label>
                        <input type="number" name="multiplier" value="{{ old('multiplier') }}" step="0.1" min="0.1" max="10"
                               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">ملاحظات</label>
                    <input type="text" name="notes" value="{{ old('notes') }}"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
                </div>

                <button type="submit" class="w-full py-2 bg-primary-600 text-white rounded-lg text-sm font-medium hover:bg-primary-700">
                    إضافة الموسم
                </button>
            </form>
        </div>
    </div>
    @endcan

    {{-- قائمة الأسعار --}}
    <div class="lg:col-span-2">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100">
            <div class="px-5 py-4 border-b border-gray-100">
                <h3 class="font-semibold text-gray-800">الأسعار الموسمية المسجلة</h3>
            </div>
            @if($seasons->isEmpty())
            <div class="py-10 text-center text-gray-400 text-sm">لا توجد أسعار موسمية</div>
            @else
            <div class="divide-y divide-gray-50">
                @foreach($seasons as $season)
                @php
                    $isActive = now()->between($season->from_date, $season->to_date);
                    $isPast   = now()->gt($season->to_date);
                @endphp
                <div class="px-5 py-4 flex items-start justify-between gap-3">
                    <div class="flex-1">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="font-semibold text-gray-800">{{ $season->name }}</span>
                            @if($isActive)
                            <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-green-100 text-green-700">نشط الآن</span>
                            @elseif($isPast)
                            <span class="px-2 py-0.5 rounded-full text-xs bg-gray-100 text-gray-500">منتهي</span>
                            @else
                            <span class="px-2 py-0.5 rounded-full text-xs bg-blue-100 text-blue-600">قادم</span>
                            @endif
                        </div>
                        <p class="text-xs text-gray-500">
                            {{ $season->from_date->format('d/m/Y') }} — {{ $season->to_date->format('d/m/Y') }}
                            · {{ $season->roomType ? $season->roomType->name : 'كل الغرف' }}
                        </p>
                        <p class="text-sm font-medium text-gray-700 mt-1">
                            @if($season->price_mode === 'fixed')
                                {{ number_format($season->price_yer, 0) }} ر.ي / ليلة
                            @else
                                معامل × {{ $season->multiplier }} ({{ round(($season->multiplier - 1) * 100) >= 0 ? '+' : '' }}{{ round(($season->multiplier - 1) * 100) }}%)
                            @endif
                        </p>
                    </div>
                    @can('rooms.edit')
                    <div class="flex gap-2">
                        <a href="{{ route('seasonal-prices.edit', $season) }}"
                           class="text-xs px-3 py-1.5 border border-gray-200 rounded-lg hover:bg-gray-50">تعديل</a>
                        <form action="{{ route('seasonal-prices.destroy', $season) }}" method="POST"
                              onsubmit="return confirm('حذف هذا الموسم؟')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-xs px-3 py-1.5 border border-red-200 text-red-600 rounded-lg hover:bg-red-50">حذف</button>
                        </form>
                    </div>
                    @endcan
                </div>
                @endforeach
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
