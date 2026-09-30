@extends('layouts.app')
@section('title', 'تقرير الأرباح والخسائر')
@section('page-title', 'تقرير الأرباح والخسائر')

@section('content')
<div dir="rtl">

{{-- Filter & Export --}}
<div class="ui-card p-4 mb-5">
    {{-- تصفية سريعة: يوم / أسبوع / شهر / سنة --}}
    <div class="flex flex-wrap gap-2 mb-4">
        @php
            $presets = [
                'today' => 'اليوم',
                'week'  => 'هذا الأسبوع',
                'month' => 'هذا الشهر',
                'year'  => 'هذه السنة',
            ];
        @endphp
        @foreach($presets as $key => $label)
        <a href="{{ route('reports.profitLoss', ['preset' => $key]) }}"
           class="px-4 py-2 rounded-lg text-sm font-semibold transition
                  {{ ($preset ?? '') === $key ? 'bg-blue-600 text-white' : 'border border-gray-200 text-gray-600 hover:bg-gray-50' }}">
            {{ $label }}
        </a>
        @endforeach
        <a href="{{ route('reports.profitLoss', ['from' => now()->subMonth()->startOfMonth()->toDateString(), 'to' => now()->subMonth()->endOfMonth()->toDateString()]) }}"
           class="px-4 py-2 rounded-lg text-sm font-semibold transition border border-gray-200 text-gray-600 hover:bg-gray-50">
            الشهر الماضي
        </a>
    </div>

    <div class="flex flex-wrap items-end gap-3 justify-between">
        <form method="GET" class="flex flex-wrap items-end gap-3">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">من تاريخ (تصفية مخصَّصة)</label>
                <input type="date" name="from" value="{{ $from }}"
                       class="border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none focus:border-blue-400">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">إلى تاريخ</label>
                <input type="date" name="to" value="{{ $to }}"
                       class="border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none focus:border-blue-400">
            </div>
            <button type="submit" class="px-4 py-2 text-white rounded-lg text-sm bg-blue-600 hover:bg-blue-700">عرض</button>
        </form>

        <div class="flex gap-2">
            <button onclick="window.print()" class="px-3 py-2 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50 flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                طباعة
            </button>
            <a href="javascript:;" onclick="exportToExcel()" class="px-3 py-2 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50 flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Excel
            </a>
        </div>
    </div>
</div>

{{-- شفافية: إجمالي الإيرادات قبل الاسترجاعات، والاسترجاعات، وأي مدفوعات بعملة أجنبية --}}
@if($totalRefunds > 0 || $foreignRevenue->isNotEmpty())
<div class="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-5 text-sm space-y-1.5">
    <p class="text-gray-500">إجمالي المُستلَم (قبل الاسترجاعات): <span class="font-semibold text-gray-700">{{ number_format($totalRevenueGross, 0) }} ر.ي</span></p>
    @if($totalRefunds > 0)
    <p class="text-red-700">الاسترجاعات (مخصومة من الإيراد أدناه): <span class="font-bold">- {{ number_format($totalRefunds, 0) }} ر.ي</span></p>
    @endif
    @if($foreignRevenue->isNotEmpty())
    <p class="text-amber-800">
        مدفوعات بعملة أجنبية (غير محتسبة في الأرقام أدناه — لا سعر صرف موحَّد):
        @foreach($foreignRevenue as $fr)
        <span class="font-semibold">{{ number_format($fr->total, 0) }} {{ $fr->currency }}</span> ({{ $fr->count }} دفعة)@if(!$loop->last)، @endif
        @endforeach
    </p>
    @endif
</div>
@endif

{{-- P&L Summary with KPIs --}}
@php
    $margin = $totalRevenue > 0 ? ($netProfit / $totalRevenue) * 100 : 0;
    $expenseRatio = $totalRevenue > 0 ? ($totalExpenses / $totalRevenue) * 100 : 0;
@endphp

@php $days = \Carbon\Carbon::parse($from)->diffInDays(\Carbon\Carbon::parse($to)) + 1; @endphp
@php $coverage = $totalExpenses > 0 ? ($totalRevenue / $totalExpenses) : 0; @endphp

