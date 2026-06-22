@extends('layouts.app')
@section('title', 'الميزانية الشهرية')
@section('page-title', 'الميزانية الشهرية')

@section('content')
<div x-data="budgetManager()" dir="rtl">

{{-- Year Selector & Actions --}}
<div class="mb-5 flex flex-wrap gap-3 items-center justify-between">
    <form class="flex gap-2 items-center">
        <label class="text-sm text-gray-600">السنة:</label>
        <select name="year" onchange="this.form.submit()" class="border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:border-blue-400 outline-none">
            @foreach($years as $y)
            <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
            @endforeach
        </select>
    </form>
    <button @click="showModal = true" class="px-4 py-2 text-sm text-white rounded-lg bg-blue-600 hover:bg-blue-700 transition">
        + إضافة ميزانية سريعة
    </button>
</div>

{{-- Summary Cards --}}
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-5">
    @php
        $totalRevenue = collect($months)->sum('actual_revenue');
        $totalBudgetRev = collect($months)->sum('revenue_target');
        $totalExpense = collect($months)->sum('actual_expense');
        $totalBudgetExp = collect($months)->sum('expense_limit');
        $totalProfit = $totalRevenue - $totalExpense;
        $totalBudgetProfit = $totalBudgetRev - $totalBudgetExp;
    @endphp

    <div class="bg-white rounded-xl shadow-sm border border-blue-100 p-4">
        <p class="text-xs text-gray-500 mb-1">الإيراد المتحقق</p>
        <p class="text-xl font-bold text-blue-700">{{ number_format($totalRevenue, 0) }}</p>
        <p class="text-xs text-gray-400 mt-1">الهدف: {{ number_format($totalBudgetRev, 0) }}</p>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-red-100 p-4">
        <p class="text-xs text-gray-500 mb-1">المصروفات الفعلية</p>
        <p class="text-xl font-bold text-red-600">{{ number_format($totalExpense, 0) }}</p>
        <p class="text-xs text-gray-400 mt-1">السقف: {{ number_format($totalBudgetExp, 0) }}</p>
    </div>

    <div class="bg-white rounded-xl shadow-sm border {{ $totalProfit >= 0 ? 'border-green-100' : 'border-red-100' }} p-4">
        <p class="text-xs text-gray-500 mb-1">الربح المتحقق</p>
        <p class="text-xl font-bold {{ $totalProfit >= 0 ? 'text-green-700' : 'text-red-700' }}">{{ number_format($totalProfit, 0) }}</p>
        <p class="text-xs text-gray-400 mt-1">المتوقع: {{ number_format($totalBudgetProfit, 0) }}</p>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-purple-100 p-4">
        <p class="text-xs text-gray-500 mb-1">معدل التحقق</p>
        <p class="text-xl font-bold text-purple-700">{{ $totalBudgetRev > 0 ? round(($totalRevenue / $totalBudgetRev) * 100) : 0 }}%</p>
        <p class="text-xs text-gray-400 mt-1">من الهدف السنوي</p>
    </div>
</div>

