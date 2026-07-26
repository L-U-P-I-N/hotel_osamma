@extends('layouts.app')
@section('title', 'كشف حساب الموظف')
@section('page-title', 'كشف حساب الموظف')

@section('content')
<div dir="rtl">

{{-- Header --}}
<div class="flex items-center justify-between mb-5 flex-wrap gap-3">
    <a href="{{ route('users.index') }}" class="text-sm text-gray-500 hover:text-gray-700">← المستخدمون</a>
    <a href="{{ route('users.statement.pdf', ['user' => $user, 'from' => $from, 'to' => $to]) }}" target="_blank"
       class="flex items-center gap-2 px-4 py-2 bg-red-600 text-white rounded-lg text-sm hover:bg-red-700 transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
        تصدير PDF
    </a>
</div>

{{-- User Card + date filter --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 mb-5">
    <div class="flex items-center gap-4 flex-wrap">
        <div class="w-14 h-14 rounded-full flex items-center justify-center text-white text-xl font-bold flex-shrink-0"
             style="background:#0F4C75;">
            {{ mb_substr($user->name, 0, 1) }}
        </div>
        <div class="flex-1 min-w-40">
            <h2 class="text-lg font-bold text-gray-800">{{ $user->name }}</h2>
            <p class="text-sm text-gray-500">{{ $user->roles->first()?->name === 'admin' ? 'مدير' : ($user->roles->first()?->name ?? '—') }} — {{ $user->employee_id ?? '—' }}</p>
        </div>

        <form method="GET" class="flex items-end gap-2 no-print">
            <div class="flex flex-col gap-1">
                <label class="text-xs font-medium text-gray-500">من تاريخ</label>
                <input type="date" name="from" value="{{ $from }}"
                       class="border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400 transition bg-white">
            </div>
            <div class="flex flex-col gap-1">
                <label class="text-xs font-medium text-gray-500">إلى تاريخ</label>
                <input type="date" name="to" value="{{ $to }}"
                       class="border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400 transition bg-white">
            </div>
            <button type="submit" class="px-4 py-2 bg-primary-800 text-white rounded-lg text-sm font-semibold hover:bg-primary-700 transition">تصفية</button>
        </form>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-5">
        <div class="bg-gray-50 rounded-lg p-3 text-center">
            <p class="text-2xl font-bold text-gray-800">{{ $totals['shifts_count'] }}</p>
            <p class="text-xs text-gray-500">عدد الورديات</p>
        </div>
        <div class="bg-green-50 rounded-lg p-3 text-center">
            <p class="text-2xl font-bold text-green-700">{{ number_format($totals['received'], 0) }}</p>
            <p class="text-xs text-gray-500">إجمالي المستلَم (ر.ي)</p>
        </div>
        <div class="bg-red-50 rounded-lg p-3 text-center">
            <p class="text-2xl font-bold text-red-700">{{ number_format($totals['withdrawals'], 0) }}</p>
            <p class="text-xs text-gray-500">إجمالي السحبيات (ر.ي)</p>
        </div>
        <div class="rounded-lg p-3 text-center {{ $totals['total_shortfall'] > 0 ? 'bg-amber-50' : 'bg-gray-50' }}">
            <p class="text-2xl font-bold {{ $totals['total_shortfall'] > 0 ? 'text-amber-700' : 'text-gray-400' }}">{{ number_format($totals['total_shortfall'], 0) }}</p>
            <p class="text-xs text-gray-500">إجمالي العجز (ر.ي)</p>
        </div>
    </div>
</div>

{{-- Shifts --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-5">
    <div class="px-5 py-4 border-b border-gray-100">
        <h3 class="font-bold text-gray-800">الورديات ({{ $shifts->count() }})</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50"><tr>
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">التاريخ</th>
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">المستلمات</th>
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">السحبيات</th>
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">الصافي</th>
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">الفعلي</th>
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">الفرق</th>
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">الحالة</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($shifts as $s)
                <tr>
                    <td class="px-4 py-2 text-gray-600">{{ $s->shift_date->format('d/m/Y') }}</td>
                    <td class="px-4 py-2 font-medium text-green-700">{{ number_format($s->total_received_yer, 0) }}</td>
                    <td class="px-4 py-2 font-medium text-red-600">{{ number_format($s->total_withdrawals_yer, 0) }}</td>
                    <td class="px-4 py-2 font-bold">{{ number_format($s->net_balance_yer, 0) }}</td>
                    <td class="px-4 py-2">{{ $s->actual_amount !== null ? number_format($s->actual_amount, 0) : '—' }}</td>
                    <td class="px-4 py-2">
                        @if($s->shortfall !== null)
                        <span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ $s->shortfall == 0 ? 'bg-green-100 text-green-700' : ($s->shortfall < 0 ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700') }}">
                            {{ $s->shortfall == 0 ? 'مطابق' : ($s->shortfall < 0 ? '▼ '.number_format(abs($s->shortfall), 0) : '▲ '.number_format($s->shortfall, 0)) }}
                        </span>
                        @else <span class="text-gray-300 text-xs">—</span> @endif
                    </td>
                    <td class="px-4 py-2">
                        <span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ $s->is_closed ? 'bg-gray-100 text-gray-600' : 'bg-blue-100 text-blue-700' }}">
                            {{ $s->is_closed ? 'مقفلة' : 'مفتوحة' }}
                        </span>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="px-4 py-8 text-center text-gray-400">لا توجد ورديات في هذه الفترة</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Payments received --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-5">
    <div class="px-5 py-4 border-b border-gray-100">
        <h3 class="font-bold text-gray-800">المستلمات ({{ $payments->count() }})</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50"><tr>
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">التاريخ</th>
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">الغرفة / النزيل</th>
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">طريقة الدفع</th>
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">المبلغ</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($payments as $p)
                <tr>
                    <td class="px-4 py-2 text-gray-600 whitespace-nowrap">{{ $p->payment_date?->format('d/m/Y H:i') ?? '—' }}</td>
                    <td class="px-4 py-2 text-gray-700">
                        <span class="font-medium">{{ $p->reservation?->display_room_number ?? '—' }}</span>
                        <span class="text-gray-400 mr-1">{{ $p->reservation?->guest?->full_name ?? '' }}</span>
                    </td>
                    <td class="px-4 py-2">
                        <span class="text-xs px-2 py-0.5 rounded-full bg-blue-50 text-blue-700">
                            {{ match($p->method) { 'cash'=>'نقداً', 'bank_transfer'=>'تحويل بنكي', 'pos'=>'POS', default=>$p->method } }}
                        </span>
                    </td>
                    <td class="px-4 py-2 font-bold text-green-700">{{ number_format($p->amount, 0) }} {{ $p->currency }}</td>
                </tr>
                @empty
                <tr><td colspan="4" class="px-4 py-8 text-center text-gray-400">لا توجد مستلمات في هذه الفترة</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Withdrawals --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-100">
    <div class="px-5 py-4 border-b border-gray-100">
        <h3 class="font-bold text-gray-800">السحبيات ({{ $withdrawals->count() }})</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50"><tr>
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">التاريخ</th>
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">النوع</th>
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">استلمه</th>
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">المبلغ</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($withdrawals as $w)
                <tr>
                    <td class="px-4 py-2 text-gray-600 whitespace-nowrap">{{ $w->withdrawal_date?->format('d/m/Y H:i') ?? '—' }}</td>
                    <td class="px-4 py-2 text-gray-500 text-xs">{{ $w->type_label }}</td>
                    <td class="px-4 py-2 text-gray-600">{{ $w->withdrawn_by_name }}</td>
                    <td class="px-4 py-2 font-bold text-red-600">{{ number_format($w->amount, 0) }} {{ $w->currency }}</td>
                </tr>
                @empty
                <tr><td colspan="4" class="px-4 py-8 text-center text-gray-400">لا توجد سحبيات في هذه الفترة</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

</div>
@endsection
