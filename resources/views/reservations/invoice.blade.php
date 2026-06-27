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

* { margin: 0; padding: 0; box-sizing: border-box; }

@page { margin: 0; }

body {
    font-family: 'Noto', sans-serif;
    font-size: 9.5pt;
    color: #222;
    direction: rtl;
    text-align: right;
    padding: 12mm 14mm;
}

/* ── HEADER (real table for reliable RTL) ── */
table.head { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
table.head td { vertical-align: middle; border: none; padding: 0; }

.logo-cell { width: 70px; text-align: right; }
.logo-cell img { height: 60px; width: auto; }

.hotel-cell { text-align: right; padding-right: 10px; }
.hotel-ar { font-size: 16pt; font-weight: 700; color: #1f3a5f; line-height: 1.2; }
.hotel-en { font-size: 8pt; color: #999; letter-spacing: 1px; }

.title-cell { text-align: left; }
.title-cell .word { font-size: 20pt; font-weight: 700; color: #1f3a5f; }
.title-cell .num  { font-size: 10pt; color: #444; margin-top: 2px; }
.title-cell .num strong { color: #1f3a5f; }
.title-cell .date { font-size: 8.5pt; color: #888; margin-top: 1px; }

.rule { border: none; border-top: 2px solid #1f3a5f; margin: 4px 0 12px; }

/* ── META ROW (guest + stay) ── */
table.meta { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
table.meta td {
    width: 50%;
    vertical-align: top;
    padding: 8px 10px;
    border: 1px solid #e3e3e3;
    text-align: right;
}
.meta-h {
    font-size: 8pt; font-weight: 700; color: #1f3a5f;
    margin-bottom: 5px; padding-bottom: 3px; border-bottom: 1px solid #eee;
}
.kv { margin-bottom: 3px; font-size: 9pt; }
.k { color: #888; }
.v { font-weight: 700; color: #222; }

/* ── SECTION LABEL ── */
.label {
    font-size: 9pt; font-weight: 700; color: #1f3a5f;
    margin-bottom: 4px;
}

/* ── DATA TABLE ── */
table.data { width: 100%; border-collapse: collapse; margin-bottom: 12px; font-size: 9pt; }
table.data th {
    background: #1f3a5f; color: #fff;
    padding: 6px 8px; text-align: right; font-weight: 700; font-size: 8.5pt;
}
table.data td { padding: 5px 8px; border-bottom: 1px solid #eee; text-align: right; }
table.data tbody tr:last-child td { border-bottom: 1px solid #ddd; }
.num-col { text-align: left; white-space: nowrap; }

/* ── TOTALS ── */
table.totals { width: 48%; border-collapse: collapse; margin-right: auto; margin-bottom: 14px; }
table.totals td { padding: 5px 10px; font-size: 9.5pt; text-align: right; }
table.totals td.amt { text-align: left; font-weight: 700; white-space: nowrap; }
table.totals tr.line td { border-bottom: 1px solid #eee; }
table.totals tr.grand td {
    background: #1f3a5f; color: #fff; font-weight: 700; font-size: 11pt;
}
table.totals tr.paid td  { color: #15803d; }
table.totals tr.due  td  { color: #b91c1c; font-weight: 700; }

/* ── NOTES ── */
.notes {
    border: 1px solid #f0e0b0; background: #fffdf5;
    border-radius: 3px; padding: 6px 10px; font-size: 8.5pt;
    color: #7a5c00; margin-bottom: 12px;
}

/* ── SIGN ── */
table.sign { width: 100%; border-collapse: collapse; margin-top: 18px; }
table.sign td {
    width: 45%; text-align: center; font-size: 8.5pt; color: #888;
    padding-top: 26px; border-top: 1px solid #ccc;
}
table.sign td.gap { width: 10%; border: none; }

/* ── FOOTER ── */
.foot {
    margin-top: 14px; padding-top: 6px; border-top: 1px solid #eee;
    text-align: center; font-size: 7.5pt; color: #aaa;
}
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
    $cur           = $reservation->currency_symbol;
    $invNo         = str_pad($reservation->id, 6, '0', STR_PAD_LEFT);
    $logoPath      = public_path('images/hotel-logo.png');
    $hasLogo       = file_exists($logoPath);
    $methodMap = ['cash'=>'نقدي','pos'=>'POS','bank_transfer'=>'تحويل بنكي'];
    $typeMap   = ['reservation'=>'دفعة حجز','renewal'=>'تجديد','compensation'=>'تعويض','extra_service'=>'خدمة إضافية'];
@endphp

{{-- ═══ HEADER ═══ --}}
<table class="head">
    <tr>
        @if($hasLogo)
        <td class="logo-cell"><img src="{{ $logoPath }}" alt="شعار"></td>
        @endif
        <td class="hotel-cell">
            <div class="hotel-ar">الفندق السعودي</div>
            <div class="hotel-en">THE SAUDI HOTEL</div>
        </td>
        <td class="title-cell">
            <div class="word">فاتورة</div>
            <div class="num">رقم: <strong>#{{ $invNo }}</strong></div>
            <div class="date">التاريخ: {{ now()->format('Y/m/d') }}</div>
        </td>
    </tr>
</table>
<hr class="rule">

{{-- ═══ GUEST + STAY ═══ --}}
<table class="meta">
    <tr>
        <td>
            <div class="meta-h">بيانات النزيل</div>
            <div class="kv"><span class="k">الاسم: </span><span class="v">{{ $reservation->guest?->full_name ?? '—' }}</span></div>
            @if($reservation->guest?->id_number)
            <div class="kv"><span class="k">رقم الهوية: </span><span class="v">{{ $reservation->guest->id_number }}</span></div>
            @endif
            @if($reservation->guest?->nationality)
            <div class="kv"><span class="k">الجنسية: </span><span class="v">{{ $reservation->guest->nationality }}</span></div>
            @endif
            @if($reservation->guest?->phone)
            <div class="kv"><span class="k">الجوال: </span><span class="v">{{ $reservation->guest->phone }}</span></div>
            @endif
        </td>
        <td>
            <div class="meta-h">تفاصيل الإقامة</div>
            <div class="kv"><span class="k">الغرفة: </span><span class="v">{{ $reservation->display_room_number }}</span>@if($reservation->room?->roomType?->name) <span class="k">({{ $reservation->room->roomType->name }})</span>@endif</div>
            <div class="kv"><span class="k">الدخول: </span><span class="v">{{ $reservation->check_in_date?->format('Y/m/d') }}@if($reservation->check_in_time) <span class="k">— {{ $reservation->check_in_time }}</span>@endif</span></div>
            <div class="kv"><span class="k">الخروج: </span><span class="v">{{ $reservation->check_out_date?->format('Y/m/d') }}@if($reservation->check_out_time) <span class="k">— {{ $reservation->check_out_time }}</span>@endif</span></div>
            <div class="kv"><span class="k">المدة: </span><span class="v">{{ $nights }} {{ $nights == 1 ? 'ليلة' : 'ليالٍ' }}</span></div>
        </td>
    </tr>
</table>

{{-- ═══ CHARGES ═══ --}}
<div class="label">تفاصيل الرسوم</div>
<table class="data">
    <thead>
        <tr>
            <th style="width:46%;">البيان</th>
            <th style="width:16%;" class="num-col">الكمية</th>
            <th style="width:19%;" class="num-col">سعر الوحدة</th>
            <th style="width:19%;" class="num-col">الإجمالي</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>إقامة — غرفة {{ $reservation->display_room_number }}</td>
            <td class="num-col">{{ $nights }} ليلة</td>
            <td class="num-col">{{ number_format($pricePerNight, 0) }} {{ $cur }}</td>
            <td class="num-col">{{ number_format($roomTotal, 0) }} {{ $cur }}</td>
        </tr>
        @foreach($reservation->extraCharges as $charge)
        <tr>
            <td>{{ $charge->description ?: $charge->type }} <span style="color:#999;font-size:8pt;">— رسوم إضافية</span></td>
            <td class="num-col">1</td>
            <td class="num-col">{{ number_format($charge->amount, 0) }} {{ $cur }}</td>
            <td class="num-col">{{ number_format($charge->amount, 0) }} {{ $cur }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

{{-- ═══ TOTALS ═══ --}}
<table class="totals">
    <tr class="line">
        <td>المجموع الفرعي</td>
        <td class="amt">{{ number_format($roomTotal + $extraTotal, 0) }} {{ $cur }}</td>
    </tr>
    @if($discount > 0)
    <tr class="line">
        <td>الخصم</td>
        <td class="amt" style="color:#b91c1c;">- {{ number_format($discount, 0) }} {{ $cur }}</td>
    </tr>
    @endif
    <tr class="grand">
        <td>الإجمالي</td>
        <td class="amt">{{ number_format($total, 0) }} {{ $cur }}</td>
    </tr>
    <tr class="paid line">
        <td>المدفوع</td>
        <td class="amt">{{ number_format($paid, 0) }} {{ $cur }}</td>
    </tr>
    @if($isPaid)
    <tr class="paid">
        <td>الحالة</td>
        <td class="amt">مسدّد بالكامل ✓</td>
    </tr>
    @else
    <tr class="due">
        <td>المتبقي</td>
        <td class="amt">{{ number_format(abs($balance), 0) }} {{ $cur }}</td>
    </tr>
    @endif
</table>

{{-- ═══ PAYMENTS ═══ --}}
@if($reservation->payments->count() > 0)
<div class="label">سجل المدفوعات</div>
<table class="data">
    <thead>
        <tr>
            <th>التاريخ</th>
            <th>النوع</th>
            <th>الطريقة</th>
            <th>المستلم</th>
            <th class="num-col">المبلغ</th>
        </tr>
    </thead>
    <tbody>
        @foreach($reservation->payments as $p)
        <tr>
            <td>{{ $p->payment_date?->format('Y/m/d') }}</td>
            <td>{{ $typeMap[$p->type] ?? $p->type }}</td>
            <td>{{ $methodMap[$p->method] ?? $p->method }}</td>
            <td>{{ $p->receivedBy?->name ?? '—' }}</td>
            <td class="num-col" style="color:#15803d;font-weight:700;">{{ number_format($p->amount, 0) }} {{ $cur }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

{{-- ═══ COMPANIONS ═══ --}}
@if($reservation->companions->count() > 0)
<div class="label">المرافقون ({{ $reservation->companions->count() }})</div>
<table class="data">
    <thead>
        <tr>
            <th style="width:8%;">#</th>
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
            <td>{{ $c->full_name }}</td>
            <td>{{ $c->getRelationshipLabel() }}</td>
            <td>{{ $c->nationality ?: '—' }}</td>
            <td>{{ $c->id_number ?? '—' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

{{-- ═══ NOTES ═══ --}}
@if($reservation->notes)
<div class="notes"><strong>ملاحظات:</strong> {{ $reservation->notes }}</div>
@endif

{{-- ═══ SIGNATURES ═══ --}}
<table class="sign">
    <tr>
        <td>توقيع النزيل</td>
        <td class="gap"></td>
        <td>ختم وتوقيع الفندق</td>
    </tr>
</table>

{{-- ═══ FOOTER ═══ --}}
<div class="foot">
    الفندق السعودي · فاتورة رقم #{{ $invNo }} · صدرت بتاريخ {{ now()->format('Y/m/d H:i') }}
</div>

</body>
</html>