{{-- Tabs for different views --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-5">
    <div class="flex gap-4 px-5 py-4 border-b border-gray-100 overflow-x-auto">
        <button @click="activeTab = 'overview'" :class="activeTab === 'overview' ? 'border-b-2 border-blue-600 text-blue-600 font-semibold' : 'text-gray-600'" class="pb-1 text-sm transition whitespace-nowrap">
            📊 نظرة عامة
        </button>
        <button @click="activeTab = 'revenue'" :class="activeTab === 'revenue' ? 'border-b-2 border-green-600 text-green-600 font-semibold' : 'text-gray-600'" class="pb-1 text-sm transition whitespace-nowrap">
            💰 الإيرادات
        </button>
        <button @click="activeTab = 'expenses'" :class="activeTab === 'expenses' ? 'border-b-2 border-red-600 text-red-600 font-semibold' : 'text-gray-600'" class="pb-1 text-sm transition whitespace-nowrap">
            💸 المصروفات
        </button>
        <button @click="activeTab = 'profit'" :class="activeTab === 'profit' ? 'border-b-2 border-purple-600 text-purple-600 font-semibold' : 'text-gray-600'" class="pb-1 text-sm transition whitespace-nowrap">
            📈 الأرباح
        </button>
    </div>

    {{-- Tab: Overview (Full Table) --}}
    <div x-show="activeTab === 'overview'" class="overflow-x-auto">
        <table class="w-full text-sm" dir="rtl">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-right font-medium text-gray-600">الشهر</th>
                    <th class="px-4 py-3 text-center font-medium text-gray-600 hidden md:table-cell">الهدف الإيرادي</th>
                    <th class="px-4 py-3 text-center font-medium text-gray-600">الإيراد الفعلي</th>
                    <th class="px-4 py-3 text-center font-medium text-gray-600">التحقق %</th>
                    <th class="px-4 py-3 text-center font-medium text-gray-600 hidden md:table-cell">سقف المصروفات</th>
                    <th class="px-4 py-3 text-center font-medium text-gray-600">المصروفات</th>
                    <th class="px-4 py-3 text-center font-medium text-gray-600">إجراءات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($months as $m)
                @php
                    $revPct   = $m['revenue_target'] > 0 ? round(($m['actual_revenue'] / $m['revenue_target']) * 100) : 0;
                    $expDiff  = $m['expense_limit'] - $m['actual_expense'];
                @endphp
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-medium text-gray-800">{{ $m['month_name'] }}</td>
                    <td class="px-4 py-3 text-center text-gray-600 hidden md:table-cell">{{ number_format($m['revenue_target'], 0) }}</td>
                    <td class="px-4 py-3 text-center font-semibold text-gray-800">{{ number_format($m['actual_revenue'], 0) }}</td>
                    <td class="px-4 py-3 text-center">
                        <span class="px-2 py-1 rounded-lg text-xs font-bold {{ $revPct >= 100 ? 'bg-green-100 text-green-700' : ($revPct >= 80 ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700') }}">
                            {{ $revPct }}%
                        </span>
                    </td>
                    <td class="px-4 py-3 text-center text-gray-600 hidden md:table-cell">{{ number_format($m['expense_limit'], 0) }}</td>
                    <td class="px-4 py-3 text-center font-semibold text-red-600">{{ number_format($m['actual_expense'], 0) }}</td>
                    <td class="px-4 py-3 text-center">
                        @if($m['budget_id'])
                        <a href="{{ route('budgets.edit', $m['budget_id']) }}" class="text-xs text-primary-600 hover:underline">تعديل</a>
                        @else
                        <button @click="openModal({{ $m['month'] }}, '{{ $m['month_name'] }}')" class="text-xs text-primary-600 hover:underline font-medium">+ إضافة</button>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Tab: Revenue --}}
    <div x-show="activeTab === 'revenue'" class="p-5">
        <table class="w-full text-sm" dir="rtl">
            <thead class="bg-gray-50 rounded-lg">
                <tr>
                    <th class="px-4 py-3 text-right font-medium text-gray-600">الشهر</th>
                    <th class="px-4 py-3 text-center font-medium text-gray-600">الهدف</th>
                    <th class="px-4 py-3 text-center font-medium text-gray-600">الفعلي</th>
                    <th class="px-4 py-3 text-center font-medium text-gray-600">التقدم</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($months as $m)
                @php $revPct = $m['revenue_target'] > 0 ? ($m['actual_revenue'] / $m['revenue_target']) * 100 : 0; @endphp
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-medium text-gray-800">{{ $m['month_name'] }}</td>
                    <td class="px-4 py-3 text-center text-gray-600">{{ number_format($m['revenue_target'], 0) }}</td>
                    <td class="px-4 py-3 text-center font-semibold text-green-600">{{ number_format($m['actual_revenue'], 0) }}</td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2">
                            <div class="flex-1 h-2 bg-gray-100 rounded-full overflow-hidden">
                                <div class="h-full rounded-full {{ $revPct >= 100 ? 'bg-green-500' : ($revPct >= 80 ? 'bg-amber-400' : 'bg-red-500') }}" style="width: {{ min($revPct, 100) }}%"></div>
                            </div>
                            <span class="text-xs font-bold text-gray-700">{{ round($revPct) }}%</span>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Tab: Expenses --}}
    <div x-show="activeTab === 'expenses'" class="p-5">
        <table class="w-full text-sm" dir="rtl">
            <thead class="bg-gray-50 rounded-lg">
                <tr>
                    <th class="px-4 py-3 text-right font-medium text-gray-600">الشهر</th>
                    <th class="px-4 py-3 text-center font-medium text-gray-600">السقف</th>
                    <th class="px-4 py-3 text-center font-medium text-gray-600">الفعلي</th>
                    <th class="px-4 py-3 text-center font-medium text-gray-600">الوفر/الزيادة</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($months as $m)
                @php
                    $expDiff = $m['expense_limit'] - $m['actual_expense'];
                    $expPct = $m['expense_limit'] > 0 ? ($m['actual_expense'] / $m['expense_limit']) * 100 : 0;
                @endphp
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-medium text-gray-800">{{ $m['month_name'] }}</td>
                    <td class="px-4 py-3 text-center text-gray-600">{{ number_format($m['expense_limit'], 0) }}</td>
                    <td class="px-4 py-3 text-center font-semibold text-red-600">{{ number_format($m['actual_expense'], 0) }}</td>
                    <td class="px-4 py-3">
                        <span class="text-xs font-bold {{ $expDiff >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            {{ $expDiff >= 0 ? '+' : '' }}{{ number_format($expDiff, 0) }}
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Tab: Profit --}}
    <div x-show="activeTab === 'profit'" class="p-5">
        <table class="w-full text-sm" dir="rtl">
            <thead class="bg-gray-50 rounded-lg">
                <tr>
                    <th class="px-4 py-3 text-right font-medium text-gray-600">الشهر</th>
                    <th class="px-4 py-3 text-center font-medium text-gray-600">الربح المتوقع</th>
                    <th class="px-4 py-3 text-center font-medium text-gray-600">الربح الفعلي</th>
                    <th class="px-4 py-3 text-center font-medium text-gray-600">الفرق</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($months as $m)
                @php $diff = $m['net_actual'] - $m['net_budget']; @endphp
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-medium text-gray-800">{{ $m['month_name'] }}</td>
                    <td class="px-4 py-3 text-center text-gray-600">{{ number_format($m['net_budget'], 0) }}</td>
                    <td class="px-4 py-3 text-center font-semibold {{ $m['net_actual'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                        {{ $m['net_actual'] >= 0 ? '+' : '' }}{{ number_format($m['net_actual'], 0) }}
                    </td>
                    <td class="px-4 py-3 text-center font-bold {{ $diff >= 0 ? 'text-green-600' : 'text-red-600' }}">
                        {{ $diff >= 0 ? '+' : '' }}{{ number_format($diff, 0) }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- Budget Modal --}}
