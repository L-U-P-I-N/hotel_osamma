@extends('layouts.app')
@section('title', 'تعديل الغرفة ' . $room->room_number)
@section('page-title', 'تعديل الغرفة ' . $room->room_number)

@section('content')
<div class="max-w-2xl mx-auto">
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
    <div class="flex items-center gap-3 mb-6 pb-4 border-b border-gray-100">
        <a href="{{ route('rooms.index') }}" class="text-gray-400 hover:text-gray-600">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </a>
        <h2 class="text-lg font-semibold text-gray-800">تعديل بيانات الغرفة</h2>
        <span class="px-2 py-0.5 text-xs font-medium rounded-full" style="background:#e8f0f7;color:#0F4C75;">{{ $room->room_number }}</span>
    </div>

    @if($errors->any())
    <div class="mb-5 p-4 bg-red-50 border border-red-200 rounded-lg">
        <p class="text-sm font-semibold text-red-700 mb-2">يرجى تصحيح الأخطاء التالية:</p>
        <ul class="list-disc list-inside space-y-1">
            @foreach($errors->all() as $error)
            <li class="text-sm text-red-600">{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form method="POST" action="{{ route('rooms.update', $room) }}" class="space-y-5">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">رقم الغرفة <span class="text-red-500">*</span></label>
                <input type="text" name="room_number" value="{{ old('room_number', $room->room_number) }}" required
                       class="w-full border @error('room_number') border-red-400 bg-red-50 @else border-gray-300 @enderror rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 outline-none transition">
                @error('room_number')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">رقم الطابق <span class="text-red-500">*</span></label>
                <input type="number" name="floor" value="{{ old('floor', $room->floor) }}" required min="1" max="30"
                       class="w-full border @error('floor') border-red-400 bg-red-50 @else border-gray-300 @enderror rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 outline-none transition">
                @error('floor')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1.5">نوع الغرفة <span class="text-red-500">*</span></label>
                <select name="room_type_id" required
                        class="w-full border @error('room_type_id') border-red-400 bg-red-50 @else border-gray-300 @enderror rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 outline-none transition">
                    @foreach($roomTypes as $type)
                    <option value="{{ $type->id }}" {{ old('room_type_id', $room->room_type_id) == $type->id ? 'selected' : '' }}>
                        {{ $type->name }} — {{ number_format($type->effective_min_price, 0) }} إلى {{ number_format($type->effective_max_price, 0) }} ر.ي / ليلة
                    </option>
                    @endforeach
                </select>
                @error('room_type_id')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1.5">الحالة الحالية</label>
                <div class="flex items-center gap-2 p-3 bg-gray-50 rounded-lg border border-gray-200">
                    @php
                        $colors = ['available'=>'bg-green-100 text-green-800','reserved'=>'bg-blue-100 text-blue-800','occupied'=>'bg-red-100 text-red-800','under_inspection'=>'bg-yellow-100 text-yellow-800','maintenance'=>'bg-gray-100 text-gray-800'];
                    @endphp
                    <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $colors[$room->status] ?? 'bg-gray-100' }}">{{ $room->status_label }}</span>
                    <span class="text-xs text-gray-500">لتغيير الحالة استخدم القائمة الرئيسية</span>
                </div>
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1.5">ملاحظات</label>
                <textarea name="notes" rows="3" maxlength="500"
                          class="w-full border @error('notes') border-red-400 bg-red-50 @else border-gray-300 @enderror rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 outline-none transition resize-none">{{ old('notes', $room->notes) }}</textarea>
                @error('notes')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="flex gap-3 pt-2">
            <button type="submit" class="flex items-center gap-2 px-6 py-2.5 text-white rounded-lg text-sm font-medium transition" style="background:#0F4C75;">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                حفظ التغييرات
            </button>
            <a href="{{ route('rooms.index') }}" class="px-6 py-2.5 border border-gray-300 text-gray-600 rounded-lg text-sm hover:bg-gray-50 transition">إلغاء</a>
        </div>
    </form>
</div>
</div>
@endsection
