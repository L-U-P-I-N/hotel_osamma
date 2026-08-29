<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<style>
    @font-face {
        font-family: 'NotoNaskh';
        font-style: normal;
        font-weight: normal;
        src: url('{{ storage_path('fonts/NotoNaskhArabic.ttf') }}') format('truetype');
    }
    @font-face {
        font-family: 'NotoNaskh';
        font-style: normal;
        font-weight: bold;
        src: url('{{ storage_path('fonts/NotoNaskhArabic-Bold.ttf') }}') format('truetype');
    }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
        font-family: 'NotoNaskh', Arial, sans-serif;
        font-size: 13px;
        color: #1f2937;
        direction: rtl;
        padding: 30px;
        background: #fff;
    }
    .header {
        text-align: center;
        padding-bottom: 18px;
        border-bottom: 3px solid #0F4C75;
        margin-bottom: 24px;
    }
    .header h1 { font-size: 20px; font-weight: bold; color: #0F4C75; margin-bottom: 4px; }
    .header p  { font-size: 12px; color: #6b7280; }
    .slip-title {
        text-align: center;
        font-size: 16px;
        font-weight: bold;
        color: #0F4C75;
        margin-bottom: 20px;
        letter-spacing: 1px;
    }
    .info-grid {
        display: table;
        width: 100%;
        margin-bottom: 22px;
        border-collapse: collapse;
    }
    .info-row { display: table-row; }
    .info-label {
        display: table-cell;
        font-weight: bold;
        color: #374151;
        padding: 5px 8px 5px 0;
        width: 130px;
    }
    .info-value { display: table-cell; color: #111827; padding: 5px 0; }
    .salary-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
    }
    .salary-table th {
        background: #0F4C75;
        color: white;
        padding: 9px 12px;
        text-align: right;
        font-size: 12px;
    }
    .salary-table td {
        padding: 9px 12px;
        border-bottom: 1px solid #e5e7eb;
        text-align: right;
    }
    .salary-table tr:nth-child(even) td { background: #f9fafb; }
    .total-row td { font-weight: bold; background: #eff6ff !important; color: #0F4C75; font-size: 14px; }
    .status-badge {
        display: inline-block;
        padding: 3px 12px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: bold;
    }
    .status-paid    { background: #dcfce7; color: #166534; }
    .status-pending { background: #fef9c3; color: #854d0e; }
    .signatures {
        margin-top: 40px;
        display: table;
        width: 100%;
    }
    .sig-col {
        display: table-cell;
        text-align: center;
        width: 33%;
    }
    .sig-line {
        border-top: 1px solid #d1d5db;
        margin: 0 20px;
        padding-top: 6px;
        font-size: 11px;
        color: #6b7280;
        margin-top: 40px;
    }
    .footer {
        text-align: center;
        font-size: 10px;
        color: #9ca3af;
        margin-top: 30px;
        padding-top: 12px;
        border-top: 1px solid #e5e7eb;
    }
    .notes-box {
        background: #f9fafb;
        border: 1px solid #e5e7eb;
        border-radius: 6px;
        padding: 10px 14px;
        margin-bottom: 20px;
        font-size: 12px;
        color: #374151;
    }
    .ltr { direction: ltr; text-align: left; }
</style>
</head>
<body>

{{-- Header --}}
<div class="header">
    @include('partials.pdf-hotel-header')
    <h1>الفندق السعودي</h1>
    <p>نظام إدارة الفندق</p>
</div>

<div class="slip-title">قسيمة راتب</div>

{{-- Employee Info --}}
<div class="info-grid">
    <div class="info-row">
        <div class="info-label">الموظف:</div>
        <div class="info-value">{{ $salary->employee->name }}</div>
    </div>
    <div class="info-row">
        <div class="info-label">المنصب:</div>
        <div class="info-value">{{ $salary->employee->position }}</div>
    </div>
    <div class="info-row">
        <div class="info-label">الفترة:</div>
        <div class="info-value">{{ \App\Models\Salary::monthName($salary->month) }} {{ $salary->year }}</div>
    </div>
    <div class="info-row">
        <div class="info-label">تاريخ الإصدار:</div>
        <div class="info-value">{{ now()->format('d/m/Y') }}</div>
    </div>
    <div class="info-row">
        <div class="info-label">الحالة:</div>
        <div class="info-value">
            <span class="status-badge {{ $salary->status === 'paid' ? 'status-paid' : 'status-pending' }}">
                {{ $salary->status === 'paid' ? 'مدفوعة' : 'مسودة' }}
            </span>
        </div>
    </div>
</div>

{{-- Salary Table --}}
<table class="salary-table">
    <thead>
        <tr>
            <th style="text-align:left;">المبلغ (ر.ي)</th>
            <th>البند</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class="ltr">{{ number_format($salary->base_salary, 0) }}</td>
            <td>الراتب الأساسي</td>
        </tr>
        <tr>
            <td class="ltr" style="color:#16a34a;">+{{ number_format($salary->bonuses, 0) }}</td>
            <td style="color:#16a34a;">المكافآت والبدلات</td>
        </tr>
        <tr>
            <td class="ltr" style="color:#dc2626;">-{{ number_format($salary->deductions, 0) }}</td>
            <td style="color:#dc2626;">خصومات أخرى</td>
        </tr>
        @if($salary->withdrawals_deduction > 0)
        <tr>
            <td class="ltr" style="color:#dc2626;">-{{ number_format($salary->withdrawals_deduction, 0) }}</td>
            <td style="color:#dc2626;">خصم مسحوبات الموظف</td>
        </tr>
        @endif
        @if($salary->attendance_deduction > 0)
        <tr>
            <td class="ltr" style="color:#dc2626;">-{{ number_format($salary->attendance_deduction, 0) }}</td>
            <td style="color:#dc2626;">خصم الغياب/الإجازة بدون راتب</td>
        </tr>
        @endif
        <tr class="total-row">
            <td class="ltr">{{ number_format($salary->net_salary, 0) }}</td>
            <td>صافي الراتب</td>
        </tr>
    </tbody>
</table>

@if($salary->notes)
<div class="notes-box">
    <strong>ملاحظات:</strong> {{ $salary->notes }}
</div>
@endif

{{-- Signatures --}}
<div class="signatures">
    <div class="sig-col">
        <div class="sig-line">توقيع الموظف</div>
    </div>
    <div class="sig-col">
        <div class="sig-line">المدير المالي</div>
    </div>
    <div class="sig-col">
        <div class="sig-line">مدير الفندق</div>
    </div>
</div>

{{-- Footer --}}
<div class="footer">
    طُبع في {{ now()->format('d/m/Y H:i') }} &mdash; رقم القسيمة: #{{ $salary->id }}
    @if($salary->creator) &mdash; أُنشئ بواسطة: {{ $salary->creator->name }} @endif
</div>

</body>
</html>