{{-- شريط واحد تصطفّ أرقامه، بدل خمس بطاقات ملوّنة متباعدة --}}
<dl class="ui-stats">
    <div class="ui-stat">
        <dt>إجمالي الإيرادات</dt>
        <dd class="num--pos">{{ number_format($totalRevenue, 0) }} <span class="unit">ر.ي</span></dd>
    </div>
    <div class="ui-stat">
        <dt>إجمالي المصروفات</dt>
        <dd class="num--neg">{{ number_format($totalExpenses, 0) }}</dd>
        <div class="ui-hint">{{ number_format($expenseRatio, 1) }}% من الإيراد</div>
    </div>
    <div class="ui-stat">
        <dt>{{ $netProfit >= 0 ? 'صافي الربح' : 'صافي الخسارة' }}</dt>
        {{-- السالب بين قوسين كما في الدفاتر: الإشارة وحدها تُفقد في المسح السريع --}}
        <dd style="color:{{ $netProfit >= 0 ? 'var(--ui-accent)' : 'var(--ui-neg)' }};">
            {{ $netProfit < 0 ? '(' . number_format(abs($netProfit), 0) . ')' : number_format($netProfit, 0) }}
        </dd>
        <div class="ui-hint">{{ number_format($margin, 1) }}% هامش</div>
    </div>
    <div class="ui-stat">
        <dt>متوسط يومي</dt>
        <dd>{{ number_format($totalRevenue / $days, 0) }}</dd>
        <div class="ui-hint">إيراد يومي</div>
    </div>
    <div class="ui-stat">
        <dt>نسبة التغطية</dt>
        <dd>{{ number_format($coverage, 2) }}x</dd>
        <div class="ui-hint">الإيراد يغطي المصروفات</div>
    </div>
</dl>

{{--
    التوزيع: أعمدة مرتّبة تنازلياً بلون واحد، لا دائرتان ملوّنتان.
    المطلوب مقارنة مقادير، والعين تقارن الأطوال ولا تقارن زوايا القطاعات. والرسم
    كان يأتي من chart.js عبر CDN — فإن انقطع الإنترنت عن الفندق بقي مكانه فارغاً
    وفيه أهمّ ما في التقرير. هذه الأعمدة تُرسم من الخادم فتظهر دائماً.
--}}
@php
    $revMax = max($revenueByMethod->pluck('total')->all() ?: [1]);
    $expMax = max($expensesByCategory->pluck('total')->all() ?: [1]);
