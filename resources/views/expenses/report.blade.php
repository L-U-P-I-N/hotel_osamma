@extends('layouts.app')
@section('title', 'تقرير المصروفات')
@section('page-title', 'تقرير المصروفات')

@section('content')
<div dir="rtl">

<!-- Filter Form -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-5">
    <form method="GET" class="flex flex-wrap gap-3 items-end">
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">من تاريخ</label>
            <input type="date" name="date_from" value="{{ $dateFrom }}" class="border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">إلى تاريخ</label>
            <input type="date" name="date_to" value="{{ $dateTo }}" class="border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none">
        </div>
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
        <button type="submit" class="px-4 py-2 text-white rounded-lg text-sm transition" style="background:#0F4C75;">توليد التقرير</button>
    </form>
</div>

<!-- Totals by Currency -->
@if($totals->isNotEmpty())
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-5">
    @php $currencyLabels = ['YER'=>'ريال يمني','SAR'=>'ريال سعودي','USD'=>'دولار أمريكي']; @endphp
    @foreach($totals as $currency => $total)
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <div class="flex items-center justify-between mb-2">
            <span class="text-sm font-medium text-gray-600">{{ $currencyLabels[$currency] ?? $currency }}</span>
            <span class="text-xs px-2 py-0.5 rounded font-mono" style="background:#e8f0f7; color:#0F4C75;">{{ $currency }}</span>
        </div>
        <div class="text-2xl font-bold text-red-600">{{ number_format($total, 2) }}</div>
        <div class="text-xs text-gray-400 mt-1">إجمالي المصروفات</div>
    </div>
    @endforeach
</div>
@endif

<!-- By Category -->
@if($byCategory->isNotEmpty())
<div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-5">
    <div class="px-6 py-4 border-b border-gray-100">
        <h3 class="font-semibold text-gray-700">توزيع المصروفات حسب الفئة</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm" dir="rtl">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الفئة</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">عدد العمليات</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">المجموع (ريال يمني)</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">المجموع (ريال سعودي)</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">المجموع (دولار)</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($byCategory as $cat => $data)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3">
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                            {{ \App\Models\Expense::categoryLabel($cat) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-gray-600">{{ $data['count'] }}</td>
                    <td class="px-4 py-3 font-medium text-red-600">{{ number_format($data['totals']['YER'] ?? 0, 2) }}</td>
                    <td class="px-4 py-3 font-medium text-red-600">{{ number_format($data['totals']['SAR'] ?? 0, 2) }}</td>
                    <td class="px-4 py-3 font-medium text-red-600">{{ number_format($data['totals']['USD'] ?? 0, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

<!-- Detailed Table -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100">
    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
        <h3 class="font-semibold text-gray-700">تفاصيل المصروفات ({{ $expenses->count() }} عملية)</h3>
        <span class="text-xs text-gray-400">{{ $dateFrom }} — {{ $dateTo }}</span>
    </div>
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
                </tr>
                @empty
                <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">لا توجد مصروفات في هذه الفترة</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

</div>
@endsection
