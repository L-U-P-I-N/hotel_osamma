@extends('layouts.app')
@section('title', 'تسجيل إجازة')
@section('page-title', 'تسجيل إجازة جديدة')

@section('content')
<div dir="rtl" class="max-w-xl mx-auto">

<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
    <h2 class="text-lg font-bold text-gray-800 mb-6">بيانات الإجازة</h2>

    @if($errors->any())
    <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
        @foreach($errors->all() as $e)<p class="text-sm text-red-700">{{ $e }}</p>@endforeach
    </div>
    @endif

    <form method="POST" action="{{ route('leaves.store') }}" class="space-y-4"
          x-data="{
              fromDate: '{{ old('from_date', today()->toDateString()) }}',
              toDate:   '{{ old('to_date', today()->toDateString()) }}',
              get days() {
                  if (!this.fromDate || !this.toDate) return 0;
                  const d1 = new Date(this.fromDate), d2 = new Date(this.toDate);
                  if (d2 < d1) return 0;
                  return Math.round((d2 - d1) / 86400000) + 1;
              }
          }">
        @csrf

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">الموظف *</label>
            <select name="employee_id" required
                    class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm outline-none focus:border-blue-400">
                <option value="">-- اختر موظفاً --</option>
                @foreach($employees as $emp)
                <option value="{{ $emp->id }}" {{ old('employee_id') == $emp->id ? 'selected' : '' }}>
                    {{ $emp->name }} — {{ $emp->position }}
                </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">نوع الإجازة *</label>
            <select name="type" required
                    class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm outline-none focus:border-blue-400">
                <option value="annual"    {{ old('type')=='annual'    ? 'selected':'' }}>سنوية</option>
                <option value="sick"      {{ old('type')=='sick'      ? 'selected':'' }}>مرضية</option>
                <option value="emergency" {{ old('type')=='emergency' ? 'selected':'' }}>طارئة</option>
                <option value="unpaid"    {{ old('type')=='unpaid'    ? 'selected':'' }}>بدون راتب</option>
            </select>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">من تاريخ *</label>
                <input type="date" name="from_date" x-model="fromDate" required
                       class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm outline-none focus:border-blue-400">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">إلى تاريخ *</label>
                <input type="date" name="to_date" x-model="toDate" required
                       :min="fromDate"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm outline-none focus:border-blue-400">
            </div>
        </div>

        <div class="bg-gray-50 rounded-lg p-4 flex justify-between items-center">
            <span class="text-sm font-medium text-gray-700">عدد الأيام:</span>
            <span class="text-xl font-bold" style="color:#0F4C75;" x-text="days + ' يوم'">—</span>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">ملاحظات</label>
            <textarea name="notes" rows="2"
                      class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm outline-none resize-none focus:border-blue-400">{{ old('notes') }}</textarea>
        </div>

        <div class="flex gap-3 pt-2">
            <button type="submit" class="px-6 py-2.5 text-white rounded-lg text-sm font-semibold transition" style="background:#0F4C75;">
                تسجيل الإجازة
            </button>
            <a href="{{ route('leaves.index') }}" class="px-6 py-2.5 border border-gray-300 text-gray-700 rounded-lg text-sm hover:bg-gray-50 transition">
                إلغاء
            </a>
        </div>
    </form>
</div>

</div>
@endsection
