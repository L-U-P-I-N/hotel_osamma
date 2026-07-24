@extends('layouts.app')
@section('title', 'تقرير عم علي')
@section('page-title', 'تقرير عم علي')

@section('content')
<div class="space-y-5" dir="rtl">

    {{-- رأس التقرير + طباعة --}}
    <div class="flex items-center justify-between flex-wrap gap-3 no-print">
        <div>
            <h2 class="text-2xl font-black text-gray-800">تقرير عم علي</h2>
            <p class="text-gray-500 text-sm mt-1">الغرف المستأجَرة حالياً — الإجمالي والمدفوع والمتبقي ومن استلم الدفعة</p>
        </div>
        <button onclick="window.print()"
                class="flex items-center gap-2 px-5 py-2.5 bg-gray-800 text-white rounded-xl text-sm font-semibold hover:bg-gray-700 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            طباعة
        </button>
    </div>

    {{-- عنوان يظهر عند الطباعة فقط --}}
    <div class="hidden print-only mb-4 text-center">
        <h1 class="text-3xl font-black text-gray-900">تقرير عم علي — الغرف المستأجَرة حالياً</h1>
        <p class="text-gray-600 mt-1 text-lg">{{ now()->format('Y/m/d — H:i') }}</p>
    </div>

    {{-- الجدول --}}
    <div id="amAliReport" class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-x-auto">
        <table class="w-full text-right border-collapse">
            <thead>
                <tr class="bg-gray-800 text-white text-lg">
                    <th class="border border-gray-300 px-4 py-3 font-bold">#</th>
                    <th class="border border-gray-300 px-4 py-3 font-bold">الغرفة</th>
                    <th class="border border-gray-300 px-4 py-3 font-bold">النزيل</th>
                    <th class="border border-gray-300 px-4 py-3 font-bold">المبلغ الإجمالي</th>
                    <th class="border border-gray-300 px-4 py-3 font-bold">المدفوع</th>
                    <th class="border border-gray-300 px-4 py-3 font-bold">المتبقي</th>
                    <th class="border border-gray-300 px-4 py-3 font-bold">مَن استلم الدفعة</th>
                </tr>
            </thead>
            <tbody class="text-lg text-gray-900">
                @forelse($reservations as $i => $res)
                @php
                    $remaining = (float) $res->total_amount - (float) $res->paid_amount;
                    $receivers = $res->payments
                        ->map(fn($p) => $p->receivedBy?->name)
                        ->filter()
                        ->unique()
                        ->implode('، ');
                @endphp
                <tr class="{{ $i % 2 === 0 ? 'bg-white' : 'bg-gray-50' }}">
                    <td class="border border-gray-300 px-4 py-3 text-gray-500 font-semibold">{{ $i + 1 }}</td>
                    <td class="border border-gray-300 px-4 py-3 font-black text-primary-800">{{ $res->display_room_number ?? $res->room?->room_number ?? '—' }}</td>
                    <td class="border border-gray-300 px-4 py-3 font-bold">{{ $res->guest?->full_name ?? '—' }}</td>
                    <td class="border border-gray-300 px-4 py-3 font-bold">{{ number_format((float) $res->total_amount, 0) }} {{ $res->currency_symbol }}</td>
                    <td class="border border-gray-300 px-4 py-3 font-bold text-green-700">{{ number_format((float) $res->paid_amount, 0) }} {{ $res->currency_symbol }}</td>
                    <td class="border border-gray-300 px-4 py-3 font-black {{ $remaining > 0 ? 'text-red-700' : 'text-gray-500' }}">{{ number_format($remaining, 0) }} {{ $res->currency_symbol }}</td>
                    <td class="border border-gray-300 px-4 py-3 font-semibold">{{ $receivers ?: '—' }}</td>
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
                    <td class="border border-gray-300 px-4 py-3" colspan="3">الإجمالي ({{ $reservations->count() }} غرفة)</td>
                    <td class="border border-gray-300 px-4 py-3">{{ number_format($totals['total'], 0) }} ر.ي</td>
                    <td class="border border-gray-300 px-4 py-3 text-green-800">{{ number_format($totals['paid'], 0) }} ر.ي</td>
                    <td class="border border-gray-300 px-4 py-3 text-red-800">{{ number_format($totals['remaining'], 0) }} ر.ي</td>
                    <td class="border border-gray-300 px-4 py-3"></td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
</div>

@push('styles')
<style>
    .print-only { display: none; }
    @media print {
        .no-print { display: none !important; }
        .print-only { display: block !important; }
        /* إظهار الجدول فقط بوضوح عند الطباعة */
        #amAliReport { border: none; box-shadow: none; }
        #amAliReport table { font-size: 15pt; }
        #amAliReport thead tr { background: #1f2937 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        #amAliReport th, #amAliReport td { border: 1px solid #333 !important; }
    }
</style>
@endpush
@endsection
