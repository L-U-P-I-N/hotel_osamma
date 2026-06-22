<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<style>
    @font-face {
        font-family: 'NotoNaskh';
        font-style: normal;
        font-weight: normal;
        src: url('{{ storage_path('fonts/NotoNaskhArabic-Regular.ttf') }}') format('truetype');
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
        padding-bottom: 16px;
        border-bottom: 3px solid #0F4C75;
        margin-bottom: 20px;
    }
    .header h1 { font-size: 20px; font-weight: bold; color: #0F4C75; }
    .header p  { font-size: 12px; color: #6b7280; margin-top: 3px; }
    .slip-title {
        text-align: center;
        font-size: 16px;
        font-weight: bold;
        color: #0F4C75;
        margin-bottom: 22px;
        letter-spacing: 1px;
    }
    .info-table { width: 100%; border-collapse: collapse; margin-bottom: 22px; }
    .info-table td { padding: 6px 8px; font-size: 12px; }
    .info-table td:first-child { font-weight: bold; color: #374151; width: 140px; }
    .info-table td:last-child   { color: #111827; }
    .info-table tr:nth-child(odd) td { background: #f9fafb; }
    .amount-box {
        border: 2px solid #0F4C75;
        border-radius: 8px;
        padding: 18px;
        text-align: center;
        margin: 22px 0;
    }
    .amount-box .label { font-size: 13px; color: #6b7280; margin-bottom: 4px; }
    .amount-box .amount { font-size: 28px; font-weight: bold; color: #0F4C75; }
    .method-badge {
        display: inline-block;
        padding: 3px 14px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: bold;
        background: #dbeafe;
        color: #1e40af;
        margin-top: 8px;
    }
    .signatures {
        margin-top: 40px;
        display: table;
        width: 100%;
    }
    .sig-col {
        display: table-cell;
        text-align: center;
        width: 50%;
    }
    .sig-line {
        border-top: 1px solid #d1d5db;
        margin: 0 30px;
        padding-top: 6px;
        font-size: 11px;
        color: #6b7280;
        margin-top: 40px;
    }
    .footer {
        text-align: center;
        font-size: 10px;
        color: #9ca3af;
        margin-top: 28px;
        padding-top: 10px;
        border-top: 1px solid #e5e7eb;
    }
    .notes-box {
        background: #fffbeb;
        border: 1px solid #fde68a;
        border-radius: 6px;
        padding: 10px 14px;
        font-size: 12px;
        color: #374151;
        margin-bottom: 16px;
    }
    .ltr { direction: ltr; }
</style>
</head>
<body>

<div class="header">
    <h1>الفندق السعودي</h1>
    <p>نظام إدارة الفندق</p>
</div>

<div class="slip-title">إيصال استلام دفعة</div>

<table class="info-table">
    <tr>
        <td>رقم الإيصال:</td>
        <td><span class="ltr">#{{ str_pad($payment->id, 6, '0', STR_PAD_LEFT) }}</span></td>
    </tr>
    <tr>
        <td>النزيل:</td>
        <td>{{ $payment->reservation->guest->full_name ?? '—' }}</td>
    </tr>
    <tr>
        <td>الغرفة:</td>
        <td>{{ $payment->reservation->room->room_number ?? '—' }}</td>
    </tr>
    <tr>
        <td>تاريخ الوصول:</td>
        <td>{{ $payment->reservation->check_in_date?->format('d/m/Y') ?? '—' }}</td>
    </tr>
    <tr>
        <td>تاريخ المغادرة:</td>
        <td>{{ $payment->reservation->check_out_date?->format('d/m/Y') ?? '—' }}</td>
    </tr>
    <tr>
        <td>تاريخ الدفع:</td>
        <td>{{ $payment->payment_date?->format('d/m/Y H:i') ?? now()->format('d/m/Y H:i') }}</td>
    </tr>
    <tr>
        <td>موظف الاستقبال:</td>
        <td>{{ $payment->receivedBy->name ?? auth()->user()->name }}</td>
    </tr>
</table>

<div class="amount-box">
    <div class="label">المبلغ المستلم</div>
    <div class="amount">{{ number_format($payment->amount, 0) }} ر.ي</div>
    <div class="method-badge">
        {{ match($payment->method) {
            'cash'          => 'نقداً',
            'bank_transfer' => 'تحويل بنكي',
            'pos'           => 'POS / بطاقة',
            default         => $payment->method,
        } }}
    </div>
</div>

@php
    $totalPaid   = $payment->reservation->paid_amount ?? 0;
    $totalAmount = $payment->reservation->total_amount ?? 0;
    $balance     = $totalAmount - $totalPaid;
@endphp
<table class="info-table">
    <tr>
        <td>إجمالي الحجز:</td>
        <td>{{ number_format($totalAmount, 0) }} ر.ي</td>
    </tr>
    <tr>
        <td>إجمالي المدفوع:</td>
        <td>{{ number_format($totalPaid, 0) }} ر.ي</td>
    </tr>
    <tr>
        <td>الرصيد المتبقي:</td>
        <td style="font-weight:bold; color:{{ $balance > 0 ? '#dc2626' : '#16a34a' }};">
            {{ number_format($balance, 0) }} ر.ي
        </td>
    </tr>
</table>

@if($payment->notes)
<div class="notes-box"><strong>ملاحظات:</strong> {{ $payment->notes }}</div>
@endif

<div class="signatures">
    <div class="sig-col">
        <div class="sig-line">توقيع النزيل</div>
    </div>
    <div class="sig-col">
        <div class="sig-line">توقيع موظف الاستقبال</div>
    </div>
</div>

<div class="footer">
    طُبع في {{ now()->format('d/m/Y H:i') }} &mdash; رقم الحجز: #{{ $payment->reservation_id }}
</div>

</body>
</html>
