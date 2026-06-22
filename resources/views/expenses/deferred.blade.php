@extends('layouts.app')
@section('title', 'المصروفات المؤجلة')
@section('page-title', 'المصروفات المؤجلة (ديون الموردين)')

@section('content')
<div dir="rtl">

@if(session('success'))
<div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm flex items-center gap-2">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
    {{ session('success') }}
</div>
@endif

{{-- Summary Cards --}}
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-5">
    <div class="bg-white rounded-xl shadow-sm border border-red-100 p-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs text-gray-500 mb-1">ديون معلقة</p>
                <p class="text-2xl font-bold text-red-600">{{ number_format($totalDeferred, 0) }}</p>
            </div>
            <div class="text-3xl">⏱️</div>
        </div>
        <p class="text-xs text-gray-400 mt-1">{{ $deferredExpenses->count() }} مصروف</p>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-green-100 p-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs text-gray-500 mb-1">تم تسويتها</p>
                <p class="text-2xl font-bold text-green-700">{{ number_format($totalSettled, 0) }}</p>
            </div>
            <div class="text-3xl">✅</div>
        </div>
        <p class="text-xs text-gray-400 mt-1">ديون محصلة</p>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-orange-100 p-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs text-gray-500 mb-1">متأخرة جداً</p>
                <p class="text-2xl font-bold text-orange-600">{{ $deferredExpenses->where('expense_date', '<', now()->subDays(30))->count() }}</p>
            </div>
            <div class="text-3xl">🚨</div>
        </div>
        <p class="text-xs text-gray-400 mt-1">أكثر من 30 يوم</p>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-blue-100 p-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs text-gray-500 mb-1">معدل التسوية</p>
                <p class="text-2xl font-bold text-blue-600">{{ $deferredExpenses->count() + $recentlySettled->count() > 0 ? round(($recentlySettled->count() / ($deferredExpenses->count() + $recentlySettled->count())) * 100) : 0 }}%</p>
            </div>
            <div class="text-3xl">📊</div>
        </div>
        <p class="text-xs text-gray-400 mt-1">نسبة الإقفال</p>
    </div>
</div>

{{-- Unsettled expenses --}}
<div class="bg-white rounded-xl shadow-sm border border-yellow-100 mb-5">
    <div class="px-5 py-4 border-b border-yellow-100 bg-yellow-50">
        <div class="flex items-center gap-2 mb-2">
            <span class="inline-block px-2 py-1 bg-yellow-200 text-yellow-900 text-xs font-bold rounded">معلقة</span>
            <h3 class="font-semibold text-gray-800">مصروفات غير مسوّية</h3>
        </div>
        <p class="text-xs text-gray-600">إجمالي: <strong>{{ number_format($deferredExpenses->sum('amount'), 0) }} ر.ي</strong> — <strong>{{ $deferredExpenses->count() }}</strong> مصروفات</p>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الحالة</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">التاريخ</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الفئة</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">المستلم</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">المبلغ (ر.ي)</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الأيام</th>
                    @can('expenses.edit')
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500">الإجراء</th>
                    @endcan
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($deferredExpenses as $exp)
                @php
                    $daysAgo = $exp->expense_date->diffInDays(today());
                    $isOverdue = $daysAgo > 30;
                    $status = $isOverdue ? 'متأخرة' : 'قيد الانتظار';
                    $statusColor = $isOverdue ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800';
                    $statusEmoji = $isOverdue ? '🚨' : '⏳';
                @endphp
                <tr class="hover:bg-gray-50 {{ $isOverdue ? 'border-l-4 border-red-500' : '' }}">
                    <td class="px-4 py-3">
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold {{ $statusColor }}">
                            {{ $statusEmoji }} {{ $status }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-gray-600 text-xs whitespace-nowrap">{{ $exp->expense_date->format('d/m/Y') }}</td>
                    <td class="px-4 py-3">
                        <span class="text-xs px-2 py-0.5 rounded-full bg-indigo-100 text-indigo-800 font-medium">
                            {{ \App\Models\Expense::categoryLabel($exp->category) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-gray-700 font-medium">{{ $exp->recipient_name ?? '—' }}</td>
                    <td class="px-4 py-3 font-bold text-red-600">{{ number_format($exp->amount, 0) }}</td>
                    <td class="px-4 py-3">
                        <span class="text-xs font-semibold {{ $daysAgo > 30 ? 'text-red-600' : 'text-gray-600' }}">
                            {{ $daysAgo }} يوم
                        </span>
                    </td>
                    @can('expenses.edit')
                    <td class="px-4 py-3">
                        <form method="POST" action="{{ route('expenses.settle', $exp) }}" class="inline">
                            @csrf @method('PATCH')
                            <button type="submit" onclick="return confirm('تأكيد تسوية المصروف؟')"
                                    class="text-xs px-3 py-1.5 rounded-lg text-white font-semibold transition hover:opacity-90"
                                    style="background:#10b981;">
                                ✓ تسوية
                            </button>
                        </form>
                    </td>
                    @endcan
                </tr>
                @empty
                <tr><td colspan="7" class="px-4 py-8 text-center text-gray-400 font-medium">✨ لا توجد مصروفات مؤجلة معلقة</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Recently settled --}}
@if($recentlySettled->isNotEmpty())
<div class="bg-white rounded-xl shadow-sm border border-green-100">
    <div class="px-5 py-4 border-b border-green-100 bg-green-50">
        <div class="flex items-center gap-2 mb-2">
            <span class="inline-block px-2 py-1 bg-green-200 text-green-900 text-xs font-bold rounded">مسوّى</span>
            <h3 class="font-semibold text-gray-800">آخر المسوّيات (30 يوم)</h3>
        </div>
        <p class="text-xs text-gray-600">إجمالي: <strong>{{ number_format($recentlySettled->sum('amount'), 0) }} ر.ي</strong> — <strong>{{ $recentlySettled->count() }}</strong> ديون محصلة</p>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الحالة</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">تاريخ المصروف</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">المستلم</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">المبلغ (ر.ي)</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">تاريخ التسوية</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">بواسطة</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($recentlySettled as $exp)
                @php
                    $settledDaysAgo = $exp->settled_at->diffInDays(today());
                @endphp
                <tr class="hover:bg-green-50 border-l-4 border-green-500">
                    <td class="px-4 py-3">
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">
                            ✅ مسوّى
                        </span>
                    </td>
                    <td class="px-4 py-3 text-gray-600 text-xs whitespace-nowrap">{{ $exp->expense_date->format('d/m/Y') }}</td>
                    <td class="px-4 py-3 text-gray-700 font-medium">{{ $exp->recipient_name ?? '—' }}</td>
                    <td class="px-4 py-3 font-bold text-green-700">{{ number_format($exp->amount, 0) }}</td>
                    <td class="px-4 py-3 text-green-700 text-xs font-semibold">
                        {{ $exp->settled_at?->format('d/m/Y H:i') }}<br>
                        <span class="text-gray-500">منذ {{ $settledDaysAgo }} يوم</span>
                    </td>
                    <td class="px-4 py-3 text-gray-500 text-xs">{{ $exp->settledBy->name ?? '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

</div>
@endsection
