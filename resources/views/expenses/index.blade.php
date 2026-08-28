@extends('layouts.app')
@section('title', 'المصروفات')
@section('page-title', 'إدارة المصروفات')

@section('content')
<div dir="rtl">

<div class="flex items-center justify-between mb-5 flex-wrap gap-2" x-data="{ withdrawalModal: false }">
    <div class="flex gap-2 flex-wrap">
        <a href="{{ route('expenses.pdf', request()->query()) }}"
           class="flex items-center gap-2 px-4 py-2 bg-red-600 text-white rounded-lg text-sm hover:bg-red-700 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
            PDF
        </a>
        <a href="{{ route('expenses.excel', request()->query()) }}"
           class="flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg text-sm hover:bg-green-700 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            Excel
        </a>
        @can('withdrawal.create')
        <button @click="withdrawalModal=true" class="flex items-center gap-2 px-4 py-2 border border-orange-300 text-orange-700 rounded-lg text-sm hover:bg-orange-50 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            تسجيل سحب
        </button>
        @endcan
    </div>
    <div class="flex gap-2">
        @can('expenses.create')
        <a href="{{ route('expenses.create') }}" class="flex items-center gap-2 px-4 py-2 text-white rounded-lg text-sm transition" style="background:#0F4C75;">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            تسجيل مصروف
        </a>
        @endcan
    </div>

    {{-- Withdrawal Modal --}}
    @can('withdrawal.create')
    <div x-show="withdrawalModal" x-cloak class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4" @click.self="withdrawalModal=false">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <h3 class="font-bold text-gray-800">تسجيل سحب</h3>
                <button @click="withdrawalModal=false" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <form method="POST" action="{{ route('shifts.withdrawal') }}" class="p-6 space-y-4">
                @csrf
                <input type="hidden" name="withdrawal_type" value="expense">
                <input type="hidden" name="currency" value="YER">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">المبلغ (ر.ي) *</label>
                    <input type="number" name="amount" step="0.01" min="0.01" required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">التصنيف</label>
                    <select name="category"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none bg-white">
                        @foreach($categories as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">مصدر التمويل *</label>
                    <select name="funding_source" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none bg-white">
                        @if($activeShift)
                        <option value="shift">من الوردية (وردية {{ $activeShift->shift_date->format('d/m/Y') }})</option>
                        @endif
                        <option value="general_safe" {{ $activeShift ? '' : 'selected' }}>من الصندوق العام</option>
                    </select>
                    @unless($activeShift)
                    <p class="text-xs text-gray-400 mt-1">لا توجد وردية مفتوحة لك — هذا السحب سيُقيَّد على الصندوق العام.</p>
                    @endunless
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">اسم المستلم *</label>
                    <input type="text" name="withdrawn_by_name" required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">السبب / البيان</label>
                    <input type="text" name="notes"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">ربط بموظف <span class="text-gray-400 font-normal">(اختياري — سلفة شخصية تُخصم من راتبه)</span></label>
                    <select name="employee_id"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none bg-white">
                        <option value="">— بلا ربط (سحب عادي) —</option>
                        @foreach($employees as $employee)
                        <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                        @endforeach
                    </select>
                    <p class="text-xs text-amber-600 mt-1">اختر الموظف فقط إذا كان هذا المبلغ سلفة شخصية له — لا اسم المستلم العام.</p>
                </div>
                <button type="submit" class="w-full py-2.5 bg-orange-600 hover:bg-orange-700 text-white rounded-lg text-sm font-semibold transition">
                    تسجيل السحب
                </button>
            </form>
        </div>
    </div>
    @endcan
</div>

@if(session('success'))
<div class="mb-4 bg-green-50 border border-green-200 text-green-700 rounded-lg px-4 py-3 text-sm">{{ session('success') }}</div>
@endif

<!-- Summary Cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-5">
    <div class="bg-white rounded-xl shadow-sm border border-red-100 p-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs text-gray-500 mb-1">إجمالي المصروفات</p>
                <p class="text-2xl font-bold text-red-600">{{ number_format($stats['total'], 0) }}</p>
            </div>
            <div class="text-3xl">💰</div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-blue-100 p-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs text-gray-500 mb-1">عدد المصروفات</p>
                <p class="text-2xl font-bold text-blue-600">{{ $stats['count'] }}</p>
            </div>
            <div class="text-3xl">📊</div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-purple-100 p-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs text-gray-500 mb-1">المتوسط</p>
                <p class="text-2xl font-bold text-purple-600">{{ number_format($stats['average'], 0) }}</p>
            </div>
            <div class="text-3xl">📈</div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-green-100 p-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs text-gray-500 mb-1">الأقل</p>
                <p class="text-2xl font-bold text-green-600">{{ number_format($stats['min'], 0) }}</p>
            </div>
            <div class="text-3xl">↓</div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-orange-100 p-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs text-gray-500 mb-1">الأعلى</p>
                <p class="text-2xl font-bold text-orange-600">{{ number_format($stats['max'], 0) }}</p>
            </div>
            <div class="text-3xl">↑</div>
        </div>
    </div>
</div>

<!-- Chart Section -->
@if($byCategory->count() > 0)
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 mb-5">
    <h3 class="font-semibold text-gray-700 mb-4">توزيع المصروفات حسب الفئة</h3>
    <div class="flex flex-col lg:flex-row gap-5">
        <div class="flex-1 h-80 flex items-center justify-center">
            <canvas id="categoryChart"></canvas>
        </div>
        <div class="lg:w-64">
            <div class="space-y-2">
                @php $colors = ['#FF6B6B', '#4ECDC4', '#45B7D1', '#FFA07A', '#98D8C8', '#6C5CE7']; @endphp
                @foreach($byCategory as $cat => $data)
                <div class="flex items-center justify-between text-sm">
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 rounded-full" style="background:{{ $colors[array_search($cat, array_keys($byCategory->toArray())) % count($colors)] }};"></div>
                        <span class="text-gray-700">{{ $categories[$cat] ?? $cat }}</span>
                    </div>
                    <span class="font-semibold text-gray-900">{{ number_format($data['total'], 0) }}</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endif

<!-- Summary by payment method (مدموج من صفحة التقرير) -->
@if(isset($byMethod) && $byMethod->count() > 0)
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 mb-5">
    <h3 class="font-semibold text-gray-700 mb-4">ملخص حسب طريقة الدفع</h3>
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        @foreach($byMethod as $method => $data)
        <div class="flex items-center justify-between p-3 rounded-lg border border-gray-100 bg-gray-50">
            <div>
                <p class="text-sm font-medium text-gray-700">{{ \App\Models\Expense::paymentMethodLabel($method) }}</p>
                <p class="text-xs text-gray-400">{{ $data['count'] }} عملية</p>
            </div>
            <p class="font-bold text-gray-900">{{ number_format($data['total'], 0) }} <span class="text-xs font-normal text-gray-400">ر.ي</span></p>
        </div>
        @endforeach
    </div>
</div>
@endif

<!-- Filters -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-5">
    <form method="GET" class="flex flex-wrap gap-3 items-end">
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
        <div class="flex flex-col gap-1">
            <label class="text-xs font-medium text-gray-500">من تاريخ</label>
            <input type="date" name="date_from" value="{{ request('date_from') }}" onchange="this.form.submit()"
                   class="border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400 transition bg-white">
        </div>
        <div class="flex flex-col gap-1">
            <label class="text-xs font-medium text-gray-500">إلى تاريخ</label>
            <input type="date" name="date_to" value="{{ request('date_to') }}" onchange="this.form.submit()"
                   class="border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400 transition bg-white">
        </div>
        <div class="flex flex-col gap-1">
            <label class="text-xs font-medium text-gray-500">الوردية</label>
            <select name="shift_id" onchange="this.form.submit()"
                    class="border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400 transition bg-white min-w-[180px]">
                <option value="">جميع الورديات</option>
                @foreach($availableShifts as $shift)
                <option value="{{ $shift->id }}" {{ request('shift_id') == $shift->id ? 'selected' : '' }}>
                    {{ $shift->shift_date->format('d/m/Y') }}
                    @if(auth()->user()->isAdmin()) — {{ $shift->user->name }} @endif
                    ({{ $shift->is_closed ? 'مقفلة' : 'مفتوحة' }})
                </option>
                @endforeach
            </select>
        </div>
        <div class="flex flex-col gap-1 flex-1 min-w-[180px]">
            <label class="text-xs font-medium text-gray-500">بحث</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="اسم المستلم أو الوصف..."
                   class="border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400 transition bg-white">
        </div>
        <button type="submit" class="sr-only">بحث</button>
        @if(request()->hasAny(['category','payment_method','date_from','date_to','search','shift_id']))
        <a href="{{ route('expenses.index') }}"
           class="flex items-center gap-1.5 px-3 py-2 text-xs text-gray-500 border border-gray-200 rounded-lg hover:bg-gray-50 transition self-end">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            مسح
        </a>
        @endif
    </form>
</div>

<!-- Table -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm" dir="rtl" id="expensesTable">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">التاريخ</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الفئة</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">المبلغ (ر.ي)</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">طريقة الدفع</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">اسم المستلم</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الوصف</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">سُجِّل بواسطة</th>
                    @canany(['expenses.edit','expenses.delete'])
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">إجراءات</th>
                    @endcanany
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($expenses as $expense)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 text-gray-600 whitespace-nowrap">{{ $expense->expense_date->format('d/m/Y') }}</td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                            {{ \App\Models\Expense::categoryLabel($expense->category) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 font-bold text-red-600">{{ number_format($expense->amount, 0) }}</td>
                    <td class="px-4 py-3">
                        @php $pm = $expense->payment_method ?? 'cash'; @endphp
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium
                            {{ $pm === 'cash' ? 'bg-green-100 text-green-800' : ($pm === 'bank_transfer' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-600') }}">
                            {{ \App\Models\Expense::paymentMethodLabel($pm) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-gray-700">
                        {{ $expense->recipient_name ?? '—' }}
                        @if($expense->employee_id)
                        <a href="{{ route('employees.withdrawals', $expense->employee_id) }}"
                           class="inline-block mr-1 px-1.5 py-0.5 rounded text-[10px] font-semibold bg-amber-100 text-amber-800 hover:bg-amber-200"
                           title="مصروف لموظف — يُخصم من راتبه">موظف</a>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-gray-500 max-w-xs truncate">{{ $expense->description ?? '—' }}</td>
                    <td class="px-4 py-3 text-gray-500">{{ $expense->paidBy?->name ?? '—' }}</td>
                    @canany(['expenses.edit','expenses.delete'])
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2">
                            @can('expenses.edit')
                            <a href="{{ route('expenses.edit', $expense) }}" class="text-xs px-3 py-1.5 rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50 transition">تعديل</a>
                            @endcan
                            @can('expenses.delete')
                            <form method="POST" action="{{ route('expenses.destroy', $expense) }}" onsubmit="return confirm('هل أنت متأكد من حذف هذا المصروف؟')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-xs px-3 py-1.5 rounded-lg border border-red-200 text-red-600 hover:bg-red-50 transition">حذف</button>
                            </form>
                            @endcan
                        </div>
                    </td>
                    @endcanany
                </tr>
                @empty
                <tr><td colspan="8" class="px-4 py-8 text-center text-gray-400">لا توجد مصروفات مسجّلة</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($expenses->hasPages())
    <div class="px-4 py-3 border-t border-gray-100">
        <x-pagination-info :items="$expenses" />
        {{ $expenses->links() }}
    </div>
    @endif
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js"></script>
<script>
    @if($byCategory->count() > 0)
    const ctx = document.getElementById('categoryChart')?.getContext('2d');
    if (ctx) {
        new Chart(ctx, {
            type: 'pie',
            data: {
                labels: [
                    @foreach($byCategory as $cat => $data)
                    '{{ $categories[$cat] ?? $cat }}',
                    @endforeach
                ],
                datasets: [{
                    data: [
                        @foreach($byCategory as $cat => $data)
                        {{ $data['total'] }},
                        @endforeach
                    ],
                    backgroundColor: ['#FF6B6B', '#4ECDC4', '#45B7D1', '#FFA07A', '#98D8C8', '#6C5CE7', '#F0A500', '#FF6B9D'],
                    borderColor: '#fff',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { usePointStyle: true, padding: 15 }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.label + ': ' + number_format(context.parsed, 0) + ' ر.ي';
                            }
                        }
                    }
                }
            }
        });
    }
    @endif

    function number_format(num, decimals = 0) {
        return num.toFixed(decimals).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }
</script>

</div>
@endsection
