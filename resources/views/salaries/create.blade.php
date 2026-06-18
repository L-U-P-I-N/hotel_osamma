@extends('layouts.app')
@section('title', 'إنشاء قسيمة راتب')
@section('page-title', 'إنشاء قسيمة راتب')

@section('content')
<div dir="rtl" class="max-w-xl mx-auto">

<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
    <h2 class="text-lg font-bold text-gray-800 mb-6">بيانات الراتب</h2>

    <form method="POST" action="{{ route('salaries.store') }}" class="space-y-4" x-data="salaryForm()" x-init="init()">
        @csrf

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">الموظف *</label>
            <select name="employee_id" required @change="loadSalary($event.target)"
                    class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm outline-none focus:border-blue-400">
                <option value="">-- اختر موظفاً --</option>
                @foreach($employees as $emp)
                <option value="{{ $emp->id }}" data-salary="{{ $emp->base_salary }}" {{ old('employee_id') == $emp->id ? 'selected' : '' }}>
                    {{ $emp->name }} - {{ $emp->position }}
                </option>
                @endforeach
            </select>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">الشهر *</label>
                <select name="month" required class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm outline-none focus:border-blue-400">
                    @foreach(range(1,12) as $m)
                    <option value="{{ $m }}" {{ (old('month', now()->month)) == $m ? 'selected' : '' }}>{{ \App\Models\Salary::monthName($m) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">السنة *</label>
                <select name="year" required class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm outline-none focus:border-blue-400">
                    @foreach(range(now()->year, now()->year - 3, -1) as $y)
                    <option value="{{ $y }}" {{ old('year', now()->year) == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">الراتب الأساسي *</label>
            <input type="number" name="base_salary" id="base_salary_field"
                   value="{{ old('base_salary', 0) }}" step="0.01" min="0" required
                   x-model="baseSalary" @input="calcNet()"
                   class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm outline-none focus:border-blue-400">
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">المكافآت</label>
                <input type="number" name="bonuses" value="{{ old('bonuses', 0) }}" step="0.01" min="0"
                       x-model="bonuses" @input="calcNet()"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm outline-none focus:border-blue-400">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">الخصومات</label>
                <input type="number" name="deductions" value="{{ old('deductions', 0) }}" step="0.01" min="0"
                       x-model="deductions" @input="calcNet()"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm outline-none focus:border-blue-400">
            </div>
        </div>

        <!-- Net salary display -->
        <div class="bg-gray-50 rounded-lg p-4 flex justify-between items-center">
            <span class="text-sm font-medium text-gray-700">صافي الراتب:</span>
            <span class="text-xl font-bold" style="color:#0F4C75;" x-text="net.toFixed(2)">0.00</span>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">ملاحظات</label>
            <textarea name="notes" rows="2" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm outline-none resize-none focus:border-blue-400">{{ old('notes') }}</textarea>
        </div>

        <div class="flex gap-3 pt-2">
            <button type="submit" class="px-6 py-2.5 text-white rounded-lg text-sm font-semibold transition" style="background:#0F4C75;">
                إنشاء القسيمة
            </button>
            <a href="{{ route('salaries.index') }}" class="px-6 py-2.5 border border-gray-300 text-gray-700 rounded-lg text-sm hover:bg-gray-50 transition">
                إلغاء
            </a>
        </div>
    </form>
</div>

</div>
@endsection

@push('scripts')
<script>
function salaryForm() {
    return {
        baseSalary: {{ old('base_salary', 0) }},
        bonuses: {{ old('bonuses', 0) }},
        deductions: {{ old('deductions', 0) }},
        net: {{ old('base_salary', 0) }},
        init() { this.calcNet(); },
        calcNet() {
            this.net = parseFloat(this.baseSalary || 0) + parseFloat(this.bonuses || 0) - parseFloat(this.deductions || 0);
        },
        loadSalary(sel) {
            const opt = sel.options[sel.selectedIndex];
            const sal = parseFloat(opt.dataset.salary || 0);
            this.baseSalary = sal;
            document.getElementById('base_salary_field').value = sal;
            this.calcNet();
        }
    }
}
</script>
@endpush