@endphp
<div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-5">
    <div class="ui-card">
        <div class="ui-panel-head"><span class="ui-panel-title">توزيع الإيرادات حسب الطريقة</span></div>
        <div class="ui-panel-body">
            @forelse($revenueByMethod->sortByDesc('total') as $row)
            @php $pct = $revMax > 0 ? round($row->total / $revMax * 100) : 0; @endphp
            <div style="margin-bottom:.5rem;">
                <div style="display:flex;align-items:baseline;gap:.5rem;font-size:.75rem;margin-bottom:.1875rem;">
                    <span>{{ match($row->method) {'cash'=>'نقداً','bank_transfer'=>'تحويل بنكي','pos'=>'POS',default=>$row->method} }}</span>
                    <span class="num" style="margin-inline-start:auto;font-weight:700;">{{ number_format($row->total, 0) }}</span>
                </div>
                <div style="height:.375rem;border-radius:999px;background:var(--ui-line-soft);overflow:hidden;">
                    <div style="height:100%;width:{{ $pct }}%;border-radius:999px;background:var(--ui-pos);"></div>
                </div>
            </div>
            @empty
            <div class="ui-empty">لا إيرادات في الفترة</div>
            @endforelse
        </div>
    </div>

    <div class="ui-card">
        <div class="ui-panel-head"><span class="ui-panel-title">توزيع المصروفات حسب الفئة</span></div>
        <div class="ui-panel-body">
            @forelse($expensesByCategory->sortByDesc('total') as $row)
            @php $pct = $expMax > 0 ? round($row->total / $expMax * 100) : 0; @endphp
            <div style="margin-bottom:.5rem;">
                <div style="display:flex;align-items:baseline;gap:.5rem;font-size:.75rem;margin-bottom:.1875rem;">
                    <span>{{ \App\Models\Expense::categoryLabel($row->category) }}</span>
                    <span class="num" style="margin-inline-start:auto;font-weight:700;">{{ number_format($row->total, 0) }}</span>
                </div>
                <div style="height:.375rem;border-radius:999px;background:var(--ui-line-soft);overflow:hidden;">
                    <div style="height:100%;width:{{ $pct }}%;border-radius:999px;background:var(--ui-neg);"></div>
                </div>
            </div>
            @empty
            <div class="ui-empty">لا مصروفات في الفترة</div>
            @endforelse
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-5">
    {{-- Revenue breakdown table --}}
    <div class="ui-card">
        <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-semibold text-gray-700 text-sm">تفصيل الإيرادات</h3>
            <span class="text-sm font-bold text-green-700">{{ number_format($totalRevenue, 0) }} ر.ي</span>
        </div>
        <table class="w-full text-sm ui-dense">
            <thead class="ui-thead">
                <tr>
                    <th class="px-4 py-2.5 text-right text-xs font-medium text-gray-500">المصدر</th>
                    <th class="px-4 py-2.5 text-right text-xs font-medium text-gray-500">المبلغ (ر.ي)</th>
                    <th class="px-4 py-2.5 text-right text-xs font-medium text-gray-500">النسبة</th>
                </tr>
            </thead>
            <tbody class="ui-divide">
                @foreach($revenueByMethod as $row)
                <tr>
                    <td class="px-4 py-2.5 text-gray-700">
                        {{ match($row->method) {'cash'=>'نقداً','bank_transfer'=>'تحويل بنكي','pos'=>'POS',default=>$row->method} }}
                        <span class="text-xs text-gray-400">({{ $row->count }} دفعة)</span>
                    </td>
                    <td class="px-4 py-2.5 font-semibold text-green-700">{{ number_format($row->total, 0) }}</td>
                    <td class="px-4 py-2.5 text-gray-500">
                        {{ $totalRevenue > 0 ? number_format(($row->total / $totalRevenue) * 100, 1) : 0 }}%
                    </td>
                </tr>
                @endforeach
                @if($revenueByMethod->isEmpty())
                <tr><td colspan="3" class="px-4 py-6 text-center text-gray-400">لا توجد إيرادات</td></tr>
                @endif
            </tbody>
        </table>
    </div>

    {{-- Expense breakdown table --}}
    <div class="ui-card">
        <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-semibold text-gray-700 text-sm">تفصيل المصروفات</h3>
            <span class="text-sm font-bold text-red-600">{{ number_format($totalExpenses, 0) }} ر.ي</span>
        </div>
        <table class="w-full text-sm ui-dense">
            <thead class="ui-thead">
                <tr>
                    <th class="px-4 py-2.5 text-right text-xs font-medium text-gray-500">الفئة</th>
                    <th class="px-4 py-2.5 text-right text-xs font-medium text-gray-500">المبلغ (ر.ي)</th>
                    <th class="px-4 py-2.5 text-right text-xs font-medium text-gray-500">النسبة من الإيرادات</th>
                </tr>
            </thead>
            <tbody class="ui-divide">
                @foreach($expensesByCategory as $row)
                <tr>
                    <td class="px-4 py-2.5 text-gray-700">
                        {{ \App\Models\Expense::categoryLabel($row->category) }}
                        <span class="text-xs text-gray-400">({{ $row->count }})</span>
                    </td>
                    <td class="px-4 py-2.5 font-semibold text-red-600">{{ number_format($row->total, 0) }}</td>
                    <td class="px-4 py-2.5 text-gray-500">
                        {{ $totalRevenue > 0 ? number_format(($row->total / $totalRevenue) * 100, 1) : '—' }}%
                    </td>
                </tr>
                @endforeach
                @if($expensesByCategory->isEmpty())
                <tr><td colspan="3" class="px-4 py-6 text-center text-gray-400">لا توجد مصروفات</td></tr>
                @endif
            </tbody>
        </table>
    </div>
</div>