<div x-show="showModal" @keydown.escape="showModal = false" x-transition class="fixed inset-0 bg-black/20 flex items-center justify-center p-4 z-50">
    <div @click.away="showModal = false" class="bg-white rounded-xl max-w-md w-full p-6 shadow-xl animate-fadeIn">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-gray-800">إضافة/تعديل ميزانية — <span x-text="selectedMonthName" class="text-blue-600"></span></h3>
            <button @click="showModal = false" class="text-gray-400 hover:text-gray-600">✕</button>
        </div>

        <form @submit.prevent="submitBudget" class="space-y-4">
            @csrf
            <input type="hidden" name="year" value="{{ $year }}">
            <input type="hidden" name="month" x-model="selectedMonth">

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">الهدف الإيرادي (ر.ي) *</label>
                <input type="number" x-model="budgetForm.revenue_target" required step="1" min="0"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm outline-none focus:border-blue-400"
                       placeholder="مثال: 50000">
                <p class="text-xs text-gray-500 mt-1">📊 هل توقعاتك للإيراد معقولة؟</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">سقف المصروفات (ر.ي) *</label>
                <input type="number" x-model="budgetForm.expense_limit" required step="1" min="0"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm outline-none focus:border-blue-400"
                       placeholder="مثال: 20000">
                <p class="text-xs text-gray-500 mt-1">💡 ضع حداً أقصى معقولاً للمصروفات</p>
            </div>

            <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 text-xs text-blue-700">
                <p><strong>ملخص الميزانية:</strong></p>
                <p class="mt-1">الربح المتوقع: <span x-text="budgetForm.revenue_target - budgetForm.expense_limit" class="font-bold"></span> ر.ي</p>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-semibold hover:bg-blue-700 transition">
                    حفظ الميزانية
                </button>
                <button type="button" @click="showModal = false" class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg text-sm hover:bg-gray-50 transition">
                    إلغاء
                </button>
            </div>
        </form>
    </div>
</div>

</div>

@endsection

@push('scripts')
<script>
function budgetManager() {
    return {
        activeTab: 'overview',
        showModal: false,
        selectedMonth: null,
        selectedMonthName: '',
        budgetForm: {
            revenue_target: '',
            expense_limit: '',
        },
        openModal(month, monthName) {
            this.selectedMonth = month;
            this.selectedMonthName = monthName;
            this.budgetForm = { revenue_target: '', expense_limit: '' };
            this.showModal = true;
        },
        submitBudget() {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route("budgets.store") }}';
            form.innerHTML = `
                {{ csrf_field() }}
                <input type="hidden" name="year" value="{{ $year }}">
                <input type="hidden" name="month" value="${this.selectedMonth}">
                <input type="hidden" name="revenue_target" value="${this.budgetForm.revenue_target}">
                <input type="hidden" name="expense_limit" value="${this.budgetForm.expense_limit}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }
}
</script>
@endpush
