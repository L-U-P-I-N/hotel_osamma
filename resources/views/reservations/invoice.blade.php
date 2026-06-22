<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
<meta charset="UTF-8">
<style>
@font-face {
    font-family: 'NotoNaskh';
    src: url('{{ storage_path("fonts/NotoNaskhArabic-Regular.ttf") }}') format('truetype');
}
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:'NotoNaskh',Arial,sans-serif; font-size:11pt; color:#1a1a1a; background:#fff; direction:rtl; }
.page { padding:20mm 15mm; }
.header { text-align:center; margin-bottom:10mm; border-bottom:2px solid #0F4C75; padding-bottom:6mm; }
.hotel-name { font-size:20pt; font-weight:bold; color:#0F4C75; }
.invoice-title { font-size:14pt; color:#555; margin-top:3mm; }
.invoice-meta { display:flex; justify-content:space-between; margin-bottom:8mm; }
.meta-box { background:#f8f9fa; border:1px solid #e5e7eb; padding:4mm 6mm; border-radius:4px; min-width:45%; }
.meta-box .label { font-size:8pt; color:#888; margin-bottom:1mm; }
.meta-box .value { font-size:11pt; font-weight:bold; color:#1a1a1a; }
.section-title { font-size:11pt; font-weight:bold; color:#0F4C75; border-right:3px solid #0F4C75; padding-right:3mm; margin:6mm 0 3mm; }
table { width:100%; border-collapse:collapse; font-size:10pt; }
th { background:#0F4C75; color:#fff; padding:3mm 4mm; text-align:right; font-size:9pt; }
td { padding:2.5mm 4mm; border-bottom:1px solid #f0f0f0; }
tr:nth-child(even) td { background:#fafafa; }
.total-row td { font-weight:bold; background:#e8f0f7 !important; }
.discount-row td { color:#dc2626; }
.amount-box { margin:8mm 0; padding:6mm 8mm; background:linear-gradient(135deg,#0F4C75,#1a6fa8); color:#fff; border-radius:6px; text-align:center; }
.amount-box .main-amount { font-size:22pt; font-weight:bold; margin:2mm 0; }
.balance-section { display:flex; gap:3mm; margin:6mm 0; }
.balance-card { flex:1; border:1px solid #e5e7eb; border-radius:4px; padding:3mm; text-align:center; }
.balance-card .lbl { font-size:8pt; color:#888; }
.balance-card .val { font-size:12pt; font-weight:bold; }
.footer { margin-top:12mm; border-top:1px solid #e5e7eb; padding-top:6mm; }
.signatures { display:flex; justify-content:space-between; margin-top:8mm; }
.sig-box { text-align:center; width:40%; }
.sig-line { border-bottom:1px solid #555; margin-bottom:2mm; height:10mm; }
.sig-label { font-size:9pt; color:#555; }
.badge { display:inline-block; padding:1mm 3mm; border-radius:3px; font-size:9pt; font-weight:bold; }
.badge-paid { background:#d1fae5; color:#065f46; }
.badge-partial { background:#fef3c7; color:#92400e; }
.badge-unpaid { background:#fee2e2; color:#991b1b; }
.print-footer { margin-top:4mm; font-size:8pt; color:#aaa; text-align:center; }
</style>
</head>
<body>
<div class="page">

    <div class="header">
        <div class="hotel-name">فندق أسامة</div>
        <div class="invoice-title">فاتورة رسمية — Invoice</div>
    </div>

    <div class="invoice-meta">
        <div class="meta-box">
            <div class="label">رقم الفاتورة</div>
            <div class="value">#{{ str_pad($reservation->id, 6, '0', STR_PAD_LEFT) }}</div>
        </div>
        <div class="meta-box">
            <div class="label">تاريخ الإصدار</div>
            <div class="value">{{ now()->format('d/m/Y H:i') }}</div>
        </div>
    </div>

    {{-- Guest & Room Info --}}
    <div class="section-title">بيانات النزيل والحجز</div>
    <table>
        <tr>
            <td style="width:25%;color:#888;font-size:9pt;">اسم النزيل</td>
            <td style="width:25%;font-weight:bold;">{{ $reservation->guest?->full_name ?? '—' }}</td>
            <td style="width:25%;color:#888;font-size:9pt;">رقم الغرفة</td>
            <td style="width:25%;font-weight:bold;">{{ $reservation->room?->room_number ?? '—' }}</td>
        </tr>
        <tr>
            <td style="color:#888;font-size:9pt;">نوع الغرفة</td>
            <td>{{ $reservation->room?->roomType?->name ?? '—' }}</td>
            <td style="color:#888;font-size:9pt;">عدد الليالي</td>
            <td><strong>{{ $reservation->nights }}</strong> ليلة</td>
        </tr>
        <tr>
            <td style="color:#888;font-size:9pt;">تاريخ الدخول</td>
            <td>{{ $reservation->check_in_date->format('d/m/Y') }}</td>
            <td style="color:#888;font-size:9pt;">تاريخ الخروج</td>
            <td>{{ $reservation->check_out_date->format('d/m/Y') }}</td>
        </tr>
        <tr>
            <td style="color:#888;font-size:9pt;">موظف الاستقبال</td>
            <td>{{ $reservation->createdBy?->name ?? '—' }}</td>
            <td style="color:#888;font-size:9pt;">حالة الدفع</td>
            <td>
                <span class="badge badge-{{ $reservation->payment_status }}">
                    {{ $reservation->payment_status_label }}
                </span>
            </td>
        </tr>
    </table>

    {{-- Items --}}
    <div class="section-title">تفاصيل الفاتورة</div>
    <table>
        <thead>
            <tr>
                <th style="width:5%">#</th>
                <th>البند</th>
                <th style="width:15%">الكمية</th>
                <th style="width:20%">السعر</th>
                <th style="width:20%">الإجمالي</th>
            </tr>
        </thead>
        <tbody>
            @php
                $nights = $reservation->nights ?: 1;
                $basePrice = $nights > 0 ? round((float)$reservation->total_amount / $nights + ($reservation->discount_amount ?? 0) / $nights, 0) : 0;
                $row = 1;
            @endphp
            <tr>
                <td>{{ $row++ }}</td>
                <td>إيجار غرفة {{ $reservation->room?->room_number }} — {{ $reservation->room?->sub_type_label ?? '' }}</td>
                <td>{{ $nights }} ليلة</td>
                <td>{{ number_format($basePrice, 0) }} ر.ي</td>
                <td>{{ number_format($basePrice * $nights, 0) }} ر.ي</td>
            </tr>
            @foreach($reservation->extraCharges as $charge)
            <tr>
                <td>{{ $row++ }}</td>
                <td>{{ $charge->description ?: $charge->type }}</td>
                <td>1</td>
                <td>{{ number_format($charge->amount, 0) }} ر.ي</td>
                <td>{{ number_format($charge->amount, 0) }} ر.ي</td>
            </tr>
            @endforeach
            @if($reservation->discount_amount > 0)
            <tr class="discount-row">
                <td>—</td>
                <td>خصم: {{ $reservation->discount_reason ?: ($reservation->discount_type === 'percent' ? $reservation->discount_value.'%' : 'ثابت') }}</td>
                <td>—</td>
                <td>—</td>
                <td style="color:#dc2626;">- {{ number_format($reservation->discount_amount, 0) }} ر.ي</td>
            </tr>
            @endif
            <tr class="total-row">
                <td colspan="4" style="text-align:left;">المجموع الكلي</td>
                <td>{{ number_format($reservation->total_amount, 0) }} ر.ي</td>
            </tr>
        </tbody>
    </table>

    {{-- Amount Summary --}}
    <div class="amount-box">
        <div style="font-size:9pt;opacity:.8;">إجمالي الفاتورة</div>
        <div class="main-amount">{{ number_format($reservation->total_amount, 0) }} ر.ي</div>
        <div style="font-size:9pt;opacity:.8;">ريال يمني</div>
    </div>

    <div class="balance-section">
        <div class="balance-card">
            <div class="lbl">المدفوع</div>
            <div class="val" style="color:#059669;">{{ number_format($reservation->paid_amount, 0) }}</div>
        </div>
        <div class="balance-card">
            <div class="lbl">المتبقي</div>
            @php $balance = (float)$reservation->total_amount - (float)$reservation->paid_amount; @endphp
            <div class="val" style="color:{{ $balance > 0 ? '#dc2626' : '#059669' }};">{{ number_format($balance, 0) }}</div>
        </div>
        <div class="balance-card">
            <div class="lbl">حالة الدفع</div>
            <div class="val" style="font-size:10pt;">{{ $reservation->payment_status_label }}</div>
        </div>
    </div>

    {{-- Payments Table --}}
    @if($reservation->payments->count())
    <div class="section-title">سجل المدفوعات</div>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>التاريخ</th>
                <th>الطريقة</th>
                <th>المبلغ</th>
                <th>بواسطة</th>
            </tr>
        </thead>
        <tbody>
            @foreach($reservation->payments as $i => $p)
            <tr>
                <td>{{ $i+1 }}</td>
                <td>{{ $p->payment_date?->format('d/m/Y') }}</td>
                <td>{{ match($p->method) { 'cash'=>'نقداً','pos'=>'POS','bank_transfer'=>'تحويل', default=>$p->method } }}</td>
                <td>{{ number_format($p->amount, 0) }} ر.ي</td>
                <td>{{ $p->receivedBy?->name ?? '—' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <div class="footer">
        <div class="signatures">
            <div class="sig-box">
                <div class="sig-line"></div>
                <div class="sig-label">توقيع النزيل</div>
            </div>
            <div class="sig-box">
                <div class="sig-line"></div>
                <div class="sig-label">توقيع الإدارة</div>
            </div>
        </div>
    </div>

    <div class="print-footer">
        طُبع في: {{ now()->format('d/m/Y H:i') }} — فندق أسامة | هذه الفاتورة وثيقة رسمية معتمدة
    </div>

</div>
</body>
</html>
