@extends('layouts.app')
@section('title', 'تقرير الأرباح والخسائر')
@section('page-title', 'تقرير الأرباح والخسائر')

@section('content')
<div dir="rtl">

{{-- Filter --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-5">
    <form method="GET" class="flex flex-wrap items-end gap-3">
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">من تاريخ</label>
            <input type="date" name="from" value="{{ $from }}"
                   class="border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none focus:border-blue-400">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">إلى تاريخ</label>
            <input type="date" name="to" value="{{ $to }}"
                   class="border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none focus:border-blue-400">
        </div>
        <button type="submit" class="px-4 py-2 text-white rounded-lg text-sm" style="background:#0F4C75;">عرض</button>
        {{-- Quick presets --}}
        <a href="{{ route('reports.profitLoss', ['from' => now()->startOfMonth()->toDateString(), 'to' => now()->toDateString()]) }}"
           class="px-3 py-2 border border-gray-200 text-gray-600 rounded-lg text-xs hover:bg-gray-50">هذا الشهر</a>
        <a href="{{ route('reports.profitLoss', ['from' => now()->subMonth()->startOfMonth()->toDateString(), 'to' => now()->subMonth()->endOfMonth()->toDateString()]) }}"
           class="px-3 py-2 border border-gray-200 text-gray-600 rounded-lg text-xs hover:bg-gray-50">الشهر الماضي</a>
        <a href="{{ route('reports.profitLoss', ['from' => now()->startOfYear()->toDateString(), 'to' => now()->toDateString()]) }}"
           class="px-3 py-2 border border-gray-200 text-gray-600 rounded-lg text-xs hover:bg-gray-50">هذه السنة</a>
    </form>
</div>

{{-- P&L Summary --}}
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-5">
    <div class="bg-white rounded-xl shadow-sm border border-green-100 p-5">
        <p class="text-xs font-medium text-gray-500 mb-2">إجمالي الإيرادات</p>
        <p class="text-3xl font-bold text-green-700">{{ number_format($totalRevenue, 0) }}</p>
        <p class="text-sm text-gray-400 mt-1">ر.ي</p>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-red-100 p-5">
        <p class="text-xs font-medium text-gray-500 mb-2">إجمالي المصروفات</p>
        <p class="text-3xl font-bold text-red-600">{{ number_format($totalExpenses, 0) }}</p>
        <p class="text-sm text-gray-400 mt-1">ر.ي</p>
    </div>
    <div class="rounded-xl p-5 shadow-sm border {{ $netProfit >= 0 ? 'bg-blue-50 border-blue-100' : 'bg-red-50 border-red-200' }}">
        <p class="text-xs font-medium text-gray-500 mb-2">{{ $netProfit >= 0 ? 'صافي الربح' : 'صافي الخسارة' }}</p>
        <p class="text-3xl font-bold {{ $netProfit >= 0 ? 'text-blue-700' : 'text-red-700' }}">{{ number_format(abs($netProfit), 0) }}</p>
        <p class="text-sm text-gray-400 mt-1">ر.ي
            @if($totalRevenue > 0)
            &nbsp;— هامش {{ number_format(($netProfit / $totalRevenue) * 100, 1) }}%
            @endif
        </p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-5">

    {{-- Revenue breakdown --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-semibold text-gray-700 text-sm">تفصيل الإيرادات</h3>
            <span class="text-sm font-bold text-green-700">{{ number_format($totalRevenue, 0) }} ر.ي</span>
        </div>
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2.5 text-right text-xs font-medium text-gray-500">المصدر</th>
                    <th class="px-4 py-2.5 text-right text-xs font-medium text-gray-500">المبلغ (ر.ي)</th>
                    <th class="px-4 py-2.5 text-right text-xs font-medium text-gray-500">النسبة</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($revenueByMethod as $row)
                <tr>
                    <td class="px-4 py-2.5 text-gray-700">
                        {{ match($row->method) {'cash'=>'نقداً','bank_transfer'=>'تحويل بنكي','pos'=>'POS',default=>$row->method} }}
                        <span class="text-xs text-gray-400">({{ $row->count }} دفعة)</span>
                    </td>
                    <td class="px-4 py-2.5 font-semibold text-green-700">{{ number_format($row->total, 0) }}</td>
                    <td class="px-4 py-2.5 text-gray-500">
                        {{ $totalRevenue > 0 ? number_format(($row->total / $totalRevenue) * 100, 1) : 0 }}%
                    </td>
                </tr>
                @endforeach
                @if($revenueByMethod->isEmpty())
                <tr><td colspan="3" class="px-4 py-6 text-center text-gray-400">لا توجد إيرادات</td></tr>
                @endif
            </tbody>
        </table>
    </div>

    {{-- Expense breakdown --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-semibold text-gray-700 text-sm">تفصيل المصروفات</h3>
            <span class="text-sm font-bold text-red-600">{{ number_format($totalExpenses, 0) }} ر.ي</span>
        </div>
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2.5 text-right text-xs font-medium text-gray-500">الفئة</th>
                    <th class="px-4 py-2.5 text-right text-xs font-medium text-gray-500">المبلغ (ر.ي)</th>
                    <th class="px-4 py-2.5 text-right text-xs font-medium text-gray-500">النسبة من الإيرادات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($expensesByCategory as $row)
                <tr>
                    <td class="px-4 py-2.5 text-gray-700">
                        {{ \App\Models\Expense::categoryLabel($row->category) }}
                        <span class="text-xs text-gray-400">({{ $row->count }})</span>
                    </td>
                    <td class="px-4 py-2.5 font-semibold text-red-600">{{ number_format($row->total, 0) }}</td>
                    <td class="px-4 py-2.5 text-gray-500">
                        {{ $totalRevenue > 0 ? number_format(($row->total / $totalRevenue) * 100, 1) : '—' }}%
                    </td>
                </tr>
                @endforeach
                @if($expensesByCategory->isEmpty())
                <tr><td colspan="3" class="px-4 py-6 text-center text-gray-400">لا توجد مصروفات</td></tr>
                @endif
            </tbody>
        </table>
    </div>
</div>

{{-- Monthly trend --}}
@if($monthlyTrend->isNotEmpty())
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
    <h3 class="font-semibold text-gray-700 text-sm mb-4">الاتجاه الشهري</h3>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2.5 text-right text-xs font-medium text-gray-500">الشهر</th>
                    <th class="px-4 py-2.5 text-right text-xs font-medium text-gray-500">الإيرادات</th>
                    <th class="px-4 py-2.5 text-right text-xs font-medium text-gray-500">المصروفات</th>
                    <th class="px-4 py-2.5 text-right text-xs font-medium text-gray-500">الربح/الخسارة</th>
                    <th class="px-4 py-2.5 text-right text-xs font-medium text-gray-500">الهامش</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($monthlyTrend as $row)
                @php $monthNet = $row->revenue - $row->expenses; @endphp
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-2.5 font-medium text-gray-700">{{ $row->month_label }}</td>
                    <td class="px-4 py-2.5 text-green-700">{{ number_format($row->revenue, 0) }}</td>
                    <td class="px-4 py-2.5 text-red-600">{{ number_format($row->expenses, 0) }}</td>
                    <td class="px-4 py-2.5 font-bold {{ $monthNet >= 0 ? 'text-blue-700' : 'text-red-600' }}">
                        {{ number_format($monthNet, 0) }}
                    </td>
                    <td class="px-4 py-2.5 text-gray-500">
                        {{ $row->revenue > 0 ? number_format(($monthNet / $row->revenue) * 100, 1) . '%' : '—' }}
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot class="bg-gray-50 font-bold">
                <tr>
                    <td class="px-4 py-3 text-gray-700">الإجمالي</td>
                    <td class="px-4 py-3 text-green-700">{{ number_format($totalRevenue, 0) }}</td>
                    <td class="px-4 py-3 text-red-600">{{ number_format($totalExpenses, 0) }}</td>
                    <td class="px-4 py-3 {{ $netProfit >= 0 ? 'text-blue-700' : 'text-red-600' }}">{{ number_format($netProfit, 0) }}</td>
                    <td class="px-4 py-3 text-gray-500">{{ $totalRevenue > 0 ? number_format(($netProfit / $totalRevenue) * 100, 1) . '%' : '—' }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endif

</div>
@endsection
