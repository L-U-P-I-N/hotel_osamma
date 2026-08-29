<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<style>
    @font-face { font-family: 'NotoNaskhArabic'; font-style: normal; font-weight: normal; src: url("{{ storage_path('fonts') }}/NotoNaskhArabic.ttf") format('truetype'); }
    @font-face { font-family: 'NotoNaskhArabic'; font-style: normal; font-weight: bold;   src: url("{{ storage_path('fonts') }}/NotoNaskhArabic-Bold.ttf") format('truetype'); }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'NotoNaskhArabic', sans-serif; font-size: 13px; direction: rtl; color: #1a1a1a; background: #fff; padding: 16px; }
    .header { text-align: center; border-bottom: 2px solid #0F4C75; padding-bottom: 10px; margin-bottom: 14px; }
    .header h1 { font-size: 16px; color: #0F4C75; font-weight: bold; }
    .header .sub { font-size: 9px; color: #555; margin-top: 4px; }
    .buckets { display: table; width: 100%; margin-bottom: 14px; border-collapse: collapse; }
    .bucket { display: table-cell; padding: 8px; text-align: center; border: 1px solid #e0e0e0; }
    .bucket .num { font-size: 14px; font-weight: bold; }
    .bucket .lbl { font-size: 8px; color: #666; margin-top: 2px; }
    table.data { width: 100%; border-collapse: collapse; font-size: 13px; direction: rtl; }
    table.data thead tr { background: #0F4C75; color: #fff; }
    table.data thead th { padding: 5px 6px; font-weight: bold; border: 1px solid #0a3a5e; text-align: right; }
    table.data tbody tr:nth-child(even) { background: #f4f8fc; }
    table.data tbody td { padding: 4px 6px; border: 1px solid #e0e0e0; text-align: right; }
    .badge-current { background: #fef9c3; color: #854d0e; }
    .badge-30 { background: #ffedd5; color: #9a3412; }
    .badge-60 { background: #fee2e2; color: #991b1b; }
    .badge-90 { background: #fecaca; color: #7f1d1d; font-weight: bold; }
    .footer { margin-top: 12px; border-top: 1px solid #eee; padding-top: 6px; font-size: 8px; color: #aaa; text-align: right; }
</style>
</head>
<body>

<div class="header">
    @include('partials.pdf-hotel-header')
    <h1>تقرير أعمار الديون</h1>
    <div class="sub">تاريخ الطباعة: {{ now()->format('d/m/Y') }} — إجمالي الديون: {{ number_format($totalDebt, 0) }} ر.ي — {{ $reservations->count() }} حجز</div>
</div>

<div class="buckets">
    <div class="bucket" style="background:#fefce8;"><div class="num" style="color:#ca8a04;">{{ number_format($buckets['current']['total'], 0) }}</div><div class="lbl">جارية ≤30 يوم ({{ $buckets['current']['count'] }})</div></div>
    <div class="bucket" style="background:#fff7ed;"><div class="num" style="color:#ea580c;">{{ number_format($buckets['30_60']['total'], 0) }}</div><div class="lbl">31–60 يوم ({{ $buckets['30_60']['count'] }})</div></div>
    <div class="bucket" style="background:#fef2f2;"><div class="num" style="color:#dc2626;">{{ number_format($buckets['60_90']['total'], 0) }}</div><div class="lbl">61–90 يوم ({{ $buckets['60_90']['count'] }})</div></div>
    <div class="bucket" style="background:#fee2e2;"><div class="num" style="color:#7f1d1d;">{{ number_format($buckets['over_90']['total'], 0) }}</div><div class="lbl">أكثر من 90 يوم ({{ $buckets['over_90']['count'] }})</div></div>
</div>

@if($reservations->isEmpty())
<p style="text-align:center;color:#999;padding:20px;">لا توجد ديون مسجلة</p>
@else
{{-- dompdf يتجاهل dir="rtl" في ترتيب الأعمدة — نكتبها هنا بترتيب معكوس، كما في تقرير الحجوزات --}}
<table class="data" dir="rtl">
    <thead>
        <tr>
            <th>التصنيف</th>
            <th>أيام</th>
            <th>تاريخ الخروج</th>
            <th>المتبقي (ر.ي)</th>
            <th>المدفوع (ر.ي)</th>
            <th>الإجمالي (ر.ي)</th>
            <th>الغرفة</th>
            <th>النزيل</th>
        </tr>
    </thead>
    <tbody>
        @foreach($reservations as $res)
        @php
            $balance = $res->total_amount - $res->paid_amount;
            $refDate = $res->actual_check_out ?? $res->check_out_date;
            $days    = $refDate ? now()->startOfDay()->diffInDays($refDate->startOfDay()) : 0;
            if ($days <= 30)     { $bdg = ['جارية', 'badge-current']; }
            elseif ($days <= 60) { $bdg = ['31–60 يوم', 'badge-30']; }
            elseif ($days <= 90) { $bdg = ['61–90 يوم', 'badge-60']; }
            else                 { $bdg = ['+90 يوم', 'badge-90']; }
        @endphp
        <tr>
            <td class="{{ $bdg[1] }}" style="text-align:center; font-size:8px;">{{ $bdg[0] }}</td>
            <td style="text-align:center; font-weight:bold; {{ $days > 60 ? 'color:#dc2626;' : '' }}">{{ $days }}</td>
            <td>{{ $refDate?->format('d/m/Y') ?? '—' }}</td>
            <td style="font-weight:bold; color:#dc2626;">{{ number_format($balance, 0) }}</td>
            <td style="color:#16a34a;">{{ number_format($res->paid_amount, 0) }}</td>
            <td>{{ number_format($res->total_amount, 0) }}</td>
            <td style="font-weight:bold; text-align:center;">{{ $res->display_room_number }}</td>
            <td>{{ $res->guest?->full_name ?? '—' }}</td>
        </tr>
        @endforeach
        <tr style="background:#fef2f2; font-weight:bold; color:#dc2626;">
            <td colspan="3"></td>
            <td>{{ number_format($totalDebt, 0) }}</td>
            <td colspan="4" style="text-align:right;">إجمالي المبالغ غير المحصّلة</td>
        </tr>
    </tbody>
</table>
@endif

<div class="footer">طُبع في: {{ now()->format('d/m/Y H:i') }}</div>
</body>
</html>
