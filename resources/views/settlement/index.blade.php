@extends('layouts.app')
@section('title', 'التسوية النقدية')
@section('page-title', 'التسوية النقدية اليومية')

@section('content')
<div x-data="settlementPage()" x-init="init()" dir="rtl">

<!-- Settlement Header -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 mb-5">
    <div class="flex items-center justify-between flex-wrap gap-4">
        <div>
            <div class="flex items-center gap-3">
                <h2 class="text-lg font-bold text-gray-800">حساب يوم {{ $settlement->shift_date->format('d/m/Y') }}</h2>
                @if($settlement->status === 'locked')
                <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-xs font-medium">مُقفل ✓</span>
                @else
                <span class="px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs font-medium">مفتوح</span>
                @endif
            </div>
            <p class="text-sm text-gray-500 mt-1">الموظف: {{ $settlement->user->name }}</p>
            @if($settlement->status === 'locked')
            <p class="text-xs text-gray-400 mt-0.5">أُقفل بواسطة {{ $settlement->lockedBy?->name }} في {{ $settlement->locked_at?->format('d/m/Y H:i') }}</p>
            @endif
        </div>
        <div class="flex items-center gap-4">
            <!-- Net Balance Display -->
            <div class="text-center px-5 py-3 rounded-xl" style="background:#e8f0f7;">
                <p class="text-xs text-gray-500 mb-0.5">الرصيد المتوقع</p>
                <p class="text-2xl font-bold" style="color:#0F4C75;">{{ number_format($settlement->net_balance, 0) }}</p>
                <p class="text-xs text-gray-400">ر.ي</p>
            </div>
            @if($settlement->status === 'open')
            @can('settlement.lock')
            <button @click="lockModal=true"
                    class="px-5 py-2.5 bg-red-600 text-white rounded-lg text-sm font-semibold hover:bg-red-700 transition">
                إقفال الحساب
            </button>
            @endcan
            @endif
        </div>
    </div>
</div>

<!-- Summary Row -->
<div class="grid grid-cols-3 gap-4 mb-5">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 text-center">
        <p class="text-xs text-gray-500 mb-1">إجمالي الإيرادات</p>
        <p class="text-xl font-bold text-green-600">{{ number_format($settlement->total_received, 0) }} <span class="text-xs font-normal text-gray-400">ر.ي</span></p>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 text-center">
        <p class="text-xs text-gray-500 mb-1">إجمالي المصروفات</p>
        <p class="text-xl font-bold text-red-600">{{ number_format($settlement->total_withdrawals, 0) }} <span class="text-xs font-normal text-gray-400">ر.ي</span></p>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 text-center" style="border-color:#0F4C75; border-width:2px;">
        <p class="text-xs text-gray-500 mb-1">الصافي</p>
        <p class="text-xl font-bold" style="color:#0F4C75;">{{ number_format($settlement->net_balance, 0) }} <span class="text-xs font-normal text-gray-400">ر.ي</span></p>
    </div>
</div>

