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

/*
   ملاحظة مهمة: dompdf يرتّب أعمدة الجداول من اليسار لليمين دائماً ولا يحترم
   direction:rtl لترتيب الأعمدة. لذلك تُكتب الأعمدة في الكود بترتيب معكوس
   (الأخير منطقياً أولاً) ليظهر العمود الأول على اليمين كما هو مطلوب بالعربية.
*/

/* ── HEADER ── */
table.head { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
.title-cell, .brand-cell { vertical-align: middle; border: none; padding: 0; }

.title-cell { text-align: left; width: 40%; }
.title-cell .word { font-size: 20pt; font-weight: 700; color: #1f3a5f; }
.title-cell .num  { font-size: 10pt; color: #444; margin-top: 2px; }
.title-cell .num strong { color: #1f3a5f; }
.title-cell .date { font-size: 8.5pt; color: #888; margin-top: 1px; }

.brand-cell { text-align: right; }
table.brand { width: 100%; border-collapse: collapse; }
table.brand td { border: none; padding: 0; vertical-align: middle; }
table.brand td.brand-name { width: 100%; }
table.brand td.brand-logo { width: 64px; text-align: left; }
.brand-name { text-align: right; padding-left: 10px; }
.hotel-ar { font-size: 16pt; font-weight: 700; color: #1f3a5f; line-height: 1.2; }
.hotel-en { font-size: 8pt; color: #999; letter-spacing: 1px; }
.brand-logo img { height: 58px; width: auto; display: block; }

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
/* dompdf لا يورّث text-align من الخلية إلى الـ div الداخلي، لذا نصرّح به هنا */
.meta-h {
    font-size: 8pt; font-weight: 700; color: #1f3a5f;
    margin-bottom: 5px; padding-bottom: 3px; border-bottom: 1px solid #eee;
    text-align: right; direction: rtl;
}
.kv { margin-bottom: 3px; font-size: 9pt; text-align: right; direction: rtl; }
.k { color: #888; }
.v { font-weight: 700; color: #222; }

/* ── SECTION LABEL ── */
.label {
    font-size: 9pt; font-weight: 700; color: #1f3a5f;
    margin-bottom: 4px; text-align: right;
}

/* ── DATA TABLE ── */
table.data { width: 100%; border-collapse: collapse; margin-bottom: 12px; font-size: 9pt; }
table.data th {
    background: #1f3a5f; color: #fff;
    padding: 6px 8px; font-weight: 700; font-size: 8.5pt; text-align: right;
}
table.data td { padding: 5px 8px; border-bottom: 1px solid #eee; text-align: right; }
table.data tbody tr:last-child td { border-bottom: 1px solid #ddd; }
/* أعمدة رقمية/قصيرة → توسيط */
.c { text-align: center !important; white-space: nowrap; }

/* ── TOTALS ── */
table.totals-wrap { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
.tw-cell { border: none; padding: 0; vertical-align: top; }
table.totals { width: 100%; border-collapse: collapse; }
table.totals td { padding: 5px 10px; font-size: 9.5pt; }
table.totals td.lbl { text-align: right; }
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
    color: #7a5c00; margin-bottom: 12px; text-align: right;
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

{{-- ═══ HEADER (logo + name on the RIGHT, invoice title on the LEFT) ═══ --}}
<table class="head">
    <tr>
        {{-- left column: invoice title --}}
        <td class="title-cell">
            <div class="word">فاتورة</div>
            <div class="num">رقم: <strong>#{{ $invNo }}</strong></div>
            <div class="date">التاريخ: {{ now()->format('Y/m/d') }}</div>
        </td>
        {{-- right column: brand (name then logo so logo sits at the far right) --}}
        <td class="brand-cell">
            <table class="brand">
                <tr>
                    <td class="brand-name">
                        <div class="hotel-ar">الفندق السعودي</div>
                        <div class="hotel-en">THE SAUDI HOTEL</div>
                    </td>
                    @if($hasLogo)
                    <td class="brand-logo"><img src="{{ $logoPath }}" alt="شعار"></td>
                    @endif
                </tr>
            </table>
        </td>
    </tr>
</table>
<hr class="rule">

{{-- ═══ GUEST + STAY (guest on the RIGHT) ═══ --}}
<table class="meta">
    <tr>
        {{-- left column: stay details --}}
        <td>
            <div class="meta-h">تفاصيل الإقامة</div>
            <div class="kv"><span class="k">الغرفة: </span><span class="v">{{ $reservation->display_room_number }}</span>@if($reservation->room?->roomType?->name) <span class="k">({{ $reservation->room->roomType->name }})</span>@endif</div>
            <div class="kv"><span class="k">الدخول: </span><span class="v">{{ $reservation->check_in_date?->format('Y/m/d') }}@if($reservation->check_in_time) <span class="k">— {{ $reservation->check_in_time }}</span>@endif</span></div>
            <div class="kv"><span class="k">الخروج: </span><span class="v">{{ $reservation->check_out_date?->format('Y/m/d') }}@if($reservation->check_out_time) <span class="k">— {{ $reservation->check_out_time }}</span>@endif</span></div>
            <div class="kv"><span class="k">المدة: </span><span class="v">{{ $nights }} {{ $nights == 1 ? 'ليلة' : 'ليالٍ' }}</span></div>
        </td>
        {{-- right column: guest details --}}
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
    </tr>
</table>

{{-- ═══ CHARGES (البيان on the RIGHT) — columns reversed for RTL ═══ --}}
<div class="label">تفاصيل الرسوم</div>
<table class="data">
    <thead>
        <tr>
            <th class="c" style="width:19%;">الإجمالي</th>
            <th class="c" style="width:19%;">سعر الوحدة</th>
            <th class="c" style="width:16%;">الكمية</th>
            <th style="width:46%;">البيان</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class="c">{{ number_format($roomTotal, 0) }} {{ $cur }}</td>
            <td class="c">{{ number_format($pricePerNight, 0) }} {{ $cur }}</td>
            <td class="c">{{ $nights }} ليلة</td>
            <td>إقامة — غرفة {{ $reservation->display_room_number }}</td>
        </tr>
        @foreach($reservation->extraCharges as $charge)
        <tr>
            <td class="c">{{ number_format($charge->amount, 0) }} {{ $cur }}</td>
            <td class="c">{{ number_format($charge->amount, 0) }} {{ $cur }}</td>
            <td class="c">1</td>
            <td>{{ $charge->description ?: $charge->type }} <span style="color:#999;font-size:8pt;">— رسوم إضافية</span></td>
        </tr>
        @endforeach
    </tbody>
</table>

{{-- ═══ TOTALS (box on the RIGHT; label right, amount left) ═══ --}}
<table class="totals-wrap">
    <tr>
        <td class="tw-cell" style="width:48%;"></td>
        <td class="tw-cell" style="width:52%;">
            <table class="totals">
                <tr class="line">
                    <td class="amt">{{ number_format($roomTotal + $extraTotal, 0) }} {{ $cur }}</td>
                    <td class="lbl">المجموع الفرعي</td>
                </tr>
                @if($discount > 0)
                <tr class="line">
                    <td class="amt" style="color:#b91c1c;">- {{ number_format($discount, 0) }} {{ $cur }}</td>
                    <td class="lbl">الخصم</td>
                </tr>
                @endif
                <tr class="grand">
                    <td class="amt">{{ number_format($total, 0) }} {{ $cur }}</td>
                    <td class="lbl">الإجمالي</td>
                </tr>
                <tr class="paid line">
                    <td class="amt">{{ number_format($paid, 0) }} {{ $cur }}</td>
                    <td class="lbl">المدفوع</td>
                </tr>
                @if($isPaid)
                <tr class="paid">
                    <td class="amt">مسدّد بالكامل ✓</td>
                    <td class="lbl">الحالة</td>
                </tr>
                @else
                <tr class="due">
                    <td class="amt">{{ number_format(abs($balance), 0) }} {{ $cur }}</td>
                    <td class="lbl">المتبقي</td>
                </tr>
                @endif
            </table>
        </td>
    </tr>
</table>

{{-- ═══ PAYMENTS (التاريخ on the RIGHT) — columns reversed ═══ --}}
@if($reservation->payments->count() > 0)
<div class="label">سجل المدفوعات</div>
<table class="data">
    <thead>
        <tr>
            <th class="c">المبلغ</th>
            <th>المستلم</th>
            <th>الطريقة</th>
            <th>النوع</th>
            <th>التاريخ</th>
        </tr>
    </thead>
    <tbody>
        @foreach($reservation->payments as $p)
        <tr>
            <td class="c" style="color:#15803d;font-weight:700;">{{ number_format($p->amount, 0) }} {{ $cur }}</td>
            <td>{{ $p->receivedBy?->name ?? '—' }}</td>
            <td>{{ $methodMap[$p->method] ?? $p->method }}</td>
            <td>{{ $typeMap[$p->type] ?? $p->type }}</td>
            <td>{{ $p->payment_date?->format('Y/m/d') }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

{{-- ═══ COMPANIONS (# on the RIGHT) — columns reversed ═══ --}}
@if($reservation->companions->count() > 0)
<div class="label">المرافقون ({{ $reservation->companions->count() }})</div>
<table class="data">
    <thead>
        <tr>
            <th>رقم الهوية</th>
            <th>الجنسية</th>
            <th>صلة القرابة</th>
            <th>الاسم</th>
            <th class="c" style="width:8%;">#</th>
        </tr>
    </thead>
    <tbody>
        @foreach($reservation->companions as $i => $c)
        <tr>
            <td>{{ $c->id_number ?? '—' }}</td>
            <td>{{ $c->nationality ?: '—' }}</td>
            <td>{{ $c->getRelationshipLabel() }}</td>
            <td>{{ $c->full_name }}</td>
            <td class="c">{{ $i + 1 }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

{{-- ═══ NOTES ═══ --}}
@if($reservation->notes)
<div class="notes"><strong>ملاحظات:</strong> {{ $reservation->notes }}</div>
@endif

{{-- ═══ SIGNATURES (guest signature on the RIGHT) ═══ --}}
<table class="sign">
    <tr>
        <td>ختم وتوقيع الفندق</td>
        <td class="gap"></td>
        <td>توقيع النزيل</td>
    </tr>
</table>

{{-- ═══ FOOTER ═══ --}}
<div class="foot">
    الفندق السعودي · فاتورة رقم #{{ $invNo }} · صدرت بتاريخ {{ now()->format('Y/m/d H:i') }}
</div>

</body>
</html>
