@extends('layouts.app')
@section('title', 'تقرير الإغلاق اليومي')
@section('page-title', 'تقرير الإغلاق اليومي')

@section('content')
<div dir="rtl">

{{-- Date filter --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-5">
    <form method="GET" class="flex flex-wrap items-end gap-3">
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">التاريخ</label>
            <input type="date" name="date" value="{{ $date }}"
                   class="border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none focus:border-blue-400">
        </div>
        <button type="submit" class="px-4 py-2 text-white rounded-lg text-sm" style="background:#0F4C75;">عرض</button>
        <a href="{{ route('reports.dailyClose', ['date' => $date]) }}&print=1" target="_blank"
           class="px-4 py-2 bg-red-600 text-white rounded-lg text-sm hover:bg-red-700 transition flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            طباعة
        </a>
    </form>
</div>

<h2 class="text-base font-bold text-gray-700 mb-4">
    تقرير إغلاق يوم {{ \Carbon\Carbon::parse($date)->isoFormat('dddd، D MMMM Y') }}
</h2>

{{-- Summary Cards --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-5">
    <div class="bg-white rounded-xl shadow-sm border border-green-100 p-4">
        <p class="text-xs text-gray-500 mb-1">إجمالي الإيرادات</p>
        <p class="text-2xl font-bold text-green-700">{{ number_format($totalRevenue, 0) }}</p>
        <p class="text-xs text-gray-400">ر.ي</p>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-red-100 p-4">
        <p class="text-xs text-gray-500 mb-1">إجمالي المصروفات</p>
        <p class="text-2xl font-bold text-red-600">{{ number_format($totalExpenses, 0) }}</p>
        <p class="text-xs text-gray-400">ر.ي</p>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-blue-100 p-4">
        <p class="text-xs text-gray-500 mb-1">صافي اليوم</p>
        <p class="text-2xl font-bold {{ $netDay >= 0 ? 'text-blue-700' : 'text-red-600' }}">{{ number_format($netDay, 0) }}</p>
        <p class="text-xs text-gray-400">ر.ي</p>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
        <p class="text-xs text-gray-500 mb-1">عدد الدفعات</p>
        <p class="text-2xl font-bold text-gray-700">{{ $paymentCount }}</p>
        <p class="text-xs text-gray-400">{{ $reservationCount }} حجز</p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-5">

    {{-- Revenue by method --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="px-5 py-3 border-b border-gray-100">
            <h3 class="font-semibold text-gray-700 text-sm">الإيرادات حسب طريقة الدفع</h3>
        </div>
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2.5 text-right text-xs font-medium text-gray-500">الطريقة</th>
                    <th class="px-4 py-2.5 text-right text-xs font-medium text-gray-500">عدد الدفعات</th>
                    <th class="px-4 py-2.5 text-right text-xs font-medium text-gray-500">المبلغ (ر.ي)</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($revenueByMethod as $row)
                <tr>
                    <td class="px-4 py-2.5">
                        <span class="text-xs px-2 py-0.5 rounded-full
                            {{ $row->method === 'cash' ? 'bg-green-100 text-green-700' : ($row->method === 'bank_transfer' ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700') }}">
                            {{ match($row->method) {'cash'=>'نقداً','bank_transfer'=>'تحويل بنكي','pos'=>'POS',default=>$row->method} }}
                        </span>
                    </td>
                    <td class="px-4 py-2.5 text-gray-600">{{ $row->count }}</td>
                    <td class="px-4 py-2.5 font-semibold text-gray-800">{{ number_format($row->total, 0) }}</td>
                </tr>
                @empty
                <tr><td colspan="3" class="px-4 py-6 text-center text-gray-400">لا توجد إيرادات لهذا اليوم</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Expenses by category --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="px-5 py-3 border-b border-gray-100">
            <h3 class="font-semibold text-gray-700 text-sm">المصروفات حسب الفئة</h3>
        </div>
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2.5 text-right text-xs font-medium text-gray-500">الفئة</th>
                    <th class="px-4 py-2.5 text-right text-xs font-medium text-gray-500">العدد</th>
                    <th class="px-4 py-2.5 text-right text-xs font-medium text-gray-500">المبلغ (ر.ي)</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($expensesByCategory as $row)
                <tr>
                    <td class="px-4 py-2.5 text-gray-700">{{ \App\Models\Expense::categoryLabel($row->category) }}</td>
                    <td class="px-4 py-2.5 text-gray-600">{{ $row->count }}</td>
                    <td class="px-4 py-2.5 font-semibold text-red-600">{{ number_format($row->total, 0) }}</td>
                </tr>
                @empty
                <tr><td colspan="3" class="px-4 py-6 text-center text-gray-400">لا توجد مصروفات لهذا اليوم</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Shifts summary --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-5">
    <div class="px-5 py-3 border-b border-gray-100">
        <h3 class="font-semibold text-gray-700 text-sm">ورديات اليوم</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2.5 text-right text-xs font-medium text-gray-500">الموظف</th>
                    <th class="px-4 py-2.5 text-right text-xs font-medium text-gray-500">البدء</th>
                    <th class="px-4 py-2.5 text-right text-xs font-medium text-gray-500">الإقفال</th>
                    <th class="px-4 py-2.5 text-right text-xs font-medium text-gray-500">مستلمات</th>
                    <th class="px-4 py-2.5 text-right text-xs font-medium text-gray-500">سحبيات</th>
                    <th class="px-4 py-2.5 text-right text-xs font-medium text-gray-500">الصافي</th>
                    <th class="px-4 py-2.5 text-right text-xs font-medium text-gray-500">عجز/فائض</th>
                    <th class="px-4 py-2.5 text-right text-xs font-medium text-gray-500">الحالة</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($shifts as $shift)
                @php $net = $shift->net_balance_yer; @endphp
                <tr>
                    <td class="px-4 py-2.5 font-medium text-gray-800">{{ $shift->user->name ?? '—' }}</td>
                    <td class="px-4 py-2.5 text-gray-500 text-xs">{{ $shift->started_at?->format('H:i') }}</td>
                    <td class="px-4 py-2.5 text-gray-500 text-xs">{{ $shift->closed_at?->format('H:i') ?? '—' }}</td>
                    <td class="px-4 py-2.5 text-green-700">{{ number_format($shift->total_received_yer, 0) }}</td>
                    <td class="px-4 py-2.5 text-red-600">{{ number_format($shift->total_withdrawals_yer, 0) }}</td>
                    <td class="px-4 py-2.5 font-semibold" style="color:#0F4C75;">{{ number_format($net, 0) }}</td>
                    <td class="px-4 py-2.5 font-semibold {{ $shift->shortfall < 0 ? 'text-red-600' : 'text-gray-400' }}">
                        {{ $shift->shortfall != 0 ? number_format($shift->shortfall, 0) : '—' }}
                    </td>
                    <td class="px-4 py-2.5">
                        @if($shift->is_closed)
                        <span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-600">مقفلة</span>
                        @else
                        <span class="text-xs px-2 py-0.5 rounded-full bg-green-100 text-green-700">مفتوحة</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="px-4 py-6 text-center text-gray-400">لا توجد ورديات لهذا اليوم</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Cash reconciliation box --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
    <h3 class="font-semibold text-gray-700 text-sm mb-4">تسوية الكاش (ريال يمني)</h3>
    <div class="space-y-2 text-sm">
        <div class="flex justify-between items-center py-2 border-b border-gray-100">
            <span class="text-gray-600">إجمالي المستلمات نقداً</span>
            <span class="font-semibold text-green-700">{{ number_format($cashRevenue, 0) }} ر.ي</span>
        </div>
        <div class="flex justify-between items-center py-2 border-b border-gray-100">
            <span class="text-gray-600">إجمالي المصروفات نقداً</span>
            <span class="font-semibold text-red-600">{{ number_format($cashExpenses, 0) }} ر.ي</span>
        </div>
        <div class="flex justify-between items-center py-2 border-b border-gray-100">
            <span class="text-gray-600">السحبيات من الصناديق</span>
            <span class="font-semibold text-red-600">{{ number_format($totalWithdrawals, 0) }} ر.ي</span>
        </div>
        <div class="flex justify-between items-center py-3 bg-blue-50 rounded-lg px-3 mt-2">
            <span class="font-bold text-gray-700">الرصيد النقدي المتوقع</span>
            <span class="text-xl font-bold" style="color:#0F4C75;">{{ number_format($expectedCash, 0) }} ر.ي</span>
        </div>
    </div>
</div>

</div>
@endsection
