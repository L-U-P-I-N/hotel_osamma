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
    body { font-family: 'NotoNaskhArabic', sans-serif; font-size: 10px; direction: rtl; color: #1a1a1a; background: #fff; padding: 16px; }
    .header { text-align: center; border-bottom: 2px solid #0F4C75; padding-bottom: 10px; margin-bottom: 14px; }
    .header h1 { font-size: 16px; color: #0F4C75; font-weight: bold; }
    .header .sub { font-size: 9px; color: #555; margin-top: 4px; }
    table.data { width: 100%; border-collapse: collapse; font-size: 9px; margin-bottom: 14px; direction: rtl; }
    table.data thead tr { background: #0F4C75; color: #fff; }
    table.data thead th { padding: 5px 6px; font-weight: bold; border: 1px solid #0a3a5e; text-align: right; white-space: nowrap; }
    table.data tbody tr:nth-child(even) { background: #f4f8fc; }
    table.data tbody td { padding: 4px 6px; border: 1px solid #e0e0e0; text-align: right; white-space: nowrap; }
    table.data tbody td.ltr { text-align: left; direction: ltr; }
    .stat-box { display: inline-block; border: 1px solid #fecaca; background: #fef2f2; padding: 6px 16px; text-align: center; margin-bottom: 14px; }
    .stat-box .num { font-size: 18px; font-weight: bold; color: #dc2626; }
    .stat-box .lbl { font-size: 8px; color: #666; }
    .footer { margin-top: 12px; border-top: 1px solid #eee; padding-top: 6px; font-size: 8px; color: #aaa; text-align: right; }
</style>
</head>
<body>
<div class="header">
    <h1>تقرير الديون والمبالغ غير المحصّلة</h1>
</div>

<div class="stat-box">
    <div class="num">{{ number_format($totalDebt, 0) }}</div>
    <div class="lbl">إجمالي المبالغ غير المحصّلة (ر.ي)</div>
</div>

<br style="clear:both;">

@php
    $statusLabels = ['checked_in'=>'داخل', 'checked_out'=>'خرج'];
@endphp

<table class="data">
    <thead>
        <tr>
            <th>النزيل</th>
            <th>الغرفة</th>
            <th>الحالة</th>
            <th>الإجمالي</th>
            <th>المدفوع</th>
            <th>المتبقي</th>
            <th>تاريخ الخروج</th>
        </tr>
    </thead>
    <tbody>
        @forelse($reservations as $res)
        @php $balance = $res->total_amount - $res->paid_amount; @endphp
        <tr>
            <td>{{ $res->guest?->full_name ?? '—' }}</td>
            <td>{{ $res->room?->room_number ?? '—' }}</td>
            <td>{{ $statusLabels[$res->status] ?? $res->status }}</td>
            <td class="ltr">{{ number_format($res->total_amount, 0) }}</td>
            <td class="ltr">{{ number_format($res->paid_amount, 0) }}</td>
            <td class="ltr" style="font-weight:bold; color:#dc2626;">{{ number_format($balance, 0) }}</td>
            <td class="ltr">{{ $res->check_out_date?->format('Y-m-d') ?? '—' }}</td>
        </tr>
        @empty
        <tr><td colspan="7" style="text-align:center; padding:10px; color:#999;">لا توجد ديون مسجلة</td></tr>
        @endforelse
    </tbody>
</table>

<div class="footer">
    طُبع بتاريخ: {{ now()->format('d/m/Y H:i') }}
</div>
</body>
</html>
