@extends('layouts.app')
@section('title', 'طلب إجازة')
@section('page-title', 'تقديم طلب إجازة')

@section('content')
<div dir="rtl" class="max-w-xl mx-auto">

<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
    <h2 class="text-lg font-bold text-gray-800 mb-6">بيانات الإجازة</h2>

    <form method="POST" action="{{ route('leaves.store') }}" class="space-y-4">
        @csrf

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">الموظف *</label>
            <select name="employee_id" required class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm outline-none focus:border-blue-400">
                <option value="">-- اختر موظفاً --</option>
                @foreach($employees as $emp)
                <option value="{{ $emp->id }}" {{ old('employee_id') == $emp->id ? 'selected' : '' }}>{{ $emp->name }} - {{ $emp->position }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">نوع الإجازة *</label>
            <select name="type" required class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm outline-none focus:border-blue-400">
                <option value="annual" {{ old('type')=='annual'?'selected':'' }}>سنوية</option>
                <option value="sick" {{ old('type')=='sick'?'selected':'' }}>مرضية</option>
                <option value="emergency" {{ old('type')=='emergency'?'selected':'' }}>طارئة</option>
            </select>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">تاريخ البداية *</label>
                <input type="date" name="start_date" value="{{ old('start_date', today()->toDateString()) }}" required
                       class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm outline-none focus:border-blue-400">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">تاريخ النهاية *</label>
                <input type="date" name="end_date" value="{{ old('end_date', today()->toDateString()) }}" required
                       class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm outline-none focus:border-blue-400">
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">سبب الإجازة</label>
            <textarea name="reason" rows="3" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm outline-none resize-none focus:border-blue-400">{{ old('reason') }}</textarea>
        </div>

        <div class="flex gap-3 pt-2">
            <button type="submit" class="px-6 py-2.5 text-white rounded-lg text-sm font-semibold transition" style="background:#0F4C75;">
                تقديم الطلب
            </button>
            <a href="{{ route('leaves.index') }}" class="px-6 py-2.5 border border-gray-300 text-gray-700 rounded-lg text-sm hover:bg-gray-50 transition">
                إلغاء
            </a>
        </div>
    </form>
</div>

</div>
@endsection
