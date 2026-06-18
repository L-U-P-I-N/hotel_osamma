@extends('layouts.app')
@section('title', 'تعديل موظف')
@section('page-title', 'تعديل بيانات الموظف')

@section('content')
<div dir="rtl" class="max-w-2xl mx-auto">

<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
    <h2 class="text-lg font-bold text-gray-800 mb-6">تعديل: {{ $employee->name }}</h2>

    <form method="POST" action="{{ route('employees.update', $employee) }}" class="space-y-4">
        @csrf @method('PUT')

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">الاسم الكامل *</label>
                <input type="text" name="name" value="{{ old('name', $employee->name) }}" required
                       class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm outline-none focus:border-blue-400">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">رقم الهوية</label>
                <input type="text" name="national_id" value="{{ old('national_id', $employee->national_id) }}"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm outline-none focus:border-blue-400">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">المسمى الوظيفي *</label>
                <input type="text" name="position" value="{{ old('position', $employee->position) }}" required
                       class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm outline-none focus:border-blue-400">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">الراتب الأساسي *</label>
                <input type="number" name="base_salary" value="{{ old('base_salary', $employee->base_salary) }}" step="0.01" min="0" required
                       class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm outline-none focus:border-blue-400">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">رقم الهاتف</label>
                <input type="text" name="phone" value="{{ old('phone', $employee->phone) }}"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm outline-none focus:border-blue-400">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">تاريخ التوظيف *</label>
                <input type="date" name="hire_date" value="{{ old('hire_date', $employee->hire_date->toDateString()) }}" required
                       class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm outline-none focus:border-blue-400">
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">ملاحظات</label>
            <textarea name="notes" rows="3" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm outline-none resize-none focus:border-blue-400">{{ old('notes', $employee->notes) }}</textarea>
        </div>

        <div class="flex items-center gap-3">
            <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $employee->is_active) ? 'checked' : '' }} class="w-4 h-4 rounded">
            <label for="is_active" class="text-sm text-gray-700">موظف نشط</label>
        </div>

        <div class="flex gap-3 pt-2">
            <button type="submit" class="px-6 py-2.5 text-white rounded-lg text-sm font-semibold transition" style="background:#0F4C75;">
                حفظ التغييرات
            </button>
            <a href="{{ route('employees.index') }}" class="px-6 py-2.5 border border-gray-300 text-gray-700 rounded-lg text-sm hover:bg-gray-50 transition">
                إلغاء
            </a>
        </div>
    </form>
</div>

</div>
@endsection
