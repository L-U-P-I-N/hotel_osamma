@extends('layouts.app')
@section('title', 'تعديل السعر الموسمي')
@section('page-title', 'تعديل السعر الموسمي')

@section('content')
<div class="max-w-lg">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <form action="{{ route('seasonal-prices.update', $seasonalPrice) }}" method="POST" class="space-y-4">
            @csrf @method('PUT')
            @if($errors->any())
            <div class="p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700">
                @foreach($errors->all() as $e)<p>{{ $e }}</p>@endforeach
            </div>
            @endif

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">اسم الموسم *</label>
                <input type="text" name="name" value="{{ old('name', $seasonalPrice->name) }}"
                       class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">نوع الغرفة</label>
                <select name="room_type_id" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
                    <option value="">كل أنواع الغرف</option>
                    @foreach($roomTypes as $rt)
                    <option value="{{ $rt->id }}" {{ $seasonalPrice->room_type_id == $rt->id ? 'selected' : '' }}>{{ $rt->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">من تاريخ *</label>
                    <input type="date" name="from_date" value="{{ old('from_date', $seasonalPrice->from_date->format('Y-m-d')) }}"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">إلى تاريخ *</label>
                    <input type="date" name="to_date" value="{{ old('to_date', $seasonalPrice->to_date->format('Y-m-d')) }}"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
                </div>
            </div>

            <div x-data="{ mode: '{{ old('price_mode', $seasonalPrice->price_mode) }}' }">
                <label class="block text-sm font-medium text-gray-700 mb-1">نوع التسعير *</label>
                <select name="price_mode" x-model="mode" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
                    <option value="fixed">سعر ثابت</option>
                    <option value="multiplier">معامل ضرب</option>
                </select>
                <div x-show="mode === 'fixed'" class="mt-2">
                    <label class="block text-xs text-gray-500 mb-1">السعر (ر.ي)</label>
                    <input type="number" name="price_yer" value="{{ old('price_yer', $seasonalPrice->price_yer) }}"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
                </div>
                <div x-show="mode === 'multiplier'" class="mt-2">
                    <label class="block text-xs text-gray-500 mb-1">المعامل</label>
                    <input type="number" name="multiplier" step="0.1" value="{{ old('multiplier', $seasonalPrice->multiplier) }}"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">ملاحظات</label>
                <input type="text" name="notes" value="{{ old('notes', $seasonalPrice->notes) }}"
                       class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" class="px-6 py-2 bg-primary-600 text-white rounded-lg text-sm font-medium hover:bg-primary-700">
                    حفظ التعديلات
                </button>
                <a href="{{ route('seasonal-prices.index') }}" class="px-6 py-2 border border-gray-200 text-gray-600 rounded-lg text-sm hover:bg-gray-50">
                    إلغاء
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
