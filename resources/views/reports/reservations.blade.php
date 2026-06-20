@extends('layouts.app')
@section('title', 'تقرير الحجوزات')
@section('page-title', 'تقرير الحجوزات')

@section('content')
<div class="space-y-4">

{{-- Filter Bar --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
    <form method="GET" class="flex flex-wrap items-end gap-3">
        {{-- Preset Quick Buttons --}}
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1.5">الفترة</label>
            <div class="flex gap-1.5">
                <a href="{{ route('reports.reservations', ['preset' => 'today']) }}"
                   class="px-3 py-1.5 rounded-lg text-xs font-medium transition border
                          {{ $preset === 'today' ? 'text-white border-transparent' : 'text-gray-600 border-gray-200 hover:bg-gray-50' }}"
                   style="{{ $preset === 'today' ? 'background:#0F4C75' : '' }}">اليوم</a>
                <a href="{{ route('reports.reservations', ['preset' => 'week']) }}"
                   class="px-3 py-1.5 rounded-lg text-xs font-medium transition border
                          {{ $preset === 'week' ? 'text-white border-transparent' : 'text-gray-600 border-gray-200 hover:bg-gray-50' }}"
                   style="{{ $preset === 'week' ? 'background:#0F4C75' : '' }}">هذا الأسبوع</a>
                <a href="{{ route('reports.reservations', ['preset' => 'custom', 'from' => $from, 'to' => $to]) }}"
                   class="px-3 py-1.5 rounded-lg text-xs font-medium transition border
                          {{ $preset === 'custom' ? 'text-white border-transparent' : 'text-gray-600 border-gray-200 hover:bg-gray-50' }}"
                   style="{{ $preset === 'custom' ? 'background:#0F4C75' : '' }}">مخصص</a>
            </div>
        </div>

        {{-- Date Range --}}
        <div class="flex items-end gap-2">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">من</label>
                <input type="date" name="from" value="{{ $from }}"
                       class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 outline-none">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">إلى</label>
                <input type="date" name="to" value="{{ $to }}"
                       class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 outline-none">
            </div>
            <input type="hidden" name="preset" value="custom">
            <button type="submit" class="px-4 py-2 text-white rounded-lg text-sm transition" style="background:#0F4C75;">
                عرض
            </button>
        </div>

        {{-- Period Label --}}
        <div class="text-xs text-gray-400 self-end pb-1">
            {{ \Carbon\Carbon::parse($from)->format('d/m/Y') }} — {{ \Carbon\Carbon::parse($to)->format('d/m/Y') }}
        </div>
    </form>
    <div class="mr-auto flex gap-2 items-end">
        <a href="{{ route('reports.reservations.pdf', ['from' => $from, 'to' => $to]) }}"
           class="flex items-center gap-2 px-4 py-2 bg-red-600 text-white rounded-lg text-sm hover:bg-red-700 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
            PDF
        </a>
        <a href="{{ route('reports.reservations.excel', ['from' => $from, 'to' => $to]) }}"
           class="flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg text-sm hover:bg-green-700 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
            Excel
        </a>
    </div>
</div>

{{-- Summary Cards --}}
<div class="grid grid-cols-3 gap-3">
    <div class="bg-white rounded-xl border border-gray-100 p-4 text-center shadow-sm">
        <div class="text-2xl font-bold text-primary-700">{{ $total }}</div>
        <div class="text-xs text-gray-500 mt-0.5">إجمالي الحجوزات</div>
    </div>
    <div class="bg-white rounded-xl border border-green-100 p-4 text-center shadow-sm">
        <div class="text-2xl font-bold text-green-600">{{ $checkedIn }}</div>
        <div class="text-xs text-gray-500 mt-0.5">مقيم حالياً</div>
    </div>
    <div class="bg-white rounded-xl border border-blue-100 p-4 text-center shadow-sm">
        <div class="text-2xl font-bold text-blue-600">{{ $checkedOut }}</div>
        <div class="text-xs text-gray-500 mt-0.5">غادر</div>
    </div>
</div>

{{-- Table --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
        <h3 class="font-semibold text-gray-700 text-sm">
            قائمة الحجوزات
            <span class="text-gray-400 font-normal mr-2 text-xs">({{ $total }} حجز)</span>
        </h3>
    </div>

    @if($reservations->isEmpty())
    <div class="py-16 text-center text-gray-400">
        <svg class="w-12 h-12 mx-auto mb-3 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
        </svg>
        <p class="text-sm">لا توجد حجوزات في هذه الفترة</p>
    </div>
    @else
    <div class="overflow-x-auto">
        <table class="w-full text-xs">
            <thead class="bg-gray-50 border-b border-gray-100">
                <tr>
                    <th class="px-3 py-2.5 text-right font-medium text-gray-500 whitespace-nowrap">#</th>
                    <th class="px-3 py-2.5 text-right font-medium text-gray-500 whitespace-nowrap">الغرفة</th>
                    <th class="px-3 py-2.5 text-right font-medium text-gray-500 whitespace-nowrap">اسم النزيل</th>
                    <th class="px-3 py-2.5 text-right font-medium text-gray-500 whitespace-nowrap">الجنسية</th>
                    <th class="px-3 py-2.5 text-right font-medium text-gray-500 whitespace-nowrap">المهنة</th>
                    <th class="px-3 py-2.5 text-right font-medium text-gray-500 whitespace-nowrap">جهة القدوم</th>
                    <th class="px-3 py-2.5 text-right font-medium text-gray-500 whitespace-nowrap">تاريخ القدوم</th>
                    <th class="px-3 py-2.5 text-right font-medium text-gray-500 whitespace-nowrap">الوقت</th>
                    <th class="px-3 py-2.5 text-right font-medium text-gray-500 whitespace-nowrap">الغرض</th>
                    <th class="px-3 py-2.5 text-right font-medium text-gray-500 whitespace-nowrap">نوع الهوية</th>
                    <th class="px-3 py-2.5 text-right font-medium text-gray-500 whitespace-nowrap">رقم الهوية</th>
                    <th class="px-3 py-2.5 text-right font-medium text-gray-500 whitespace-nowrap">صادر من</th>
                    <th class="px-3 py-2.5 text-right font-medium text-gray-500 whitespace-nowrap">تاريخ الإصدار</th>
                    <th class="px-3 py-2.5 text-right font-medium text-gray-500 whitespace-nowrap">رقم الجوال</th>
                    <th class="px-3 py-2.5 text-right font-medium text-gray-500 whitespace-nowrap">حالة الدفع</th>
                    <th class="px-3 py-2.5 text-right font-medium text-gray-500 whitespace-nowrap">ملاحظات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($reservations as $r)
                @php $g = $r->guest; @endphp
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-3 py-2.5 text-gray-400">{{ $r->id }}</td>
                    <td class="px-3 py-2.5 font-bold text-primary-800 whitespace-nowrap">
                        {{ $r->room?->room_number ?? '—' }}
                    </td>
                    <td class="px-3 py-2.5 font-medium text-gray-800 whitespace-nowrap">
                        <a href="{{ route('reservations.show', $r) }}" class="hover:text-primary-600 hover:underline">
                            {{ $g?->full_name ?? '—' }}
                        </a>
                    </td>
                    <td class="px-3 py-2.5 text-gray-600 whitespace-nowrap">{{ $g?->nationality ?? '—' }}</td>
                    <td class="px-3 py-2.5 text-gray-600 whitespace-nowrap">{{ $g?->occupation ?? '—' }}</td>
                    <td class="px-3 py-2.5 text-gray-600 whitespace-nowrap">{{ $r->origin ?? '—' }}</td>
                    <td class="px-3 py-2.5 text-gray-600 whitespace-nowrap">{{ $r->check_in_date?->format('d/m/Y') ?? '—' }}</td>
                    <td class="px-3 py-2.5 text-gray-600 whitespace-nowrap">{{ $r->check_in_time ?? '—' }}</td>
                    <td class="px-3 py-2.5 text-gray-600 whitespace-nowrap">{{ $r->purpose ?? '—' }}</td>
                    <td class="px-3 py-2.5 text-gray-600 whitespace-nowrap">{{ $g?->getIdTypeLabel() ?? '—' }}</td>
                    <td class="px-3 py-2.5 text-gray-600 whitespace-nowrap font-mono">{{ $g?->id_number ?? '—' }}</td>
                    <td class="px-3 py-2.5 text-gray-600 whitespace-nowrap">{{ $g?->id_issuer ?? '—' }}</td>
                    <td class="px-3 py-2.5 text-gray-600 whitespace-nowrap">{{ $g?->id_issue_date?->format('d/m/Y') ?? '—' }}</td>
                    <td class="px-3 py-2.5 text-gray-600 whitespace-nowrap font-mono" dir="ltr">{{ $g?->phone ?? '—' }}</td>
                    <td class="px-3 py-2.5 whitespace-nowrap">
                        @php
                            $ps = $r->payment_status ?? 'pending';
                            $psBg = match($ps) { 'paid' => 'bg-green-100 text-green-700', 'partial' => 'bg-amber-100 text-amber-700', default => 'bg-red-100 text-red-700' };
                            $psLabel = match($ps) { 'paid' => 'مدفوع', 'partial' => 'جزئي', default => 'معلق' };
                        @endphp
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $psBg }}">{{ $psLabel }}</span>
                    </td>
                    <td class="px-3 py-2.5 max-w-[200px]">
                        @php $payNote = $r->payments->first(fn($p) => $p->notes)?->notes; @endphp
                        @if($r->notes)
                        <div class="text-amber-700 text-xs bg-amber-50 rounded px-1.5 py-0.5 mb-0.5">{{ $r->notes }}</div>
                        @endif
                        @if($payNote)
                        <div class="text-blue-700 text-xs bg-blue-50 rounded px-1.5 py-0.5">💱 {{ $payNote }}</div>
                        @endif
                        @if(!$r->notes && !$payNote)
                        <span class="text-gray-300 text-xs">—</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if($reservations->hasPages())
    <div class="px-5 py-3 border-t border-gray-100">
        {{ $reservations->links() }}
    </div>
    @endif
    @endif
</div>

</div>
@endsection
