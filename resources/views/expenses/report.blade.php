@extends('layouts.app')
@section('title', 'تقرير المصروفات')
@section('page-title', 'تقرير المصروفات')

@section('content')
<div dir="rtl">

<!-- Filter Form -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-5">
    <form method="GET" class="flex flex-wrap gap-3 items-end">
        <div class="flex flex-col gap-1">
            <label class="text-xs font-medium text-gray-500">من تاريخ</label>
            <input type="date" name="date_from" value="{{ $dateFrom }}" onchange="this.form.submit()"
                   class="border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400 transition bg-white">
        </div>
        <div class="flex flex-col gap-1">
            <label class="text-xs font-medium text-gray-500">إلى تاريخ</label>
            <input type="date" name="date_to" value="{{ $dateTo }}" onchange="this.form.submit()"
                   class="border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400 transition bg-white">
        </div>
        <div class="flex flex-col gap-1">
            <label class="text-xs font-medium text-gray-500">الفئة</label>
            <select name="category" onchange="this.form.submit()"
                    class="border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400 transition bg-white min-w-[140px]">
                <option value="">جميع الفئات</option>
                @foreach($categories as $key => $label)
                <option value="{{ $key }}" {{ request('category')==$key?'selected':'' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex flex-col gap-1">
            <label class="text-xs font-medium text-gray-500">طريقة الدفع</label>
            <select name="payment_method" onchange="this.form.submit()"
                    class="border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400 transition bg-white min-w-[140px]">
                <option value="">الكل</option>
                <option value="cash" {{ request('payment_method')=='cash'?'selected':'' }}>نقداً من الصندوق</option>
                <option value="bank_transfer" {{ request('payment_method')=='bank_transfer'?'selected':'' }}>تحويل بنكي</option>
                <option value="later" {{ request('payment_method')=='later'?'selected':'' }}>لاحقاً</option>
            </select>
        </div>
        <button type="submit" class="sr-only">عرض</button>
        <a href="{{ route('expenses.index') }}"
           class="flex items-center gap-1.5 px-3 py-2 text-xs text-gray-500 border border-gray-200 rounded-lg hover:bg-gray-50 transition self-end">
            ← القائمة
        </a>
    </form>
</div>

<!-- Summary Cards -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-5">
    <div class="bg-white rounded-xl shadow-sm border-2 p-5" style="border-color:#0F4C75;">
        <p class="text-xs text-gray-500 font-medium">الإجمالي الكلي</p>
        <p class="text-3xl font-bold mt-1" style="color:#0F4C75;">{{ number_format($total, 0) }} <span class="text-base font-normal text-gray-400">ر.ي</span></p>
        <p class="text-xs text-gray-400 mt-1">{{ $expenses->count() }} عملية</p>
    </div>
    @foreach(['cash' => ['label' => 'نقداً من الصندوق', 'color' => 'red'], 'bank_transfer' => ['label' => 'تحويل بنكي', 'color' => 'blue'], 'later' => ['label' => 'لاحقاً', 'color' => 'gray']] as $method => $opt)
    @php $methodData = $byMethod[$method] ?? ['count' => 0, 'total' => 0]; @endphp
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <p class="text-xs text-gray-500 font-medium">{{ $opt['label'] }}</p>
        <p class="text-2xl font-bold text-{{ $opt['color'] }}-600 mt-1">{{ number_format($methodData['total'], 0) }} <span class="text-sm font-normal text-gray-400">ر.ي</span></p>
        <p class="text-xs text-gray-400 mt-1">{{ $methodData['count'] }} عملية</p>
    </div>
    @endforeach
</div>

<!-- By Category -->
@if($byCategory->isNotEmpty())
<div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-5">
    <div class="px-6 py-4 border-b border-gray-100">
        <h3 class="font-semibold text-gray-700">توزيع المصروفات حسب الفئة</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm" dir="rtl">
            <thead class="bg-gray-50"><tr>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الفئة</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">عدد العمليات</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الإجمالي (ر.ي)</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">النسبة من الكلي</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($byCategory as $cat => $data)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3">
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                            {{ \App\Models\Expense::categoryLabel($cat) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-gray-600">{{ $data['count'] }}</td>
                    <td class="px-4 py-3 font-bold text-red-600">{{ number_format($data['total'], 0) }}</td>
                    <td class="px-4 py-3 text-gray-500 text-xs">
                        {{ $total > 0 ? number_format(($data['total'] / $total) * 100, 1).'%' : '—' }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

<!-- Expenses Detail -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-5">
    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
        <h3 class="font-semibold text-gray-700">تفاصيل المصروفات</h3>
        <span class="text-xs text-gray-400">{{ $dateFrom }} — {{ $dateTo }}</span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm" dir="rtl">
            <thead class="bg-gray-50"><tr>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">التاريخ</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الفئة</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">طريقة الدفع</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">المبلغ (ر.ي)</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">اسم المستلم</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الوصف</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">سُجِّل بواسطة</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($expenses as $expense)
                @php $pm = $expense->payment_method ?? 'cash'; @endphp
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 text-gray-600 whitespace-nowrap">{{ $expense->expense_date->format('d/m/Y') }}</td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                            {{ \App\Models\Expense::categoryLabel($expense->category) }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium
                            {{ $pm === 'cash' ? 'bg-green-100 text-green-800' : ($pm === 'bank_transfer' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-600') }}">
                            {{ \App\Models\Expense::paymentMethodLabel($pm) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 font-bold text-red-600">{{ number_format($expense->amount, 0) }}</td>
                    <td class="px-4 py-3 text-gray-700">{{ $expense->recipient_name ?? '—' }}</td>
                    <td class="px-4 py-3 text-gray-500 max-w-xs truncate">{{ $expense->description ?? '—' }}</td>
                    <td class="px-4 py-3 text-gray-500 whitespace-nowrap">{{ $expense->paidBy?->name ?? '—' }}</td>
                </tr>
                @empty
                <tr><td colspan="7" class="px-4 py-8 text-center text-gray-400">لا توجد مصروفات في هذه الفترة</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

</div>
@endsection
