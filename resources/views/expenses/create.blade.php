@extends('layouts.app')
@section('title', 'تسجيل مصروف')
@section('page-title', 'تسجيل مصروف جديد')

@section('content')
<div dir="rtl" class="max-w-xl mx-auto">

<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
    <h2 class="text-lg font-bold text-gray-800 mb-6">بيانات المصروف</h2>

    <form method="POST" action="{{ route('expenses.store') }}" class="space-y-4">
        @csrf

        @if($errors->any())
        <div class="bg-red-50 border border-red-200 rounded-lg p-3 text-sm text-red-700">
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach
            </ul>
        </div>
        @endif

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">المبلغ (ر.ي) *</label>
                <input type="number" name="amount" value="{{ old('amount') }}" step="0.01" min="0.01" required
                       class="w-full border @error('amount') border-red-400 @else border-gray-300 @enderror rounded-lg px-4 py-2.5 text-sm outline-none focus:border-blue-400">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">التاريخ *</label>
                <input type="date" name="expense_date" value="{{ old('expense_date', today()->toDateString()) }}" required
                       class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm outline-none focus:border-blue-400">
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">الفئة *</label>
            <select name="category" required class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm outline-none focus:border-blue-400">
                @foreach($categories as $key => $label)
                <option value="{{ $key }}" {{ old('category')==$key?'selected':'' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <input type="hidden" name="payment_method" value="cash">

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">صرف لموظف في الفندق؟</label>
            <select name="employee_id" id="employee_select"
                    onchange="var o=this.options[this.selectedIndex]; var r=document.getElementById('recipient_field'); if(this.value){ r.value=o.dataset.name; } document.getElementById('employee_hint').classList.toggle('hidden', !this.value);"
                    class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm outline-none focus:border-blue-400">
                <option value="">— لا، مستلم خارجي —</option>
                @foreach($employees as $emp)
                <option value="{{ $emp->id }}" data-name="{{ $emp->name }}" {{ old('employee_id') == $emp->id ? 'selected' : '' }}>
                    {{ $emp->name }} — {{ $emp->position }}
                </option>
                @endforeach
            </select>
            <p id="employee_hint" class="{{ old('employee_id') ? '' : 'hidden' }} text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-lg p-2 mt-1.5">
                سيُقيَّد هذا المبلغ كمسحوبات على الموظف ويُخصم تلقائياً من راتبه الشهري.
            </p>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">اسم المستلم *</label>
            <input type="text" name="recipient_name" id="recipient_field" value="{{ old('recipient_name') }}" placeholder="اسم الشخص الذي صُرف له المبلغ" required
                   class="w-full border @error('recipient_name') border-red-400 @else border-gray-300 @enderror rounded-lg px-4 py-2.5 text-sm outline-none focus:border-blue-400">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">الوصف / الملاحظات</label>
            <textarea name="description" rows="3" placeholder="وصف تفصيلي للمصروف..."
                      class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm outline-none resize-none focus:border-blue-400">{{ old('description') }}</textarea>
        </div>

        <div class="bg-green-50 border border-green-200 rounded-lg p-3 text-xs text-green-700">
            سيُخصم هذا المبلغ تلقائياً من صندوق التسوية النقدية اليوم.
        </div>

        <div class="flex gap-3 pt-2">
            <button type="submit" class="px-6 py-2.5 text-white rounded-lg text-sm font-semibold transition" style="background:#0F4C75;">
                تسجيل المصروف
            </button>
            <a href="{{ route('expenses.index') }}" class="px-6 py-2.5 border border-gray-300 text-gray-700 rounded-lg text-sm hover:bg-gray-50 transition">
                إلغاء
            </a>
        </div>
    </form>
</div>

</div>
@endsection
