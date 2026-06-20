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
    .stats-row { display: table; width: 100%; margin-bottom: 14px; }
    .stat-box { display: table-cell; border: 1px solid #ddd; padding: 6px 16px; text-align: center; }
    .stat-box .num { font-size: 18px; font-weight: bold; color: #0F4C75; }
    .stat-box .lbl { font-size: 8px; color: #666; }
    .footer { margin-top: 12px; border-top: 1px solid #eee; padding-top: 6px; font-size: 8px; color: #aaa; text-align: right; }
    h2 { font-size: 11px; font-weight: bold; color: #fff; background: #0F4C75; padding: 5px 10px; margin-bottom: 0; text-align: right; }
    .half { width: 48%; display: inline-block; vertical-align: top; margin-left: 2%; }
</style>
</head>
<body>
<div class="header">
    <h1>تقرير النزلاء</h1>
    <div class="sub">الفترة: {{ \Carbon\Carbon::parse($from)->format('d/m/Y') }} — {{ \Carbon\Carbon::parse($to)->format('d/m/Y') }}</div>
</div>

<div class="stats-row">
    <div class="stat-box">
        <div class="num">{{ $totalGuests }}</div>
        <div class="lbl">إجمالي النزلاء المسجلين</div>
    </div>
    <div class="stat-box">
        <div class="num">{{ $newGuests }}</div>
        <div class="lbl">نزلاء جدد في الفترة</div>
    </div>
    <div class="stat-box">
        <div class="num">{{ $returningGuests }}</div>
        <div class="lbl">نزلاء متكررون في الفترة</div>
    </div>
</div>

<div class="half">
    <h2>الجنسيات الأكثر (أعلى 10)</h2>
    <table class="data">
        <thead>
            <tr>
                <th>الجنسية</th>
                <th>العدد</th>
            </tr>
        </thead>
        <tbody>
            @forelse($byNationality as $nat)
            <tr>
                <td>{{ $nat->nationality ?: 'غير محدد' }}</td>
                <td>{{ $nat->count }}</td>
            </tr>
            @empty
            <tr><td colspan="2" style="text-align:center; padding:10px; color:#999;">لا توجد بيانات</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<br style="clear:both;">

<h2>أكثر النزلاء زيارة في الفترة</h2>
<table class="data">
    <thead>
        <tr>
            <th>الاسم</th>
            <th>عدد الحجوزات</th>
        </tr>
    </thead>
    <tbody>
        @forelse($topGuests as $g)
        <tr>
            <td>{{ $g->full_name }}</td>
            <td>{{ $g->period_reservations }}</td>
        </tr>
        @empty
        <tr><td colspan="2" style="text-align:center; padding:10px; color:#999;">لا توجد بيانات</td></tr>
        @endforelse
    </tbody>
</table>

<div class="footer">
    طُبع بتاريخ: {{ now()->format('d/m/Y H:i') }}
</div>
</body>
</html>
