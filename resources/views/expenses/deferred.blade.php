@extends('layouts.app')
@section('title', 'المصروفات المؤجلة')
@section('page-title', 'المصروفات المؤجلة (ديون الموردين)')

@section('content')
<div dir="rtl">

@if(session('success'))
<div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">{{ session('success') }}</div>
@endif

{{-- Summary --}}
<div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-5">
    <div class="bg-white rounded-xl shadow-sm border border-red-100 p-4">
        <p class="text-xs text-gray-500 mb-1">إجمالي الديون المؤجلة</p>
        <p class="text-2xl font-bold text-red-600">{{ number_format($totalDeferred, 0) }}</p>
        <p class="text-xs text-gray-400">ر.ي — {{ $deferredExpenses->count() }} مصروف</p>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-green-100 p-4">
        <p class="text-xs text-gray-500 mb-1">تم تسويتها</p>
        <p class="text-2xl font-bold text-green-700">{{ number_format($totalSettled, 0) }}</p>
        <p class="text-xs text-gray-400">ر.ي</p>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
        <p class="text-xs text-gray-500 mb-1">عدد المصروفات المؤجلة</p>
        <p class="text-2xl font-bold text-gray-700">{{ $deferredExpenses->count() }}</p>
        <p class="text-xs text-gray-400">غير مسوّية</p>
    </div>
</div>

{{-- Unsettled expenses --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-5">
    <div class="px-5 py-3 border-b border-gray-100">
        <h3 class="font-semibold text-gray-700 text-sm">مصروفات غير مسوّية</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">التاريخ</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الفئة</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">المستلم</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الوصف</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">المبلغ (ر.ي)</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">أيام</th>
                    @can('expenses.edit')
                    <th class="px-4 py-3"></th>
                    @endcan
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($deferredExpenses as $exp)
                @php $daysAgo = $exp->expense_date->diffInDays(today()); @endphp
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 text-gray-600 text-xs">{{ $exp->expense_date->format('d/m/Y') }}</td>
                    <td class="px-4 py-3">
                        <span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-700">
                            {{ \App\Models\Expense::categoryLabel($exp->category) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-gray-700">{{ $exp->recipient_name ?? '—' }}</td>
                    <td class="px-4 py-3 text-gray-500 max-w-xs truncate">{{ $exp->description ?? '—' }}</td>
                    <td class="px-4 py-3 font-bold text-red-600">{{ number_format($exp->amount, 0) }}</td>
                    <td class="px-4 py-3">
                        <span class="text-xs font-medium {{ $daysAgo > 30 ? 'text-red-600' : 'text-gray-600' }}">
                            {{ $daysAgo }} يوم
                        </span>
                    </td>
                    @can('expenses.edit')
                    <td class="px-4 py-3">
                        <form method="POST" action="{{ route('expenses.settle', $exp) }}">
                            @csrf @method('PATCH')
                            <button type="submit" onclick="return confirm('تأكيد تسوية هذا المصروف؟')"
                                    class="text-xs px-3 py-1.5 rounded-lg text-white transition" style="background:#0F4C75;">
                                تسوية ✓
                            </button>
                        </form>
                    </td>
                    @endcan
                </tr>
                @empty
                <tr><td colspan="7" class="px-4 py-8 text-center text-gray-400">لا توجد مصروفات مؤجلة غير مسوّية</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Recently settled --}}
@if($recentlySettled->isNotEmpty())
<div class="bg-white rounded-xl shadow-sm border border-gray-100">
    <div class="px-5 py-3 border-b border-gray-100">
        <h3 class="font-semibold text-gray-700 text-sm">آخر المسوّيات (30 يوم)</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">تاريخ المصروف</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">المستلم</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">المبلغ (ر.ي)</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">تاريخ التسوية</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">بواسطة</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($recentlySettled as $exp)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 text-gray-600 text-xs">{{ $exp->expense_date->format('d/m/Y') }}</td>
                    <td class="px-4 py-3 text-gray-700">{{ $exp->recipient_name ?? '—' }}</td>
                    <td class="px-4 py-3 text-gray-700">{{ number_format($exp->amount, 0) }}</td>
                    <td class="px-4 py-3 text-green-700 text-xs">{{ $exp->settled_at?->format('d/m/Y H:i') }}</td>
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
