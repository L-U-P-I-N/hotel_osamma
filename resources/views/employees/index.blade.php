@extends('layouts.app')
@section('title', 'الموظفون')
@section('page-title', 'إدارة الموظفين')

@section('content')
<div dir="rtl">

<!-- Header -->
<div class="flex items-center justify-between mb-5">
    <p class="text-sm text-gray-500">إجمالي الموظفين: {{ $employees->total() }}</p>
    @can('hr.create')
    <a href="{{ route('employees.create') }}" class="flex items-center gap-2 px-4 py-2 text-white rounded-lg text-sm transition" style="background:#0F4C75;">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        إضافة موظف
    </a>
    @endcan
</div>

<!-- Table -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100">
    <div class="overflow-x-auto">
        <table class="w-full text-sm" dir="rtl">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">#</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الاسم</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الوظيفة</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الراتب الأساسي</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">رقم الهاتف</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">تاريخ التوظيف</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الحالة</th>
                    @canany(['hr.edit','hr.delete'])
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">إجراءات</th>
                    @endcanany
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($employees as $employee)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 text-gray-500">{{ $employee->id }}</td>
                    <td class="px-4 py-3 font-semibold text-gray-800">{{ $employee->name }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $employee->position }}</td>
                    <td class="px-4 py-3 font-medium" style="color:#0F4C75;">{{ number_format($employee->base_salary, 2) }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $employee->phone ?? '-' }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $employee->hire_date->format('d/m/Y') }}</td>
                    <td class="px-4 py-3">
                        @if($employee->is_active)
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">نشط</span>
                        @else
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">غير نشط</span>
                        @endif
                    </td>
                    @canany(['hr.edit','hr.delete'])
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2">
                            @can('hr.edit')
                            <a href="{{ route('employees.edit', $employee) }}" class="text-xs px-3 py-1.5 rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50 transition">تعديل</a>
                            @endcan
                            @can('hr.delete')
                            <form method="POST" action="{{ route('employees.destroy', $employee) }}" onsubmit="return confirm('هل أنت متأكد من حذف هذا الموظف؟')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-xs px-3 py-1.5 rounded-lg border border-red-200 text-red-600 hover:bg-red-50 transition">حذف</button>
                            </form>
                            @endcan
                        </div>
                    </td>
                    @endcanany
                </tr>
                @empty
                <tr><td colspan="8" class="px-4 py-8 text-center text-gray-400">لا يوجد موظفون مسجلون</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($employees->hasPages())
    <div class="px-4 py-3 border-t border-gray-100">
        {{ $employees->links() }}
    </div>
    @endif
</div>

</div>
@endsection