<!-- Payment Details Table -->
@if($perCurrency['payment_details']->isNotEmpty())
<div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-5">
    <div class="px-6 py-4 border-b border-gray-100">
        <h3 class="font-semibold text-gray-700">تفاصيل الإيرادات النقدية</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm" dir="rtl">
            <thead class="bg-gray-50"><tr>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">التاريخ</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الغرفة</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">المبلغ (ر.ي)</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($perCurrency['payment_details'] as $p)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 text-gray-600">{{ \Carbon\Carbon::parse($p->payment_date)->format('d/m/Y H:i') }}</td>
                    <td class="px-4 py-3 text-gray-700">{{ $p->reservation?->room?->room_number ?? '-' }}</td>
                    <td class="px-4 py-3 font-bold text-green-700">{{ number_format($p->amount, 0) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

<!-- Cash Expenses -->
@php $cashExpenses = $settlement->withdrawals->where('withdrawal_type', 'expense'); @endphp
<div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-5">
    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
        <div>
            <h3 class="font-semibold text-gray-700">المصروفات النقدية من الصندوق</h3>
            <p class="text-xs text-gray-400 mt-0.5">تُسجَّل من وحدة المصروفات وتُخصم تلقائياً</p>
        </div>
        @if($settlement->status === 'open')
        @can('expenses.create')
        <a href="{{ route('expenses.create') }}"
           class="flex items-center gap-2 px-4 py-2 text-white rounded-lg text-sm transition" style="background:#0F4C75;">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            تسجيل مصروف
        </a>
        @endcan
        @endif
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm" dir="rtl">
            <thead class="bg-gray-50"><tr>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">التاريخ</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الفئة</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">المبلغ (ر.ي)</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">المستلم</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">البيان</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($cashExpenses as $w)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 text-gray-600">{{ \Carbon\Carbon::parse($w->withdrawal_date)->format('d/m/Y H:i') }}</td>
                    <td class="px-4 py-3">
                        @if($w->expense)
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                            {{ \App\Models\Expense::categoryLabel($w->expense->category) }}
                        </span>
                        @else
                        <span class="text-gray-400 text-xs">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 font-bold text-red-600">{{ number_format($w->amount, 0) }}</td>
                    <td class="px-4 py-3 text-gray-700">{{ $w->withdrawn_by_name }}</td>
                    <td class="px-4 py-3 text-gray-500">{{ $w->notes ?: '—' }}</td>
                </tr>
                @empty
                <tr><td colspan="5" class="px-4 py-6 text-center text-gray-400 text-sm">لا توجد مصروفات نقدية</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Lock Modal with Actual Amount Comparison -->
<div x-show="lockModal" x-cloak class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4" @click.self="lockModal=false">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6">
        <div class="text-center mb-5">
            <div class="w-14 h-14 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-3">
                <svg class="w-7 h-7 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
            </div>
            <h3 class="font-bold text-gray-800 text-lg">إقفال الحساب</h3>
        </div>

        <!-- System balance -->
        <div class="rounded-xl p-4 mb-4 text-center" style="background:#e8f0f7;">
            <p class="text-xs text-gray-500 mb-1">المبلغ المتوقع في الصندوق</p>
            <p class="text-2xl font-bold" style="color:#0F4C75;">{{ number_format($settlement->net_balance, 0) }} <span class="text-sm font-normal">ر.ي</span></p>
        </div>

        <!-- Actual amount input -->
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1.5">المبلغ الموجود فعلياً لديك</label>
            <input type="number" x-model="actualAmount" @input="calcDiff()"
                   placeholder="0"
                   class="w-full border-2 border-gray-300 rounded-xl px-4 py-3 text-lg font-bold text-center outline-none focus:border-primary-500"
                   min="0" step="1">
        </div>

        <!-- Difference display -->
        <div x-show="actualAmount !== ''" class="rounded-xl p-3 mb-4 text-center"
             :class="diff === 0 ? 'bg-green-50 border border-green-200' : (diff < 0 ? 'bg-red-50 border border-red-200' : 'bg-yellow-50 border border-yellow-200')">
            <p class="text-xs mb-1"
               :class="diff === 0 ? 'text-green-600' : (diff < 0 ? 'text-red-600' : 'text-yellow-700')">
                <span x-text="diff === 0 ? 'المبلغ متطابق ✓' : (diff < 0 ? 'نقص في الصندوق ✗' : 'فائض في الصندوق')"></span>
            </p>
            <p x-show="diff !== 0" class="text-xl font-bold"
               :class="diff < 0 ? 'text-red-700' : 'text-yellow-700'"
               x-text="(diff < 0 ? '- ' : '+ ') + Math.abs(diff).toLocaleString() + ' ر.ي'"></p>
        </div>

        <div class="flex gap-3">
            <form method="POST" action="{{ route('settlement.lock') }}" class="flex-1" id="lockForm">
                @csrf
                <input type="hidden" name="actual_amount" :value="actualAmount">
                <button type="submit"
                        :disabled="actualAmount === ''"
                        :class="actualAmount === '' ? 'opacity-50 cursor-not-allowed' : 'hover:bg-red-700'"
                        class="w-full bg-red-600 text-white py-2.5 rounded-lg text-sm font-semibold transition">
                    تأكيد الإقفال
                </button>
            </form>
            <button @click="lockModal=false"
                    class="flex-1 border border-gray-300 text-gray-700 py-2.5 rounded-lg text-sm hover:bg-gray-50 transition">إلغاء</button>
        </div>
        <p x-show="diff < 0 && actualAmount !== ''" class="text-xs text-red-500 text-center mt-2">سيُسجَّل النقص ويُقفل الحساب</p>
    </div>
</div>

</div>
@endsection

@push('scripts')
<script>
function settlementPage() {
    return {
        lockModal: false,
        actualAmount: '',
        diff: 0,
        systemBalance: {{ $settlement->net_balance }},
        init() {},
        calcDiff() {
            const actual = parseFloat(this.actualAmount) || 0;
            this.diff = actual - this.systemBalance;
        }
    }
}
</script>
@endpush
