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
    body { font-family: 'NotoNaskhArabic', sans-serif; font-size: 8px; direction: rtl; color: #1a1a1a; background: #fff; padding: 16px; }
    .header { text-align: center; border-bottom: 2px solid #0F4C75; padding-bottom: 10px; margin-bottom: 14px; }
    .header h1 { font-size: 16px; color: #0F4C75; font-weight: bold; }
    .header .sub { font-size: 9px; color: #555; margin-top: 4px; }
    table.data { width: 100%; border-collapse: collapse; font-size: 8px; margin-bottom: 14px; direction: rtl; }
    table.data thead tr { background: #0F4C75; color: #fff; }
    table.data thead th { padding: 5px 4px; font-weight: bold; border: 1px solid #0a3a5e; text-align: right; white-space: nowrap; }
    table.data tbody tr:nth-child(even) { background: #f4f8fc; }
    table.data tbody td { padding: 3px 4px; border: 1px solid #e0e0e0; text-align: right; white-space: nowrap; }
    table.data tbody td.ltr { text-align: left; direction: ltr; }
    table.data tbody td.center { text-align: center; }
    .stats-row { display: table; width: 100%; margin-bottom: 14px; }
    .stat-box { display: table-cell; border: 1px solid #ddd; padding: 6px 16px; text-align: center; }
    .stat-box .num { font-size: 18px; font-weight: bold; color: #0F4C75; }
    .stat-box .lbl { font-size: 8px; color: #666; }
    .footer { margin-top: 12px; border-top: 1px solid #eee; padding-top: 6px; font-size: 8px; color: #aaa; text-align: right; }
</style>
</head>
<body>
<div class="header">
    <h1>تقرير الحجوزات</h1>
    <div class="sub">الفترة: {{ \Carbon\Carbon::parse($from)->format('d/m/Y') }} — {{ \Carbon\Carbon::parse($to)->format('d/m/Y') }}</div>
</div>

<div class="stats-row">
    <div class="stat-box">
        <div class="num">{{ $total }}</div>
        <div class="lbl">إجمالي الحجوزات</div>
    </div>
    <div class="stat-box">
        <div class="num">{{ $checkedIn }}</div>
        <div class="lbl">مقيم حالياً</div>
    </div>
    <div class="stat-box">
        <div class="num">{{ $checkedOut }}</div>
        <div class="lbl">غادر</div>
    </div>
</div>

@php
    $idTypeMap = ['national_id' => 'بطاقة', 'passport' => 'جواز', 'residence' => 'إقامة'];
@endphp

<table class="data">
    <thead>
        <tr>
            <th>الغرفة</th>
            <th>اسم النزيل</th>
            <th>الجنسية</th>
            <th>المهنة</th>
            <th>جهة القدوم</th>
            <th>تاريخ الدخول</th>
            <th>الوقت</th>
            <th>الغرض</th>
            <th>نوع الهوية</th>
            <th>رقم الهوية</th>
            <th>صادر من</th>
            <th>تاريخ الإصدار</th>
            <th>رقم الجوال</th>
            <th>حالة الدفع</th>
        </tr>
    </thead>
    <tbody>
        @forelse($reservations as $r)
        @php
            $g = $r->guest;
            $ps = $r->payment_status ?? 'pending';
            $psLabel = match($ps) { 'paid' => 'مدفوع', 'partial' => 'جزئي', default => 'معلق' };
        @endphp
        <tr>
            <td>{{ $r->room?->room_number ?? '—' }}</td>
            <td>{{ $g?->full_name ?? '—' }}</td>
            <td>{{ $g?->nationality ?? '—' }}</td>
            <td>{{ $g?->occupation ?? '—' }}</td>
            <td>{{ $r->origin ?? '—' }}</td>
            <td class="ltr">{{ $r->check_in_date?->format('d/m/Y') ?? '—' }}</td>
            <td class="ltr">{{ $r->check_in_time ?? '—' }}</td>
            <td>{{ $r->purpose ?? '—' }}</td>
            <td>{{ $idTypeMap[$g?->id_type] ?? ($g?->id_type ?? '—') }}</td>
            <td class="ltr">{{ $g?->id_number ?? '—' }}</td>
            <td>{{ $g?->id_issuer ?? '—' }}</td>
            <td class="ltr">{{ $g?->id_issue_date?->format('d/m/Y') ?? '—' }}</td>
            <td class="ltr">{{ $g?->phone ?? '—' }}</td>
            <td>{{ $psLabel }}</td>
        </tr>
        @empty
        <tr><td colspan="14" style="text-align:center; padding:10px; color:#999;">لا توجد حجوزات</td></tr>
        @endforelse
    </tbody>
</table>

<div class="footer">
    طُبع بتاريخ: {{ now()->format('d/m/Y H:i') }}
</div>
</body>
</html>
