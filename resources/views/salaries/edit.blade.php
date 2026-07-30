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
              autoDeductions: {{ (float) $salary->withdrawals_deduction + (float) $salary->attendance_deduction }},
              get net() { return Math.max(0, this.base + this.bonuses - this.deductions - this.autoDeductions); }
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
                <label class="block text-sm font-medium text-gray-700 mb-1.5">الخصومات اليدوية الأخرى (ر.ي)</label>
                <input type="number" name="deductions" min="0" step="1"
                       x-model.number="deductions"
                       value="{{ old('deductions', $salary->deductions) }}"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm outline-none focus:border-blue-400">
            </div>

            <label class="flex items-start gap-2.5 bg-amber-50 border border-amber-200 rounded-lg p-3 cursor-pointer">
                <input type="checkbox" name="include_withdrawals" value="1" class="mt-0.5"
                       {{ old('include_withdrawals') ? 'checked' : '' }}>
                <span class="text-xs text-amber-800">
                    <strong>إعادة احتساب خصم المسحوبات تلقائياً:</strong>
                    الحالي: {{ number_format($salary->withdrawals_deduction, 0) }} ر.ي. تفعيل هذا الخيار
                    يُعيد احتسابه من الصفر بناءً على مصروفات/سحبيات الموظف الحالية لهذا الشهر (استبدال، لا إضافة).
                </span>
            </label>

            <label class="flex items-start gap-2.5 bg-red-50 border border-red-200 rounded-lg p-3 cursor-pointer">
                <input type="checkbox" name="include_attendance_deduction" value="1" class="mt-0.5"
                       {{ old('include_attendance_deduction') ? 'checked' : '' }}>
                <span class="text-xs text-red-800">
                    <strong>إعادة احتساب خصم الغياب/الإجازة بدون راتب تلقائياً:</strong>
                    الحالي: {{ number_format($salary->attendance_deduction, 0) }} ر.ي. تفعيل هذا الخيار
                    يُعيد احتسابه من الصفر بناءً على سجلّ الحضور والإجازات الحالي لهذا الشهر (استبدال، لا إضافة).
                </span>
            </label>

            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 flex justify-between items-center">
                <span class="text-sm font-medium text-gray-700">صافي الراتب:</span>
                <span class="text-xl font-bold" style="color:#0F4C75;" x-text="net.toLocaleString('ar') + ' ر.ي'"></span>
            </div>
            <p class="text-xs text-gray-400 -mt-2">لا يعكس هذا الرقم أي تغيير من خانتَي إعادة الاحتساب أعلاه إلا بعد الحفظ.</p>

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
