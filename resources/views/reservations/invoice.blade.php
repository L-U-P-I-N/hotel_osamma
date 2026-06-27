<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
<meta charset="UTF-8">
<style>
@font-face {
    font-family: 'Noto';
    font-weight: 400;
    src: url("{{ storage_path('fonts') }}/NotoNaskhArabic.ttf") format('truetype');
}
@font-face {
    font-family: 'Noto';
    font-weight: 700;
    src: url("{{ storage_path('fonts') }}/NotoNaskhArabic-Bold.ttf") format('truetype');
}

* { margin:0; padding:0; box-sizing:border-box; }

body {
    font-family: 'Noto', sans-serif;
    font-size: 9pt;
    color: #1a2332;
    background: #fff;
    direction: rtl;
    padding: 9mm 11mm;
}

/* ════════════════════════════════════
   HEADER
════════════════════════════════════ */
.hdr {
    display: table;
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 10px;
    padding-bottom: 10px;
    border-bottom: 3px solid #B8973A;
}
.hdr-right { display: table-cell; vertical-align: middle; width: 50%; }
.hdr-left  { display: table-cell; vertical-align: middle; width: 50%; text-align: left; }

.logo-img { height: 55px; }
.hotel-name { font-size: 17pt; font-weight: 700; color: #B8973A; line-height: 1.1; }
.hotel-sub  { font-size: 8pt; color: #8a7a5a; margin-top: 1px; }

.inv-badge {
    display: inline-block;
    background: #1a2332;
    color: #B8973A;
    font-size: 12pt;
    font-weight: 700;
    padding: 4px 14px;
    border-radius: 4px;
    letter-spacing: 2px;
    margin-bottom: 5px;
}
.inv-meta { font-size: 8pt; color: #6b7280; line-height: 1.8; }
.inv-meta strong { color: #1a2332; }

/* ════════════════════════════════════
   GUEST + STAY STRIP
════════════════════════════════════ */
.strip {
    display: table;
    width: 100%;
    border-collapse: separate;
    border-spacing: 6px 0;
    margin-bottom: 10px;
    background: #f9f7f2;
    border: 1px solid #e8e0cc;
    border-radius: 6px;
    padding: 0;
}
.strip-cell {
    display: table-cell;
    padding: 8px 10px;
    vertical-align: top;
    border-left: 1px solid #e8e0cc;
}
.strip-cell:last-child { border-left: none; }
.strip-title {
    font-size: 7pt;
    font-weight: 700;
    color: #B8973A;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 4px;
    padding-bottom: 3px;
    border-bottom: 1px solid #e8e0cc;
}
.srow { margin-bottom: 2px; font-size: 8.5pt; }
.skey { color: #8a7a5a; font-size: 8pt; }
.sval { font-weight: 700; color: #1a2332; }

/* ════════════════════════════════════
   SECTION TITLE
════════════════════════════════════ */
.sec {
    font-size: 9pt;
    font-weight: 700;
    color: #fff;
    background: #1a2332;
    padding: 5px 10px;
    border-radius: 4px 4px 0 0;
    margin-bottom: 0;
}

/* ════════════════════════════════════
   TABLES
════════════════════════════════════ */
table {
    width: 100%;
    border-collapse: collapse;
    direction: rtl;
    font-size: 8.5pt;
    margin-bottom: 10px;
}
table thead tr { background: #1a2332; color: #B8973A; }
table thead th {
    padding: 5px 8px;
    text-align: right;
    font-size: 8pt;
    font-weight: 700;
    border: 1px solid #0f1828;
}
table tbody td {
    padding: 4px 8px;
    text-align: right;
    border: 1px solid #e8e0cc;
    vertical-align: middle;
}
table tbody tr:nth-child(even) td { background: #faf8f4; }
table tfoot td {
    padding: 4px 8px;
    text-align: right;
    border: 1px solid #e8e0cc;
    font-weight: 700;
    background: #f0ebe0;
}

/* ════════════════════════════════════
   FINANCIAL STRIP
════════════════════════════════════ */
.fin-strip {
    display: table;
    width: 100%;
    border-collapse: collapse;
    border: 1px solid #e8e0cc;
    border-radius: 6px;
    overflow: hidden;
    margin-bottom: 10px;
}
.fin-cell {
    display: table-cell;
    text-align: center;
    padding: 10px 8px;
    border-left: 1px solid #e8e0cc;
    width: 33.33%;
}
.fin-cell:last-child { border-left: none; }
.fin-lbl { font-size: 7.5pt; color: #8a7a5a; margin-bottom: 3px; }
.fin-amt { font-size: 14pt; font-weight: 700; line-height: 1.1; }
.fin-cur { font-size: 8pt; color: #8a7a5a; margin-top: 1px; }
.fin-total { background: #1a2332; }
.fin-total .fin-lbl { color: #B8973A; }
.fin-total .fin-amt { color: #B8973A; }
.fin-paid   { background: #f0fdf4; }
.fin-paid   .fin-amt { color: #15803d; }
.fin-remain { background: #fff7ed; }
.fin-remain .fin-amt { color: #b91c1c; }
.fin-clear  { background: #f0fdf4; }
.fin-clear  .fin-amt { color: #15803d; font-size: 20pt; }

/* ════════════════════════════════════
   BADGES
════════════════════════════════════ */
.badge {
    display: inline-block;
    padding: 1px 8px;
    border-radius: 20px;
    font-size: 7.5pt;
    font-weight: 700;
}
.b-in  { background: #dcfce7; color: #15803d; border:1px solid #86efac; }
.b-out { background: #f1f5f9; color: #475569; border:1px solid #cbd5e1; }
.b-paid    { background: #dcfce7; color: #15803d; }
.b-partial { background: #fef9c3; color: #a16207; }
.b-unpaid  { background: #fee2e2; color: #b91c1c; }

/* ════════════════════════════════════
   NOTES
════════════════════════════════════ */
.notes {
    background: #fffbeb;
    border: 1px solid #fde68a;
    border-radius: 4px;
    padding: 5px 10px;
    font-size: 8.5pt;
    color: #92400e;
    margin-bottom: 10px;
}

/* ════════════════════════════════════
   SIGNATURE
════════════════════════════════════ */
.sig-row {
    display: table;
    width: 100%;
    margin-top: 12px;
    margin-bottom: 10px;
}
.sig-cell {
    display: table-cell;
    width: 42%;
    text-align: center;
    padding-top: 28px;
    border-top: 1px solid #cbd5e1;
    font-size: 8pt;
    color: #6b7280;
}
.sig-gap { display: table-cell; width: 16%; }

/* ════════════════════════════════════
   FOOTER
════════════════════════════════════ */
.footer {
    text-align: center;
    font-size: 7.5pt;
    color: #9ca3af;
    border-top: 1px solid #e8e0cc;
    padding-top: 6px;
}
.footer strong { color: #B8973A; }
</style>
</head>
<body>
@php
    $nights        = $reservation->nights;
    $pricePerNight = $nights > 0 ? round($reservation->total_amount / $nights, 0) : 0;
    $roomTotal     = $pricePerNight * $nights;
    $extraTotal    = $reservation->extraCharges->sum('amount');
    $discount      = (float)($reservation->discount_amount ?? 0);
    $total         = (float)$reservation->total_amount;
    $paid          = (float)$reservation->paid_amount;
    $balance       = $total - $paid;
    $isPaid        = $balance <= 0;
    $logoPath      = public_path('images/hotel-logo.png');
    $stMap = ['checked_in' => 'مقيم حالياً', 'checked_out' => 'غادر'];
    $psMap = ['paid' => 'مكتمل الدفع', 'partial' => 'دفع جزئي', 'unpaid' => 'غير مدفوع'];
    $psBadge = ['paid'=>'b-paid','partial'=>'b-partial','unpaid'=>'b-unpaid'];
    $stBadge = ['checked_in'=>'b-in','checked_out'=>'b-out'];
    $methodMap = ['cash'=>'نقدي','pos'=>'POS','bank_transfer'=>'تحويل بنكي'];
    $typeMap   = ['reservation'=>'دفعة حجز','renewal'=>'تجديد','compensation'=>'تعويض','extra_service'=>'خدمة إضافية'];
@endphp

{{-- ════ HEADER ════ --}}
<div class="hdr">
    <div class="hdr-right">
        @if(file_exists($logoPath))
            <img src="{{ $logoPath }}" class="logo-img">
        @else
            <div class="hotel-name">الفندق السعودي</div>
            <div class="hotel-sub">The Saudi Hotel</div>
        @endif
    </div>
    <div class="hdr-left">
        <div class="inv-badge">فاتورة</div>
        <div class="inv-meta">
            رقم الفاتورة: <strong>#{{ str_pad($reservation->id, 6, '0', STR_PAD_LEFT) }}</strong><br>
            تاريخ الإصدار: <strong>{{ now()->format('d/m/Y') }}</strong><br>
            الحالة:
            <span class="badge {{ $stBadge[$reservation->status] ?? 'b-out' }}">{{ $stMap[$reservation->status] ?? '—' }}</span>
            <span class="badge {{ $psBadge[$reservation->payment_status] ?? '' }}">{{ $psMap[$reservation->payment_status] ?? '—' }}</span>
        </div>
    </div>
</div>

{{-- ════ GUEST + STAY STRIP ════ --}}
<div class="strip">
    {{-- Guest --}}
    <div class="strip-cell" style="width:40%;">
        <div class="strip-title">بيانات النزيل</div>
        <div class="srow"><span class="skey">الاسم: </span><span class="sval">{{ $reservation->guest?->full_name ?? '—' }}</span></div>
        @if($reservation->guest?->id_number)
        <div class="srow"><span class="skey">رقم الهوية: </span><span class="sval">{{ $reservation->guest->id_number }}</span></div>
        @endif
        @if($reservation->guest?->nationality)
        <div class="srow"><span class="skey">الجنسية: </span><span class="sval">{{ $reservation->guest->nationality }}</span></div>
        @endif
        @if($reservation->guest?->phone)
        <div class="srow"><span class="skey">الجوال: </span><span class="sval">{{ $reservation->guest->phone }}</span></div>
        @endif
    </div>
    {{-- Stay --}}
    <div class="strip-cell" style="width:35%;">
        <div class="strip-title">تفاصيل الإقامة</div>
        <div class="srow"><span class="skey">الغرفة: </span><span class="sval">{{ $reservation->display_room_number }}</span>@if($reservation->room?->roomType?->name) <span class="skey">({{ $reservation->room->roomType->name }})</span>@endif</div>
        <div class="srow"><span class="skey">الدخول: </span><span class="sval">{{ $reservation->check_in_date?->format('d/m/Y') }}@if($reservation->check_in_time) <span class="skey">{{ $reservation->check_in_time }}</span>@endif</span></div>
        <div class="srow"><span class="skey">الخروج: </span><span class="sval">{{ $reservation->check_out_date?->format('d/m/Y') }}@if($reservation->check_out_time) <span class="skey">{{ $reservation->check_out_time }}</span>@endif</span></div>
        <div class="srow"><span class="skey">المدة: </span><span class="sval">{{ $nights }} {{ $nights == 1 ? 'ليلة' : 'ليالٍ' }}</span></div>
    </div>
    {{-- Notes / Origin --}}
    <div class="strip-cell" style="width:25%;">
        <div class="strip-title">بيانات إضافية</div>
        @if($reservation->origin)
        <div class="srow"><span class="skey">القدوم من: </span><span class="sval">{{ $reservation->origin }}</span></div>
        @endif
        @if($reservation->purpose)
        <div class="srow"><span class="skey">الغرض: </span><span class="sval">{{ $reservation->purpose }}</span></div>
        @endif
        <div class="srow"><span class="skey">سُجِّل بواسطة: </span><span class="sval">{{ $reservation->createdBy?->name ?? '—' }}</span></div>
        @if($reservation->companions->count() > 0)
        <div class="srow"><span class="skey">المرافقون: </span><span class="sval">{{ $reservation->companions->count() }} أشخاص</span></div>
        @endif
    </div>
</div>

{{-- ════ CHARGES TABLE ════ --}}
<div class="sec">تفاصيل الرسوم</div>
<table>
    <thead>
        <tr>
            <th style="width:50%;">البيان</th>
            <th style="width:15%;text-align:center;">الكمية</th>
            <th style="width:17%;text-align:center;">سعر الوحدة</th>
            <th style="width:18%;text-align:center;">الإجمالي</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>
                إقامة — غرفة <strong>{{ $reservation->display_room_number }}</strong>
                @if($reservation->room?->roomType?->name)
                <span style="color:#6b7280;font-size:8pt;"> ({{ $reservation->room->roomType->name }})</span>
                @endif
            </td>
            <td style="text-align:center;">{{ $nights }} ليلة</td>
            <td style="text-align:center;">{{ number_format($pricePerNight, 0) }} {{ $reservation->currency_symbol }}</td>
            <td style="text-align:center;font-weight:700;">{{ number_format($roomTotal, 0) }} {{ $reservation->currency_symbol }}</td>
        </tr>
        @foreach($reservation->extraCharges as $charge)
        <tr>
            <td>
                <strong>{{ $charge->description ?: $charge->type }}</strong>
                <span style="color:#6b7280;font-size:8pt;"> — رسوم إضافية</span>
            </td>
            <td style="text-align:center;">1</td>
            <td style="text-align:center;">{{ number_format($charge->amount, 0) }} {{ $reservation->currency_symbol }}</td>
            <td style="text-align:center;font-weight:700;color:#b91c1c;">{{ number_format($charge->amount, 0) }} {{ $reservation->currency_symbol }}</td>
        </tr>
        @endforeach
    </tbody>
    @if($discount > 0)
    <tfoot>
        <tr>
            <td colspan="3" style="text-align:right;">خصم</td>
            <td style="text-align:center;color:#b91c1c;">- {{ number_format($discount, 0) }} {{ $reservation->currency_symbol }}</td>
        </tr>
    </tfoot>
    @endif
</table>

{{-- ════ FINANCIAL STRIP ════ --}}
<div class="fin-strip">
    <div class="fin-cell fin-total">
        <div class="fin-lbl">إجمالي الفاتورة</div>
        <div class="fin-amt">{{ number_format($total, 0) }}</div>
        <div class="fin-cur">{{ $reservation->currency_symbol }}</div>
    </div>
    <div class="fin-cell fin-paid">
        <div class="fin-lbl">المبلغ المدفوع</div>
        <div class="fin-amt">{{ number_format($paid, 0) }}</div>
        <div class="fin-cur">{{ $reservation->currency_symbol }}</div>
    </div>
    @if($isPaid)
    <div class="fin-cell fin-clear">
        <div class="fin-lbl">حالة الحساب</div>
        <div class="fin-amt">✓</div>
        <div class="fin-cur">مسوَّى بالكامل</div>
    </div>
    @else
    <div class="fin-cell fin-remain">
        <div class="fin-lbl">المبلغ المتبقي</div>
        <div class="fin-amt">{{ number_format(abs($balance), 0) }}</div>
        <div class="fin-cur">{{ $reservation->currency_symbol }}</div>
    </div>
    @endif
</div>

{{-- ════ PAYMENTS HISTORY ════ --}}
@if($reservation->payments->count() > 0)
<div class="sec">سجل المدفوعات</div>
<table>
    <thead>
        <tr>
            <th>التاريخ</th>
            <th>نوع الدفعة</th>
            <th>طريقة الدفع</th>
            <th>مُستلم بواسطة</th>
            <th style="text-align:center;">المبلغ</th>
        </tr>
    </thead>
    <tbody>
        @foreach($reservation->payments as $p)
        <tr>
            <td style="white-space:nowrap;">{{ $p->payment_date?->format('d/m/Y') }}</td>
            <td>{{ $typeMap[$p->type] ?? $p->type }}</td>
            <td>{{ $methodMap[$p->method] ?? $p->method }}</td>
            <td>{{ $p->receivedBy?->name ?? '—' }}</td>
            <td style="text-align:center;font-weight:700;color:#15803d;white-space:nowrap;">
                {{ number_format($p->amount, 0) }} {{ $reservation->currency_symbol }}
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

{{-- ════ COMPANIONS ════ --}}
@if($reservation->companions->count() > 0)
<div class="sec">المرافقون ({{ $reservation->companions->count() }})</div>
<table>
    <thead>
        <tr>
            <th>#</th>
            <th>الاسم الكامل</th>
            <th>صلة القرابة</th>
            <th>الجنسية</th>
            <th>رقم الهوية</th>
        </tr>
    </thead>
    <tbody>
        @foreach($reservation->companions as $i => $c)
        <tr>
            <td>{{ $i + 1 }}</td>
            <td><strong>{{ $c->full_name }}</strong></td>
            <td>{{ $c->getRelationshipLabel() }}</td>
            <td>{{ $c->nationality ?: '—' }}</td>
            <td>{{ $c->id_number ?? '—' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

{{-- ════ NOTES ════ --}}
@if($reservation->notes)
<div class="notes"><strong>ملاحظات: </strong>{{ $reservation->notes }}</div>
@endif

{{-- ════ SIGNATURES ════ --}}
<div class="sig-row">
    <div class="sig-cell">توقيع النزيل</div>
    <div class="sig-gap"></div>
    <div class="sig-cell">ختم وتوقيع الفندق</div>
</div>

{{-- ════ FOOTER ════ --}}
<div class="footer">
    <strong>الفندق السعودي — The Saudi Hotel</strong> &nbsp;|&nbsp;
    فاتورة رقم #{{ str_pad($reservation->id, 6, '0', STR_PAD_LEFT) }} &nbsp;|&nbsp;
    صدرت بتاريخ: {{ now()->format('d/m/Y H:i') }}
</div>

</body>
</html>