{{-- مَن استلم الإيراد: مسؤولية كل موظف عن النقدية التي مرّت بيده --}}
@if($revenueByReceiver->isNotEmpty())
<div class="ui-card mb-5">
    <div class="px-5 py-3 border-b border-gray-100">
        <h3 class="font-semibold text-gray-700 text-sm">الإيراد حسب مَن استلمه</h3>
        <p class="text-xs text-gray-400 mt-0.5">ما قبضه كل موظف خلال الفترة — وكم منه نقداً بيده</p>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm ui-dense">
            <thead class="ui-thead">
                <tr>
                    <th class="px-4 py-2.5 text-right text-xs font-medium text-gray-500">الموظف المستلم</th>
                    <th class="px-4 py-2.5 text-right text-xs font-medium text-gray-500">عدد الدفعات</th>
                    <th class="px-4 py-2.5 text-right text-xs font-medium text-gray-500">الإجمالي (ر.ي)</th>
                    <th class="px-4 py-2.5 text-right text-xs font-medium text-gray-500">منه نقداً</th>
                    <th class="px-4 py-2.5 text-right text-xs font-medium text-gray-500">النسبة</th>
                </tr>
            </thead>
            <tbody class="ui-divide">
                @foreach($revenueByReceiver as $row)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-2.5 font-semibold text-gray-800">{{ $row->name }}</td>
                    <td class="px-4 py-2.5 text-gray-500">{{ $row->count }}</td>
                    <td class="px-4 py-2.5 font-semibold text-green-700">{{ number_format($row->total, 0) }}</td>
                    <td class="px-4 py-2.5 text-amber-700">{{ number_format($row->cash, 0) }}</td>
                    <td class="px-4 py-2.5 text-gray-500">
                        {{ $totalRevenueGross > 0 ? number_format(($row->total / $totalRevenueGross) * 100, 1) : 0 }}%
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- تفصيل كل دفعة: ممّن، ولأي غرفة/حجز، ومَن استلمها وفي أي وردية --}}
<div class="ui-card mb-5">
    <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between flex-wrap gap-2">
        <div>
            <h3 class="font-semibold text-gray-700 text-sm">تفاصيل الإيرادات (كل دفعة)</h3>
            <p class="text-xs text-gray-400 mt-0.5">مصدر كل مبلغ: ممّن قُبض، ولأي حجز وغرفة، ومَن استلمه وفي أي وردية</p>
        </div>
        <span class="text-xs text-gray-500">{{ $revenueDetails->count() }} دفعة معروضة</span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm ui-dense">
            <thead class="ui-thead">
                <tr>
                    <th class="px-4 py-2.5 text-right text-xs font-medium text-gray-500">التاريخ</th>
                    <th class="px-4 py-2.5 text-right text-xs font-medium text-gray-500">من (الدافع)</th>
                    <th class="px-4 py-2.5 text-right text-xs font-medium text-gray-500">الحجز / الغرفة</th>
                    <th class="px-4 py-2.5 text-right text-xs font-medium text-gray-500">المبلغ (ر.ي)</th>
                    <th class="px-4 py-2.5 text-right text-xs font-medium text-gray-500">طريقة الدفع</th>
                    <th class="px-4 py-2.5 text-right text-xs font-medium text-gray-500">مرجع التحويل</th>
                    <th class="px-4 py-2.5 text-right text-xs font-medium text-gray-500">استلمها</th>
                    <th class="px-4 py-2.5 text-right text-xs font-medium text-gray-500">الوردية</th>
                    <th class="px-4 py-2.5 text-right text-xs font-medium text-gray-500">ملاحظات</th>
                </tr>
            </thead>
            <tbody class="ui-divide">
                @forelse($revenueDetails as $p)
                @php $res = $p->reservation; @endphp
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-2.5 whitespace-nowrap text-gray-600">{{ $p->payment_date?->format('d/m/Y H:i') ?? '—' }}</td>
                    <td class="px-4 py-2.5 text-gray-800">
                        {{-- الدافع قد يكون غير النزيل (قريب أو شركة)، فيُسجَّل اسمه في الدفعة --}}
                        {{ $p->paid_by_name ?: ($res?->guest?->full_name ?? '—') }}
                        @if($p->paid_by_name && $res?->guest && $p->paid_by_name !== $res->guest->full_name)
                        <div class="text-xs text-gray-400">عن النزيل: {{ $res->guest->full_name }}</div>
                        @endif
                    </td>
                    <td class="px-4 py-2.5 text-gray-600 text-xs">
                        @if($res)
                        <a href="{{ route('reservations.show', $res) }}" class="text-blue-600 hover:underline">#{{ $res->id }}</a>
                        — غرفة {{ $res->display_room_number }}
                        @else — @endif
                    </td>
                    <td class="px-4 py-2.5 font-semibold text-green-700">{{ number_format((float) $p->amount, 0) }}</td>
                    <td class="px-4 py-2.5 text-gray-600">{{ match($p->method) {'cash'=>'نقداً','bank_transfer'=>'تحويل بنكي','pos'=>'POS',default=>$p->method} }}</td>
                    <td class="px-4 py-2.5 text-gray-500 text-xs" dir="ltr">{{ $p->bank_transfer_ref ?: '—' }}</td>
                    <td class="px-4 py-2.5 text-gray-700">{{ $p->receivedBy?->name ?? '—' }}</td>
                    <td class="px-4 py-2.5 text-gray-500 text-xs">
                        {{ $p->shift ? ($p->shift->user?->name ?? 'وردية #' . $p->shift->id) : 'بلا وردية' }}
                    </td>
                    <td class="px-4 py-2.5 text-gray-400 text-xs">{{ $p->notes ?: '—' }}</td>
                </tr>
                @empty
                <tr><td colspan="9" class="px-4 py-8 text-center text-gray-400">لا توجد دفعات خلال هذه الفترة</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Monthly trend --}}
