<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<style>
    @font-face {
        font-family: 'NotoNaskhArabic';
        font-style: normal; font-weight: normal;
        src: url("{{ storage_path('fonts') }}/NotoNaskhArabic.ttf") format('truetype');
    }
    @font-face {
        font-family: 'NotoNaskhArabic';
        font-style: normal; font-weight: bold;
        src: url("{{ storage_path('fonts') }}/NotoNaskhArabic-Bold.ttf") format('truetype');
    }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'NotoNaskhArabic', sans-serif; font-size: 9px; direction: rtl; color: #1a1a1a; background: #fff; padding: 14px; }
    .header { text-align: center; border-bottom: 2px solid #0F4C75; padding-bottom: 8px; margin-bottom: 12px; }
    .header h1 { font-size: 15px; color: #0F4C75; font-weight: bold; }
    .header .sub { font-size: 8px; color: #555; margin-top: 3px; }
    table.data { width: 100%; border-collapse: collapse; font-size: 8.5px; direction: rtl; margin-bottom: 12px; }
    table.data thead tr { background: #0F4C75; color: #fff; }
    table.data thead th { padding: 5px 6px; font-weight: bold; border: 1px solid #0a3a5e; text-align: right; white-space: nowrap; }
    table.data tbody tr:nth-child(even) { background: #f4f8fc; }
    table.data tbody td { padding: 4px 6px; border: 1px solid #e0e0e0; text-align: right; }
    table.data tbody td.c { text-align: center; }
    .total-row td { font-weight: bold; background: #e8f0f7 !important; color: #0F4C75; }
    .badge-open  { color: #92400e; font-size: 7.5px; }
    .badge-close { color: #166534; font-size: 7.5px; }
    .footer { margin-top: 10px; border-top: 1px solid #eee; padding-top: 4px; font-size: 7.5px; color: #aaa; text-align: right; }
</style>
</head>
<body>

@php
    $selectedUser = $selectedUser ?? null;
    $allPeriods   = $allPeriods ?? false;
    $shiftTotals  = $shiftTotals ?? null;
@endphp

<div class="header">
    @include('partials.pdf-hotel-header-full')
    <h1>تقرير الورديات{{ $selectedUser ? ' — ' . $selectedUser->name : '' }}</h1>
    <div class="sub">
        @if($allPeriods)
            كل الفترات (تاريخ الورديات كاملاً)
        @else
            الفترة: {{ \Carbon\Carbon::parse($from)->format('d/m/Y') }} — {{ \Carbon\Carbon::parse($to)->format('d/m/Y') }}
        @endif
        @if(! $selectedUser) · كل الموظفين @endif
    </div>
</div>

@if($shiftTotals && $shiftTotals['count'] > 0)
<table class="data" dir="rtl" style="margin-bottom:10px;">
    <thead>
        <tr>
            <th>العجز التراكمي</th>
            <th>الصافي</th>
            <th>الاسترجاعات</th>
            <th>إجمالي السحبيات</th>
            <th>إجمالي المستلم</th>
            <th>عدد الورديات</th>
        </tr>
    </thead>
    <tbody>
        <tr style="font-weight:bold;background:#f4f8fc;">
            <td style="text-align:center;color:{{ $shiftTotals['deficit'] > 0 ? '#dc2626' : '#999' }};">
                {{ number_format($shiftTotals['deficit'], 0) }}
                @if($shiftTotals['deficit_count'] > 0)
                <div style="font-size:7px;font-weight:normal;color:#dc2626;">{{ $shiftTotals['deficit_count'] }} وردية بعجز</div>
                @endif
            </td>
            <td style="text-align:center;">{{ number_format($shiftTotals['net_yer'], 0) }}</td>
            <td style="text-align:center;color:#ea580c;">{{ number_format($shiftTotals['refunds_yer'], 0) }}</td>
            <td style="text-align:center;color:#dc2626;">{{ number_format($shiftTotals['withdrawals_yer'], 0) }}</td>
            <td style="text-align:center;color:#16a34a;">{{ number_format($shiftTotals['received_yer'], 0) }}</td>
            <td style="text-align:center;">
                {{ $shiftTotals['count'] }}
                <div style="font-size:7px;font-weight:normal;color:#888;">{{ $shiftTotals['closed'] }} مغلقة · {{ $shiftTotals['open'] }} مفتوحة</div>
            </td>
        </tr>
    </tbody>
</table>
@endif

@if($shifts->isEmpty())
<p style="text-align:center;color:#999;padding:20px;">لا توجد ورديات مطابقة</p>
@else
<table class="data" dir="rtl">
    <thead>
        <tr>
            <th>متبقي YER</th>
            <th>سحبيات YER</th>
            <th>مستلم YER</th>
            <th>عدد الدفعات</th>
            <th>الحالة</th>
            <th>نهاية</th>
            <th>بداية</th>
            <th>الموظف</th>
            <th>التاريخ</th>
        </tr>
    </thead>
    <tbody>
        @foreach($shifts as $shift)
        @php
            $netYER = $shift->net_balance_yer;
        @endphp
        <tr>
            <td style="font-weight:bold; color:{{ $netYER >= 0 ? '#16a34a' : '#dc2626' }};">{{ number_format($netYER, 0) }}</td>
            <td style="color:#dc2626;">{{ $shift->total_withdrawals_yer > 0 ? number_format($shift->total_withdrawals_yer, 0) : '—' }}</td>
            <td style="font-weight:bold; color:#0F4C75;">{{ number_format($shift->total_received_yer, 0) }}</td>
            <td class="c">{{ $shift->payments->count() }}</td>
            <td class="c">
                @if($shift->is_closed)
                <span class="badge-close">مغلقة</span>
                @else
                <span class="badge-open">مفتوحة</span>
                @endif
            </td>
            <td class="c">{{ $shift->ended_at?->format('H:i') ?? '—' }}</td>
            <td class="c">{{ $shift->started_at?->format('H:i') ?? '—' }}</td>
            <td style="font-weight:bold;">{{ $shift->user?->name ?? '—' }}</td>
            <td>{{ $shift->shift_date->format('d/m/Y') }}</td>
        </tr>
        @endforeach
        <tr class="total-row">
            <td>{{ number_format($shifts->sum('total_received_yer') - $shifts->sum('total_withdrawals_yer') - $shifts->sum('total_refunds_yer'), 0) }}</td>
            <td>{{ number_format($shifts->sum('total_withdrawals_yer'), 0) }}</td>
            <td>{{ number_format($shifts->sum('total_received_yer'), 0) }}</td>
            <td class="c">{{ $shifts->sum(fn($s) => $s->payments->count()) }}</td>
            <td colspan="5">الإجمالي</td>
        </tr>
    </tbody>
</table>
@endif

<div class="footer">طُبع في: {{ now()->format('d/m/Y H:i') }}</div>
</body>
</html>
