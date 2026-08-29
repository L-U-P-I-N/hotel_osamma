@extends('layouts.app')
@section('title', 'تقرير الصندوق العام')
@section('page-title', 'تقرير الصندوق العام')

@section('content')
<div class="space-y-5" dir="rtl">

    <div class="flex items-center justify-between flex-wrap gap-3">
        <div>
            <h2 class="text-2xl font-black text-gray-800">الصندوق العام</h2>
            <p class="text-gray-500 text-sm mt-1">كل حركات حساب الصندوق العام (1120) — وارد وصادر، والرصيد الجاري</p>
        </div>
        <a href="{{ route('reports.generalSafe.pdf', ['from' => $from, 'to' => $to]) }}" target="_blank"
           class="flex items-center gap-2 px-5 py-2.5 bg-red-600 text-white rounded-xl text-sm font-semibold hover:bg-red-700 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
            تصدير PDF
        </a>
    </div>

    {{-- رصيد الصندوق الحالي (لحظي، بصرف النظر عن فلتر التاريخ) --}}
    <div class="rounded-xl p-5 border" style="background:#fffbeb; border-color:#fde68a;">
        <div class="text-xs text-amber-600">رصيد الصندوق العام الحالي</div>
        <div class="text-3xl font-bold text-amber-800 mt-1">{{ number_format($currentBalance, 0) }} <span class="text-lg font-normal">ر.ي</span></div>
    </div>

    {{-- فلتر الفترة --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
        <form method="GET" class="flex flex-wrap gap-3 items-end">
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
            <button type="submit" class="px-4 py-2 text-white rounded-lg text-sm font-semibold" style="background:#0F4C75;">عرض</button>
        </form>
    </div>

    {{-- ملخص الفترة --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
            <div class="text-xs text-gray-500">الرصيد الافتتاحي</div>
            <div class="text-xl font-bold text-gray-700 mt-1">{{ number_format($openingBalance, 0) }} ر.ي</div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
            <div class="text-xs text-gray-500">إجمالي الوارد خلال الفترة</div>
            <div class="text-xl font-bold text-green-700 mt-1">{{ number_format($movements->sum('in'), 0) }} ر.ي</div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
            <div class="text-xs text-gray-500">إجمالي الصادر خلال الفترة</div>
            <div class="text-xl font-bold text-red-700 mt-1">{{ number_format($movements->sum('out'), 0) }} ر.ي</div>
        </div>
    </div>

    {{-- جدول الحركات --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-x-auto">
        <table class="w-full text-right border-collapse">
            <thead>
                <tr class="bg-gray-800 text-white text-sm">
                    <th class="border border-gray-300 px-3 py-3 font-bold">التاريخ</th>
                    <th class="border border-gray-300 px-3 py-3 font-bold">البيان</th>
                    <th class="border border-gray-300 px-3 py-3 font-bold">وارد</th>
                    <th class="border border-gray-300 px-3 py-3 font-bold">صادر</th>
                    <th class="border border-gray-300 px-3 py-3 font-bold">الرصيد الجاري</th>
                </tr>
            </thead>
            <tbody class="text-sm text-gray-900">
                <tr class="bg-gray-50">
                    <td class="border border-gray-300 px-3 py-2.5 text-gray-400" colspan="4">رصيد ما قبل {{ \Carbon\Carbon::parse($from)->format('Y/m/d') }}</td>
                    <td class="border border-gray-300 px-3 py-2.5 font-bold text-gray-600">{{ number_format($openingBalance, 0) }}</td>
                </tr>
                @forelse($movements as $m)
                <tr>
                    <td class="border border-gray-300 px-3 py-2.5 whitespace-nowrap">{{ $m['date']?->format('d/m/Y') ?? '—' }}</td>
                    <td class="border border-gray-300 px-3 py-2.5">{{ $m['description'] ?? '—' }}</td>
                    <td class="border border-gray-300 px-3 py-2.5 font-semibold text-green-700">{{ $m['in'] > 0 ? number_format($m['in'], 0) : '—' }}</td>
                    <td class="border border-gray-300 px-3 py-2.5 font-semibold text-red-700">{{ $m['out'] > 0 ? number_format($m['out'], 0) : '—' }}</td>
                    <td class="border border-gray-300 px-3 py-2.5 font-bold">{{ number_format($m['balance'], 0) }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="border border-gray-300 px-4 py-10 text-center text-gray-400 text-lg">لا توجد حركات خلال هذه الفترة</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