@if($monthlyTrend->isNotEmpty())
<div class="ui-card p-5 mb-5">
    <h3 class="font-semibold text-gray-700 text-sm mb-4">الاتجاه الشهري</h3>
    <div class="overflow-x-auto">
        <table class="w-full text-sm ui-dense">
            <thead class="ui-thead">
                <tr>
                    <th class="px-4 py-2.5 text-right text-xs font-medium text-gray-500">الشهر</th>
                    <th class="px-4 py-2.5 text-right text-xs font-medium text-gray-500">الإيرادات</th>
                    <th class="px-4 py-2.5 text-right text-xs font-medium text-gray-500">المصروفات</th>
                    <th class="px-4 py-2.5 text-right text-xs font-medium text-gray-500">الربح/الخسارة</th>
                    <th class="px-4 py-2.5 text-right text-xs font-medium text-gray-500">الهامش</th>
                </tr>
            </thead>
            <tbody class="ui-divide">
                @foreach($monthlyTrend as $row)
                @php $monthNet = $row->revenue - $row->expenses; @endphp
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-2.5 font-medium text-gray-700">{{ $row->month_label }}</td>
                    <td class="px-4 py-2.5 text-green-700">{{ number_format($row->revenue, 0) }}</td>
                    <td class="px-4 py-2.5 text-red-600">{{ number_format($row->expenses, 0) }}</td>
                    <td class="px-4 py-2.5 font-bold {{ $monthNet >= 0 ? 'text-blue-700' : 'text-red-600' }}">
                        {{ number_format($monthNet, 0) }}
                    </td>
                    <td class="px-4 py-2.5 text-gray-500">
                        {{ $row->revenue > 0 ? number_format(($monthNet / $row->revenue) * 100, 1) . '%' : '—' }}
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot class="bg-gray-50 font-bold">
                <tr>
                    <td class="px-4 py-3 text-gray-700">الإجمالي</td>
                    <td class="px-4 py-3 text-green-700">{{ number_format($totalRevenue, 0) }}</td>
                    <td class="px-4 py-3 text-red-600">{{ number_format($totalExpenses, 0) }}</td>
                    <td class="px-4 py-3 {{ $netProfit >= 0 ? 'text-blue-700' : 'text-red-600' }}">{{ number_format($netProfit, 0) }}</td>
                    <td class="px-4 py-3 text-gray-500">{{ $totalRevenue > 0 ? number_format(($netProfit / $totalRevenue) * 100, 1) . '%' : '—' }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endif

</div>

<style media="print">
    .btn-group, form, [onclick*="export"], [onclick*="print"] { display: none !important; }
    body { background: white; }
    .rounded-xl { page-break-inside: avoid; }
</style>

@endsection

@push('scripts')
<script>
function exportToExcel() {
    const table = document.body.innerHTML;
    const link = document.createElement('a');
    link.href = 'data:application/vnd.ms-excel,' + encodeURIComponent(table);
    link.download = 'profit-loss-{{ now()->format("d-m-Y") }}.xls';
    link.click();
}
</script>
@endpush
