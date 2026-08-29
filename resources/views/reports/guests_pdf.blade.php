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
    .stats { display: table; width: 100%; border-collapse: collapse; margin-bottom: 16px; }
    .stat-cell { display: table-cell; border: 1px solid #ddd; padding: 8px 16px; text-align: center; width: 33%; }
    .stat-cell .num { font-size: 20px; font-weight: bold; color: #0F4C75; }
    .stat-cell .num-g { font-size: 20px; font-weight: bold; color: #16a34a; }
    .stat-cell .num-b { font-size: 20px; font-weight: bold; color: #2563eb; }
    .stat-cell .lbl { font-size: 8px; color: #666; margin-top: 2px; }
    .two-col { display: table; width: 100%; border-collapse: collapse; margin-bottom: 16px; }
    .col-half { display: table-cell; width: 50%; vertical-align: top; padding-left: 8px; }
    .col-half:last-child { padding-left: 0; padding-right: 8px; }
    h2 { font-size: 11px; font-weight: bold; color: #fff; background: #0F4C75; padding: 5px 10px; margin-bottom: 0; text-align: right; }
    table.data { width: 100%; border-collapse: collapse; font-size: 9.5px; direction: rtl; }
    table.data thead tr { background: #e8f0f7; }
    table.data thead th { padding: 5px 8px; font-weight: bold; border: 1px solid #cdd8e3; text-align: right; }
    table.data tbody tr:nth-child(even) { background: #f9fbfd; }
    table.data tbody td { padding: 5px 8px; border: 1px solid #e0e7ef; text-align: right; }
    table.data tbody td.ltr { text-align: left; direction: ltr; }
    .footer { margin-top: 12px; border-top: 1px solid #eee; padding-top: 6px; font-size: 8px; color: #aaa; text-align: right; }
</style>
</head>
<body>

<div class="header">
    @include('partials.pdf-hotel-header')
    <h1>تقرير النزلاء</h1>
    <div class="sub">الفترة: {{ \Carbon\Carbon::parse($from)->format('d/m/Y') }} — {{ \Carbon\Carbon::parse($to)->format('d/m/Y') }}</div>
</div>

<div class="stats">
    <div class="stat-cell"><div class="num">{{ $totalGuests }}</div><div class="lbl">إجمالي النزلاء المسجلين</div></div>
    <div class="stat-cell"><div class="num-g">{{ $newGuests }}</div><div class="lbl">نزلاء جدد في الفترة</div></div>
    <div class="stat-cell"><div class="num-b">{{ $returningGuests }}</div><div class="lbl">نزلاء متكررون</div></div>
</div>

{{-- dompdf يتجاهل dir="rtl" في ترتيب الأعمدة — نكتبها هنا بترتيب معكوس، كما في تقرير الحجوزات --}}
<div class="two-col">
    <div class="col-half">
        <h2>الجنسيات الأكثر (أعلى 10)</h2>
        <table class="data" dir="rtl">
            <thead><tr><th>الجنسية</th><th>العدد</th></tr></thead>
            <tbody>
                @forelse($byNationality as $row)
                <tr>
                    <td>{{ $row->nationality ?: '—' }}</td>
                    <td class="ltr" style="text-align:center;">{{ $row->count }}</td>
                </tr>
                @empty
                <tr><td colspan="2" style="text-align:center;padding:8px;color:#999;">لا توجد بيانات</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="col-half">
        <h2>أكثر النزلاء زيارة</h2>
        <table class="data" dir="rtl">
            <thead><tr><th>اسم النزيل</th><th>عدد الحجوزات</th></tr></thead>
            <tbody>
                @forelse($topGuests as $guest)
                <tr>
                    <td>{{ $guest->full_name }}</td>
                    <td class="ltr" style="text-align:center;">{{ $guest->period_reservations }}</td>
                </tr>
                @empty
                <tr><td colspan="2" style="text-align:center;padding:8px;color:#999;">لا توجد بيانات</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="footer">طُبع في: {{ now()->format('d/m/Y H:i') }}</div>

</body>
</html>
