@extends('layouts.app')
@section('title', 'الإجازات')
@section('page-title', 'إدارة الإجازات')

@section('content')
<div dir="rtl">

@if(session('success'))
<div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">{{ session('success') }}</div>
@endif

{{-- Header --}}
<div class="flex items-center justify-between mb-5 flex-wrap gap-3">
    @can('hr.create')
    <a href="{{ route('leaves.create') }}"
       class="flex items-center gap-2 px-4 py-2 text-white rounded-lg text-sm transition" style="background:#0F4C75;">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        تسجيل إجازة
    </a>
    @endcan
</div>

{{-- Filters --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-5">
    <form method="GET" class="flex flex-wrap gap-3 items-end">
        <div class="flex flex-col gap-1">
            <label class="text-xs font-medium text-gray-500">الموظف</label>
            <select name="employee_id" onchange="this.form.submit()"
                    class="border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none bg-white min-w-[160px]">
                <option value="">جميع الموظفين</option>
                @foreach($employees as $emp)
                <option value="{{ $emp->id }}" {{ request('employee_id') == $emp->id ? 'selected' : '' }}>{{ $emp->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex flex-col gap-1">
            <label class="text-xs font-medium text-gray-500">النوع</label>
            <select name="type" onchange="this.form.submit()"
                    class="border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none bg-white min-w-[140px]">
                <option value="">جميع الأنواع</option>
                <option value="annual"    {{ request('type')=='annual'    ? 'selected':'' }}>سنوية</option>
                <option value="sick"      {{ request('type')=='sick'      ? 'selected':'' }}>مرضية</option>
                <option value="emergency" {{ request('type')=='emergency' ? 'selected':'' }}>طارئة</option>
                <option value="unpaid"    {{ request('type')=='unpaid'    ? 'selected':'' }}>بدون راتب</option>
            </select>
        </div>
        <div class="flex flex-col gap-1">
            <label class="text-xs font-medium text-gray-500">السنة</label>
            <select name="year" onchange="this.form.submit()"
                    class="border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none bg-white">
                <option value="">كل السنوات</option>
                @foreach(range(now()->year, now()->year - 2, -1) as $y)
                <option value="{{ $y }}" {{ request('year') == $y ? 'selected' : '' }}>{{ $y }}</option>
                @endforeach
            </select>
        </div>
        @if(request()->hasAny(['employee_id','type','year']))
        <a href="{{ route('leaves.index') }}"
           class="flex items-center gap-1.5 px-3 py-2 text-xs text-gray-500 border border-gray-200 rounded-lg hover:bg-gray-50 transition self-end">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            مسح
        </a>
        @endif
    </form>
</div>

{{-- Table --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-100">
    <div class="overflow-x-auto">
        <table class="w-full text-sm" dir="rtl">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الموظف</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">النوع</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">من</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">إلى</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الأيام</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">ملاحظات</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">سُجِّل بواسطة</th>
                    @can('hr.delete')
                    <th class="px-4 py-3"></th>
                    @endcan
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($leaves as $leave)
                @php
                    $typeColors = ['annual'=>'bg-blue-100 text-blue-800','sick'=>'bg-yellow-100 text-yellow-800','emergency'=>'bg-red-100 text-red-800','unpaid'=>'bg-gray-100 text-gray-700'];
                @endphp
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-medium text-gray-800">{{ $leave->employee->name }}</td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $typeColors[$leave->type] ?? 'bg-gray-100 text-gray-700' }}">
                            {{ \App\Models\Leave::typeLabel($leave->type) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-gray-600 whitespace-nowrap">{{ $leave->from_date->format('d/m/Y') }}</td>
                    <td class="px-4 py-3 text-gray-600 whitespace-nowrap">{{ $leave->to_date->format('d/m/Y') }}</td>
                    <td class="px-4 py-3 font-bold text-center" style="color:#0F4C75;">{{ $leave->days }}</td>
                    <td class="px-4 py-3 text-gray-500 max-w-xs truncate">{{ $leave->notes ?? '—' }}</td>
                    <td class="px-4 py-3 text-gray-400 text-xs">{{ $leave->creator?->name ?? '—' }}</td>
                    @can('hr.delete')
                    <td class="px-4 py-3">
                        <form method="POST" action="{{ route('leaves.destroy', $leave) }}"
                              onsubmit="return confirm('هل تريد حذف هذه الإجازة؟')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-xs px-3 py-1.5 rounded-lg border border-red-200 text-red-600 hover:bg-red-50 transition">
                                حذف
                            </button>
                        </form>
                    </td>
                    @endcan
                </tr>
                @empty
                <tr><td colspan="8" class="px-4 py-8">
                    <x-empty-state
                        icon="📅"
                        title="لا توجد إجازات"
                        message="لم يتم تسجيل أي إجازات حتى الآن"
                        action_text="طلب إجازة"
                        action_url="{{ route('leaves.create') }}"
                    />
                </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($leaves->hasPages())
    <div class="px-4 py-3 border-t border-gray-100">
        <x-pagination-info :items="$leaves" />
        {{ $leaves->links() }}
    </div>
    @endif
</div>

</div>
@endsection
