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
    .total-card { border: 2px solid #0F4C75; border-radius: 6px; padding: 12px; margin-bottom: 16px; text-align: center; background: #e8f0f7; }
    .total-card .num { font-size: 22px; font-weight: bold; color: #0F4C75; }
    .total-card .lbl { font-size: 9px; color: #5b90c5; margin-top: 2px; }
    h2 { font-size: 11px; font-weight: bold; color: #fff; background: #0F4C75; padding: 5px 10px; margin-bottom: 0; text-align: right; }
    table.data { width: 100%; border-collapse: collapse; font-size: 9.5px; margin-bottom: 16px; direction: rtl; }
    table.data thead tr { background: #e8f0f7; }
    table.data thead th { padding: 5px 8px; font-weight: bold; border: 1px solid #cdd8e3; text-align: right; white-space: nowrap; }
    table.data tbody tr:nth-child(even) { background: #f9fbfd; }
    table.data tbody td { padding: 5px 8px; border: 1px solid #e0e7ef; text-align: right; white-space: nowrap; }
    table.data tbody td.ltr { text-align: left; direction: ltr; }
    .total-row td { font-weight: bold; background: #e8f0f7 !important; color: #0F4C75; }
    .badge-paid { color: #16a34a; } .badge-pending { color: #d97706; }
    .footer { margin-top: 12px; border-top: 1px solid #eee; padding-top: 6px; font-size: 8px; color: #aaa; text-align: right; }
</style>
</head>
<body>

@php
    $monthNames = [1=>'يناير',2=>'فبراير',3=>'مارس',4=>'أبريل',5=>'مايو',6=>'يونيو',
                   7=>'يوليو',8=>'أغسطس',9=>'سبتمبر',10=>'أكتوبر',11=>'نوفمبر',12=>'ديسمبر'];
@endphp

<div class="header">
    <h1>تقرير الرواتب — {{ $year }}</h1>
    <div class="sub">تاريخ الطباعة: {{ now()->format('d/m/Y') }}</div>
</div>

<div class="total-card">
    <div class="num">{{ number_format($totalNet, 0) }} ر.ي</div>
    <div class="lbl">إجمالي صافي الرواتب لسنة {{ $year }}</div>
</div>

<h2>ملخص شهري</h2>
<table class="data" dir="rtl">
    <thead>
        <tr>
            <th>الشهر</th>
            <th>الموظفين</th>
            <th>الأساسي (ر.ي)</th>
            <th>المكافآت (ر.ي)</th>
            <th>الخصومات (ر.ي)</th>
            <th>الصافي (ر.ي)</th>
            <th>مدفوع / معلق</th>
        </tr>
    </thead>
    <tbody>
        @forelse($byMonth as $month => $data)
        <tr>
            <td>{{ $monthNames[$month] ?? $month }}</td>
            <td class="ltr" style="text-align:center;">{{ $data['count'] }}</td>
            <td class="ltr">{{ number_format($data['total_base'], 0) }}</td>
            <td class="ltr" style="color:#16a34a;">{{ number_format($data['total_bonus'], 0) }}</td>
            <td class="ltr" style="color:#dc2626;">{{ number_format($data['total_ded'], 0) }}</td>
            <td class="ltr" style="font-weight:bold;color:#0F4C75;">{{ number_format($data['total_net'], 0) }}</td>
            <td>
                <span class="badge-paid">{{ $data['paid'] }} مدفوع</span>
                @if($data['pending'] > 0) / <span class="badge-pending">{{ $data['pending'] }} معلق</span>@endif
            </td>
        </tr>
        @empty
        <tr><td colspan="7" style="text-align:center;padding:8px;color:#999;">لا توجد رواتب مسجلة</td></tr>
        @endforelse
        @if($byMonth->isNotEmpty())
        <tr class="total-row">
            <td>الإجمالي</td>
            <td class="ltr" style="text-align:center;">—</td>
            <td class="ltr">{{ number_format($byMonth->sum('total_base'), 0) }}</td>
            <td class="ltr">{{ number_format($byMonth->sum('total_bonus'), 0) }}</td>
            <td class="ltr">{{ number_format($byMonth->sum('total_ded'), 0) }}</td>
            <td class="ltr">{{ number_format($totalNet, 0) }}</td>
            <td>—</td>
        </tr>
        @endif
    </tbody>
</table>

@if($salaries->isNotEmpty())
<h2>تفاصيل الرواتب</h2>
<table class="data" dir="rtl">
    <thead>
        <tr>
            <th>الموظف</th>
            <th>الشهر</th>
            <th>الأساسي (ر.ي)</th>
            <th>المكافآت (ر.ي)</th>
            <th>الخصومات (ر.ي)</th>
            <th>الصافي (ر.ي)</th>
            <th>الحالة</th>
        </tr>
    </thead>
    <tbody>
        @foreach($salaries as $sal)
        <tr>
            <td>{{ $sal->employee?->name ?? '—' }}</td>
            <td>{{ $monthNames[$sal->month] ?? $sal->month }} {{ $sal->year }}</td>
            <td class="ltr">{{ number_format($sal->base_salary, 0) }}</td>
            <td class="ltr" style="color:#16a34a;">{{ number_format($sal->bonuses, 0) }}</td>
            <td class="ltr" style="color:#dc2626;">{{ number_format($sal->deductions, 0) }}</td>
            <td class="ltr" style="font-weight:bold;color:#0F4C75;">{{ number_format($sal->net_salary, 0) }}</td>
            <td>{{ $sal->status === 'paid' ? 'مدفوع' : 'معلق' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

<div class="footer">طُبع في: {{ now()->format('d/m/Y H:i') }}</div>

</body>
</html>
