@extends('layouts.app')
@section('title', 'سجل الحضور')
@section('page-title', 'سجل الحضور اليومي')

@section('content')
<div dir="rtl">

<!-- Date Filter -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-5">
    <form method="GET" class="flex flex-wrap gap-3 items-end">
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">التاريخ</label>
            <input type="date" name="date" value="{{ $date }}"
                   class="border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none focus:border-blue-400">
        </div>
        <button type="submit" class="px-4 py-2 text-white rounded-lg text-sm transition" style="background:#0F4C75;">عرض</button>
        <a href="{{ route('attendance.index') }}" class="px-4 py-2 bg-gray-100 text-gray-600 rounded-lg text-sm hover:bg-gray-200 transition">اليوم</a>
    </form>
</div>

@can('hr.manage')
<form method="POST" action="{{ route('attendance.store') }}">
    @csrf
    <input type="hidden" name="date" value="{{ $date }}">

    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-semibold text-gray-700">الحضور ليوم {{ $selectedDate->format('d/m/Y') }}</h3>
            <button type="submit" class="px-4 py-2 text-white rounded-lg text-sm font-semibold transition" style="background:#0F4C75;">
                حفظ الحضور
            </button>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm" dir="rtl">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الموظف</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الوظيفة</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الحالة</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($employees as $emp)
                    @php $current = $attendanceMap[$emp->id] ?? 'present'; @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-semibold text-gray-800">{{ $emp->name }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $emp->position }}</td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap gap-2">
                                @foreach(['present'=>'حاضر','absent'=>'غائب','late'=>'متأخر','on_leave'=>'إجازة'] as $val=>$label)
                                <label class="flex items-center gap-1.5 cursor-pointer">
                                    <input type="radio" name="attendance[{{ $emp->id }}]" value="{{ $val }}"
                                           {{ $current === $val ? 'checked' : '' }}
                                           class="w-3.5 h-3.5">
                                    <span class="text-xs px-2 py-0.5 rounded-full {{ match($val){
                                        'present'=>'bg-green-100 text-green-800',
                                        'absent'=>'bg-red-100 text-red-800',
                                        'late'=>'bg-yellow-100 text-yellow-800',
                                        'on_leave'=>'bg-blue-100 text-blue-800',
                                        default=>'bg-gray-100 text-gray-800'
                                    } }}">{{ $label }}</span>
                                </label>
                                @endforeach
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="3" class="px-4 py-8 text-center text-gray-400">لا يوجد موظفون نشطون</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</form>
@else
<!-- Read-only view -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100">
    <div class="px-6 py-4 border-b border-gray-100">
        <h3 class="font-semibold text-gray-700">الحضور ليوم {{ $selectedDate->format('d/m/Y') }}</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm" dir="rtl">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الموظف</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الوظيفة</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الحالة</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($employees as $emp)
                @php $status = $attendanceMap[$emp->id] ?? null; @endphp
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-semibold text-gray-800">{{ $emp->name }}</td>
                    <td class="px-4 py-3 text-gray-500">{{ $emp->position }}</td>
                    <td class="px-4 py-3">
                        @if($status)
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ \App\Models\Attendance::statusColor($status) }}">
                            {{ \App\Models\Attendance::statusLabel($status) }}
                        </span>
                        @else
                        <span class="text-gray-400 text-xs">لم يُسجَّل</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="3" class="px-4 py-8 text-center text-gray-400">لا يوجد موظفون</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endcan

</div>
@endsection
