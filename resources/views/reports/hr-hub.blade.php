@extends('layouts.app')
@section('title', 'الموارد البشرية')
@section('page-title', 'الموارد البشرية')

@section('content')
<div dir="rtl" class="space-y-5">

{{-- Tab Bar --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-100">
    <div class="flex border-b border-gray-100">
        <a href="{{ route('reports.hrHub', array_merge(request()->only(['year']), ['tab' => 'salaries'])) }}"
           class="px-5 py-3 text-sm font-medium border-b-2 transition
                  {{ $tab === 'salaries' ? 'border-blue-600 text-blue-700 bg-blue-50/50' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
            الرواتب
        </a>
        <a href="{{ route('reports.hrHub', array_merge(request()->only(['from', 'to']), ['tab' => 'staff'])) }}"
           class="px-5 py-3 text-sm font-medium border-b-2 transition
                  {{ $tab === 'staff' ? 'border-blue-600 text-blue-700 bg-blue-50/50' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
            أداء الموظفين
        </a>
    </div>

    {{-- ===== SALARIES TAB ===== --}}
    @if($tab === 'salaries')
    @php
        $monthNames = [1=>'يناير',2=>'فبراير',3=>'مارس',4=>'أبريل',5=>'مايو',6=>'يونيو',7=>'يوليو',8=>'أغسطس',9=>'سبتمبر',10=>'أكتوبر',11=>'نوفمبر',12=>'ديسمبر'];
    @endphp
    <div class="p-4">
        <div class="flex flex-wrap gap-3 items-end justify-between">
            <form method="GET" class="flex flex-wrap gap-3 items-end">
                <input type="hidden" name="tab" value="salaries">
                <div class="flex flex-col gap-1">
                    <label class="text-xs font-medium text-gray-500">السنة</label>
                    <select name="year" onchange="this.form.submit()"
                            class="border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400 transition bg-white">
                        @foreach($years as $y)
                        <option value="{{ $y }}" {{ $y == $year ? 'selected' : '' }}>{{ $y }}</option>
                        @endforeach
                        @if($years->isEmpty())
                        <option value="{{ now()->year }}" selected>{{ now()->year }}</option>
                        @endif
                    </select>
                </div>
                <button type="submit" class="sr-only">عرض</button>
            </form>
            <div class="flex gap-2">
                <a href="{{ route('reports.salaries.pdf', ['year' => $year]) }}"
                   class="flex items-center gap-2 px-4 py-2 bg-red-600 text-white rounded-lg text-sm hover:bg-red-700 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    تصدير PDF
                </a>
                <a href="{{ route('reports.salaries.excel', ['year' => $year]) }}"
                   class="flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg text-sm hover:bg-green-700 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                    Excel
                </a>
            </div>
        </div>
    </div>

    <div class="mx-4 mb-4 rounded-xl p-5 border" style="background:#e8f0f7; border-color:#9fbedd;">
        <div class="text-xs" style="color:#0F4C75;">إجمالي صافي الرواتب — {{ $year }}</div>
        <div class="text-3xl font-bold mt-1" style="color:#0F4C75;">{{ number_format($totalNet, 0) }} <span class="text-lg font-normal">ر.ي</span></div>
    </div>

    <div class="mx-4 mb-4 bg-white rounded-xl border border-gray-100">
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-700">ملخص شهري — {{ $year }}</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الشهر</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">عدد الموظفين</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">إجمالي الأساسي</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">المكافآت</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الخصومات</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الصافي</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">مدفوع / معلق</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($byMonth as $month => $data)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium text-gray-800">{{ $monthNames[$month] ?? $month }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $data['count'] }}</td>
                        <td class="px-4 py-3 text-gray-700">{{ number_format($data['total_base'], 0) }}</td>
                        <td class="px-4 py-3 text-green-600">{{ number_format($data['total_bonus'], 0) }}</td>
                        <td class="px-4 py-3 text-red-500">{{ number_format($data['total_ded'], 0) }}</td>
                        <td class="px-4 py-3 font-bold" style="color:#0F4C75;">{{ number_format($data['total_net'], 0) }}</td>
                        <td class="px-4 py-3 text-xs">
                            <span class="text-green-600">{{ $data['paid'] }} مدفوع</span>
                            @if($data['pending'] > 0) / <span class="text-amber-600">{{ $data['pending'] }} معلق</span>@endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="px-4 py-8 text-center text-gray-400">لا توجد رواتب مسجلة لهذه السنة</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($salaries->isNotEmpty())
    <div class="mx-4 mb-4 bg-white rounded-xl border border-gray-100">
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-700">تفاصيل الرواتب</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الموظف</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الشهر</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الأساسي</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">مكافآت</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">خصومات</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الصافي</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الحالة</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($salaries as $sal)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium text-gray-800">{{ $sal->employee->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $monthNames[$sal->month] ?? $sal->month }} {{ $sal->year }}</td>
                        <td class="px-4 py-3 text-gray-700">{{ number_format($sal->base_salary, 0) }}</td>
                        <td class="px-4 py-3 text-green-600">{{ number_format($sal->bonuses, 0) }}</td>
                        <td class="px-4 py-3 text-red-500">{{ number_format($sal->deductions, 0) }}</td>
                        <td class="px-4 py-3 font-bold" style="color:#0F4C75;">{{ number_format($sal->net_salary, 0) }}</td>
                        <td class="px-4 py-3">
                            @if($sal->status === 'paid')
                            <span class="px-2 py-0.5 rounded-full text-xs bg-green-100 text-green-700">مدفوع</span>
                            @else
                            <span class="px-2 py-0.5 rounded-full text-xs bg-amber-100 text-amber-700">معلق</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- ===== STAFF TAB ===== --}}
    @elseif($tab === 'staff')
    <div class="p-4">
        <div class="flex flex-wrap gap-3 items-end justify-between">
            <form method="GET" class="flex flex-wrap gap-3 items-end">
                <input type="hidden" name="tab" value="staff">
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
            <div class="flex gap-2">
                <a href="{{ route('reports.staff.pdf', ['from' => $from, 'to' => $to]) }}"
                   class="inline-flex items-center gap-1.5 px-3 py-2 bg-red-600 text-white text-xs rounded-lg hover:bg-red-700 transition font-medium">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    تصدير PDF
                </a>
                <a href="{{ route('reports.staff.excel', ['from' => $from, 'to' => $to]) }}"
                   class="inline-flex items-center gap-1.5 px-3 py-2 bg-green-600 text-white text-xs rounded-lg hover:bg-green-700 transition font-medium">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Excel
                </a>
            </div>
        </div>
    </div>

    {{-- Summary Row --}}
    <div class="grid grid-cols-3 gap-3 mx-4 mb-4">
        <div class="bg-gray-50 rounded-xl border border-gray-100 p-4 text-center">
            <div class="text-2xl font-bold" style="color:#0F4C75">{{ $staffData->sum('checkins') }}</div>
            <div class="text-xs text-gray-500 mt-0.5">إجمالي الحجوزات</div>
        </div>
        <div class="bg-gray-50 rounded-xl border border-gray-100 p-4 text-center">
            <div class="text-2xl font-bold text-green-600">{{ number_format($staffData->sum('revenue'), 0) }}</div>
            <div class="text-xs text-gray-500 mt-0.5">إجمالي المستلم (ر.ي)</div>
        </div>
        <div class="bg-gray-50 rounded-xl border border-gray-100 p-4 text-center">
            <div class="text-2xl font-bold text-blue-600">{{ $staffData->where('checkins', '>', 0)->count() }}</div>
            <div class="text-xs text-gray-500 mt-0.5">موظف نشط في الفترة</div>
        </div>
    </div>

    {{-- Per-employee expandable cards --}}
    <div class="mx-4 mb-4 space-y-3">
    @forelse($staffData->sortByDesc('checkins') as $row)
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden" x-data="{ open: false }">
        <div class="px-5 py-4 flex items-center justify-between cursor-pointer select-none hover:bg-gray-50 transition"
             @click="open = !open">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full flex items-center justify-center text-white font-bold text-sm flex-shrink-0"
                     style="background:#0F4C75;">
                    {{ mb_substr($row['user']->name, 0, 1) }}
                </div>
                <div>
                    <div class="font-semibold text-gray-800">{{ $row['user']->name }}</div>
                    <div class="text-xs text-gray-400 flex items-center gap-1 mt-0.5">
                        @if($row['user']->employee_id)
                            <span>{{ $row['user']->employee_id }}</span>
                            <span>•</span>
                        @endif
                        @if($row['user']->roles->first())
                            <span style="color:#0F4C75;">{{ $row['user']->roles->first()->name }}</span>
                        @endif
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-6">
                <div class="text-center min-w-[60px]">
                    <div class="text-xl font-bold text-gray-800">{{ $row['checkins'] }}</div>
                    <div class="text-xs text-gray-400">حجز</div>
                </div>
                <div class="text-center min-w-[60px]">
                    <div class="text-xl font-bold text-indigo-600">{{ $row['checked_out'] }}</div>
                    <div class="text-xs text-gray-400">مغادرة</div>
                </div>
                <div class="text-center min-w-[100px]">
                    <div class="text-xl font-bold text-green-600">{{ number_format($row['revenue'], 0) }}</div>
                    <div class="text-xs text-gray-400">ر.ي مستلم</div>
                </div>
                <svg class="w-5 h-5 text-gray-400 transition-transform duration-200 flex-shrink-0"
                     :class="{ 'rotate-180': open }"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </div>
        </div>
        <div x-show="open" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
             class="border-t border-gray-100">
            @if($row['reservations']->isEmpty())
            <div class="px-5 py-8 text-center text-sm text-gray-400">لا توجد حجوزات في هذه الفترة</div>
            @else
            <div class="overflow-x-auto">
                <table class="w-full text-xs">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2.5 text-right text-gray-500 font-medium whitespace-nowrap">#</th>
                            <th class="px-4 py-2.5 text-right text-gray-500 font-medium whitespace-nowrap">الغرفة</th>
                            <th class="px-4 py-2.5 text-right text-gray-500 font-medium whitespace-nowrap">النوع</th>
                            <th class="px-4 py-2.5 text-right text-gray-500 font-medium whitespace-nowrap">النزيل</th>
                            <th class="px-4 py-2.5 text-right text-gray-500 font-medium whitespace-nowrap">تاريخ الدخول</th>
                            <th class="px-4 py-2.5 text-right text-gray-500 font-medium whitespace-nowrap">تاريخ الخروج</th>
                            <th class="px-4 py-2.5 text-right text-gray-500 font-medium whitespace-nowrap">إجمالي الحجز</th>
                            <th class="px-4 py-2.5 text-right text-gray-500 font-medium whitespace-nowrap">المدفوع</th>
                            <th class="px-4 py-2.5 text-right text-gray-500 font-medium whitespace-nowrap">حالة الدفع</th>
                            <th class="px-4 py-2.5 text-right text-gray-500 font-medium whitespace-nowrap">الحالة</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($row['reservations'] as $res)
                        @php
                            $statusInfo = match($res->status) {
                                'checked_in'  => ['label' => 'مقيم',   'class' => 'bg-green-100 text-green-700'],
                                'checked_out' => ['label' => 'غادر',   'class' => 'bg-gray-100 text-gray-600'],
                                default       => ['label' => $res->status, 'class' => 'bg-gray-100 text-gray-600'],
                            };
                            $payInfo = match($res->payment_status ?? 'unpaid') {
                                'paid'     => ['label' => 'مدفوع',     'class' => 'bg-green-100 text-green-700'],
                                'partial'  => ['label' => 'جزئي',      'class' => 'bg-amber-100 text-amber-700'],
                                'deferred' => ['label' => 'مؤجل',      'class' => 'bg-purple-100 text-purple-700'],
                                default    => ['label' => 'غير مدفوع', 'class' => 'bg-red-100 text-red-700'],
                            };
                            $paidAmount = $res->payments->sum('amount');
                        @endphp
                        <tr class="hover:bg-blue-50 transition">
                            <td class="px-4 py-2.5 text-gray-400">#{{ $res->id }}</td>
                            <td class="px-4 py-2.5 font-bold whitespace-nowrap" style="color:#0F4C75">{{ $res->display_room_number }}</td>
                            <td class="px-4 py-2.5 text-gray-500 whitespace-nowrap">{{ $res->room?->roomType?->name ?? '—' }}</td>
                            <td class="px-4 py-2.5 font-medium text-gray-800 whitespace-nowrap">
                                <a href="{{ route('reservations.show', $res) }}" class="hover:underline hover:text-blue-600">
                                    {{ $res->guest?->full_name ?? '—' }}
                                </a>
                            </td>
                            <td class="px-4 py-2.5 text-gray-600 whitespace-nowrap">{{ $res->check_in_date?->format('d/m/Y') ?? '—' }}</td>
                            <td class="px-4 py-2.5 text-gray-600 whitespace-nowrap">{{ $res->check_out_date?->format('d/m/Y') ?? '—' }}</td>
                            <td class="px-4 py-2.5 font-semibold text-gray-800 whitespace-nowrap">{{ number_format($res->total_amount, 0) }}</td>
                            <td class="px-4 py-2.5 font-semibold text-green-700 whitespace-nowrap">{{ number_format($paidAmount, 0) }}</td>
                            <td class="px-4 py-2.5 whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded-full font-medium text-xs {{ $payInfo['class'] }}">{{ $payInfo['label'] }}</span>
                            </td>
                            <td class="px-4 py-2.5 whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded-full font-medium text-xs {{ $statusInfo['class'] }}">{{ $statusInfo['label'] }}</span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-gray-50 border-t border-gray-200">
                        <tr>
                            <td colspan="6" class="px-4 py-2.5 text-right text-gray-500 font-semibold text-xs">الإجمالي</td>
                            <td class="px-4 py-2.5 font-bold text-gray-800 text-xs whitespace-nowrap">{{ number_format($row['reservations']->sum('total_amount'), 0) }} ر.ي</td>
                            <td class="px-4 py-2.5 font-bold text-green-700 text-xs whitespace-nowrap">{{ number_format($row['revenue'], 0) }} ر.ي</td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            @endif
        </div>
    </div>
    @empty
    <div class="bg-white rounded-xl border border-gray-100 py-14 text-center text-gray-400">
        <svg class="w-10 h-10 mx-auto mb-3 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
        </svg>
        <p class="text-sm">لا يوجد موظفون نشطون</p>
    </div>
    @endforelse
    </div>
    @endif

</div>

</div>
@endsection
