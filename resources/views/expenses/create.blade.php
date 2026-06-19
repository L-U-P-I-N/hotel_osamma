@extends('layouts.app')
@section('title', 'تسجيل مصروف')
@section('page-title', 'تسجيل مصروف جديد')

@section('content')
<div dir="rtl" class="max-w-xl mx-auto">

<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
    <h2 class="text-lg font-bold text-gray-800 mb-6">بيانات المصروف</h2>

    <form method="POST" action="{{ route('expenses.store') }}" class="space-y-4">
        @csrf

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">المبلغ *</label>
                <input type="number" name="amount" value="{{ old('amount') }}" step="0.01" min="0.01" required
                       class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm outline-none focus:border-blue-400">
            </div>
            <input type="hidden" name="currency" value="YER">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">الفئة *</label>
            <select name="category" required class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm outline-none focus:border-blue-400">
                @foreach($categories as $key => $label)
                <option value="{{ $key }}" {{ old('category')==$key?'selected':'' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">المورد (اختياري)</label>
            <select name="supplier_id" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm outline-none focus:border-blue-400">
                <option value="">-- بدون مورد --</option>
                @foreach($suppliers as $sup)
                <option value="{{ $sup->id }}" {{ old('supplier_id')==$sup->id?'selected':'' }}>{{ $sup->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">تاريخ المصروف *</label>
            <input type="date" name="expense_date" value="{{ old('expense_date', today()->toDateString()) }}" required
                   class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm outline-none focus:border-blue-400">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">الوصف / الملاحظات</label>
            <textarea name="description" rows="3" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm outline-none resize-none focus:border-blue-400">{{ old('description') }}</textarea>
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
