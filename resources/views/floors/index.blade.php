@extends('layouts.app')
@section('title', 'إدارة الطوابق')
@section('page-title', 'إدارة الطوابق')

@section('content')
<div class="max-w-4xl mx-auto space-y-6" x-data="{ editId: null, editData: {} }">

    @if(session('success'))
    <div class="p-4 bg-green-50 border border-green-200 rounded-xl text-green-700 text-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="p-4 bg-red-50 border border-red-200 rounded-xl text-red-700 text-sm">{{ session('error') }}</div>
    @endif

    <!-- Add Floor -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">إضافة طابق جديد</h2>
        @if($errors->any())
        <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg">
            @foreach($errors->all() as $error)
            <p class="text-sm text-red-600">{{ $error }}</p>
            @endforeach
        </div>
        @endif
        <form method="POST" action="{{ route('floors.store') }}">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">رقم الطابق <span class="text-red-500">*</span></label>
                    <input type="number" name="floor_number" value="{{ old('floor_number') }}" required min="1" max="50"
                           class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none transition"
                           placeholder="مثال: 1">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">عدد الأبواب <span class="text-red-500">*</span></label>
                    <input type="number" name="door_count" value="{{ old('door_count', 10) }}" required min="1" max="100"
                           class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none transition"
                           placeholder="مثال: 10">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">الاسم (اختياري)</label>
                    <input type="text" name="name" value="{{ old('name') }}"
                           class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none transition"
                           placeholder="مثال: الطابق الأول">
                </div>
            </div>
            <div class="mt-4">
                <button type="submit" class="px-6 py-2.5 text-white rounded-lg text-sm font-medium transition" style="background:#0F4C75;">
                    إضافة الطابق
                </button>
            </div>
        </form>
    </div>

    <!-- Floors Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-700">الطوابق المسجلة</h3>
        </div>
        @if($floors->isEmpty())
        <div class="px-6 py-12 text-center text-gray-400">لا توجد طوابق مسجلة بعد</div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-100">
                    <tr>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500">رقم الطابق</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500">الاسم</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500">عدد الأبواب</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500">نطاق الأرقام</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500">الغرف المسجلة</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500">إجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($floors as $floor)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 font-bold text-primary-800">{{ $floor->floor_number }}</td>
                        <td class="px-6 py-4 text-gray-600">{{ $floor->name ?? '—' }}</td>
                        <td class="px-6 py-4 text-gray-800 font-medium">{{ $floor->door_count }}</td>
                        <td class="px-6 py-4">
                            <span class="inline-flex px-2 py-1 bg-blue-50 text-blue-700 rounded-lg text-xs font-mono">
                                {{ $floor->floor_number * 100 + 1 }} – {{ $floor->floor_number * 100 + $floor->door_count }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center gap-1">
                                <span class="font-semibold text-gray-800">{{ $floor->used_doors }}</span>
                                <span class="text-gray-400">/ {{ $floor->door_count }}</span>
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2">
                                <button @click="editId = {{ $floor->id }}; editData = { floor_number: {{ $floor->floor_number }}, door_count: {{ $floor->door_count }}, name: '{{ $floor->name }}' }"
                                        class="px-3 py-1.5 text-xs bg-blue-50 text-blue-700 rounded-lg hover:bg-blue-100 transition">
                                    تعديل
                                </button>
                                <form method="POST" action="{{ route('floors.destroy', $floor) }}" onsubmit="return confirm('هل أنت متأكد من حذف الطابق {{ $floor->floor_number }}؟')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="px-3 py-1.5 text-xs bg-red-50 text-red-700 rounded-lg hover:bg-red-100 transition">
                                        حذف
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>

                    <!-- Edit Row -->
                    <tr x-show="editId === {{ $floor->id }}" x-cloak class="bg-blue-50">
                        <td colspan="6" class="px-6 py-4">
                            <form method="POST" action="{{ route('floors.update', $floor) }}" class="flex flex-wrap items-end gap-4">
                                @csrf
                                @method('PUT')
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">رقم الطابق</label>
                                    <input type="number" name="floor_number" :value="editData.floor_number" required min="1" max="50"
                                           class="border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-primary-500 w-24">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">عدد الأبواب</label>
                                    <input type="number" name="door_count" :value="editData.door_count" required min="1" max="100"
                                           class="border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-primary-500 w-24">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">الاسم</label>
                                    <input type="text" name="name" :value="editData.name"
                                           class="border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-primary-500 w-40">
                                </div>
                                <button type="submit" class="px-4 py-2 text-white rounded-lg text-sm font-medium" style="background:#0F4C75;">حفظ</button>
                                <button type="button" @click="editId = null" class="px-4 py-2 border border-gray-300 text-gray-600 rounded-lg text-sm hover:bg-gray-50">إلغاء</button>
                            </form>
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
