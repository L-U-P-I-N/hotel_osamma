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

<div class="header">
    <h1>تقرير الورديات</h1>
    <div class="sub">الفترة: {{ \Carbon\Carbon::parse($from)->format('d/m/Y') }} — {{ \Carbon\Carbon::parse($to)->format('d/m/Y') }}</div>
</div>

@if($shifts->isEmpty())
<p style="text-align:center;color:#999;padding:20px;">لا توجد ورديات في هذه الفترة</p>
@else
<table class="data" dir="rtl">
    <thead>
        <tr>
            <th>التاريخ</th>
            <th>الموظف</th>
            <th>بداية</th>
            <th>نهاية</th>
            <th>الحالة</th>
            <th>عدد الدفعات</th>
            <th>مستلم YER</th>
            <th>سحبيات YER</th>
            <th>صافي YER</th>
            <th>مستلم SAR</th>
            <th>صافي SAR</th>
            <th>مستلم USD</th>
            <th>صافي USD</th>
        </tr>
    </thead>
    <tbody>
        @foreach($shifts as $shift)
        @php
            $netYER = $shift->total_received_yer - $shift->total_withdrawals_yer;
            $netSAR = $shift->total_received_sar - $shift->total_withdrawals_sar;
            $netUSD = $shift->total_received_usd - $shift->total_withdrawals_usd;
        @endphp
        <tr>
            <td>{{ $shift->shift_date->format('d/m/Y') }}</td>
            <td style="font-weight:bold;">{{ $shift->user?->name ?? '—' }}</td>
            <td class="c">{{ $shift->started_at?->format('H:i') ?? '—' }}</td>
            <td class="c">{{ $shift->ended_at?->format('H:i') ?? '—' }}</td>
            <td class="c">
                @if($shift->is_closed)
                <span class="badge-close">مغلقة</span>
                @else
                <span class="badge-open">مفتوحة</span>
                @endif
            </td>
            <td class="c">{{ $shift->payments->count() }}</td>
            <td style="font-weight:bold; color:#0F4C75;">{{ number_format($shift->total_received_yer, 0) }}</td>
            <td style="color:#dc2626;">{{ $shift->total_withdrawals_yer > 0 ? number_format($shift->total_withdrawals_yer, 0) : '—' }}</td>
            <td style="font-weight:bold; color:{{ $netYER >= 0 ? '#16a34a' : '#dc2626' }};">{{ number_format($netYER, 0) }}</td>
            <td>{{ $shift->total_received_sar > 0 ? number_format($shift->total_received_sar, 0) : '—' }}</td>
            <td style="font-weight:bold; color:{{ $netSAR >= 0 ? '#16a34a' : '#dc2626' }};">{{ $shift->total_received_sar > 0 || $shift->total_withdrawals_sar > 0 ? number_format($netSAR, 0) : '—' }}</td>
            <td>{{ $shift->total_received_usd > 0 ? number_format($shift->total_received_usd, 2) : '—' }}</td>
            <td style="font-weight:bold; color:{{ $netUSD >= 0 ? '#16a34a' : '#dc2626' }};">{{ $shift->total_received_usd > 0 || $shift->total_withdrawals_usd > 0 ? number_format($netUSD, 2) : '—' }}</td>
        </tr>
        @endforeach
        <tr class="total-row">
            <td colspan="5">الإجمالي</td>
            <td class="c">{{ $shifts->sum(fn($s) => $s->payments->count()) }}</td>
            <td>{{ number_format($shifts->sum('total_received_yer'), 0) }}</td>
            <td>{{ number_format($shifts->sum('total_withdrawals_yer'), 0) }}</td>
            <td>{{ number_format($shifts->sum('total_received_yer') - $shifts->sum('total_withdrawals_yer'), 0) }}</td>
            <td>{{ number_format($shifts->sum('total_received_sar'), 0) }}</td>
            <td>{{ number_format($shifts->sum('total_received_sar') - $shifts->sum('total_withdrawals_sar'), 0) }}</td>
            <td>{{ number_format($shifts->sum('total_received_usd'), 2) }}</td>
            <td>{{ number_format($shifts->sum('total_received_usd') - $shifts->sum('total_withdrawals_usd'), 2) }}</td>
        </tr>
    </tbody>
</table>
@endif

<div class="footer">طُبع في: {{ now()->format('d/m/Y H:i') }}</div>
</body>
</html>
