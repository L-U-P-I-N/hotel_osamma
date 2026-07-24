@extends('layouts.app')
@section('title', 'تقرير عم علي')
@section('page-title', 'تقرير عم علي')

@section('content')
<div class="space-y-6" dir="rtl">

    {{-- رأس التقرير + طباعة --}}
    <div class="flex items-center justify-between flex-wrap gap-3 no-print">
        <div>
            <h2 class="text-2xl font-black text-gray-800">تقرير عم علي</h2>
            <p class="text-gray-500 text-sm mt-1">الغرف المستأجَرة حالياً + دفعات يوم محدَّد ومن استلمها من الموظفين</p>
        </div>
        <button onclick="window.print()"
                class="flex items-center gap-2 px-5 py-2.5 bg-gray-800 text-white rounded-xl text-sm font-semibold hover:bg-gray-700 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            طباعة
        </button>
    </div>

    {{-- ═══════════ القسم (أ): الغرف المستأجَرة حالياً ═══════════ --}}
    <div>
        <h3 class="text-xl font-black text-gray-800 mb-3">الغرف المستأجَرة حالياً</h3>
        <div id="roomsReport" class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-x-auto">
            <table class="w-full text-right border-collapse">
                <thead>
                    <tr class="bg-gray-800 text-white text-lg">
                        <th class="border border-gray-300 px-4 py-3 font-bold">#</th>
                        <th class="border border-gray-300 px-4 py-3 font-bold">الغرفة</th>
                        <th class="border border-gray-300 px-4 py-3 font-bold">النزيل</th>
                        <th class="border border-gray-300 px-4 py-3 font-bold">الإقامة قبل التجديد</th>
                        <th class="border border-gray-300 px-4 py-3 font-bold">المبلغ الإجمالي</th>
                        <th class="border border-gray-300 px-4 py-3 font-bold">المدفوع</th>
                        <th class="border border-gray-300 px-4 py-3 font-bold">المتبقي</th>
                    </tr>
                </thead>
                <tbody class="text-lg text-gray-900">
                    @forelse($reservations as $i => $res)
                    @php
                        $remaining = (float) $res->total_amount - (float) $res->paid_amount;
                        // سعر الإقامة قبل آخر تجديد = مجموع الفترات عدا آخر فترة (إن وُجد تجديد)
                        $segs = $res->segments;
                        $hasRenewal = $segs->where('type', 'renewal')->count() > 0;
                        $prevAccommodation = $hasRenewal && $segs->count() > 1
                            ? (float) $segs->slice(0, $segs->count() - 1)->sum('amount')
                            : null;
                    @endphp
                    <tr class="{{ $i % 2 === 0 ? 'bg-white' : 'bg-gray-50' }}">
                        <td class="border border-gray-300 px-4 py-3 text-gray-500 font-semibold">{{ $i + 1 }}</td>
                        <td class="border border-gray-300 px-4 py-3 font-black text-primary-800">{{ $res->display_room_number ?? $res->room?->room_number ?? '—' }}</td>
                        <td class="border border-gray-300 px-4 py-3 font-bold">{{ $res->guest?->full_name ?? '—' }}</td>
                        <td class="border border-gray-300 px-4 py-3 font-semibold text-gray-600">
                            {{ $prevAccommodation !== null ? number_format($prevAccommodation, 0) . ' ' . $res->currency_symbol : '—' }}
                        </td>
                        <td class="border border-gray-300 px-4 py-3 font-bold">{{ number_format((float) $res->total_amount, 0) }} {{ $res->currency_symbol }}</td>
                        <td class="border border-gray-300 px-4 py-3 font-bold text-green-700">{{ number_format((float) $res->paid_amount, 0) }} {{ $res->currency_symbol }}</td>
                        <td class="border border-gray-300 px-4 py-3 font-black {{ $remaining > 0 ? 'text-red-700' : 'text-gray-500' }}">{{ number_format($remaining, 0) }} {{ $res->currency_symbol }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="border border-gray-300 px-4 py-10 text-center text-gray-400 text-lg">لا توجد غرف مستأجَرة حالياً</td>
                    </tr>
                    @endforelse
                </tbody>
                @if($reservations->count() > 0)
                <tfoot>
                    <tr class="bg-gray-100 text-lg font-black text-gray-900">
                        <td class="border border-gray-300 px-4 py-3" colspan="4">الإجمالي ({{ $reservations->count() }} غرفة)</td>
                        <td class="border border-gray-300 px-4 py-3">{{ number_format($totals['total'], 0) }} ر.ي</td>
                        <td class="border border-gray-300 px-4 py-3 text-green-800">{{ number_format($totals['paid'], 0) }} ر.ي</td>
                        <td class="border border-gray-300 px-4 py-3 text-red-800">{{ number_format($totals['remaining'], 0) }} ر.ي</td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>

    {{-- ═══════════ القسم (ب): دفعات يوم محدَّد ═══════════ --}}
    <div>
        <div class="flex items-center justify-between flex-wrap gap-3 mb-3">
            <h3 class="text-xl font-black text-gray-800">دفعات اليوم — {{ \Carbon\Carbon::parse($date)->format('Y/m/d') }}</h3>
            <form method="GET" action="{{ route('reports.amAli') }}" class="flex items-center gap-2 no-print">
                <label class="text-sm font-semibold text-gray-600">اختر اليوم:</label>
                <input type="date" name="date" value="{{ $date }}" max="{{ now()->toDateString() }}"
                       class="border border-gray-300 rounded-lg px-3 py-2 text-base focus:ring-2 focus:ring-primary-500 outline-none">
                <button type="submit" class="px-4 py-2 bg-primary-800 text-white rounded-lg text-sm font-semibold hover:bg-primary-700 transition">عرض</button>
            </form>
        </div>

        <div id="paymentsReport" class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-x-auto">
            <table class="w-full text-right border-collapse">
                <thead>
                    <tr class="bg-gray-800 text-white text-lg">
                        <th class="border border-gray-300 px-4 py-3 font-bold">#</th>
                        <th class="border border-gray-300 px-4 py-3 font-bold">الوقت</th>
                        <th class="border border-gray-300 px-4 py-3 font-bold">الغرفة</th>
                        <th class="border border-gray-300 px-4 py-3 font-bold">النزيل</th>
                        <th class="border border-gray-300 px-4 py-3 font-bold">نوع الدفعة</th>
                        <th class="border border-gray-300 px-4 py-3 font-bold">المبلغ المستلَم</th>
                        <th class="border border-gray-300 px-4 py-3 font-bold">الموظف المستلِم</th>
                    </tr>
                </thead>
                <tbody class="text-lg text-gray-900">
                    @forelse($dayPayments as $i => $p)
                    @php
                        $typeLabel = ['reservation' => 'حجز', 'renewal' => 'تجديد', 'compensation' => 'تعويض', 'extra_service' => 'خدمة إضافية'][$p->type] ?? $p->type;
                    @endphp
                    <tr class="{{ $i % 2 === 0 ? 'bg-white' : 'bg-gray-50' }}">
                        <td class="border border-gray-300 px-4 py-3 text-gray-500 font-semibold">{{ $i + 1 }}</td>
                        <td class="border border-gray-300 px-4 py-3 text-gray-600 font-semibold whitespace-nowrap">{{ $p->payment_date?->format('H:i') ?? '—' }}</td>
                        <td class="border border-gray-300 px-4 py-3 font-black text-primary-800">{{ $p->reservation?->display_room_number ?? $p->reservation?->room?->room_number ?? '—' }}</td>
                        <td class="border border-gray-300 px-4 py-3 font-bold">{{ $p->reservation?->guest?->full_name ?? '—' }}</td>
                        <td class="border border-gray-300 px-4 py-3 font-semibold text-gray-600">{{ $typeLabel }}</td>
                        <td class="border border-gray-300 px-4 py-3 font-black text-green-700">{{ number_format((float) $p->amount, 0) }} {{ $p->currency }}</td>
                        <td class="border border-gray-300 px-4 py-3 font-bold">{{ $p->receivedBy?->name ?? 'غير معروف' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="border border-gray-300 px-4 py-10 text-center text-gray-400 text-lg">لا توجد دفعات في هذا اليوم</td>
                    </tr>
                    @endforelse
                </tbody>
                @if($dayPayments->count() > 0)
                <tfoot>
                    <tr class="bg-gray-100 text-lg font-black text-gray-900">
                        <td class="border border-gray-300 px-4 py-3" colspan="5">إجمالي دفعات اليوم ({{ $dayPayments->count() }} دفعة)</td>
                        <td class="border border-gray-300 px-4 py-3 text-green-800" colspan="2">
                            @foreach($dayTotals as $cur => $amt)
                                {{ number_format($amt, 0) }} {{ $cur }}@if(!$loop->last) — @endif
                            @endforeach
                        </td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>

        {{-- ملخص: كم استلم كل موظف في هذا اليوم --}}
        @if(!empty($byEmployee))
        <div class="mt-4 bg-white rounded-xl shadow-sm border border-gray-200 overflow-x-auto">
            <div class="px-5 py-3 border-b border-gray-100 bg-gray-50">
                <h4 class="font-black text-gray-800 text-lg">إجمالي ما استلمه كل موظف في اليوم</h4>
            </div>
            <table class="w-full text-right border-collapse">
                <thead>
                    <tr class="bg-gray-700 text-white text-lg">
                        <th class="border border-gray-300 px-4 py-3 font-bold">الموظف</th>
                        <th class="border border-gray-300 px-4 py-3 font-bold">إجمالي المستلَم</th>
                    </tr>
                </thead>
                <tbody class="text-lg text-gray-900">
                    @foreach($byEmployee as $name => $currencies)
                    <tr>
                        <td class="border border-gray-300 px-4 py-3 font-bold">{{ $name }}</td>
                        <td class="border border-gray-300 px-4 py-3 font-black text-green-700">
                            @foreach($currencies as $cur => $amt)
                                {{ number_format($amt, 0) }} {{ $cur }}@if(!$loop->last) — @endif
                            @endforeach
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>

@push('styles')
<style>
    @media print {
        .no-print { display: none !important; }
        #roomsReport, #paymentsReport { border: none; box-shadow: none; }
        #roomsReport table, #paymentsReport table { font-size: 14pt; }
        #roomsReport thead tr, #paymentsReport thead tr { background: #1f2937 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        #roomsReport th, #roomsReport td, #paymentsReport th, #paymentsReport td { border: 1px solid #333 !important; }
    }
</style>
@endpush
@endsection
