@extends('layouts.app')
@section('title', 'التشغيل اليومي')
@section('page-title', 'التشغيل اليومي')

@section('content')
<div dir="rtl" class="space-y-5">

@if(session('pdf_warning'))
<div class="p-4 bg-amber-50 border border-amber-200 rounded-lg text-amber-800 text-sm flex items-start gap-2">
    <svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
    {{ session('pdf_warning') }}
</div>
@endif

{{-- Tab Bar --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-100">
    <div class="flex border-b border-gray-100">
        <a href="{{ route('reports.dailyHub', ['tab' => 'daily', 'date' => $date]) }}"
           class="px-5 py-3 text-sm font-medium border-b-2 transition
                  {{ $tab === 'daily' ? 'border-blue-600 text-blue-700 bg-blue-50/50' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
            القائمة اليومية
        </a>
        <a href="{{ route('reports.dailyHub', array_merge(request()->only(['from','to']), ['tab' => 'occupancy'])) }}"
           class="px-5 py-3 text-sm font-medium border-b-2 transition
                  {{ $tab === 'occupancy' ? 'border-blue-600 text-blue-700 bg-blue-50/50' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
            إشغال الغرف
        </a>
        <a href="{{ route('reports.dailyHub', array_merge(request()->only(['from','to']), ['tab' => 'reservations'])) }}"
           class="px-5 py-3 text-sm font-medium border-b-2 transition
                  {{ $tab === 'reservations' ? 'border-blue-600 text-blue-700 bg-blue-50/50' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
            الحجوزات
        </a>
    </div>

    {{-- ══════ TAB: القائمة اليومية ══════ --}}
    @if($tab === 'daily')
    <div class="p-4">
        <div class="flex flex-wrap items-end gap-3 mb-4">
            <form method="GET" action="{{ route('reports.dailyHub') }}" class="flex items-end gap-3">
                <input type="hidden" name="tab" value="daily">
                <div class="flex flex-col gap-1">
                    <label class="text-xs font-medium text-gray-500">التاريخ</label>
                    <input type="date" name="date" value="{{ $date }}" onchange="this.form.submit()"
                           class="border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400 transition bg-white">
                </div>
                <button type="submit" class="sr-only">عرض</button>
            </form>
            <div class="flex gap-2 mr-auto">
                <a href="{{ route('reports.daily.pdf', ['date' => $date]) }}"
                   class="flex items-center gap-2 px-4 py-2 bg-red-600 text-white rounded-lg text-sm hover:bg-red-700 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    PDF
                </a>
                <a href="{{ route('reports.daily.excel', ['date' => $date]) }}"
                   class="flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg text-sm hover:bg-green-700 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                    Excel
                </a>
            </div>
        </div>

        <div class="grid grid-cols-3 gap-3 mb-4">
            <div class="bg-gray-50 rounded-xl border border-gray-100 p-4 text-center">
                <div class="text-2xl font-bold" style="color:#0F4C75;">{{ $dailyReservations->count() }}</div>
                <div class="text-xs text-gray-500 mt-0.5">إجمالي النزلاء</div>
            </div>
            <div class="bg-gray-50 rounded-xl border border-gray-100 p-4 text-center">
                <div class="text-2xl font-bold text-green-600">{{ $dailyReservations->where('status','checked_in')->count() }}</div>
                <div class="text-xs text-gray-500 mt-0.5">مسجل دخول</div>
            </div>
            <div class="bg-gray-50 rounded-xl border border-gray-100 p-4 text-center">
                <div class="text-2xl font-bold text-gray-700">{{ $dailyReservations->sum(fn($r) => $r->companions->count()) }}</div>
                <div class="text-xs text-gray-500 mt-0.5">مرافق</div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-100">
            <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
                <h3 class="font-semibold text-gray-700 text-sm">
                    قائمة النزلاء ليوم <span style="color:#0F4C75;">{{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}</span>
                </h3>
                <span class="text-sm text-gray-400">{{ $dailyReservations->count() }} نزيل</span>
            </div>
            @if($dailyReservations->isEmpty())
            <div class="py-12 text-center text-gray-400 text-sm">لا يوجد نزلاء في هذا التاريخ</div>
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
                        @foreach($dailyReservations as $res)
                        @php
                            $idTypeMap = ['national_id'=>'بطاقة','passport'=>'جواز','residence'=>'إقامة'];
                            $payColors = ['unpaid'=>'text-red-600','partial'=>'text-yellow-600','paid'=>'text-green-600','deferred'=>'text-purple-600'];
                            $companions = $res->companions->count();
                        @endphp
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-3 py-2.5 font-bold text-gray-800 whitespace-nowrap">{{ $res->display_room_number }}</td>
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
                            <td class="px-3 py-2.5 text-gray-600 whitespace-nowrap">{{ $companions > 0 ? $companions . ' مرافق' : 'لوحده' }}</td>
                            <td class="px-3 py-2.5 whitespace-nowrap">
                                <span class="{{ $payColors[$res->payment_status] ?? 'text-gray-600' }} font-medium">{{ $res->payment_status_label }}</span>
                                <div class="text-gray-400 text-xs">{{ number_format($res->paid_amount,0) }} / {{ number_format($res->total_amount,0) }}</div>
                            </td>
                            <td class="px-3 py-2.5 text-gray-600 whitespace-nowrap font-mono">{{ $res->guest?->phone }}</td>
                            <td class="px-3 py-2.5 max-w-[160px]">
                                @php $payNote = $res->payments->first(fn($p) => $p->notes)?->notes; @endphp
                                @if($res->notes)<div class="text-amber-700 text-xs bg-amber-50 rounded px-1.5 py-0.5 mb-0.5">{{ $res->notes }}</div>@endif
                                @if($payNote)<div class="text-blue-700 text-xs bg-blue-50 rounded px-1.5 py-0.5">💱 {{ $payNote }}</div>@endif
                                @if(!$res->notes && !$payNote)<span class="text-gray-300">—</span>@endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    </div>

    {{-- ══════ TAB: إشغال الغرف ══════ --}}
    @elseif($tab === 'occupancy')
    <div class="p-4 space-y-4">
        <div class="flex flex-wrap items-end gap-3">
            <form method="GET" action="{{ route('reports.dailyHub') }}" class="flex flex-wrap gap-3 items-end">
                <input type="hidden" name="tab" value="occupancy">
                <div class="flex flex-col gap-1">
                    <label class="text-xs font-medium text-gray-500">من تاريخ</label>
                    <input type="date" name="from" value="{{ $from }}" onchange="this.form.submit()"
                           class="border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400 transition bg-white">
                </div>
                <div class="flex flex-col gap-1">
                    <label class="text-xs font-medium text-gray-500">إلى تاريخ</label>
                    <input type="date" name="to" value="{{ $to }}" onchange="this.form.submit()"
                           class="border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400 transition bg-white">
                </div>
                <button type="submit" class="sr-only">عرض</button>
            </form>
            <div class="flex gap-2 mr-auto">
                <a href="{{ route('reports.occupancy.pdf', ['from' => $from, 'to' => $to]) }}"
                   class="flex items-center gap-2 px-4 py-2 bg-red-600 text-white rounded-lg text-sm hover:bg-red-700 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    PDF
                </a>
                <a href="{{ route('reports.occupancy.excel', ['from' => $from, 'to' => $to]) }}"
                   class="flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg text-sm hover:bg-green-700 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                    Excel
                </a>
            </div>
        </div>

        <div class="grid grid-cols-3 gap-4">
            <div class="bg-gray-50 rounded-xl border border-gray-100 p-5 text-center">
                <div class="text-3xl font-bold" style="color:#0F4C75;">{{ $totalRooms }}</div>
                <div class="text-sm text-gray-500 mt-1">إجمالي الغرف</div>
            </div>
            <div class="bg-gray-50 rounded-xl border border-gray-100 p-5 text-center">
                <div class="text-3xl font-bold text-green-600">{{ $dailyOccupancy->count() > 0 ? round($dailyOccupancy->avg('percent')) : 0 }}%</div>
                <div class="text-sm text-gray-500 mt-1">متوسط الإشغال</div>
            </div>
            <div class="bg-gray-50 rounded-xl border border-gray-100 p-5 text-center">
                <div class="text-3xl font-bold text-blue-600">{{ $dailyOccupancy->count() }}</div>
                <div class="text-sm text-gray-500 mt-1">عدد الأيام</div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-100 p-5">
            <h3 class="font-semibold text-gray-700 mb-4 text-sm">منحنى الإشغال اليومي</h3>
            <div style="height:220px;">
                <canvas id="occupancyChart"></canvas>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-100">
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="font-semibold text-gray-700 text-sm">تفاصيل الإشغال اليومي</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50"><tr>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">التاريخ</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">نسبة الإشغال</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">شريط</th>
                    </tr></thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse($dailyOccupancy as $day)
                        <tr>
                            <td class="px-4 py-3 text-gray-600">{{ $day['date'] }}</td>
                            <td class="px-4 py-3 font-medium text-gray-800">{{ $day['percent'] }}%</td>
                            <td class="px-4 py-3 w-48">
                                <div class="w-full bg-gray-100 rounded-full h-2">
                                    <div class="h-2 rounded-full" style="width:{{ $day['percent'] }}%; background:#0F4C75;"></div>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="3" class="px-4 py-6 text-center text-gray-400">لا توجد بيانات للفترة المحددة</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ══════ TAB: الحجوزات ══════ --}}
    @elseif($tab === 'reservations')
    <div class="p-4 space-y-4">
        <div class="flex flex-wrap items-end gap-3">
            <form method="GET" action="{{ route('reports.dailyHub') }}" class="flex flex-wrap gap-3 items-end">
                <input type="hidden" name="tab" value="reservations">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">الفترة</label>
                    <div class="flex gap-1.5">
                        <button type="submit" name="preset" value="today"
                                class="px-3 py-1.5 rounded-lg text-xs font-medium transition border
                                       {{ $preset==='today' ? 'text-white border-transparent' : 'text-gray-600 border-gray-200 hover:bg-gray-50' }}"
                                style="{{ $preset==='today' ? 'background:#0F4C75' : '' }}">اليوم</button>
                        <button type="submit" name="preset" value="week"
                                class="px-3 py-1.5 rounded-lg text-xs font-medium transition border
                                       {{ $preset==='week' ? 'text-white border-transparent' : 'text-gray-600 border-gray-200 hover:bg-gray-50' }}"
                                style="{{ $preset==='week' ? 'background:#0F4C75' : '' }}">هذا الأسبوع</button>
                        <button type="submit" name="preset" value="custom"
                                class="px-3 py-1.5 rounded-lg text-xs font-medium transition border
                                       {{ $preset==='custom' ? 'text-white border-transparent' : 'text-gray-600 border-gray-200 hover:bg-gray-50' }}"
                                style="{{ $preset==='custom' ? 'background:#0F4C75' : '' }}">مخصص</button>
                    </div>
                </div>
                <div class="flex items-end gap-2">
                    <div class="flex flex-col gap-1">
                        <label class="text-xs font-medium text-gray-500">من</label>
                        <input type="date" name="from" value="{{ $from }}" onchange="this.form.submit()"
                               class="border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400 transition bg-white">
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="text-xs font-medium text-gray-500">إلى</label>
                        <input type="date" name="to" value="{{ $to }}" onchange="this.form.submit()"
                               class="border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400 transition bg-white">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">حالة الحجز</label>
                    <div class="flex gap-1.5">
                        <button type="submit" name="status" value="all"
                                class="px-3 py-1.5 rounded-lg text-xs font-medium transition border
                                       {{ $status==='all' ? 'text-white border-transparent' : 'text-gray-600 border-gray-200 hover:bg-gray-50' }}"
                                style="{{ $status==='all' ? 'background:#0F4C75' : '' }}">الكل</button>
                        <button type="submit" name="status" value="checked_in"
                                class="px-3 py-1.5 rounded-lg text-xs font-medium transition border
                                       {{ $status==='checked_in' ? 'text-white border-transparent' : 'text-gray-600 border-gray-200 hover:bg-gray-50' }}"
                                style="{{ $status==='checked_in' ? 'background:#16a34a' : '' }}">لم يغادروا</button>
                        <button type="submit" name="status" value="checked_out"
                                class="px-3 py-1.5 rounded-lg text-xs font-medium transition border
                                       {{ $status==='checked_out' ? 'text-white border-transparent' : 'text-gray-600 border-gray-200 hover:bg-gray-50' }}"
                                style="{{ $status==='checked_out' ? 'background:#2563eb' : '' }}">غادروا</button>
                    </div>
                </div>
            </form>
            <div class="flex flex-col gap-1 mr-auto" x-data="reservationsColumnPicker()">
                <div class="flex gap-2">
                    <button type="button" @click="open = true"
                       class="flex items-center gap-2 px-4 py-2 bg-red-600 text-white rounded-lg text-sm hover:bg-red-700 transition"
                       title="PDF يدعم حتى 30 يوماً — استخدم Excel للفترات الأطول">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        PDF
                    </button>
                    <a href="{{ route('reports.reservations.excel', ['from' => $from, 'to' => $to, 'status' => $status]) }}"
                       class="flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg text-sm hover:bg-green-700 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                        Excel
                    </a>
                </div>
                <p class="text-xs text-gray-500 mt-1">
                    سيُصدَّر حسب الفلتر الحالي:
                    <strong>{{ ['all' => 'كل الحجوزات', 'checked_in' => 'من لم يغادروا فقط', 'checked_out' => 'من غادروا فقط'][$status] }}</strong>
                </p>
                @php $days = \Carbon\Carbon::parse($from)->diffInDays(\Carbon\Carbon::parse($to)) + 1; @endphp
                @if($days > 30)
                <p class="text-xs text-amber-600 mt-1">⚠ الفترة {{ $days }} يوم — PDF يدعم حتى 30 يوماً، استخدم Excel</p>
                @endif

                {{-- نافذة اختيار أعمدة تقرير PDF --}}
                <div x-show="open" x-cloak style="display:none;"
                     class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 p-4"
                     @click.self="open = false">
                    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[85vh] flex flex-col">
                        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between flex-shrink-0">
                            <h3 class="font-bold text-gray-800">اختر أعمدة تقرير الحجوزات (PDF)</h3>
                            <button type="button" @click="open = false" class="text-gray-400 hover:text-gray-600">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        <div class="p-6 overflow-y-auto">
                            <div class="flex gap-2 mb-4">
                                <button type="button" @click="selectAll()" class="text-xs px-3 py-1.5 rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50">تحديد الكل</button>
                                <button type="button" @click="selectNone()" class="text-xs px-3 py-1.5 rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50">إلغاء الكل</button>
                            </div>
                            <div class="grid grid-cols-2 gap-2.5">
                                @foreach(\App\Http\Controllers\ReportController::RESERVATIONS_PDF_COLUMNS as $key => $label)
                                <label class="flex items-center gap-2 text-sm text-gray-700 border border-gray-100 rounded-lg px-3 py-2 hover:bg-gray-50 cursor-pointer"
                                       :class="forced && '{{ $key }}' === 'stay_status' ? 'opacity-70 cursor-not-allowed' : ''">
                                    <input type="checkbox" value="{{ $key }}"
                                           :checked="columns.includes('{{ $key }}')"
                                           @change="toggleColumn('{{ $key }}')"
                                           :disabled="forced && '{{ $key }}' === 'stay_status'"
                                           class="rounded border-gray-300">
                                    {{ $label }}
                                    @if($key === 'stay_status')
                                    <span x-show="forced" x-cloak class="text-[10px] text-amber-600">(إجباري عند عرض الكل)</span>
                                    @endif
                                </label>
                                @endforeach
                            </div>
                        </div>
                        <div class="flex gap-3 px-6 py-4 border-t border-gray-100 flex-shrink-0">
                            <button type="button" @click="download()" :disabled="columns.length === 0"
                                    class="flex-1 py-2.5 bg-red-600 hover:bg-red-700 text-white rounded-xl text-sm font-bold transition disabled:opacity-40">
                                تصدير PDF (<span x-text="columns.length"></span> عمود)
                            </button>
                            <button type="button" @click="open = false" class="px-6 py-2.5 border border-gray-300 text-gray-700 rounded-xl text-sm hover:bg-gray-50 transition">إلغاء</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-3 gap-3">
            <div class="bg-white rounded-xl border border-gray-100 p-4 text-center shadow-sm">
                <div class="text-2xl font-bold" style="color:#0F4C75;">{{ $total }}</div>
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

        <div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
            <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
                <h3 class="font-semibold text-gray-700 text-sm">
                    قائمة الحجوزات
                    <span class="text-gray-400 font-normal mr-2 text-xs">({{ $printedCount }} حجز)</span>
                </h3>
            </div>

            @if($reservations->isEmpty())
            <div class="py-12 text-center text-gray-400 text-sm">لا توجد حجوزات في هذه الفترة</div>
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
                            <th class="px-3 py-2.5 text-right font-medium text-gray-500 whitespace-nowrap">تاريخ الدخول</th>
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
                        @php
                            $g = $r->guest;
                            $ps = $r->payment_status ?? 'pending';
                            $psBg = match($ps) { 'paid'=>'bg-green-100 text-green-700','partial'=>'bg-amber-100 text-amber-700',default=>'bg-red-100 text-red-700' };
                            $psLabel = match($ps) { 'paid'=>'مدفوع','partial'=>'جزئي',default=>'معلق' };
                            $idTypeMap = ['national_id'=>'بطاقة','passport'=>'جواز','residence'=>'إقامة'];
                        @endphp
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-3 py-2.5 text-gray-400">{{ $r->id }}</td>
                            <td class="px-3 py-2.5 font-bold text-gray-800 whitespace-nowrap">{{ $r->display_room_number }}</td>
                            <td class="px-3 py-2.5 font-medium text-gray-800 whitespace-nowrap">
                                <a href="{{ route('reservations.show', $r) }}" class="hover:text-blue-600 hover:underline">{{ $g?->full_name ?? '—' }}</a>
                            </td>
                            <td class="px-3 py-2.5 text-gray-600 whitespace-nowrap">{{ $g?->nationality ?? '—' }}</td>
                            <td class="px-3 py-2.5 text-gray-600 whitespace-nowrap">{{ $g?->occupation ?? '—' }}</td>
                            <td class="px-3 py-2.5 text-gray-600 whitespace-nowrap">{{ $r->origin ?? '—' }}</td>
                            <td class="px-3 py-2.5 text-gray-600 whitespace-nowrap">{{ $r->check_in_date?->format('d/m/Y') ?? '—' }}</td>
                            <td class="px-3 py-2.5 text-gray-600 whitespace-nowrap">{{ $r->check_in_time ?? '—' }}</td>
                            <td class="px-3 py-2.5 text-gray-600 whitespace-nowrap">{{ $r->purpose ?? '—' }}</td>
                            <td class="px-3 py-2.5 text-gray-600 whitespace-nowrap">{{ $idTypeMap[$g?->id_type] ?? $g?->id_type ?? '—' }}</td>
                            <td class="px-3 py-2.5 text-gray-600 whitespace-nowrap font-mono">{{ $g?->id_number ?? '—' }}</td>
                            <td class="px-3 py-2.5 text-gray-600 whitespace-nowrap">{{ $g?->id_issuer ?? '—' }}</td>
                            <td class="px-3 py-2.5 text-gray-600 whitespace-nowrap">{{ $g?->id_issue_date?->format('d/m/Y') ?? '—' }}</td>
                            <td class="px-3 py-2.5 text-gray-600 whitespace-nowrap font-mono" dir="ltr">{{ $g?->phone ?? '—' }}</td>
                            <td class="px-3 py-2.5 whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $psBg }}">{{ $psLabel }}</span>
                            </td>
                            <td class="px-3 py-2.5 max-w-[180px]">
                                @php $payNote = $r->payments->first(fn($p) => $p->notes)?->notes; @endphp
                                @if($r->notes)<div class="text-amber-700 text-xs bg-amber-50 rounded px-1.5 py-0.5 mb-0.5">{{ $r->notes }}</div>@endif
                                @if($payNote)<div class="text-blue-700 text-xs bg-blue-50 rounded px-1.5 py-0.5">💱 {{ $payNote }}</div>@endif
                                @if(!$r->notes && !$payNote)<span class="text-gray-300 text-xs">—</span>@endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($reservations->hasPages())
            <div class="px-5 py-3 border-t border-gray-100">
                {{ $reservations->links() }}
            </div>
            @endif
            @endif
        </div>
    </div>
    @endif

</div>{{-- end tab container --}}
</div>{{-- end main --}}

@endsection

@push('scripts')
@if($tab === 'reservations')
<script>
const RESERVATIONS_PDF_COLUMNS = @json(array_keys(\App\Http\Controllers\ReportController::RESERVATIONS_PDF_COLUMNS));
function reservationsColumnPicker() {
    return {
        open: false,
        // عند عرض "الكل" يكون عمود حالة الإقامة إجبارياً — لا يمكن إلغاؤه، لتمييز من غادر ممن لم يغادر
        forced: @json($status) === 'all',
        columns: [...RESERVATIONS_PDF_COLUMNS],
        selectAll()  { this.columns = [...RESERVATIONS_PDF_COLUMNS]; },
        selectNone() { this.columns = this.forced ? ['stay_status'] : []; },
        toggleColumn(key) {
            if (this.forced && key === 'stay_status') return;
            const idx = this.columns.indexOf(key);
            if (idx === -1) this.columns.push(key); else this.columns.splice(idx, 1);
        },
        download() {
            if (this.columns.length === 0) return;
            const params = new URLSearchParams();
            params.set('from', @json($from));
            params.set('to', @json($to));
            params.set('status', @json($status));
            this.columns.forEach(c => params.append('columns[]', c));
            window.location.href = @json(route('reports.reservations.pdf')) + '?' + params.toString();
            this.open = false;
        },
    }
}
</script>
@endif
@if($tab === 'occupancy')
<script>
new Chart(document.getElementById('occupancyChart'), {
    type: 'line',
    data: {
        labels: {!! json_encode($dailyOccupancy->pluck('date')) !!},
        datasets: [{
            label: 'نسبة الإشغال %',
            data: {!! json_encode($dailyOccupancy->pluck('percent')) !!},
            borderColor: '#0F4C75',
            backgroundColor: 'rgba(15,76,117,0.1)',
            fill: true, tension: 0.4, pointRadius: 3,
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        scales: { y: { beginAtZero: true, max: 100, ticks: { callback: v => v + '%' } } },
        plugins: { legend: { display: false } }
    }
});
</script>
@endif
@endpush
