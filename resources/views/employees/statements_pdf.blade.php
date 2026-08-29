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
    body { font-family: 'NotoNaskhArabic', sans-serif; font-size: 10px; direction: rtl; color: #1a1a1a; background: #fff; padding: 14px; }
    .header { text-align: center; border-bottom: 2px solid #0F4C75; padding-bottom: 10px; margin-bottom: 12px; }
    .header h1 { font-size: 16px; color: #0F4C75; font-weight: bold; }
    .header .sub { font-size: 9px; color: #555; margin-top: 4px; }

    .cards { display: table; width: 100%; margin-bottom: 12px; }
    .card { display: table-cell; width: 25%; padding: 5px; }
    .card-inner { border: 1px solid #e0e0e0; border-radius: 5px; padding: 7px 8px; text-align: center; }
    .card-label { font-size: 8px; color: #777; margin-bottom: 3px; }
    .card-value { font-size: 13px; font-weight: bold; }
    .green { color: #16a34a; } .red { color: #dc2626; } .amber { color: #b45309; }

    table.data { width: 100%; border-collapse: collapse; font-size: 9.5px; direction: rtl; table-layout: fixed; }
    table.data thead { display: table-header-group; }
    table.data thead tr { background: #1f2937; color: #fff; }
    table.data thead th { padding: 5px 5px; font-weight: bold; border: 1px solid #111827; text-align: center; }
    table.data tbody tr:nth-child(even) { background: #f4f8fc; }
    table.data tbody td { padding: 4px 5px; border: 1px solid #ccc; text-align: right; word-break: normal; vertical-align: top; }
    table.data tbody td.c { text-align: center; }
    table.data tfoot td { padding: 5px; border: 1px solid #ccc; background: #e8f0f7; font-weight: bold; color: #0F4C75; }
    .empty { text-align: center; color: #999; padding: 16px; }
    .footer { margin-top: 12px; border-top: 1px solid #eee; padding-top: 6px; font-size: 8px; color: #aaa; text-align: right; }
</style>
</head>
<body>

<div class="header">
    @include('partials.pdf-logo')
    <h1>كشف حساب الموظفين</h1>
    <div class="sub">
        الفترة: {{ \Carbon\Carbon::parse($from)->format('Y/m/d') }} — {{ \Carbon\Carbon::parse($to)->format('Y/m/d') }}
    </div>
</div>

<div class="cards">
    <div class="card"><div class="card-inner">
        <div class="card-label">إجمالي صافي الرواتب</div>
        <div class="card-value">{{ number_format($totals['salaries_net'], 0) }}</div>
    </div></div>
    <div class="card"><div class="card-inner">
        <div class="card-label">المدفوع</div>
        <div class="card-value green">{{ number_format($totals['salaries_paid'], 0) }}</div>
    </div></div>
    <div class="card"><div class="card-inner">
        <div class="card-label">غير المدفوع (مستحق للموظفين)</div>
        <div class="card-value red">{{ number_format($totals['salaries_due'], 0) }}</div>
    </div></div>
    <div class="card"><div class="card-inner">
        <div class="card-label">إجمالي السلف</div>
        <div class="card-value amber">{{ number_format($totals['advances'], 0) }}</div>
    </div></div>
</div>

@if($rows->isEmpty())
<p class="empty">لا يوجد موظفون</p>
@else
{{-- dompdf لا يعكس ترتيب أعمدة الجدول بحسب dir="rtl" — نكتبها بترتيب معكوس
     ليظهر أول عمود منطقياً (الموظف) في أقصى اليمين. --}}
<table class="data" dir="rtl">
    <thead>
        <tr>
            <th style="width:13%;">السلف</th>
            <th style="width:14%;">غير المدفوع</th>
            <th style="width:14%;">المدفوع</th>
            <th style="width:14%;">صافي الرواتب</th>
            <th style="width:9%;">عدد الأشهر</th>
            <th style="width:13%;">الراتب الأساسي</th>
            <th style="width:23%;">الموظف</th>
        </tr>
    </thead>
    <tbody>
        @foreach($rows as $row)
        <tr>
            <td class="c" style="color:#b45309;">{{ number_format($row['advances'], 0) }}</td>
            <td class="c" style="color:{{ $row['salaries_due'] > 0 ? '#dc2626' : '#999' }};font-weight:bold;">{{ number_format($row['salaries_due'], 0) }}</td>
            <td class="c" style="color:#16a34a;">{{ number_format($row['salaries_paid'], 0) }}</td>
            <td class="c" style="font-weight:bold;">{{ number_format($row['salaries_net'], 0) }}</td>
            <td class="c">{{ $row['months_count'] }}</td>
            <td class="c">{{ number_format((float) $row['employee']->base_salary, 0) }}</td>
            <td style="font-weight:bold;">
                {{ $row['employee']->name }}
                @if($row['employee']->position)
                <div style="font-size:8px;color:#888;font-weight:normal;">{{ $row['employee']->position }}</div>
                @endif
            </td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td class="c">{{ number_format($totals['advances'], 0) }}</td>
            <td class="c">{{ number_format($totals['salaries_due'], 0) }}</td>
            <td class="c">{{ number_format($totals['salaries_paid'], 0) }}</td>
            <td class="c">{{ number_format($totals['salaries_net'], 0) }}</td>
            <td colspan="3" style="text-align:right;">الإجمالي</td>
        </tr>
    </tfoot>
</table>
@endif

<div class="footer">طُبع في: {{ now()->format('d/m/Y H:i') }}</div>

</body>
</html>
