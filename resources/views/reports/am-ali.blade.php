@extends('layouts.app')
@section('title', 'تقرير عم علي')
@section('page-title', 'تقرير عم علي')

@section('content')
<div class="space-y-6" dir="rtl">

    {{-- رأس التقرير + طباعة --}}
    <div class="flex items-center justify-between flex-wrap gap-3 no-print">
        <div>
            <h2 class="text-2xl font-black text-gray-800">تقرير عم علي</h2>
            <p class="text-gray-500 text-sm mt-1">مقارنة كل غرفة بين نزيل اليوم ونزيل الأمس — الدفعات ومن استلمها والمديونية</p>
        </div>
        <div class="flex items-center gap-2">
            <form method="GET" action="{{ route('reports.amAli') }}" class="flex items-center gap-2">
                <label class="text-sm font-semibold text-gray-600">اليوم:</label>
                <input type="date" name="date" value="{{ $date }}" max="{{ now()->toDateString() }}"
                       class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 outline-none">
                <button type="submit" class="px-4 py-2 bg-primary-800 text-white rounded-lg text-sm font-semibold hover:bg-primary-700 transition">عرض</button>
            </form>
            <a href="{{ route('reports.amAli.pdf', ['date' => $date]) }}" target="_blank"
               class="flex items-center gap-2 px-5 py-2.5 bg-red-600 text-white rounded-xl text-sm font-semibold hover:bg-red-700 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                تصدير PDF
            </a>
            <button onclick="window.print()"
                    class="flex items-center gap-2 px-5 py-2.5 bg-gray-800 text-white rounded-xl text-sm font-semibold hover:bg-gray-700 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                طباعة
            </button>
        </div>
    </div>

    <div class="no-print text-sm text-gray-500">
        اليوم: <b class="text-gray-700">{{ \Carbon\Carbon::parse($date)->format('Y/m/d') }}</b>
        — الأمس: <b class="text-gray-700">{{ \Carbon\Carbon::parse($yesterday)->format('Y/m/d') }}</b>
    </div>

    <div id="amAliReport" class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-x-auto">
        <table class="w-full text-right border-collapse">
            <thead>
                <tr class="bg-gray-800 text-white text-sm">
                    <th class="border border-gray-300 px-3 py-3 font-bold">الغرفة</th>
                    <th class="border border-gray-300 px-3 py-3 font-bold">حالة الغرفة</th>
                    <th class="border border-gray-300 px-3 py-3 font-bold">نزيل اليوم / وقت الدخول</th>
                    <th class="border border-gray-300 px-3 py-3 font-bold">كم دفع</th>
                    <th class="border border-gray-300 px-3 py-3 font-bold">من استلم</th>
                    <th class="border border-gray-300 px-3 py-3 font-bold">مديونية اليوم</th>
                    <th class="border border-gray-300 px-3 py-3 font-bold">نزيل الأمس / سدد عند من</th>
                    <th class="border border-gray-300 px-3 py-3 font-bold">مديونية الأمس</th>
                </tr>
            </thead>
            <tbody class="text-sm text-gray-900">
                @forelse($rows as $i => $row)
                <tr class="{{ $i % 2 === 0 ? 'bg-white' : 'bg-gray-50' }}">
                    <td class="border border-gray-300 px-3 py-3 font-black text-primary-800">{{ $row['room']->room_number }}</td>
                    <td class="border border-gray-300 px-3 py-3">
                        <span class="px-2 py-1 rounded-full text-xs font-semibold {{ $row['status'] === 'مشغولة' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                            {{ $row['status'] }}
                        </span>
                    </td>
                    @if($row['today'])
                    <td class="border border-gray-300 px-3 py-3">
                        <div class="font-bold">{{ $row['today']['guest_name'] }}</div>
                        <div class="text-xs text-gray-500">{{ $row['today']['check_in_date']?->format('d/m/Y') }} — {{ $row['today']['check_in_time'] ?? '—' }}</div>
                    </td>
                    <td class="border border-gray-300 px-3 py-3 font-bold text-green-700">{{ number_format($row['today']['paid'], 0) }} {{ $row['today']['currency'] }}</td>
                    <td class="border border-gray-300 px-3 py-3 font-semibold">{{ $row['today']['received_by'] ?? '—' }}</td>
                    <td class="border border-gray-300 px-3 py-3 font-black {{ $row['today']['remaining'] > 0 ? 'text-red-700' : 'text-gray-400' }}">{{ number_format($row['today']['remaining'], 0) }} {{ $row['today']['currency'] }}</td>
                    @else
                    <td class="border border-gray-300 px-3 py-3 text-gray-300 text-center" colspan="4">—</td>
                    @endif
                    @if($row['yday'])
                    <td class="border border-gray-300 px-3 py-3">
                        <div class="font-bold">{{ $row['yday']['guest_name'] }}</div>
                        <div class="text-xs text-gray-500">سدد عند: {{ $row['yday']['received_by'] ?? '—' }}</div>
                    </td>
                    <td class="border border-gray-300 px-3 py-3 font-black {{ $row['yday']['remaining'] > 0 ? 'text-red-700' : 'text-gray-400' }}">{{ number_format($row['yday']['remaining'], 0) }} {{ $row['yday']['currency'] }}</td>
                    @else
                    <td class="border border-gray-300 px-3 py-3 text-gray-300 text-center" colspan="2">—</td>
                    @endif
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="border border-gray-300 px-4 py-10 text-center text-gray-400 text-lg">لا توجد غرف</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@push('styles')
<style>
    @media print {
        @page { size: landscape; }
        .no-print { display: none !important; }
        #amAliReport { border: none; box-shadow: none; }
        #amAliReport table { font-size: 11pt; }
        #amAliReport thead tr { background: #1f2937 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        #amAliReport th, #amAliReport td { border: 1px solid #333 !important; }
    }
</style>
@endpush
@endsection
