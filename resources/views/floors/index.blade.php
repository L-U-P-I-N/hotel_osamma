@extends('layouts.app')
@section('title', 'إدارة الطوابق')
@section('page-title', 'إدارة الطوابق')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h2 class="text-base font-semibold text-gray-800 mb-5 pb-3 border-b border-gray-100">إضافة طابق جديد</h2>
        <form method="POST" action="{{ route('floors.store') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-4 items-end">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">رقم الطابق <span class="text-red-500">*</span></label>
                <input type="number" name="floor_number" value="{{ old('floor_number') }}" required min="1" max="99"
                       class="w-full border @error('floor_number') border-red-400 bg-red-50 @else border-gray-300 @enderror rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 outline-none transition">
                @error('floor_number')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">عدد الأبواب <span class="text-red-500">*</span></label>
                <input type="number" name="door_count" value="{{ old('door_count', 10) }}" required min="1" max="50"
                       class="w-full border @error('door_count') border-red-400 bg-red-50 @else border-gray-300 @enderror rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 outline-none transition">
                @error('door_count')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">الاسم (اختياري)</label>
                <input type="text" name="name" value="{{ old('name') }}" maxlength="100"
                       placeholder="مثال: الطابق الأرضي"
                       class="w-full border @error('name') border-red-400 bg-red-50 @else border-gray-300 @enderror rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 outline-none transition">
                @error('name')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <button type="submit" class="w-full flex items-center justify-center gap-2 px-5 py-2.5 text-white rounded-lg text-sm font-medium transition" style="background:#0F4C75;">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    إضافة
                </button>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h2 class="text-base font-semibold text-gray-800">الطوابق المسجلة</h2>
        </div>

        @if($floors->isEmpty())
        <div class="px-6 py-12 text-center text-gray-400 text-sm">لا توجد طوابق مسجلة</div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100">
                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">رقم الطابق</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">الاسم</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">عدد الأبواب</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">نطاق الغرف</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">الغرف المسجلة</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">الإجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($floors as $floor)
                    <tr class="hover:bg-gray-50 transition" x-data="{ editing: false }">
                        <td class="px-6 py-4 font-semibold text-gray-800">{{ $floor->floor_number }}</td>
                        <td class="px-6 py-4 text-gray-600">{{ $floor->name ?: '—' }}</td>
                        <td class="px-6 py-4 text-gray-600">{{ $floor->door_count }}</td>
                        <td class="px-6 py-4 text-gray-500 text-xs">
                            {{ $floor->floor_number * 100 + 1 }} - {{ $floor->floor_number * 100 + $floor->door_count }}
                        </td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary-100 text-primary-800">
                                {{ $floor->rooms_count }} غرفة
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2">
                                <button @click="editing = true" class="text-xs px-3 py-1.5 border border-gray-300 text-gray-600 rounded-lg hover:bg-gray-50 transition">تعديل</button>
                                <form method="POST" action="{{ route('floors.destroy', $floor) }}"
                                      onsubmit="return confirm('هل أنت متأكد من حذف الطابق {{ $floor->floor_number }}؟')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-xs px-3 py-1.5 border border-red-200 text-red-600 rounded-lg hover:bg-red-50 transition">حذف</button>
                                </form>
                            </div>

                            <div x-show="editing" x-cloak class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center" @click.self="editing=false">
                                <div class="bg-white rounded-xl shadow-xl p-6 w-full max-w-md mx-4">
                                    <h3 class="text-base font-semibold text-gray-800 mb-5">تعديل الطابق {{ $floor->floor_number }}</h3>
                                    <form method="POST" action="{{ route('floors.update', $floor) }}" class="space-y-4">
                                        @csrf
                                        @method('PUT')
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1.5">رقم الطابق <span class="text-red-500">*</span></label>
                                            <input type="number" name="floor_number" value="{{ old('floor_number', $floor->floor_number) }}" required min="1" max="99"
                                                   class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 outline-none">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1.5">عدد الأبواب <span class="text-red-500">*</span></label>
                                            <input type="number" name="door_count" value="{{ old('door_count', $floor->door_count) }}" required min="1" max="50"
                                                   class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 outline-none">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1.5">الاسم (اختياري)</label>
                                            <input type="text" name="name" value="{{ old('name', $floor->name) }}" maxlength="100"
                                                   class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 outline-none">
                                        </div>
                                        <div class="flex gap-3 pt-2">
                                            <button type="submit" class="px-5 py-2.5 text-white rounded-lg text-sm font-medium transition" style="background:#0F4C75;">حفظ</button>
                                            <button type="button" @click="editing=false" class="px-5 py-2.5 border border-gray-300 text-gray-600 rounded-lg text-sm hover:bg-gray-50 transition">إلغاء</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

</div>
@endsection
