@extends('layouts.app')
@section('title', 'القائمة اليومية')
@section('page-title', 'القائمة اليومية للنزلاء')

@section('content')
<div class="space-y-4">

<!-- Filter + Export -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
    <div class="flex flex-wrap items-end gap-3">
        <form method="GET" class="flex items-end gap-3 flex-wrap">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">التاريخ</label>
                <input type="date" name="date" value="{{ $date }}"
                       class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 outline-none">
            </div>
            <button type="submit" class="px-4 py-2 text-white rounded-lg text-sm transition" style="background:#0F4C75;">عرض</button>
        </form>
        <div class="mr-auto flex gap-2">
            <a href="{{ route('reports.daily.pdf', ['date' => $date]) }}"
               class="flex items-center gap-2 px-4 py-2 bg-red-600 text-white rounded-lg text-sm hover:bg-red-700 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                تصدير PDF
            </a>
            <a href="{{ route('reports.daily.excel', ['date' => $date]) }}"
               class="flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg text-sm hover:bg-green-700 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                تصدير Excel
            </a>
        </div>
    </div>
</div>

<!-- Summary -->
<div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
    <div class="bg-white rounded-xl border border-gray-100 p-4 text-center">
        <div class="text-2xl font-bold text-primary-700">{{ $reservations->count() }}</div>
        <div class="text-xs text-gray-500 mt-0.5">إجمالي النزلاء</div>
    </div>
    <div class="bg-white rounded-xl border border-gray-100 p-4 text-center">
        <div class="text-2xl font-bold text-green-600">{{ $reservations->where('status', 'checked_in')->count() }}</div>
        <div class="text-xs text-gray-500 mt-0.5">مسجل دخول</div>
    </div>
    <div class="bg-white rounded-xl border border-gray-100 p-4 text-center">
        <div class="text-2xl font-bold text-blue-600">{{ $reservations->where('status', 'confirmed')->count() }}</div>
        <div class="text-xs text-gray-500 mt-0.5">حجز مؤكد</div>
    </div>
    <div class="bg-white rounded-xl border border-gray-100 p-4 text-center">
        <div class="text-2xl font-bold text-gray-700">{{ $reservations->sum(fn($r) => $r->companions->count()) }}</div>
        <div class="text-xs text-gray-500 mt-0.5">مرافق</div>
    </div>
</div>

