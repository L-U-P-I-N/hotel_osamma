@extends('layouts.app')
@section('title', 'تسجيل الحضور اليومي')
@section('page-title', 'تسجيل الحضور اليومي')

@section('content')
<div dir="rtl">

@if(session('success'))
<div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">{{ session('success') }}</div>
@endif
@if($errors->any())
<div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
    @foreach($errors->all() as $e)<p class="text-sm text-red-700">{{ $e }}</p>@endforeach
</div>
@endif

{{-- Header --}}
<div class="flex items-center justify-between mb-5 flex-wrap gap-3">
    <a href="{{ route('attendance.index') }}"
       class="flex items-center gap-2 text-sm text-gray-600 hover:text-gray-800">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
        الكشف الشهري
    </a>
</div>

{{-- Date Filter --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-5">
    <form method="GET" class="flex items-end gap-3">
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">التاريخ</label>
            <input type="date" name="date" value="{{ $date }}"
                   class="border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400">
        </div>
        <button type="submit" class="px-4 py-2 text-white rounded-lg text-sm transition" style="background:#0F4C75;">عرض</button>
    </form>
</div>

{{-- Attendance Form --}}
<form method="POST" action="{{ route('attendance.saveDaily') }}"
      onsubmit="this.querySelectorAll('button[type=submit]').forEach(b => b.disabled = true)">
    @csrf
    <input type="hidden" name="date" value="{{ $date }}">

    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <div>
                <h3 class="font-semibold text-gray-700">حضور يوم {{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}</h3>
                <p class="text-xs text-gray-400 mt-0.5">{{ $employees->count() }} موظف</p>
            </div>
            <button type="submit" class="px-5 py-2 text-white rounded-lg text-sm font-semibold transition" style="background:#0F4C75;">
                حفظ الحضور
            </button>
        </div>

        @if($employees->isEmpty())
        <div class="p-10 text-center text-gray-400">لا يوجد موظفون نشطون</div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الموظف</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">المنصب</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 w-40">الحالة *</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 w-32">وقت الدخول</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 w-32">وقت الخروج</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">ملاحظة</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($employees as $emp)
                    @php $rec = $records[$emp->id] ?? null; @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium text-gray-800">{{ $emp->name }}</td>
                        <td class="px-4 py-3 text-gray-500 text-xs">{{ $emp->position }}</td>
                        <td class="px-4 py-3">
                            <select name="attendance[{{ $emp->id }}][status]" required
                                    class="w-full border border-gray-200 rounded-lg px-2 py-1.5 text-sm outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400 bg-white">
                                @foreach(['present'=>'حاضر','absent'=>'غائب','late'=>'متأخر','leave'=>'إجازة','holiday'=>'عطلة'] as $val => $label)
                                <option value="{{ $val }}" {{ ($rec?->status ?? 'present') === $val ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td class="px-4 py-3">
                            <input type="time" name="attendance[{{ $emp->id }}][check_in]"
                                   value="{{ $rec?->check_in ?? '' }}"
                                   class="w-full border border-gray-200 rounded-lg px-2 py-1.5 text-sm outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400">
                        </td>
                        <td class="px-4 py-3">
                            <input type="time" name="attendance[{{ $emp->id }}][check_out]"
                                   value="{{ $rec?->check_out ?? '' }}"
                                   class="w-full border border-gray-200 rounded-lg px-2 py-1.5 text-sm outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400">
                        </td>
                        <td class="px-4 py-3">
                            <input type="text" name="attendance[{{ $emp->id }}][notes]"
                                   value="{{ $rec?->notes ?? '' }}" maxlength="255"
                                   class="w-full border border-gray-200 rounded-lg px-2 py-1.5 text-sm outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400">
                        </td>
                        <td class="px-4 py-3">
                            @if($rec)
                            <form method="POST" action="{{ route('attendance.destroy', $emp->id) }}"
                                  onsubmit="return confirm('حذف سجل حضور {{ addslashes($emp->name) }} ليوم {{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}؟')">
                                @csrf @method('DELETE')
                                <input type="hidden" name="date" value="{{ $date }}">
                                <button type="submit" class="p-1.5 rounded-lg text-red-600 hover:bg-red-50 transition" title="حذف السجل">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </form>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="px-5 py-3 border-t border-gray-100 flex justify-end">
            <button type="submit" class="px-6 py-2 text-white rounded-lg text-sm font-semibold transition" style="background:#0F4C75;">
                حفظ الحضور
            </button>
        </div>
        @endif
    </div>
</form>

</div>
@endsection
