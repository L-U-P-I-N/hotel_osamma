@extends('layouts.app')
@section('title', 'تعديل مورد')
@section('page-title', 'تعديل بيانات المورد')

@section('content')
<div dir="rtl" class="max-w-xl mx-auto">

<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
    <h2 class="text-lg font-bold text-gray-800 mb-6">تعديل: {{ $supplier->name }}</h2>

    <form method="POST" action="{{ route('suppliers.update', $supplier) }}" class="space-y-4">
        @csrf @method('PUT')

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">اسم المورد *</label>
                <input type="text" name="name" value="{{ old('name', $supplier->name) }}" required
                       class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm outline-none focus:border-blue-400">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">رقم الهاتف</label>
                <input type="text" name="phone" value="{{ old('phone', $supplier->phone) }}"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm outline-none focus:border-blue-400">
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">الفئة / النشاط</label>
            <input type="text" name="category" value="{{ old('category', $supplier->category) }}"
                   class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm outline-none focus:border-blue-400">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">ملاحظات</label>
            <textarea name="notes" rows="3" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm outline-none resize-none focus:border-blue-400">{{ old('notes', $supplier->notes) }}</textarea>
        </div>

        <div class="flex items-center gap-3">
            <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $supplier->is_active) ? 'checked' : '' }} class="w-4 h-4 rounded">
            <label for="is_active" class="text-sm text-gray-700">مورد نشط</label>
        </div>

        <div class="flex gap-3 pt-2">
            <button type="submit" class="px-6 py-2.5 text-white rounded-lg text-sm font-semibold transition" style="background:#0F4C75;">
                حفظ التغييرات
            </button>
            <a href="{{ route('suppliers.index') }}" class="px-6 py-2.5 border border-gray-300 text-gray-700 rounded-lg text-sm hover:bg-gray-50 transition">
                إلغاء
            </a>
        </div>
    </form>
</div>

</div>
@endsection