<!-- Daily Table -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
        <h3 class="font-semibold text-gray-700">
            قائمة النزلاء ليوم
            <span class="text-primary-700 mr-1">{{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}</span>
        </h3>
        <span class="text-sm text-gray-400">{{ $reservations->count() }} نزيل</span>
    </div>

    @if($reservations->isEmpty())
    <div class="py-16 text-center text-gray-400">
        <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
        <p class="text-sm">لا يوجد نزلاء في هذا التاريخ</p>
    </div>
    @else
    <div class="overflow-x-auto">
        <table class="w-full text-xs">
            <thead class="bg-gray-50 border-b border-gray-100">
                <tr>
                    <th class="px-3 py-2.5 text-right font-medium text-gray-500 whitespace-nowrap">الغرفة</th>
                    <th class="px-3 py-2.5 text-right font-medium text-gray-500 whitespace-nowrap">اسم النزيل</th>
                    <th class="px-3 py-2.5 text-right font-medium text-gray-500 whitespace-nowrap">الجنسية</th>
                    <th class="px-3 py-2.5 text-right font-medium text-gray-500 whitespace-nowrap">المهنة</th>
                    <th class="px-3 py-2.5 text-right font-medium text-gray-500 whitespace-nowrap">جهة القدوم</th>
                    <th class="px-3 py-2.5 text-right font-medium text-gray-500 whitespace-nowrap">تاريخ الدخول</th>
                    <th class="px-3 py-2.5 text-right font-medium text-gray-500 whitespace-nowrap">الوقت</th>
                    <th class="px-3 py-2.5 text-right font-medium text-gray-500 whitespace-nowrap">الغرض</th>
                    <th class="px-3 py-2.5 text-right font-medium text-gray-500 whitespace-nowrap">نوع الهوية</th>
                    <th class="px-3 py-2.5 text-right font-medium text-gray-500 whitespace-nowrap">رقم الهوية</th>
                    <th class="px-3 py-2.5 text-right font-medium text-gray-500 whitespace-nowrap">صادر من</th>
                    <th class="px-3 py-2.5 text-right font-medium text-gray-500 whitespace-nowrap">تاريخ الإصدار</th>
                    <th class="px-3 py-2.5 text-right font-medium text-gray-500 whitespace-nowrap">المرافقون</th>
                    <th class="px-3 py-2.5 text-right font-medium text-gray-500 whitespace-nowrap">الدفع</th>
                    <th class="px-3 py-2.5 text-right font-medium text-gray-500 whitespace-nowrap">الجوال</th>
                    <th class="px-3 py-2.5 text-right font-medium text-gray-500 whitespace-nowrap">ملاحظات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($reservations as $res)
                @php
                    $idTypeMap = ['national_id' => 'بطاقة', 'passport' => 'جواز', 'residence' => 'إقامة'];
                    $payColors = ['unpaid'=>'text-red-600','partial'=>'text-yellow-600','paid'=>'text-green-600','deferred'=>'text-purple-600'];
                    $companions = $res->companions->count();
                @endphp
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-3 py-2.5 font-bold text-gray-800 whitespace-nowrap">
                        {{ $res->room?->room_number }}
                        @if($res->status === 'confirmed')
                        <span class="text-blue-500 text-xs">(محجوز)</span>
                        @endif
                    </td>
                    <td class="px-3 py-2.5 font-medium text-gray-800 whitespace-nowrap">{{ $res->guest?->full_name }}</td>
                    <td class="px-3 py-2.5 text-gray-600 whitespace-nowrap">{{ $res->guest?->nationality }}</td>
                    <td class="px-3 py-2.5 text-gray-600 whitespace-nowrap">{{ $res->guest?->occupation }}</td>
                    <td class="px-3 py-2.5 text-gray-600 whitespace-nowrap">{{ $res->origin }}</td>
                    <td class="px-3 py-2.5 text-gray-600 whitespace-nowrap">{{ $res->check_in_date?->format('d/m/Y') }}</td>
                    <td class="px-3 py-2.5 text-gray-600 whitespace-nowrap">{{ $res->check_in_time ?? '—' }}</td>
                    <td class="px-3 py-2.5 text-gray-600 whitespace-nowrap">{{ $res->purpose }}</td>
                    <td class="px-3 py-2.5 text-gray-600 whitespace-nowrap">{{ $idTypeMap[$res->guest?->id_type] ?? $res->guest?->id_type }}</td>
                    <td class="px-3 py-2.5 text-gray-600 whitespace-nowrap font-mono">{{ $res->guest?->id_number }}</td>
                    <td class="px-3 py-2.5 text-gray-600 whitespace-nowrap">{{ $res->guest?->id_issuer }}</td>
                    <td class="px-3 py-2.5 text-gray-600 whitespace-nowrap">{{ $res->guest?->id_issue_date?->format('Y/m/d') }}</td>
                    <td class="px-3 py-2.5 text-gray-600 whitespace-nowrap">
                        @if($companions > 0)
                        {{ $companions }} مرافق
                        @else
                        لوحده
                        @endif
                    </td>
                    <td class="px-3 py-2.5 whitespace-nowrap">
                        <span class="{{ $payColors[$res->payment_status] ?? 'text-gray-600' }} font-medium">
                            {{ $res->payment_status_label }}
                        </span>
                        <div class="text-gray-400 text-xs">{{ number_format($res->paid_amount, 0) }} / {{ number_format($res->total_amount, 0) }}</div>
                    </td>
                    <td class="px-3 py-2.5 text-gray-600 whitespace-nowrap font-mono">{{ $res->guest?->phone }}</td>
                    <td class="px-3 py-2.5 max-w-[180px]">
                        @php $payNote = $res->payments->first(fn($p) => $p->notes)?->notes; @endphp
                        @if($res->notes)
                        <div class="text-amber-700 text-xs bg-amber-50 rounded px-1.5 py-0.5 mb-0.5">{{ $res->notes }}</div>
                        @endif
                        @if($payNote)
                        <div class="text-blue-700 text-xs bg-blue-50 rounded px-1.5 py-0.5">💱 {{ $payNote }}</div>
                        @endif
                        @if(!$res->notes && !$payNote)
                        <span class="text-gray-300">—</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>

</div>
@endsection
