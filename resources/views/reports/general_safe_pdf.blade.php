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
    body { font-family: 'NotoNaskhArabic', sans-serif; font-size: 11px; direction: rtl; color: #1a1a1a; background: #fff; padding: 14px; }
    .header { text-align: center; border-bottom: 2px solid #0F4C75; padding-bottom: 10px; margin-bottom: 14px; }
    .header h1 { font-size: 17px; color: #0F4C75; font-weight: bold; }
    .header .sub { font-size: 10px; color: #555; margin-top: 4px; }
    .cards { display: table; width: 100%; margin-bottom: 14px; }
    .card { display: table-cell; width: 25%; padding: 6px; }
    .card-inner { border: 1px solid #e0e0e0; border-radius: 6px; padding: 8px 10px; text-align: center; }
    .card-label { font-size: 8px; color: #777; margin-bottom: 3px; }
    .card-value { font-size: 13px; font-weight: bold; }
    .amber { color: #b45309; }
    .green { color: #16a34a; }
    .red { color: #dc2626; }
    table.data { width: 100%; border-collapse: collapse; font-size: 10px; direction: rtl; }
    table.data thead tr { background: #1f2937; color: #fff; }
    table.data thead th { padding: 6px 6px; font-weight: bold; border: 1px solid #111827; text-align: center; }
    table.data tbody tr:nth-child(even) { background: #f4f8fc; }
    table.data tbody td { padding: 5px 6px; border: 1px solid #ccc; text-align: right; word-break: normal; vertical-align: top; }
    table.data tbody td.c { text-align: center; }
    .opening-row td { background: #f8fafc; color: #888; }
    .empty { text-align: center; color: #999; padding: 16px; }
    .footer { margin-top: 12px; border-top: 1px solid #eee; padding-top: 6px; font-size: 8px; color: #aaa; text-align: right; }
</style>
</head>
<body>

<div class="header">
    @include('partials.pdf-logo')
    <h1>تقرير الصندوق العام</h1>
    <div class="sub">
        الفترة: {{ \Carbon\Carbon::parse($from)->format('Y/m/d') }} — {{ \Carbon\Carbon::parse($to)->format('Y/m/d') }}
    </div>
</div>

<div class="cards">
    <div class="card">
        <div class="card-inner">
            <div class="card-label">الرصيد الحالي</div>
            <div class="card-value amber">{{ number_format($currentBalance, 0) }} ر.ي</div>
        </div>
    </div>
    <div class="card">
        <div class="card-inner">
            <div class="card-label">الرصيد الافتتاحي</div>
            <div class="card-value">{{ number_format($openingBalance, 0) }} ر.ي</div>
        </div>
    </div>
    <div class="card">
        <div class="card-inner">
            <div class="card-label">إجمالي الوارد</div>
            <div class="card-value green">{{ number_format($movements->sum('in'), 0) }} ر.ي</div>
        </div>
    </div>
    <div class="card">
        <div class="card-inner">
            <div class="card-label">إجمالي الصادر</div>
            <div class="card-value red">{{ number_format($movements->sum('out'), 0) }} ر.ي</div>
        </div>
    </div>
</div>

{{-- dompdf لا يعكس ترتيب أعمدة الجدول بحسب dir="rtl" — نكتبها بترتيب معكوس
     ليظهر أول عمود منطقياً في أقصى اليمين. --}}
<table class="data" dir="rtl">
    <thead>
        <tr>
            <th style="width:20%;">الرصيد الجاري</th>
            <th style="width:14%;">صادر</th>
            <th style="width:14%;">وارد</th>
            <th style="width:38%;">البيان</th>
            <th style="width:14%;">التاريخ</th>
        </tr>
    </thead>
    <tbody>
        <tr class="opening-row">
            <td class="c" style="font-weight:bold;">{{ number_format($openingBalance, 0) }}</td>
            <td colspan="3">رصيد ما قبل {{ \Carbon\Carbon::parse($from)->format('Y/m/d') }}</td>
            <td class="c">—</td>
        </tr>
        @forelse($movements as $m)
        <tr>
            <td class="c" style="font-weight:bold;">{{ number_format($m['balance'], 0) }}</td>
            <td class="c" style="color:#dc2626;">{{ $m['out'] > 0 ? number_format($m['out'], 0) : '—' }}</td>
            <td class="c" style="color:#16a34a;">{{ $m['in'] > 0 ? number_format($m['in'], 0) : '—' }}</td>
            <td>{{ $m['description'] ?? '—' }}</td>
            <td class="c">{{ $m['date']?->format('d/m/Y') ?? '—' }}</td>
        </tr>
        @empty
        <tr>
            <td colspan="5" class="empty">لا توجد حركات خلال هذه الفترة</td>
        </tr>
        @endforelse
    </tbody>
</table>

<div class="footer">طُبع في: {{ now()->format('d/m/Y H:i') }}</div>

</body>
</html>
