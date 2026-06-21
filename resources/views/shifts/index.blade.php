@extends('layouts.app')
@section('title', 'الوردية')
@section('page-title', 'الوردية')

@section('content')
<div x-data="{ withdrawalModal: false, closeModal: false }">

@if(session('success'))
<div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">{{ session('success') }}</div>
@endif
@if($errors->any())
<div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
    @foreach($errors->all() as $e)
    <p class="text-sm text-red-700">{{ $e }}</p>
    @endforeach
</div>
@endif

@if($activeShift)
{{-- ===== وردية مفتوحة ===== --}}

{{-- رأس الوردية --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-5 flex items-center justify-between flex-wrap gap-3">
    <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center">
            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <div>
            <p class="font-bold text-gray-800">وردية مفتوحة</p>
            <p class="text-xs text-gray-400">
                بدأت {{ $activeShift->started_at->format('H:i') }}
                — {{ $activeShift->shift_date->format('d/m/Y') }}
                @if(auth()->user()->isAdmin()) · {{ $activeShift->user->name }} @endif
            </p>
        </div>
    </div>
    <div class="flex gap-2 flex-wrap">
        @can('withdrawal.create')
        <button @click="withdrawalModal=true"
                class="flex items-center gap-2 px-4 py-2 border border-red-300 text-red-600 rounded-lg text-sm hover:bg-red-50 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/></svg>
            تسجيل سحب
        </button>
        @endcan
        <a href="{{ route('shifts.pdf', $activeShift) }}" target="_blank"
           class="flex items-center gap-2 px-4 py-2 border border-blue-300 text-blue-600 rounded-lg text-sm hover:bg-blue-50 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
            طباعة
        </a>
        <button @click="closeModal=true"
                class="flex items-center gap-2 px-4 py-2 bg-gray-800 text-white rounded-lg text-sm hover:bg-gray-700 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            إقفال الوردية
        </button>
    </div>
</div>

{{-- إجماليات العملات --}}
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-5">
    @foreach(['yer'=>['label'=>'ريال يمني','symbol'=>'ر.ي'], 'sar'=>['label'=>'ريال سعودي','symbol'=>'ر.س'], 'usd'=>['label'=>'دولار','symbol'=>'$']] as $cur => $info)
    @php
        $recv = $activeShift->{'total_received_'.$cur};
        $wdr  = $activeShift->{'total_withdrawals_'.$cur};
        $net  = $recv - $wdr;
    @endphp
    @if($recv > 0 || $wdr > 0 || $cur === 'yer')
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
        <p class="text-xs text-gray-500 mb-3 font-medium">{{ $info['label'] }}</p>
        <div class="space-y-2 text-sm">
            <div class="flex justify-between">
                <span class="text-gray-500">مستلمات</span>
                <span class="font-semibold text-green-700">{{ number_format($recv, 0) }} {{ $info['symbol'] }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-500">سحبيات</span>
                <span class="font-semibold text-red-600">{{ number_format($wdr, 0) }} {{ $info['symbol'] }}</span>
            </div>
            <div class="flex justify-between border-t border-gray-100 pt-2 mt-2">
                <span class="text-gray-700 font-medium">الصافي</span>
                <span class="font-bold {{ $net >= 0 ? 'text-primary-800' : 'text-red-700' }}">{{ number_format($net, 0) }} {{ $info['symbol'] }}</span>
            </div>
        </div>
    </div>
    @endif
    @endforeach
</div>

{{-- المستلمات + السحبيات --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

<div class="bg-white rounded-xl shadow-sm border border-gray-100">
    <div class="px-5 py-4 border-b border-gray-100">
        <h3 class="font-semibold text-gray-700">المستلمات ({{ $activeShift->payments->count() }})</h3>
    </div>
    @if($activeShift->payments->count() > 0)
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50"><tr>
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">الوقت</th>
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">الغرفة / النزيل</th>
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">المبلغ</th>
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">الطريقة</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($activeShift->payments as $p)
                <tr>
                    <td class="px-4 py-2 text-gray-400 text-xs">{{ $p->payment_date->format('H:i') }}</td>
                    <td class="px-4 py-2 text-gray-700 text-xs">
                        <span class="font-medium">{{ $p->reservation?->room?->room_number ?? '—' }}</span>
                        <span class="text-gray-400 mr-1">{{ $p->reservation?->guest?->full_name ?? '' }}</span>
                    </td>
                    <td class="px-4 py-2 font-semibold text-green-700 whitespace-nowrap">{{ number_format($p->amount, 0) }} {{ $p->currency }}</td>
                    <td class="px-4 py-2 text-gray-500 text-xs">{{ match($p->method) { 'cash'=>'نقدي','pos'=>'POS','bank_transfer'=>'تحويل', default=>$p->method } }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
    <div class="p-8 text-center text-gray-400 text-sm">لا توجد مستلمات بعد</div>
    @endif
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100">
    <div class="px-5 py-4 border-b border-gray-100">
        <h3 class="font-semibold text-gray-700">السحبيات ({{ $activeShift->withdrawals->count() }})</h3>
    </div>
    @if($activeShift->withdrawals->count() > 0)
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50"><tr>
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">المستلم</th>
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">المبلغ</th>
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">النوع</th>
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">البيان</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($activeShift->withdrawals as $w)
                <tr class="{{ $w->isExchange() ? 'bg-yellow-50' : '' }}">
                    <td class="px-4 py-2 text-gray-700 text-xs">{{ $w->withdrawn_by_name }}</td>
                    <td class="px-4 py-2 font-semibold text-red-600 whitespace-nowrap">
                        {{ number_format($w->amount, 0) }} {{ $w->currency }}
                        @if($w->isExchange() && $w->exchange_to_amount)
                        <span class="text-xs text-yellow-700 mr-1">← {{ number_format($w->exchange_to_amount, 0) }} {{ $w->exchange_to_currency }}</span>
                        @endif
                    </td>
                    <td class="px-4 py-2">
                        @if($w->isExchange())
                        <span class="px-1.5 py-0.5 bg-yellow-100 text-yellow-700 text-xs rounded">صرف عملة</span>
                        @else
                        <span class="text-gray-400 text-xs">مصروف</span>
                        @endif
                    </td>
                    <td class="px-4 py-2 text-gray-400 text-xs">{{ $w->notes ?? '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
    <div class="p-8 text-center text-gray-400 text-sm">لا توجد سحبيات</div>
    @endif
</div>
</div>

@else
{{-- ===== لا توجد وردية ===== --}}
<div class="max-w-md mx-auto">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-10 text-center">
        <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <h3 class="text-lg font-bold text-gray-700 mb-2">لا توجد وردية مفتوحة</h3>
        <p class="text-gray-400 text-sm">الوردية تُفتح تلقائياً عند تسجيل الدخول</p>
    </div>
</div>
@endif

{{-- ===== ورديات الأدمن على جميع الموظفين ===== --}}
@if(auth()->user()->isAdmin() && $allActive->where('user_id', '!=', auth()->id())->count() > 0)
<div class="mt-5 bg-white rounded-xl shadow-sm border border-blue-100">
    <div class="px-5 py-3 border-b border-blue-100 bg-blue-50 rounded-t-xl">
        <h3 class="font-semibold text-blue-800 text-sm">ورديات مفتوحة لموظفين آخرين</h3>
    </div>
    <div class="divide-y divide-gray-50">
        @foreach($allActive->where('user_id', '!=', auth()->id()) as $s)
        <div class="px-5 py-3 flex items-center justify-between text-sm">
            <div class="flex items-center gap-3">
                <span class="font-medium text-gray-700">{{ $s->user->name }}</span>
                <span class="text-xs text-gray-400">منذ {{ $s->started_at->format('H:i') }}</span>
            </div>
            <div class="flex items-center gap-3 text-xs">
                <span class="text-green-700 font-medium">{{ number_format($s->total_received_yer, 0) }} ر.ي</span>
                <a href="{{ route('shifts.pdf', $s) }}" target="_blank"
                   class="flex items-center gap-1 px-2 py-1 border border-gray-200 text-gray-500 rounded hover:bg-gray-50 transition">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    PDF
                </a>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif

{{-- ورديات سابقة --}}
@if($recentShifts->where('is_closed', true)->count() > 0)
<div class="mt-5 bg-white rounded-xl shadow-sm border border-gray-100">
    <div class="px-5 py-3 border-b border-gray-100">
        <h3 class="font-semibold text-gray-700 text-sm">الورديات السابقة</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50"><tr>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">التاريخ</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">البداية</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">النهاية</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">المستلمات (ر.ي)</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">السحبيات (ر.ي)</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الصافي (ر.ي)</th>
                <th class="px-4 py-3"></th>
            </tr></thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($recentShifts->where('is_closed', true) as $s)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 text-gray-600">{{ $s->shift_date->format('d/m/Y') }}</td>
                    <td class="px-4 py-3 text-gray-500 text-xs">{{ $s->started_at->format('H:i') }}</td>
                    <td class="px-4 py-3 text-gray-500 text-xs">{{ $s->ended_at?->format('H:i') ?? '—' }}</td>
                    <td class="px-4 py-3 font-medium text-green-700">{{ number_format($s->total_received_yer, 0) }}</td>
                    <td class="px-4 py-3 font-medium text-red-600">{{ number_format($s->total_withdrawals_yer, 0) }}</td>
                    <td class="px-4 py-3 font-bold {{ ($s->total_received_yer - $s->total_withdrawals_yer) >= 0 ? 'text-primary-700' : 'text-red-700' }}">
                        {{ number_format($s->total_received_yer - $s->total_withdrawals_yer, 0) }}
                    </td>
                    <td class="px-4 py-3">
                        <a href="{{ route('shifts.pdf', $s) }}" target="_blank"
                           class="flex items-center gap-1 px-2 py-1 border border-gray-200 text-gray-500 rounded text-xs hover:bg-gray-50 transition">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                            PDF
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- Modal: تسجيل سحب --}}
@if($activeShift)
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
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 outline-none">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">اسم المستلم *</label>
                <input type="text" name="withdrawn_by_name" required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 outline-none">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">السبب / البيان</label>
                <input type="text" name="notes"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 outline-none">
            </div>
            <button type="submit" class="w-full py-2.5 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm font-semibold transition">
                تسجيل السحب
            </button>
        </form>
    </div>
</div>
@endcan

{{-- Modal: إقفال الوردية --}}
<div x-show="closeModal" x-cloak class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4" @click.self="closeModal=false">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-bold text-gray-800">إقفال الوردية</h3>
            <button @click="closeModal=false" class="text-gray-400 hover:text-gray-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form method="POST" action="{{ route('shifts.close') }}" class="p-6 space-y-4">
            @csrf
            <div class="bg-gray-50 rounded-lg p-4 text-sm space-y-1">
                <div class="flex justify-between"><span class="text-gray-500">المستلمات:</span><span class="font-semibold text-green-700">{{ number_format($activeShift->total_received_yer, 0) }} ر.ي</span></div>
                <div class="flex justify-between"><span class="text-gray-500">السحبيات:</span><span class="font-semibold text-red-600">{{ number_format($activeShift->total_withdrawals_yer, 0) }} ر.ي</span></div>
                <div class="flex justify-between border-t border-gray-200 pt-1 mt-1"><span class="font-medium">الصافي:</span><span class="font-bold">{{ number_format($activeShift->total_received_yer - $activeShift->total_withdrawals_yer, 0) }} ر.ي</span></div>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">ملاحظات الإقفال</label>
                <textarea name="notes" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 outline-none resize-none"></textarea>
            </div>
            <button type="submit" class="w-full py-2.5 bg-gray-800 text-white rounded-lg text-sm font-semibold hover:bg-gray-700 transition"
                    onclick="return confirm('هل أنت متأكد من إقفال الوردية؟')">
                تأكيد الإقفال
            </button>
        </form>
    </div>
</div>
@endif

</div>
@endsection
