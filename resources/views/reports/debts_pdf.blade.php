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
    .total-card { border: 2px solid #dc2626; border-radius: 6px; padding: 12px; margin-bottom: 16px; text-align: center; background: #fef2f2; }
    .total-card .num { font-size: 22px; font-weight: bold; color: #dc2626; }
    .total-card .lbl { font-size: 9px; color: #ef4444; margin-top: 2px; }
    table.data { width: 100%; border-collapse: collapse; font-size: 10px; direction: rtl; }
    table.data thead tr { background: #0F4C75; color: #fff; }
    table.data thead th { padding: 6px 8px; font-weight: bold; border: 1px solid #0a3a5e; text-align: right; white-space: nowrap; }
    table.data tbody tr:nth-child(even) { background: #f4f8fc; }
    table.data tbody td { padding: 5px 8px; border: 1px solid #e0e0e0; text-align: right; white-space: nowrap; }
    table.data tbody td.ltr { text-align: left; direction: ltr; }
    .total-row td { font-weight: bold; background: #fef2f2 !important; color: #dc2626; }
    .footer { margin-top: 12px; border-top: 1px solid #eee; padding-top: 6px; font-size: 8px; color: #aaa; text-align: right; }
</style>
</head>
<body>

<div class="header">
    <h1>تقرير الديون والمبالغ غير المحصّلة</h1>
    <div class="sub">تاريخ الطباعة: {{ now()->format('d/m/Y') }}</div>
</div>

<div class="total-card">
    <div class="num">{{ number_format($totalDebt, 2) }} ر.ي</div>
    <div class="lbl">{{ $reservations->count() }} حجز به رصيد متبقي</div>
</div>

@if($reservations->isEmpty())
<p style="text-align:center;color:#999;padding:20px;">لا توجد ديون مسجلة</p>
@else
<table class="data" dir="rtl">
    <thead>
        <tr>
            <th>النزيل</th>
            <th>الغرفة</th>
            <th>الحالة</th>
            <th>الإجمالي (ر.ي)</th>
            <th>المدفوع (ر.ي)</th>
            <th>المتبقي (ر.ي)</th>
            <th>تاريخ الدخول</th>
            <th>تاريخ الخروج</th>
        </tr>
    </thead>
    <tbody>
        @foreach($reservations as $res)
        @php $balance = $res->total_amount - $res->paid_amount; @endphp
        <tr>
            <td>{{ $res->guest?->full_name ?? '—' }}</td>
            <td style="font-weight:bold;text-align:center;">{{ $res->display_room_number }}</td>
            <td style="text-align:center;">{{ $res->status === 'checked_in' ? 'داخل' : 'خرج' }}</td>
            <td class="ltr">{{ number_format($res->total_amount, 0) }}</td>
            <td class="ltr" style="color:#16a34a;">{{ number_format($res->paid_amount, 0) }}</td>
            <td class="ltr" style="font-weight:bold;color:#dc2626;">{{ number_format($balance, 0) }}</td>
            <td class="ltr">{{ $res->check_in_date?->format('d/m/Y') ?? '—' }}</td>
            <td class="ltr">{{ $res->check_out_date?->format('d/m/Y') ?? '—' }}</td>
        </tr>
        @endforeach
        <tr class="total-row">
            <td colspan="5">إجمالي المبالغ غير المحصّلة</td>
            <td class="ltr">{{ number_format($totalDebt, 0) }}</td>
            <td></td>
            <td></td>
        </tr>
    </tbody>
</table>
@endif

<div class="footer">طُبع في: {{ now()->format('d/m/Y H:i') }}</div>

</body>
</html>
