@extends('layouts.app')
@section('title', 'تعديل قسيمة راتب')
@section('page-title', 'تعديل قسيمة الراتب')

@section('content')
<div dir="rtl" class="max-w-xl mx-auto">

<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-lg font-bold text-gray-800">تعديل قسيمة الراتب</h2>
        <a href="{{ route('salaries.index') }}" class="text-sm text-gray-500 hover:text-gray-700">← العودة</a>
    </div>

    <div class="bg-gray-50 rounded-lg p-4 mb-6 text-sm text-gray-600">
        <strong>الموظف:</strong> {{ $salary->employee->name }} &nbsp;|&nbsp;
        <strong>الفترة:</strong> {{ \App\Models\Salary::monthName($salary->month) }} {{ $salary->year }}
    </div>

    <form method="POST" action="{{ route('salaries.update', $salary) }}"
          x-data="{
              base: {{ $salary->base_salary }},
              bonuses: {{ $salary->bonuses }},
              deductions: {{ $salary->deductions }},
              get net() { return Math.max(0, this.base + this.bonuses - this.deductions); }
          }">
        @csrf @method('PUT')

        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">الراتب الأساسي (ر.ي) *</label>
                <input type="number" name="base_salary" min="0" step="1" required
                       x-model.number="base"
                       value="{{ old('base_salary', $salary->base_salary) }}"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm outline-none focus:border-blue-400">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">المكافآت والبدلات (ر.ي)</label>
                <input type="number" name="bonuses" min="0" step="1"
                       x-model.number="bonuses"
                       value="{{ old('bonuses', $salary->bonuses) }}"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm outline-none focus:border-blue-400">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">الخصومات (ر.ي)</label>
                <input type="number" name="deductions" min="0" step="1"
                       x-model.number="deductions"
                       value="{{ old('deductions', $salary->deductions) }}"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm outline-none focus:border-blue-400">
            </div>

            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 flex justify-between items-center">
                <span class="text-sm font-medium text-gray-700">صافي الراتب:</span>
                <span class="text-xl font-bold" style="color:#0F4C75;" x-text="net.toLocaleString('ar') + ' ر.ي'"></span>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">ملاحظات</label>
                <textarea name="notes" rows="3"
                          class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm outline-none resize-none focus:border-blue-400">{{ old('notes', $salary->notes) }}</textarea>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" class="px-6 py-2.5 text-white rounded-lg text-sm font-semibold transition" style="background:#0F4C75;">
                    حفظ التعديلات
                </button>
                <a href="{{ route('salaries.index') }}" class="px-6 py-2.5 border border-gray-300 text-gray-700 rounded-lg text-sm hover:bg-gray-50 transition">
                    إلغاء
                </a>
            </div>
        </div>
    </form>
</div>

</div>
@endsection
