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
    .stat-box { display: inline-block; border: 1px solid #ddd; padding: 6px 16px; text-align: center; margin-bottom: 14px; }
    .stat-box .num { font-size: 18px; font-weight: bold; color: #0F4C75; }
    .stat-box .lbl { font-size: 8px; color: #666; }
    .footer { margin-top: 12px; border-top: 1px solid #eee; padding-top: 6px; font-size: 8px; color: #aaa; text-align: right; }
    h2 { font-size: 11px; font-weight: bold; color: #fff; background: #0F4C75; padding: 5px 10px; margin-bottom: 0; text-align: right; }
</style>
</head>
<body>
<div class="header">
    <h1>تقرير الرواتب — {{ $year }}</h1>
</div>

<div class="stat-box">
    <div class="num">{{ number_format($totalNet, 0) }}</div>
    <div class="lbl">إجمالي صافي الرواتب (ر.ي)</div>
</div>

<br style="clear:both;">

@php
    $monthNames = [1=>'يناير',2=>'فبراير',3=>'مارس',4=>'أبريل',5=>'مايو',6=>'يونيو',7=>'يوليو',8=>'أغسطس',9=>'سبتمبر',10=>'أكتوبر',11=>'نوفمبر',12=>'ديسمبر'];
@endphp

<h2>ملخص شهري — {{ $year }}</h2>
<table class="data">
    <thead>
        <tr>
            <th>الشهر</th>
            <th>الموظفين</th>
            <th>الأساسي</th>
            <th>المكافآت</th>
            <th>الخصومات</th>
            <th>الصافي</th>
            <th>مدفوع / معلق</th>
        </tr>
    </thead>
    <tbody>
        @forelse($byMonth as $month => $data)
        <tr>
            <td>{{ $monthNames[$month] ?? $month }}</td>
            <td>{{ $data['count'] }}</td>
            <td>{{ number_format($data['total_base'], 0) }}</td>
            <td>{{ number_format($data['total_bonus'], 0) }}</td>
            <td>{{ number_format($data['total_ded'], 0) }}</td>
            <td>{{ number_format($data['total_net'], 0) }}</td>
            <td>{{ $data['paid'] }} مدفوع @if($data['pending'] > 0)/ {{ $data['pending'] }} معلق@endif</td>
        </tr>
        @empty
        <tr><td colspan="7" style="text-align:center; padding:10px; color:#999;">لا توجد رواتب مسجلة</td></tr>
        @endforelse
    </tbody>
</table>

@if($salaries->isNotEmpty())
<h2>تفاصيل الرواتب</h2>
<table class="data">
    <thead>
        <tr>
            <th>الموظف</th>
            <th>الشهر</th>
            <th>الأساسي</th>
            <th>المكافآت</th>
            <th>الخصومات</th>
            <th>الصافي</th>
            <th>الحالة</th>
        </tr>
    </thead>
    <tbody>
        @foreach($salaries as $sal)
        <tr>
            <td>{{ $sal->employee?->full_name ?? '—' }}</td>
            <td>{{ $monthNames[$sal->month] ?? $sal->month }}</td>
            <td>{{ number_format($sal->base_salary, 0) }}</td>
            <td>{{ number_format($sal->bonuses, 0) }}</td>
            <td>{{ number_format($sal->deductions, 0) }}</td>
            <td>{{ number_format($sal->net_salary, 0) }}</td>
            <td>{{ $sal->status === 'paid' ? 'مدفوع' : 'معلق' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

<div class="footer">
    طُبع بتاريخ: {{ now()->format('d/m/Y H:i') }}
</div>
</body>
</html>
