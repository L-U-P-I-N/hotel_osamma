<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
<meta charset="UTF-8">
<style>
@font-face {
    font-family: 'NotoNaskhArabic';
    font-style: normal; font-weight: 400;
    src: url("{{ storage_path('fonts') }}/NotoNaskhArabic.ttf") format('truetype');
}
@font-face {
    font-family: 'NotoNaskhArabic';
    font-style: normal; font-weight: 700;
    src: url("{{ storage_path('fonts') }}/NotoNaskhArabic-Bold.ttf") format('truetype');
}

* { margin:0; padding:0; box-sizing:border-box; }
body {
    font-family: 'NotoNaskhArabic', sans-serif;
    font-size: 10pt;
    color: #1e293b;
    background: #fff;
    direction: rtl;
    padding: 12mm 14mm 10mm;
}

/* ── HEADER ── */
.header {
    display: table;
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 14px;
    padding-bottom: 12px;
    border-bottom: 3px solid #1e3a5f;
}
.header-right { display: table-cell; vertical-align: middle; }
.header-left  { display: table-cell; vertical-align: middle; text-align: left; }

.hotel-name {
    font-size: 22pt;
    font-weight: 700;
    color: #1e3a5f;
    letter-spacing: -0.5px;
}
.hotel-sub { font-size: 9pt; color: #64748b; margin-top: 2px; }

.invoice-tag {
    display: inline-block;
    background: #1e3a5f;
    color: #fff;
    font-size: 14pt;
    font-weight: 700;
    padding: 6px 18px;
    border-radius: 6px;
    letter-spacing: 1px;
}
.invoice-meta { font-size: 8.5pt; color: #64748b; margin-top: 6px; text-align: left; }
.invoice-meta strong { color: #1e293b; }

/* ── INFO BAND ── */
.info-band {
    display: table;
    width: 100%;
    border-collapse: separate;
    border-spacing: 8px 0;
    margin-bottom: 14px;
}
.info-box {
    display: table-cell;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    padding: 10px 12px;
    vertical-align: top;
    width: 33.33%;
}
.info-box-title {
    font-size: 7.5pt;
    font-weight: 700;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 5px;
    padding-bottom: 4px;
    border-bottom: 1px solid #f1f5f9;
}
.info-line { display: table; width: 100%; margin-bottom: 3px; }
.info-k { display: table-cell; font-size: 8pt; color: #64748b; white-space: nowrap; padding-left: 6px; }
.info-v { display: table-cell; font-size: 9pt; color: #1e293b; font-weight: 700; }

/* ── STATUS BADGES ── */
.badge {
    display: inline-block;
    padding: 2px 10px;
    border-radius: 20px;
    font-size: 8pt;
    font-weight: 700;
}
.badge-checked_in  { background: #dcfce7; color: #15803d; border: 1px solid #86efac; }
.badge-checked_out { background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; }
.badge-paid        { background: #dcfce7; color: #15803d; border: 1px solid #86efac; }
.badge-partial     { background: #fef9c3; color: #a16207; border: 1px solid #fde047; }
.badge-unpaid      { background: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5; }

/* ── SECTION TITLE ── */
.sec-title {
    font-size: 9.5pt;
    font-weight: 700;
    color: #1e3a5f;
    padding: 7px 10px;
    background: #f0f6ff;
    border-right: 4px solid #1e3a5f;
    border-radius: 0 4px 4px 0;
    margin-bottom: 0;
}

/* ── TABLE ── */
table.tbl {
    width: 100%;
    border-collapse: collapse;
    font-size: 9pt;
    margin-bottom: 12px;
}
table.tbl thead tr { background: #1e3a5f; color: #fff; }
table.tbl thead th {
    padding: 6px 10px;
    text-align: right;
    font-size: 8.5pt;
    font-weight: 700;
}
table.tbl tbody td {
    padding: 5px 10px;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
}
table.tbl tbody tr:last-child td { border-bottom: none; }
table.tbl tbody tr:nth-child(even) td { background: #f8fafc; }

/* ── FINANCIAL SUMMARY ── */
.fin-wrap {
    display: table;
    width: 100%;
    border-collapse: separate;
    border-spacing: 10px 0;
    margin-bottom: 12px;
}
.fin-left  { display: table-cell; width: 55%; vertical-align: top; }
.fin-right { display: table-cell; width: 45%; vertical-align: top; }

.fin-box {
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    overflow: hidden;
}
.fin-box-header {
    background: #1e3a5f;
    color: #fff;
    font-size: 9pt;
    font-weight: 700;
    padding: 7px 12px;
}
.fin-row {
    display: table;
    width: 100%;
    padding: 5px 12px;
    border-bottom: 1px solid #f1f5f9;
}
.fin-row:last-child { border-bottom: none; }
.fin-k { display: table-cell; font-size: 9pt; color: #64748b; }
.fin-v { display: table-cell; text-align: left; font-size: 9pt; font-weight: 700; color: #1e293b; }

.fin-total-row {
    display: table;
    width: 100%;
    padding: 7px 12px;
    background: #f0f6ff;
    border-top: 2px solid #1e3a5f;
}
.fin-total-k { display: table-cell; font-size: 10pt; font-weight: 700; color: #1e3a5f; }
.fin-total-v { display: table-cell; text-align: left; font-size: 12pt; font-weight: 700; color: #1e3a5f; }

.balance-box {
    border: 2px solid #1e3a5f;
    border-radius: 8px;
    padding: 14px 16px;
    text-align: center;
}
.balance-label { font-size: 9pt; color: #64748b; margin-bottom: 4px; }
.balance-amount {
    font-size: 22pt;
    font-weight: 700;
    line-height: 1.1;
}
.balance-currency { font-size: 10pt; font-weight: 400; color: #64748b; margin-top: 2px; }
.balance-note { font-size: 8pt; color: #64748b; margin-top: 6px; }

/* ── NIGHTS CARD ── */
.nights-grid {
    display: table;
    width: 100%;
    border-collapse: separate;
    border-spacing: 8px;
    margin-top: 8px;
}
.night-cell {
    display: table-cell;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    padding: 8px;
    text-align: center;
}
.night-cell .lbl { font-size: 7.5pt; color: #94a3b8; }
.night-cell .val { font-size: 14pt; font-weight: 700; color: #1e3a5f; }
.night-cell .sub { font-size: 7.5pt; color: #94a3b8; }

/* ── SIGNATURE ── */
.sig-row {
    display: table;
    width: 100%;
    border-collapse: separate;
    border-spacing: 20px 0;
    margin-top: 14px;
}
.sig-cell {
    display: table-cell;
    text-align: center;
    padding-top: 32px;
    border-top: 1px solid #cbd5e1;
    font-size: 8.5pt;
    color: #64748b;
}

/* ── FOOTER ── */
.footer {
    margin-top: 16px;
    padding-top: 8px;
    border-top: 1px solid #e2e8f0;
    text-align: center;
    font-size: 7.5pt;
    color: #94a3b8;
}
.footer strong { color: #64748b; }

/* ── DIVIDER ── */
.divider { border: none; border-top: 1px solid #e2e8f0; margin: 10px 0; }

/* ── NOTES ── */
.notes-box {
    background: #fffbeb;
    border: 1px solid #fde68a;
    border-radius: 6px;
    padding: 8px 12px;
    font-size: 9pt;
    color: #92400e;
    margin-bottom: 10px;
}
</style>
</head>
<body>
@php
    $nights        = $reservation->nights;
    $pricePerNight = $nights > 0 ? round($reservation->total_amount / $nights, 0) : 0;
    $balance       = (float)$reservation->total_amount - (float)$reservation->paid_amount;
    $isPaid        = $balance <= 0;
    $psMap = ['paid'=>'مكتمل الدفع','partial'=>'دفع جزئي','unpaid'=>'غير مدفوع'];
    $stMap = ['checked_in'=>'مقيم حالياً','checked_out'=>'غادر'];
    $extraTotal = $reservation->extraCharges->sum('amount');
@endphp

{{-- ══════════════════════════════ HEADER ══════════════════════════════ --}}
<div class="header">
    <div class="header-right">
        <div class="hotel-name">فندق السعودي</div>
        <div class="hotel-sub">نظام إدارة الفندق</div>
    </div>
    <div class="header-left">
        <div class="invoice-tag">فاتورة</div>
        <div class="invoice-meta">
            رقم الفاتورة: <strong>#{{ str_pad($reservation->id, 6, '0', STR_PAD_LEFT) }}</strong><br>
            تاريخ الإصدار: <strong>{{ now()->format('d/m/Y') }}</strong>
        </div>
    </div>
</div>

{{-- ══════════════════════════════ INFO BAND ══════════════════════════════ --}}
<div class="info-band">
    {{-- Guest --}}
    <div class="info-box">
        <div class="info-box-title">بيانات النزيل</div>
        <div class="info-line">
            <span class="info-k">الاسم</span>
            <span class="info-v">{{ $reservation->guest?->full_name ?? '—' }}</span>
        </div>
        @if($reservation->guest?->phone)
        <div class="info-line">
            <span class="info-k">الجوال</span>
            <span class="info-v">{{ $reservation->guest->phone }}</span>
        </div>
        @endif
        @if($reservation->guest?->nationality)
        <div class="info-line">
            <span class="info-k">الجنسية</span>
            <span class="info-v">{{ $reservation->guest->nationality }}</span>
        </div>
        @endif
        @if($reservation->guest?->id_number)
        <div class="info-line">
            <span class="info-k">رقم الهوية</span>
            <span class="info-v">{{ $reservation->guest->id_number }}</span>
        </div>
        @endif
    </div>

    {{-- Stay --}}
    <div class="info-box">
        <div class="info-box-title">تفاصيل الإقامة</div>
        <div class="info-line">
            <span class="info-k">الغرفة</span>
            <span class="info-v">{{ $reservation->display_room_number }}</span>
        </div>
        @if($reservation->room?->roomType?->name)
        <div class="info-line">
            <span class="info-k">النوع</span>
            <span class="info-v">{{ $reservation->room->roomType->name }}</span>
        </div>
        @endif
        <div class="info-line">
            <span class="info-k">الدخول</span>
            <span class="info-v" style="color:#15803d;">
                {{ $reservation->check_in_date?->format('d/m/Y') }}
                @if($reservation->check_in_time) <span style="font-weight:400;font-size:8pt;color:#64748b;">{{ $reservation->check_in_time }}</span>@endif
            </span>
        </div>
        <div class="info-line">
            <span class="info-k">الخروج</span>
            <span class="info-v" style="color:#b91c1c;">
                {{ $reservation->check_out_date?->format('d/m/Y') }}
                @if($reservation->check_out_time) <span style="font-weight:400;font-size:8pt;color:#64748b;">{{ $reservation->check_out_time }}</span>@endif
            </span>
        </div>
        <div class="info-line">
            <span class="info-k">المدة</span>
            <span class="info-v">{{ $nights }} {{ $nights == 1 ? 'ليلة' : 'ليالٍ' }}</span>
        </div>
    </div>

    {{-- Status --}}
    <div class="info-box">
        <div class="info-box-title">الحالة</div>
        <div style="margin-bottom:6px;">
            <span class="badge badge-{{ $reservation->status }}">{{ $stMap[$reservation->status] ?? $reservation->status }}</span>
        </div>
        <div>
            <span class="badge badge-{{ $reservation->payment_status }}">{{ $psMap[$reservation->payment_status] ?? $reservation->payment_status }}</span>
        </div>
        @if($reservation->origin || $reservation->purpose)
        <hr class="divider" style="margin:8px 0;">
        @if($reservation->origin)
        <div class="info-line"><span class="info-k">القدوم من</span><span class="info-v">{{ $reservation->origin }}</span></div>
        @endif
        @if($reservation->purpose)
        <div class="info-line"><span class="info-k">الغرض</span><span class="info-v">{{ $reservation->purpose }}</span></div>
        @endif
        @endif
    </div>
</div>

{{-- ══════════════════════════════ CHARGES TABLE ══════════════════════════════ --}}
<div class="sec-title">تفاصيل الرسوم</div>
<table class="tbl">
    <thead>
        <tr>
            <th style="width:50%">البيان</th>
            <th style="text-align:center;">الكمية</th>
            <th style="text-align:center;">سعر الوحدة</th>
            <th style="text-align:left;">المبلغ</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>
                <strong>إقامة — غرفة {{ $reservation->display_room_number }}</strong>
                @if($reservation->room?->roomType?->name)
                <span style="font-size:8pt;color:#64748b;"> ({{ $reservation->room->roomType->name }})</span>
                @endif
            </td>
            <td style="text-align:center;">{{ $nights }}</td>
            <td style="text-align:center;">{{ number_format($pricePerNight, 0) }} {{ $reservation->currency_symbol }}</td>
            <td style="text-align:left;font-weight:700;">{{ number_format($pricePerNight * $nights, 0) }} {{ $reservation->currency_symbol }}</td>
        </tr>
        @foreach($reservation->extraCharges as $charge)
        <tr>
            <td>
                <strong>{{ $charge->description ?: $charge->type }}</strong>
                <span style="font-size:8pt;color:#64748b;"> — رسوم إضافية</span>
            </td>
            <td style="text-align:center;">1</td>
            <td style="text-align:center;">{{ number_format($charge->amount, 0) }} {{ $reservation->currency_symbol }}</td>
            <td style="text-align:left;font-weight:700;color:#b91c1c;">{{ number_format($charge->amount, 0) }} {{ $reservation->currency_symbol }}</td>
        </tr>
        @endforeach
        @if($reservation->discount_amount > 0)
        <tr style="background:#fff7ed;">
            <td colspan="3" style="color:#9a3412;font-weight:700;">خصم</td>
            <td style="text-align:left;font-weight:700;color:#b91c1c;">- {{ number_format($reservation->discount_amount, 0) }} {{ $reservation->currency_symbol }}</td>
        </tr>
        @endif
    </tbody>
</table>

{{-- ══════════════════════════════ FINANCIAL SUMMARY ══════════════════════════════ --}}
<div class="fin-wrap">
    {{-- Payments history on left --}}
    <div class="fin-left">
        @if($reservation->payments->count() > 0)
        <div class="sec-title" style="margin-bottom:0;">سجل المدفوعات</div>
        <table class="tbl">
            <thead>
                <tr>
                    <th>التاريخ</th>
                    <th>الطريقة</th>
                    <th style="text-align:left;">المبلغ</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reservation->payments as $p)
                @php
                    $ml = match($p->method) {
                        'cash'          => 'نقدي',
                        'pos'           => 'POS',
                        'bank_transfer' => 'تحويل بنكي',
                        default         => $p->method,
                    };
                @endphp
                <tr>
                    <td style="white-space:nowrap;font-size:8.5pt;">{{ $p->payment_date?->format('d/m/Y') }}</td>
                    <td style="font-size:8.5pt;">{{ $ml }}</td>
                    <td style="text-align:left;font-weight:700;color:#15803d;white-space:nowrap;">
                        {{ number_format($p->amount, 0) }} {{ $reservation->currency_symbol }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>

    {{-- Financial summary on right --}}
    <div class="fin-right">
        <div class="fin-box">
            <div class="fin-box-header">الملخص المالي</div>
            <div class="fin-row">
                <span class="fin-k">إجمالي الإقامة</span>
                <span class="fin-v">{{ number_format($pricePerNight * $nights, 0) }} {{ $reservation->currency_symbol }}</span>
            </div>
            @if($extraTotal > 0)
            <div class="fin-row">
                <span class="fin-k">رسوم إضافية</span>
                <span class="fin-v" style="color:#b91c1c;">+ {{ number_format($extraTotal, 0) }} {{ $reservation->currency_symbol }}</span>
            </div>
            @endif
            @if($reservation->discount_amount > 0)
            <div class="fin-row">
                <span class="fin-k">خصم</span>
                <span class="fin-v" style="color:#b91c1c;">- {{ number_format($reservation->discount_amount, 0) }} {{ $reservation->currency_symbol }}</span>
            </div>
            @endif
            <div class="fin-total-row">
                <span class="fin-total-k">الإجمالي</span>
                <span class="fin-total-v">{{ number_format($reservation->total_amount, 0) }} {{ $reservation->currency_symbol }}</span>
            </div>
            <div class="fin-row" style="padding:6px 12px;background:#f0fdf4;">
                <span class="fin-k" style="color:#15803d;">المدفوع</span>
                <span class="fin-v" style="color:#15803d;">{{ number_format($reservation->paid_amount, 0) }} {{ $reservation->currency_symbol }}</span>
            </div>
        </div>

        <div style="margin-top:10px;">
            <div class="balance-box" style="border-color:{{ $isPaid ? '#15803d' : '#b91c1c' }};">
                <div class="balance-label">{{ $isPaid ? 'الحساب مسوَّى' : 'المبلغ المتبقي' }}</div>
                <div class="balance-amount" style="color:{{ $isPaid ? '#15803d' : '#b91c1c' }};">
                    {{ $isPaid ? '✓' : number_format(abs($balance), 0) }}
                </div>
                @if(!$isPaid)
                <div class="balance-currency">{{ $reservation->currency_symbol }}</div>
                @endif
                <div class="balance-note">
                    {{ $isPaid ? 'تم استلام كامل المبلغ' : 'يرجى سداد المبلغ المتبقي' }}
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ══════════════════════════════ COMPANIONS ══════════════════════════════ --}}
@if($reservation->companions->count() > 0)
<div class="sec-title">المرافقون ({{ $reservation->companions->count() }})</div>
<table class="tbl">
    <thead>
        <tr>
            <th>#</th>
            <th>الاسم</th>
            <th>صلة القرابة</th>
            <th>الجنسية</th>
            <th>رقم الهوية</th>
        </tr>
    </thead>
    <tbody>
        @foreach($reservation->companions as $i => $c)
        <tr>
            <td>{{ $i + 1 }}</td>
            <td style="font-weight:700;">{{ $c->full_name }}</td>
            <td>{{ $c->getRelationshipLabel() }}</td>
            <td>{{ $c->nationality ?: '—' }}</td>
            <td>{{ $c->id_number ?? '—' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

{{-- ══════════════════════════════ NOTES ══════════════════════════════ --}}
@if($reservation->notes)
<div class="notes-box">
    <strong>ملاحظات: </strong>{{ $reservation->notes }}
</div>
@endif

{{-- ══════════════════════════════ SIGNATURE ══════════════════════════════ --}}
<div class="sig-row">
    <div class="sig-cell" style="width:40%;">توقيع النزيل</div>
    <div class="sig-cell" style="width:20%;"></div>
    <div class="sig-cell" style="width:40%;">ختم وتوقيع الفندق</div>
</div>

{{-- ══════════════════════════════ FOOTER ══════════════════════════════ --}}
<div class="footer">
    <strong>فندق السعودي</strong> &nbsp;|&nbsp;
    فاتورة رقم #{{ str_pad($reservation->id, 6, '0', STR_PAD_LEFT) }} &nbsp;|&nbsp;
    صدرت بتاريخ: {{ now()->format('d/m/Y H:i') }}
</div>

</body>
</html>
