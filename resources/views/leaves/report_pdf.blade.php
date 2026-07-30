<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<style>
    @font-face {
        font-family: 'NotoNaskh';
        font-style: normal; font-weight: normal;
        src: url('{{ storage_path('fonts/NotoNaskhArabic.ttf') }}') format('truetype');
    }
    @font-face {
        font-family: 'NotoNaskh';
        font-style: normal; font-weight: bold;
        src: url('{{ storage_path('fonts/NotoNaskhArabic-Bold.ttf') }}') format('truetype');
    }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'NotoNaskh', Arial, sans-serif; font-size: 12px; color: #1f2937; direction: rtl; padding: 24px; }
    .header { text-align: center; padding-bottom: 14px; border-bottom: 3px solid #0F4C75; margin-bottom: 18px; }
    .header h1 { font-size: 17px; font-weight: bold; color: #0F4C75; }
    .header p { font-size: 11px; color: #6b7280; margin-top: 3px; }

    table.data { width: 100%; border-collapse: collapse; direction: rtl; }
    table.data thead th { background: #0F4C75; color: #fff; padding: 7px 8px; text-align: center; border: 1px solid #0a3a5e; }
    table.data tbody td { padding: 6px 8px; text-align: center; border: 1px solid #e0e0e0; }
    table.data tbody tr:nth-child(even) { background: #f4f8fc; }
    td.name { text-align: right; font-weight: bold; }
    .footer { margin-top: 14px; border-top: 1px solid #eee; padding-top: 6px; font-size: 9px; color: #aaa; text-align: right; }
</style>
</head>
<body>

<div class="header">
    @include('partials.pdf-logo')
    <h1>تقرير أرصدة الإجازات</h1>
    <p>سنة {{ $year }} — الرصيد السنوي للإجازة الاعتيادية: {{ $annualEntitlement }} يوماً</p>
</div>

{{--
    dompdf لا يعكس ترتيب أعمدة الجدول بحسب dir="rtl" — نكتب الأعمدة بترتيب معكوس
    حتى يظهر اسم الموظف في أقصى اليمين كالمعتاد عربياً.
--}}
<table class="data">
    <thead>
        <tr>
            <th>المتبقي (اعتيادية)</th>
            <th>الإجمالي المأخوذ</th>
            <th>بدون راتب</th>
            <th>طارئة</th>
            <th>مرضية</th>
            <th>اعتيادية</th>
            <th>الموظف</th>
        </tr>
    </thead>
    <tbody>
        @forelse($summary as $row)
        <tr>
            <td style="font-weight:bold;color:{{ $row['annual_remaining'] > 0 ? '#16a34a' : '#dc2626' }};">{{ $row['annual_remaining'] }}</td>
            <td style="font-weight:bold;">{{ $row['total_taken'] }}</td>
            <td>{{ $row['by_type']['unpaid'] }}</td>
            <td>{{ $row['by_type']['emergency'] }}</td>
            <td>{{ $row['by_type']['sick'] }}</td>
            <td>{{ $row['by_type']['annual'] }}</td>
            <td class="name">{{ $row['employee']->name }}</td>
        </tr>
        @empty
        <tr><td colspan="7" style="padding:16px;color:#999;">لا يوجد موظفون نشطون</td></tr>
        @endforelse
    </tbody>
</table>

<div class="footer">طُبع في: {{ now()->format('d/m/Y H:i') }}</div>

</body>
</html>
