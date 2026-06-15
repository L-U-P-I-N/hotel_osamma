@extends('layouts.app')
@section('title', 'أداء الموظفين')
@section('page-title', 'تقرير أداء الموظفين')

@section('content')
<div class="space-y-5">

<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
    <form method="GET" class="flex flex-wrap gap-3 items-end">
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">من تاريخ</label>
            <input type="date" name="from" value="{{ $from }}" class="border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">إلى تاريخ</label>
            <input type="date" name="to" value="{{ $to }}" class="border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none">
        </div>
        <button type="submit" class="px-4 py-2 text-white rounded-lg text-sm transition" style="background:#0F4C75;">عرض</button>
    </form>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100">
    <div class="px-6 py-4 border-b border-gray-100">
        <h3 class="font-semibold text-gray-700">أداء الموظفين
            <span class="text-sm font-normal text-gray-400 mr-2">{{ $from }} — {{ $to }}</span>
        </h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50"><tr>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الموظف</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الدور</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">عدد تسجيلات الدخول</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">إجمالي المستلم (ر.ي)</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($staffData as $row)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center text-white font-bold text-sm" style="background:#0F4C75;">
                                {{ mb_substr($row['user']->name, 0, 1) }}
                            </div>
                            <div>
                                <div class="font-medium text-gray-800">{{ $row['user']->name }}</div>
                                <div class="text-xs text-gray-400">{{ $row['user']->employee_id }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium" style="background:#e8f0f7; color:#0F4C75;">
                            {{ $row['user']->roles->first()?->name ?? '-' }}
                        </span>
                    </td>
                    <td class="px-4 py-3 font-bold text-gray-800">{{ $row['checkins'] }}</td>
                    <td class="px-4 py-3 font-medium text-green-700">{{ number_format($row['revenue'], 0) }}</td>
                </tr>
                @empty
                <tr><td colspan="4" class="px-4 py-8 text-center text-gray-400">لا توجد بيانات</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
</div>
@endsection
