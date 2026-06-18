@extends('layouts.app')
@section('title', 'المصروفات')
@section('page-title', 'إدارة المصروفات')

@section('content')
<div dir="rtl">

<!-- Header -->
<div class="flex items-center justify-between mb-5">
    <div class="flex items-center gap-3">
        <a href="{{ route('expenses.report') }}" class="flex items-center gap-2 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg text-sm hover:bg-gray-50 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            تقرير المصروفات
        </a>
        <a href="{{ route('suppliers.index') }}" class="flex items-center gap-2 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg text-sm hover:bg-gray-50 transition">
            الموردون
        </a>
    </div>
    @can('expenses.manage')
    <a href="{{ route('expenses.create') }}" class="flex items-center gap-2 px-4 py-2 text-white rounded-lg text-sm transition" style="background:#0F4C75;">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        تسجيل مصروف
    </a>
    @endcan
</div>

<!-- Filters -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-5">
    <form method="GET" class="flex flex-wrap gap-3 items-end">
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">الفئة</label>
            <select name="category" class="border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none">
                <option value="">جميع الفئات</option>
                @foreach($categories as $key => $label)
                <option value="{{ $key }}" {{ request('category')==$key?'selected':'' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">العملة</label>
            <select name="currency" class="border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none">
                <option value="">جميع العملات</option>
                <option value="YER" {{ request('currency')=='YER'?'selected':'' }}>ريال يمني</option>
                <option value="SAR" {{ request('currency')=='SAR'?'selected':'' }}>ريال سعودي</option>
                <option value="USD" {{ request('currency')=='USD'?'selected':'' }}>دولار أمريكي</option>
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">المورد</label>
            <select name="supplier_id" class="border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none">
                <option value="">جميع الموردين</option>
                @foreach($suppliers as $sup)
                <option value="{{ $sup->id }}" {{ request('supplier_id')==$sup->id?'selected':'' }}>{{ $sup->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">من تاريخ</label>
            <input type="date" name="date_from" value="{{ request('date_from') }}" class="border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">إلى تاريخ</label>
            <input type="date" name="date_to" value="{{ request('date_to') }}" class="border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none">
        </div>
        <button type="submit" class="px-4 py-2 text-white rounded-lg text-sm transition" style="background:#0F4C75;">تصفية</button>
        <a href="{{ route('expenses.index') }}" class="px-4 py-2 bg-gray-100 text-gray-600 rounded-lg text-sm hover:bg-gray-200 transition">إعادة تعيين</a>
    </form>
</div>

<!-- Table -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100">
    <div class="overflow-x-auto">
        <table class="w-full text-sm" dir="rtl">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">التاريخ</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الفئة</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">المبلغ</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">العملة</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">المورد</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الوصف</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">سُجِّل بواسطة</th>
                    @can('expenses.manage')
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">إجراءات</th>
                    @endcan
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($expenses as $expense)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 text-gray-600">{{ $expense->expense_date->format('d/m/Y') }}</td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                            {{ \App\Models\Expense::categoryLabel($expense->category) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 font-bold text-red-600">{{ number_format($expense->amount, 2) }}</td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-0.5 rounded text-xs font-medium" style="background:#e8f0f7; color:#0F4C75;">{{ $expense->currency }}</span>
                    </td>
                    <td class="px-4 py-3 text-gray-600">{{ $expense->supplier?->name ?? '-' }}</td>
                    <td class="px-4 py-3 text-gray-500 max-w-xs truncate">{{ $expense->description ?? '-' }}</td>
                    <td class="px-4 py-3 text-gray-500">{{ $expense->paidBy?->name ?? '-' }}</td>
                    @can('expenses.manage')
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2">
                            <a href="{{ route('expenses.edit', $expense) }}" class="text-xs px-3 py-1.5 rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50 transition">تعديل</a>
                            <form method="POST" action="{{ route('expenses.destroy', $expense) }}" onsubmit="return confirm('هل أنت متأكد من حذف هذا المصروف؟')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-xs px-3 py-1.5 rounded-lg border border-red-200 text-red-600 hover:bg-red-50 transition">حذف</button>
                            </form>
                        </div>
                    </td>
                    @endcan
                </tr>
                @empty
                <tr><td colspan="8" class="px-4 py-8 text-center text-gray-400">لا توجد مصروفات</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($expenses->hasPages())
    <div class="px-4 py-3 border-t border-gray-100">
        {{ $expenses->links() }}
    </div>
    @endif
</div>

</div>
@endsection
