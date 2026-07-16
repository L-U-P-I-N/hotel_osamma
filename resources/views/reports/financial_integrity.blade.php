@extends('layouts.app')
@section('title', 'فحص تكامل البيانات المالية')
@section('page-title', 'فحص تكامل البيانات المالية')

@section('content')
<div class="space-y-5" dir="rtl" x-data="{ open: {} }">

    {{-- تنبيه: قراءة فقط --}}
    <div class="rounded-xl p-4 border bg-blue-50 border-blue-200 flex items-start gap-3">
        <svg class="w-5 h-5 text-blue-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <div class="text-sm text-blue-800">
            <p class="font-semibold mb-1">هذا التقرير للقراءة فقط — لا يُعدّل أو يحذف أي بيانات.</p>
            <p class="text-blue-600 text-xs leading-relaxed">يكتشف السجلات اليتيمة (بلا وردية) والمنسوبة لوردية بتاريخ مختلف، والمجاميع المحفوظة غير المحدَّثة. لتصحيح ما يظهر هنا استخدم أدوات النقل الموجودة في شاشة "الورديات" (نقل مستلمة/سحب إلى وردية محدَّدة)، أو أعد تنفيذ العملية المرتبطة لإعادة احتساب المجموع.</p>
        </div>
    </div>

    {{-- الملخص --}}
    <div class="rounded-xl p-5 border {{ $totalIssues > 0 ? 'bg-amber-50 border-amber-200' : 'bg-emerald-50 border-emerald-200' }}">
        <div class="text-xs {{ $totalIssues > 0 ? 'text-amber-600' : 'text-emerald-600' }}">إجمالي الحالات المكتشفة</div>
        <div class="text-3xl font-bold mt-1 {{ $totalIssues > 0 ? 'text-amber-700' : 'text-emerald-700' }}">{{ $totalIssues }}</div>
        @if($totalIssues === 0)
        <div class="text-xs text-emerald-500 mt-1">✓ لا توجد حالات — البيانات متّسقة</div>
        @endif
    </div>

    @php
        $sections = [
            'orphanPayments'   => ['title' => 'مستلمات بلا وردية',              'count' => $orphanPayments->count(),   'color' => 'red'],
            'orphanWithdrawals'=> ['title' => 'سحبيات بلا وردية',                'count' => $orphanWithdrawals->count(),'color' => 'red'],
            'orphanExpenses'   => ['title' => 'مصروفات نقدية بلا وردية',         'count' => $orphanExpenses->count(),   'color' => 'red'],
            'misPayments'      => ['title' => 'مستلمات في وردية بتاريخ مختلف',   'count' => $misattributedPayments->count(),   'color' => 'amber'],
            'misWithdrawals'   => ['title' => 'سحبيات في وردية بتاريخ مختلف',    'count' => $misattributedWithdrawals->count(),'color' => 'amber'],
            'misExpenses'      => ['title' => 'مصروفات في وردية بتاريخ مختلف',   'count' => $misattributedExpenses->count(),   'color' => 'amber'],
            'staleShifts'      => ['title' => 'ورديات بمجاميع غير محدَّثة',       'count' => $staleShifts->count(),      'color' => 'amber'],
            'corruptedShifts'  => ['title' => 'ورديات بحالة إقفال متضاربة',      'count' => $corruptedShifts->count(),  'color' => 'red'],
            'duplicateOpen'    => ['title' => 'موظفون لديهم أكثر من وردية مفتوحة','count' => $duplicateOpenShifts->count(),'color' => 'red'],
        ];
    @endphp

    @foreach($sections as $key => $s)
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <button @click="open['{{ $key }}'] = !open['{{ $key }}']"
                class="w-full flex items-center justify-between px-5 py-4 text-right">
            <span class="flex items-center gap-2.5">
                <span class="w-2.5 h-2.5 rounded-full {{ $s['count'] > 0 ? 'bg-'.$s['color'].'-500' : 'bg-gray-200' }}"></span>
                <span class="font-semibold text-gray-700 text-sm">{{ $s['title'] }}</span>
                <span class="px-2 py-0.5 rounded-full text-xs font-bold {{ $s['count'] > 0 ? 'bg-'.$s['color'].'-100 text-'.$s['color'].'-700' : 'bg-gray-100 text-gray-400' }}">{{ $s['count'] }}</span>
            </span>
            <svg :class="open['{{ $key }}'] ? 'rotate-180' : ''" class="w-4 h-4 text-gray-400 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>

        <div x-show="open['{{ $key }}']" x-cloak class="border-t border-gray-100">
            @if($s['count'] === 0)
            <div class="p-6 text-center text-gray-400 text-sm">لا توجد حالات ✓</div>
            @else

                @if($key === 'orphanPayments')
                <div class="overflow-x-auto"><table class="w-full text-sm">
                    <thead class="bg-gray-50"><tr>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">التاريخ</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">النزيل / الغرفة</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">المبلغ</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">استلمها</th>
                        <th class="px-4 py-2"></th>
                    </tr></thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($orphanPayments as $p)
                        <tr>
                            <td class="px-4 py-2 text-gray-500 text-xs whitespace-nowrap">{{ $p->payment_date?->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-2 text-xs text-gray-700">{{ $p->reservation?->guest?->full_name ?? '—' }} / {{ $p->reservation?->display_room_number ?? '—' }}</td>
                            <td class="px-4 py-2 font-semibold text-green-700 text-xs whitespace-nowrap">{{ number_format($p->amount, 0) }} {{ $p->currency }}</td>
                            <td class="px-4 py-2 text-xs text-gray-500">{{ $p->receivedBy?->name ?? '—' }}</td>
                            <td class="px-4 py-2"><a href="{{ route('shifts.index') }}" class="text-xs text-blue-600 underline">اذهب لشاشة الورديات</a></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table></div>

                @elseif($key === 'orphanWithdrawals')
                <div class="overflow-x-auto"><table class="w-full text-sm">
                    <thead class="bg-gray-50"><tr>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">التاريخ</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">المستلم</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">المبلغ</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">البيان</th>
                    </tr></thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($orphanWithdrawals as $w)
                        <tr>
                            <td class="px-4 py-2 text-gray-500 text-xs whitespace-nowrap">{{ $w->withdrawal_date?->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-2 text-xs text-gray-700">{{ $w->withdrawn_by_name }}</td>
                            <td class="px-4 py-2 font-semibold text-red-600 text-xs whitespace-nowrap">{{ number_format($w->amount, 0) }} {{ $w->currency }}</td>
                            <td class="px-4 py-2 text-xs text-gray-400">{{ $w->notes ?? '—' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table></div>

                @elseif($key === 'orphanExpenses')
                <div class="overflow-x-auto"><table class="w-full text-sm">
                    <thead class="bg-gray-50"><tr>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">التاريخ</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">المستلم</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">المبلغ</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">سجّله</th>
                        <th class="px-4 py-2"></th>
                    </tr></thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($orphanExpenses as $e)
                        <tr>
                            <td class="px-4 py-2 text-gray-500 text-xs whitespace-nowrap">{{ $e->expense_date?->format('d/m/Y') }}</td>
                            <td class="px-4 py-2 text-xs text-gray-700">{{ $e->recipient_name }}</td>
                            <td class="px-4 py-2 font-semibold text-red-600 text-xs whitespace-nowrap">{{ number_format($e->amount, 0) }} {{ $e->currency }}</td>
                            <td class="px-4 py-2 text-xs text-gray-500">{{ $e->paidBy?->name ?? '—' }}</td>
                            <td class="px-4 py-2"><a href="{{ route('expenses.edit', $e) }}" class="text-xs text-blue-600 underline">تعديل وربطه بوردية</a></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table></div>

                @elseif($key === 'misPayments')
                <div class="overflow-x-auto"><table class="w-full text-sm">
                    <thead class="bg-gray-50"><tr>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">تاريخ المستلمة</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">تاريخ ورديتها الحالية</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">النزيل</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">المبلغ</th>
                        <th class="px-4 py-2"></th>
                    </tr></thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($misattributedPayments as $p)
                        <tr>
                            <td class="px-4 py-2 text-gray-500 text-xs whitespace-nowrap">{{ $p->payment_date?->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-2 text-amber-600 text-xs whitespace-nowrap">{{ $p->shift->shift_date->format('d/m/Y') }} ({{ $p->shift->user?->name }})</td>
                            <td class="px-4 py-2 text-xs text-gray-700">{{ $p->reservation?->guest?->full_name ?? '—' }}</td>
                            <td class="px-4 py-2 font-semibold text-green-700 text-xs whitespace-nowrap">{{ number_format($p->amount, 0) }} {{ $p->currency }}</td>
                            <td class="px-4 py-2"><a href="{{ route('shifts.index') }}" class="text-xs text-blue-600 underline">نقل من شاشة الورديات</a></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table></div>

                @elseif($key === 'misWithdrawals')
                <div class="overflow-x-auto"><table class="w-full text-sm">
                    <thead class="bg-gray-50"><tr>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">تاريخ السحب</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">تاريخ ورديته الحالية</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">المستلم</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">المبلغ</th>
                        <th class="px-4 py-2"></th>
                    </tr></thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($misattributedWithdrawals as $w)
                        <tr>
                            <td class="px-4 py-2 text-gray-500 text-xs whitespace-nowrap">{{ $w->withdrawal_date?->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-2 text-amber-600 text-xs whitespace-nowrap">{{ $w->shift->shift_date->format('d/m/Y') }} ({{ $w->shift->user?->name }})</td>
                            <td class="px-4 py-2 text-xs text-gray-700">{{ $w->withdrawn_by_name }}</td>
                            <td class="px-4 py-2 font-semibold text-red-600 text-xs whitespace-nowrap">{{ number_format($w->amount, 0) }} {{ $w->currency }}</td>
                            <td class="px-4 py-2"><a href="{{ route('shifts.index') }}" class="text-xs text-blue-600 underline">نقل من شاشة الورديات</a></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table></div>

                @elseif($key === 'misExpenses')
                <div class="overflow-x-auto"><table class="w-full text-sm">
                    <thead class="bg-gray-50"><tr>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">تاريخ المصروف</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">تاريخ ورديته الحالية</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">المستلم</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">المبلغ</th>
                        <th class="px-4 py-2"></th>
                    </tr></thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($misattributedExpenses as $e)
                        <tr>
                            <td class="px-4 py-2 text-gray-500 text-xs whitespace-nowrap">{{ $e->expense_date?->format('d/m/Y') }}</td>
                            <td class="px-4 py-2 text-amber-600 text-xs whitespace-nowrap">{{ $e->shift->shift_date->format('d/m/Y') }} ({{ $e->shift->user?->name }})</td>
                            <td class="px-4 py-2 text-xs text-gray-700">{{ $e->recipient_name }}</td>
                            <td class="px-4 py-2 font-semibold text-red-600 text-xs whitespace-nowrap">{{ number_format($e->amount, 0) }} {{ $e->currency }}</td>
                            <td class="px-4 py-2"><a href="{{ route('expenses.edit', $e) }}" class="text-xs text-blue-600 underline">تعديل ونقله لورديته الصحيحة</a></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table></div>

                @elseif($key === 'staleShifts')
                <div class="overflow-x-auto"><table class="w-full text-sm">
                    <thead class="bg-gray-50"><tr>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">الوردية</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">الموظف</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">الفروقات</th>
                        <th class="px-4 py-2"></th>
                    </tr></thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($staleShifts as $row)
                        <tr>
                            <td class="px-4 py-2 text-gray-700 text-xs whitespace-nowrap">{{ $row['shift']->shift_date->format('d/m/Y') }} @if($row['shift']->is_closed)<span class="text-gray-400">(مقفلة)</span>@else<span class="text-emerald-600">(مفتوحة)</span>@endif</td>
                            <td class="px-4 py-2 text-xs text-gray-700">{{ $row['shift']->user?->name ?? '—' }}</td>
                            <td class="px-4 py-2 text-xs text-amber-700">
                                @foreach($row['diffs'] as $d)<div>{{ $d }}</div>@endforeach
                            </td>
                            <td class="px-4 py-2"><a href="{{ route('shifts.pdf', $row['shift']) }}" target="_blank" class="text-xs text-blue-600 underline">تصدير PDF للتحقق</a></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table></div>
                <p class="px-4 py-3 text-xs text-gray-400 border-t border-gray-50">يُصحَّح تلقائياً عند تنفيذ أي عملية نقل/تعديل على الوردية (تُعيد احتساب مجاميعها).</p>

                @elseif($key === 'corruptedShifts')
                <div class="overflow-x-auto"><table class="w-full text-sm">
                    <thead class="bg-gray-50"><tr>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">الوردية</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">الموظف</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">is_closed</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">closed_at</th>
                    </tr></thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($corruptedShifts as $cs)
                        <tr>
                            <td class="px-4 py-2 text-gray-700 text-xs whitespace-nowrap">#{{ $cs->id }} — {{ $cs->shift_date->format('d/m/Y') }}</td>
                            <td class="px-4 py-2 text-xs text-gray-700">{{ $cs->user?->name ?? '—' }}</td>
                            <td class="px-4 py-2 text-xs">{{ $cs->is_closed ? 'true' : 'false' }}</td>
                            <td class="px-4 py-2 text-xs">{{ $cs->closed_at?->format('d/m/Y H:i') ?? '—' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table></div>

                @elseif($key === 'duplicateOpen')
                <div class="overflow-x-auto"><table class="w-full text-sm">
                    <thead class="bg-gray-50"><tr>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">الوردية</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">الموظف</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">وقت الفتح</th>
                    </tr></thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($duplicateOpenShifts as $ds)
                        <tr>
                            <td class="px-4 py-2 text-gray-700 text-xs whitespace-nowrap">#{{ $ds->id }} — {{ $ds->shift_date->format('d/m/Y') }}</td>
                            <td class="px-4 py-2 text-xs text-gray-700">{{ $ds->user?->name ?? '—' }}</td>
                            <td class="px-4 py-2 text-xs text-gray-500">{{ $ds->started_at?->format('d/m/Y H:i') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table></div>
                <p class="px-4 py-3 text-xs text-gray-400 border-t border-gray-50">حالة غير طبيعية — النظام يمنع فتح وردية ثانية لنفس الموظف، فوجودها يعني تلاعباً مباشراً بقاعدة البيانات أو خللاً سابقاً. راجعها يدوياً قبل أي إجراء.</p>
                @endif

            @endif
        </div>
    </div>
    @endforeach

</div>
@endsection
