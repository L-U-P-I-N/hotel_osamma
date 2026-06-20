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
    .stats { display: table; width: 100%; border-collapse: collapse; margin-bottom: 14px; }
    .stats td { border: 1px solid #ddd; padding: 8px 12px; text-align: center; width: 33%; }
    .stats .num { font-size: 18px; font-weight: bold; color: #0F4C75; }
    .stats .lbl { font-size: 8px; color: #666; margin-top: 2px; }
    table.data { width: 100%; border-collapse: collapse; font-size: 10px; direction: rtl; }
    table.data thead tr { background: #0F4C75; color: #fff; }
    table.data thead th { padding: 6px 8px; font-weight: bold; border: 1px solid #0a3a5e; text-align: right; }
    table.data tbody tr:nth-child(even) { background: #f4f8fc; }
    table.data tbody td { padding: 5px 8px; border: 1px solid #e0e0e0; text-align: right; }
    .bar-cell { min-width: 80px; }
    .bar-bg { width: 100%; background: #e5e7eb; height: 8px; border-radius: 4px; display: inline-block; }
    .bar-fill { height: 8px; border-radius: 4px; background: #0F4C75; display: inline-block; }
    .footer { margin-top: 12px; border-top: 1px solid #eee; padding-top: 6px; font-size: 8px; color: #aaa; text-align: right; }
</style>
</head>
<body>

<div class="header">
    <h1>تقرير الإشغال</h1>
    <div class="sub">الفترة: {{ \Carbon\Carbon::parse($from)->format('d/m/Y') }} — {{ \Carbon\Carbon::parse($to)->format('d/m/Y') }}</div>
</div>

<table class="stats" dir="rtl">
    <tr>
        <td><div class="num">{{ $totalRooms }}</div><div class="lbl">إجمالي الغرف</div></td>
        <td><div class="num" style="color:#16a34a;">{{ $dailyOccupancy->count() > 0 ? round($dailyOccupancy->avg('percent')) : 0 }}%</div><div class="lbl">متوسط الإشغال</div></td>
        <td><div class="num" style="color:#2563eb;">{{ $dailyOccupancy->count() }}</div><div class="lbl">عدد الأيام</div></td>
    </tr>
</table>

<table class="data" dir="rtl">
    <thead>
        <tr>
            <th>التاريخ</th>
            <th>نسبة الإشغال</th>
            <th>شريط بياني</th>
        </tr>
    </thead>
    <tbody>
        @forelse($dailyOccupancy as $day)
        <tr>
            <td>{{ \Carbon\Carbon::parse($day['date'])->format('d/m/Y') }}</td>
            <td style="font-weight:bold; color:#0F4C75;">{{ $day['percent'] }}%</td>
            <td class="bar-cell">
                <div class="bar-bg"><div class="bar-fill" style="width:{{ $day['percent'] }}%;"></div></div>
            </td>
        </tr>
        @empty
        <tr><td colspan="3" style="text-align:center;padding:12px;color:#999;">لا توجد بيانات للفترة المحددة</td></tr>
        @endforelse
    </tbody>
</table>

<div class="footer">طُبع في: {{ now()->format('d/m/Y H:i') }}</div>
</body>
</html>
