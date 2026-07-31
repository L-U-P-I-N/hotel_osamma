@extends('layouts.app')
@section('title', 'تقرير الجهات الحكومية')
@section('page-title', 'تقرير الجهات الحكومية')

@section('content')
<div class="space-y-5" dir="rtl">

    <div class="flex flex-wrap items-end gap-3 justify-between">
        <form method="GET" action="{{ route('reports.government') }}" class="flex gap-2 items-end flex-wrap">
            <div class="flex flex-col gap-1">
                <label class="text-xs font-medium text-gray-500">من (تاريخ الدخول)</label>
                <input type="date" name="from" value="{{ $from }}"
                       class="border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400 bg-white">
            </div>
            <div class="flex flex-col gap-1">
                <label class="text-xs font-medium text-gray-500">إلى</label>
                <input type="date" name="to" value="{{ $to }}"
                       class="border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400 bg-white">
            </div>
            <div class="flex flex-col gap-1">
                <label class="text-xs font-medium text-gray-500">بحث باسم النزيل أو رقم الغرفة</label>
                <input type="text" name="search" value="{{ $search }}" placeholder="اسم النزيل أو رقم الغرفة..."
                       class="border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400 bg-white">
            </div>
            <button type="submit" class="px-4 py-2 text-white rounded-lg text-sm font-medium" style="background:#0F4C75;">فلترة</button>
            <a href="{{ route('reports.government') }}" class="px-3 py-2 text-xs text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50">إعادة تعيين</a>
        </form>
        <a href="{{ route('reports.government.pdf', ['from' => $from, 'to' => $to, 'search' => $search]) }}"
           class="flex items-center gap-2 px-4 py-2 bg-red-600 text-white rounded-lg text-sm hover:bg-red-700 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
            تصدير PDF
        </a>
    </div>

    <div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between flex-wrap gap-2">
            <h3 class="font-semibold text-gray-700 text-sm">تقرير اليوم ({{ now()->format('Y/m/d') }})</h3>
            <a href="{{ route('reports.government.pdf', ['mode' => 'today']) }}"
               class="flex items-center gap-2 px-4 py-2 bg-red-600 text-white rounded-lg text-sm hover:bg-red-700 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                تصدير PDF - تقرير اليوم
            </a>
        </div>

        @foreach([['title' => 'النزلاء المتواجدون اليوم', 'rows' => $presentToday], ['title' => 'النزلاء الذين غادروا اليوم', 'rows' => $departedToday]] as $todaySection)
        <div class="px-5 py-3 border-b border-gray-100">
            <h4 class="text-sm font-semibold text-gray-600 mb-2">
                {{ $todaySection['title'] }}
                <span class="text-gray-400 font-normal mr-2 text-xs">({{ $todaySection['rows']->count() }})</span>
            </h4>
            @if($todaySection['rows']->isEmpty())
            <div class="py-6 text-center text-gray-400 text-sm">لا يوجد</div>
            @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-100">
                        <tr>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 whitespace-nowrap">الغرفة</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 whitespace-nowrap">اسم النزيل</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 whitespace-nowrap">الجنسية</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 whitespace-nowrap">المهنة</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 whitespace-nowrap">جهة القدوم</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 whitespace-nowrap">تاريخ الدخول</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">بيانات المرافقين</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 whitespace-nowrap">ملاحظة</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($todaySection['rows'] as $res)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-semibold text-gray-800 whitespace-nowrap">{{ $res->display_room_number }}</td>
                            <td class="px-4 py-3 font-medium text-gray-800 whitespace-nowrap">{{ $res->guest?->full_name ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-600 whitespace-nowrap">{{ $res->guest?->nationality ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-600 whitespace-nowrap">{{ $res->guest?->occupation ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-600 whitespace-nowrap">{{ $res->origin ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-500 text-xs whitespace-nowrap">{{ $res->check_in_date?->format('d/m/Y') ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-600 text-xs">
                                @if($res->companions->isEmpty())
                                <span class="text-gray-300">—</span>
                                @else
                                @foreach($res->companions as $c)
                                <div class="whitespace-nowrap">{{ $c->full_name }} ({{ $c->getRelationshipLabel() }})</div>
                                @endforeach
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-600 text-xs whitespace-pre-line">{{ $res->government_note }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
        @endforeach
    </div>

    <div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100">
            <h3 class="font-semibold text-gray-700 text-sm">
                سجل النزلاء (الجهات الحكومية)
                <span class="text-gray-400 font-normal mr-2 text-xs">({{ $reservations->total() }} حجز)</span>
            </h3>
        </div>

        @if($reservations->isEmpty())
        <div class="py-12 text-center text-gray-400 text-sm">لا توجد حجوزات في هذه الفترة</div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-100">
                    <tr>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 whitespace-nowrap">الغرفة</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 whitespace-nowrap">اسم النزيل</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 whitespace-nowrap">الجنسية</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 whitespace-nowrap">المهنة</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 whitespace-nowrap">جهة القدوم</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 whitespace-nowrap">تاريخ الدخول</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 whitespace-nowrap">الوقت</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 whitespace-nowrap">الغرض</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 whitespace-nowrap">نوع الهوية</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 whitespace-nowrap">رقم الهوية</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 whitespace-nowrap">صادر من</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 whitespace-nowrap">تاريخ الإصدار</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">بيانات المرافقين</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 whitespace-nowrap">ملاحظة</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 whitespace-nowrap">حالة الإقامة</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($reservations as $res)
                    @php
                        $inTime = $res->check_in_time ?: $res->check_in_date?->format('H:i');
                        $departed = $res->status === 'checked_out';
                    @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-semibold text-gray-800 whitespace-nowrap">{{ $res->display_room_number }}</td>
                        <td class="px-4 py-3 font-medium text-gray-800 whitespace-nowrap">{{ $res->guest?->full_name ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-600 whitespace-nowrap">{{ $res->guest?->nationality ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-600 whitespace-nowrap">{{ $res->guest?->occupation ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-600 whitespace-nowrap">{{ $res->origin ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-500 text-xs whitespace-nowrap">{{ $res->check_in_date?->format('d/m/Y') ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-500 text-xs whitespace-nowrap">{{ $inTime ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-600 whitespace-nowrap">{{ $res->purpose ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-600 whitespace-nowrap">{{ $res->guest?->getIdTypeLabel() ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-600 whitespace-nowrap">{{ $res->guest?->id_number ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-600 whitespace-nowrap">{{ $res->guest?->id_issuer ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-500 text-xs whitespace-nowrap">{{ $res->guest?->id_issue_date?->format('d/m/Y') ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-600 text-xs">
                            @if($res->companions->isEmpty())
                            <span class="text-gray-300">—</span>
                            @else
                            @foreach($res->companions as $c)
                            <div class="whitespace-nowrap">{{ $c->full_name }} ({{ $c->getRelationshipLabel() }})</div>
                            @endforeach
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-600 text-xs whitespace-pre-line">{{ $res->government_note }}</td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <span class="px-2 py-1 rounded-full text-xs font-semibold {{ $departed ? 'bg-gray-100 text-gray-600' : 'bg-green-100 text-green-700' }}">
                                {{ $departed ? 'غادروا' : 'لم يغادروا' }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="px-5 py-3 border-t border-gray-100">
            {{ $reservations->links() }}
        </div>
        @endif
    </div>

</div>
@endsection
