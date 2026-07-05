<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>فاتورة #{{ str_pad($reservation->id, 6, '0', STR_PAD_LEFT) }}</title>
<style>
@font-face {
    font-family: 'Tajawal';
    font-weight: 400;
    src: url("{{ storage_path('fonts') }}/NotoNaskhArabic.ttf") format('truetype');
}
@font-face {
    font-family: 'Tajawal';
    font-weight: 700;
    src: url("{{ storage_path('fonts') }}/NotoNaskhArabic-Bold.ttf") format('truetype');
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

@page {
    margin: 8mm;
    size: A4;
}

body {
    font-family: 'Tajawal', sans-serif;
    font-size: 10pt;
    color: #2c3e50;
    direction: rtl;
    background: #f8f9fa;
    line-height: 1.3;
}

/* ===== INVOICE CONTAINER ===== */
.invoice-container {
    max-width: 210mm;
    margin: 0 auto;
    background: #ffffff;
    box-shadow: 0 10px 40px rgba(0,0,0,0.1);
    border-radius: 12px;
    overflow: hidden;
}

/* ===== HEADER SECTION ===== */
.invoice-header {
    background-color: #1e3c72;
    padding: 14px 24px;
    position: relative;
    color: white;
}

.header-content {
    width: 100%;
    border-collapse: collapse;
}

.brand-section {
    text-align: right;
    vertical-align: top;
}

.brand-section .logo {
    width: 54px;
    height: 54px;
    line-height: 54px;
    text-align: center;
    background: rgba(255,255,255,0.25);
    border-radius: 50%;
    margin-bottom: 8px;
    font-size: 22px;
}

.brand-section h1 {
    font-size: 22px;
    font-weight: 700;
    margin-bottom: 3px;
    letter-spacing: -0.5px;
}

.brand-section .tagline {
    font-size: 12px;
    opacity: 0.9;
    font-weight: 400;
    letter-spacing: 2px;
}

.invoice-info {
    text-align: left;
    vertical-align: top;
    background: rgba(255,255,255,0.15);
    padding: 10px 14px;
    border-radius: 12px;
    border: 1px solid rgba(255,255,255,0.2);
}

.invoice-info .label {
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 2px;
    opacity: 0.8;
    margin-bottom: 3px;
}

.invoice-info .invoice-number {
    font-size: 22px;
    font-weight: 700;
    margin-bottom: 5px;
}

.invoice-info .invoice-date {
    font-size: 14px;
    opacity: 0.9;
}

.status-badge {
    display: inline-block;
    padding: 5px 16px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 700;
    margin-top: 6px;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.status-paid {
    background: #10b981;
    color: white;
}

.status-due {
    background: #ef4444;
    color: white;
}

/* ===== CLIENT INFO SECTION ===== */
.client-section {
    padding: 10px 24px;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
}

.client-grid {
    width: 100%;
    border-collapse: separate;
    border-spacing: 15px 0;
}

.client-grid > tr > td {
    width: 50%;
    vertical-align: top;
}

.client-card {
    background: white;
    padding: 10px 14px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.client-card h3 {
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 2px;
    color: #64748b;
    margin-bottom: 5px;
    padding-bottom: 4px;
    border-bottom: 2px solid #e2e8f0;
}

.info-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 3px;
    font-size: 13px;
}

.info-row .label {
    color: #64748b;
    font-weight: 400;
}

.info-row .value {
    color: #1e293b;
    font-weight: 700;
}

/* ===== INVOICE TABLE ===== */
.invoice-table-section {
    padding: 10px 24px;
}

.invoice-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}

.invoice-table thead {
    background-color: #1e3c72;
    color: white;
}

.invoice-table th {
    padding: 8px 12px;
    text-align: right;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1px;
    font-size: 11px;
}

.invoice-table th:first-child {
    border-top-left-radius: 8px;
}

.invoice-table th:last-child {
    border-top-right-radius: 8px;
}

.invoice-table td {
    padding: 6px 12px;
    border-bottom: 1px solid #e2e8f0;
    vertical-align: middle;
}

.invoice-table tbody tr:nth-child(even) {
    background: #f8fafc;
}

.invoice-table tbody tr:hover {
    background: #f1f5f9;
}

.item-description {
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 3px;
}

.item-details {
    font-size: 11px;
    color: #64748b;
}

.text-center {
    text-align: center;
}

.text-left {
    text-align: left;
}

/* ===== TOTALS SECTION ===== */
.totals-section {
    padding: 0 24px 10px;
    display: flex;
    justify-content: flex-end;
}

.totals-table {
    width: 350px;
    border-collapse: collapse;
}

.totals-table td {
    padding: 5px 12px;
    font-size: 13px;
}

.totals-table .label {
    text-align: right;
    color: #64748b;
    font-weight: 400;
}

.totals-table .value {
    text-align: left;
    color: #1e293b;
    font-weight: 700;
}

.totals-table .grand-total {
    background-color: #1e3c72;
    color: white;
    font-size: 16px;
    font-weight: 700;
}

.totals-table .grand-total td {
    padding: 8px 12px;
}

.totals-table .discount-row .value {
    color: #ef4444;
}

/* ===== FOOTER SECTION ===== */
.invoice-footer {
    padding: 10px 24px;
    background: #f8fafc;
    border-top: 1px solid #e2e8f0;
}

.footer-grid {
    width: 100%;
    border-collapse: separate;
    border-spacing: 15px 0;
    margin-bottom: 8px;
}

.footer-grid > tr > td {
    width: 50%;
    vertical-align: top;
}

.signature-box {
    text-align: center;
    padding: 8px;
    background: white;
    border-radius: 8px;
    border: 2px dashed #cbd5e1;
}

.signature-box h4 {
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: #64748b;
    margin-bottom: 5px;
}

.signature-line {
    width: 80%;
    height: 2px;
    background: #94a3b8;
    margin: 0 auto;
}

.footer-notes {
    background: #fffbeb;
    border-right: 4px solid #f59e0b;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.footer-notes h4 {
    font-size: 13px;
    color: #92400e;
    margin-bottom: 10px;
    font-weight: 700;
}

.footer-notes p {
    font-size: 12px;
    color: #a16207;
    line-height: 1.8;
}

.copyright {
    text-align: center;
    padding-top: 20px;
    border-top: 1px solid #e2e8f0;
}

.copyright p {
    font-size: 11px;
    color: #94a3b8;
}

.copyright .company-name {
    font-weight: 700;
    color: #475569;
}

/* ===== UTILITY CLASSES ===== */
.text-primary { color: #1e3c72; }
.text-success { color: #10b981; }
.text-danger { color: #ef4444; }
.text-warning { color: #f59e0b; }
.font-bold { font-weight: 700; }
.font-extrabold { font-weight: 700; }
</style>
</head>
<body>
@php
    $nights        = $reservation->nights;
    $pricePerNight = $nights > 0 ? round($reservation->total_amount / $nights, 0) : 0;
    $roomTotal     = $pricePerNight * $nights;
    $extraTotal    = $reservation->extraCharges->sum('amount');
    $subtotal      = $roomTotal + $extraTotal;
    $discount      = (float)($reservation->discount_amount ?? 0);
    $total         = (float)$reservation->total_amount;
    $paid          = (float)$reservation->paid_amount;
    $balance       = $total - $paid;
    $isPaid        = $balance <= 0;
    $cur           = $reservation->currency_symbol;
    $invNo         = str_pad($reservation->id, 6, '0', STR_PAD_LEFT);
    $methodMap = ['cash'=>'نقدي','pos'=>'POS','bank_transfer'=>'تحويل بنكي'];
    $typeMap   = ['reservation'=>'دفعة حجز','renewal'=>'تجديد','compensation'=>'تعويض','extra_service'=>'خدمة إضافية'];
@endphp

<div class="invoice-container">
    <!-- Header -->
    <header class="invoice-header">
        <table class="header-content">
            <tr>
                <td class="invoice-info">
                    <div class="label">فاتورة</div>
                    <div class="invoice-number">#{{ $invNo }}</div>
                    <div class="invoice-date">{{ now()->format('Y/m/d') }}</div>
                    <span class="status-badge {{ $isPaid ? 'status-paid' : 'status-due' }}">
                        {{ $isPaid ? 'مسددة بالكامل' : 'متبقي مبلغ' }}
                    </span>
                </td>
                <td class="brand-section">
                    <div class="logo">ف</div>
                    <h1>الفندق السعودي</h1>
                    <p class="tagline">THE SAUDI HOTEL</p>
                </td>
            </tr>
        </table>
    </header>

    <!-- Client Info -->
    <section class="client-section">
        <table class="client-grid">
        <tr>
            <td>
            <div class="client-card">
                <h3>تفاصيل الإقامة</h3>
                <div class="info-row">
                    <span class="label">الغرفة:</span>
                    <span class="value">{{ $reservation->display_room_number }} ({{ $reservation->room_type_label }})</span>
                </div>
                <div class="info-row">
                    <span class="label">الدخول:</span>
                    <span class="value">{{ $reservation->check_in_date?->format('Y/m/d') }}</span>
                </div>
                <div class="info-row">
                    <span class="label">الخروج:</span>
                    <span class="value">{{ $reservation->check_out_date?->format('Y/m/d') }}</span>
                </div>
                <div class="info-row">
                    <span class="label">المدة:</span>
                    <span class="value">{{ $nights }} {{ $nights == 1 ? 'ليلة' : 'ليالٍ' }}</span>
                </div>
            </div>
            </td>
            <td>
            <div class="client-card">
                <h3>بيانات النزيل</h3>
                <div class="info-row">
                    <span class="label">الاسم:</span>
                    <span class="value">{{ $reservation->guest?->full_name ?? '—' }}</span>
                </div>
                @if($reservation->guest?->id_number)
                <div class="info-row">
                    <span class="label">رقم الهوية:</span>
                    <span class="value">{{ $reservation->guest->id_number }}</span>
                </div>
                @endif
                @if($reservation->guest?->phone)
                <div class="info-row">
                    <span class="label">الجوال:</span>
                    <span class="value">{{ $reservation->guest->phone }}</span>
                </div>
                @endif
            </div>
            </td>
        </tr>
        </table>
    </section>

    <!-- Invoice Table -->
    <section class="invoice-table-section">
        <table class="invoice-table">
            <thead>
                <tr>
                    <th style="width: 25%;" class="text-left">الإجمالي</th>
                    <th style="width: 20%;" class="text-center">سعر الوحدة</th>
                    <th style="width: 15%;" class="text-center">الكمية</th>
                    <th style="width: 40%; text-align: right;">البيان</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="text-left font-bold">{{ number_format($roomTotal, 0) }} {{ $cur }}</td>
                    <td class="text-center">{{ number_format($pricePerNight, 0) }} {{ $cur }}</td>
                    <td class="text-center">{{ $nights }}</td>
                    <td style="text-align: right;">
                        <div class="item-description">إقامة - غرفة {{ $reservation->display_room_number }}</div>
                        <div class="item-details">{{ $reservation->room_type_label }} | {{ $nights }} {{ $nights == 1 ? 'ليلة' : 'ليالٍ' }}</div>
                    </td>
                </tr>

                @foreach($reservation->extraCharges as $charge)
                <tr>
                    <td class="text-left font-bold">{{ number_format($charge->amount, 0) }} {{ $cur }}</td>
                    <td class="text-center">{{ number_format($charge->amount, 0) }} {{ $cur }}</td>
                    <td class="text-center">1</td>
                    <td style="text-align: right;">
                        <div class="item-description">{{ $charge->description ?: $charge->type }}</div>
                        <div class="item-details">رسوم إضافية</div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </section>

    <!-- Totals Section -->
    <section class="totals-section">
        <table class="totals-table">
            <tr>
                <td class="label">المجموع الفرعي:</td>
                <td class="value">{{ number_format($subtotal, 0) }} {{ $cur }}</td>
            </tr>
            @if($discount > 0)
            <tr class="discount-row">
                <td class="label">الخصم:</td>
                <td class="value">-{{ number_format($discount, 0) }} {{ $cur }}</td>
            </tr>
            @endif
            <tr class="grand-total">
                <td class="label">الإجمالي:</td>
                <td class="value">{{ number_format($total, 0) }} {{ $cur }}</td>
            </tr>
            <tr>
                <td class="label">المدفوع:</td>
                <td class="value" style="color: #10b981;">{{ number_format($paid, 0) }} {{ $cur }}</td>
            </tr>
            @if(!$isPaid)
            <tr style="background: #fef2f2;">
                <td class="label" style="color: #ef4444;">المتبقي:</td>
                <td class="value" style="color: #ef4444; font-weight: 700;">{{ number_format(abs($balance), 0) }} {{ $cur }}</td>
            </tr>
            @else
            <tr style="background: #f0fdf4;">
                <td class="label" style="color: #10b981;">الحالة:</td>
                <td class="value" style="color: #10b981; font-weight: 700;">✓ مسددة بالكامل</td>
            </tr>
            @endif
        </table>
    </section>

    <!-- Payments History -->
    @if($reservation->payments->count() > 0)
    <section class="payments-section" style="padding: 0 24px 10px;">
        <h3 style="font-size: 14px; color: #64748b; margin-bottom: 5px; text-transform: uppercase; letter-spacing: 1px;">سجل المدفوعات</h3>
        <table class="payments-table" style="width: 100%; border-collapse: collapse; font-size: 12px;">
            <thead>
                <tr style="background: #f1f5f9;">
                    <th style="padding: 8px; text-align: left; font-weight: 700; color: #475569;">المبلغ</th>
                    <th style="padding: 8px; text-align: right; font-weight: 700; color: #475569;">الطريقة</th>
                    <th style="padding: 8px; text-align: right; font-weight: 700; color: #475569;">النوع</th>
                    <th style="padding: 8px; text-align: right; font-weight: 700; color: #475569;">التاريخ</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reservation->payments as $p)
                <tr style="border-bottom: 1px solid #e2e8f0;">
                    <td style="padding: 8px; text-align: left; color: #10b981; font-weight: 700;">{{ number_format($p->amount, 0) }} {{ $cur }}</td>
                    <td style="padding: 8px; text-align: right; color: #64748b;">{{ $methodMap[$p->method] ?? $p->method }}</td>
                    <td style="padding: 8px; text-align: right; color: #1e293b;">{{ $typeMap[$p->type] ?? $p->type }}</td>
                    <td style="padding: 8px; text-align: right; color: #64748b;">{{ $p->payment_date?->format('Y/m/d H:i') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </section>
    @endif

    <!-- Notes Section -->
    @if($reservation->notes)
    <section class="notes-section" style="padding: 0 24px 10px;">
        <div style="background: #fffbeb; border-right: 4px solid #f59e0b; padding: 10px; border-radius: 8px;">
            <h4 style="font-size: 13px; color: #92400e; margin-bottom: 10px; font-weight: 700;">ملاحظات</h4>
            <p style="font-size: 12px; color: #a16207; line-height: 1.8;">{{ $reservation->notes }}</p>
        </div>
    </section>
    @endif

    <!-- Footer -->
    <footer class="invoice-footer">
        <table class="footer-grid">
        <tr>
            <td>
            <div class="signature-box">
                <h4>توقيع النزيل</h4>
                <div class="signature-line"></div>
            </div>
            </td>
            <td>
            <div class="signature-box">
                <h4>ختم وتوقيع الفندق</h4>
                <div class="signature-line"></div>
            </div>
            </td>
        </tr>
        </table>

        <div class="copyright">
            <p>
                <span class="company-name">الفندق السعودي</span> ·
                فاتورة رقم #{{ $invNo }} ·
                صدرت بتاريخ {{ now()->format('Y/m/d H:i') }}
            </p>
        </div>
    </footer>
</div>

</body>
</html>
