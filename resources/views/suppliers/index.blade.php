@extends('layouts.app')
@section('title', 'الموردون')
@section('page-title', 'إدارة الموردين')

@section('content')
<div dir="rtl" x-data="{ createModal: false, editModal: false, editData: {} }">

<!-- Header -->
<div class="flex items-center justify-between mb-5">
    <p class="text-sm text-gray-500">إجمالي الموردين: {{ $suppliers->total() }}</p>
    @can('expenses.manage')
    <a href="{{ route('suppliers.create') }}" class="flex items-center gap-2 px-4 py-2 text-white rounded-lg text-sm transition" style="background:#0F4C75;">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        إضافة مورد
    </a>
    @endcan
</div>

<!-- Table -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100">
    <div class="overflow-x-auto">
        <table class="w-full text-sm" dir="rtl">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الاسم</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">رقم الهاتف</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الفئة</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الحالة</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">ملاحظات</th>
                    @can('expenses.manage')
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">إجراءات</th>
                    @endcan
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($suppliers as $supplier)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-semibold text-gray-800">{{ $supplier->name }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $supplier->phone ?? '-' }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $supplier->category ?? '-' }}</td>
                    <td class="px-4 py-3">
                        @if($supplier->is_active)
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">نشط</span>
                        @else
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">غير نشط</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-gray-500 max-w-xs truncate">{{ $supplier->notes ?? '-' }}</td>
                    @can('expenses.manage')
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2">
                            <a href="{{ route('suppliers.edit', $supplier) }}" class="text-xs px-3 py-1.5 rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50 transition">تعديل</a>
                            <form method="POST" action="{{ route('suppliers.destroy', $supplier) }}" onsubmit="return confirm('هل أنت متأكد من حذف هذا المورد؟')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-xs px-3 py-1.5 rounded-lg border border-red-200 text-red-600 hover:bg-red-50 transition">حذف</button>
                            </form>
                        </div>
                    </td>
                    @endcan
                </tr>
                @empty
                <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">لا يوجد موردون مسجلون</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($suppliers->hasPages())
    <div class="px-4 py-3 border-t border-gray-100">
        {{ $suppliers->links() }}
    </div>
    @endif
</div>

</div>
@endsection
