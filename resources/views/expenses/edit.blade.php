@extends('layouts.app')
@section('title', 'تعديل مصروف')
@section('page-title', 'تعديل المصروف')

@section('content')
<div dir="rtl" class="max-w-xl mx-auto">

<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
    <h2 class="text-lg font-bold text-gray-800 mb-6">تعديل المصروف</h2>

    <form method="POST" action="{{ route('expenses.update', $expense) }}" class="space-y-4">
        @csrf @method('PUT')

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
                <input type="number" name="amount" value="{{ old('amount', $expense->amount) }}" step="0.01" min="0.01" required
                       class="w-full border @error('amount') border-red-400 @else border-gray-300 @enderror rounded-lg px-4 py-2.5 text-sm outline-none focus:border-blue-400">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">التاريخ *</label>
                <input type="date" name="expense_date" value="{{ old('expense_date', $expense->expense_date->toDateString()) }}" required
                       class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm outline-none focus:border-blue-400">
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">الفئة *</label>
            <select name="category" required class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm outline-none focus:border-blue-400">
                @foreach($categories as $key => $label)
                <option value="{{ $key }}" {{ old('category',$expense->category)==$key?'selected':'' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">اسم المستلم</label>
            <input type="text" name="recipient_name" value="{{ old('recipient_name', $expense->recipient_name) }}"
                   placeholder="اسم الشخص الذي صرف له المبلغ"
                   class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm outline-none focus:border-blue-400">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">الوصف / الملاحظات</label>
            <textarea name="description" rows="3" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm outline-none resize-none focus:border-blue-400">{{ old('description', $expense->description) }}</textarea>
        </div>

        <div class="flex gap-3 pt-2">
            <button type="submit" class="px-6 py-2.5 text-white rounded-lg text-sm font-semibold transition" style="background:#0F4C75;">
                حفظ التغييرات
            </button>
            <a href="{{ route('expenses.index') }}" class="px-6 py-2.5 border border-gray-300 text-gray-700 rounded-lg text-sm hover:bg-gray-50 transition">
                إلغاء
            </a>
        </div>
    </form>
</div>

</div>
@endsection
