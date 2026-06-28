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

/*
   تحسين بصري فقط (ألوان/خلفيات/حدود/مسافات بنظام فحمي + ذهبي مستوحى من الشعار). لم يُغيَّر أي
   text-align ولا حجم خط ولا بنية محتوى — كلها كما هي لضمان عرض RTL الصحيح.

   ملاحظة: dompdf يرتّب أعمدة الجداول يسار→يمين دائماً ولا يحترم direction:rtl
   للأعمدة، لذلك تُكتب الأعمدة بترتيب معكوس ليظهر العمود الأول على اليمين.
*/
* { margin: 0; padding: 0; box-sizing: border-box; }

@page { margin: 0; }

body {
    font-family: 'Noto', sans-serif;
    font-size: 9.5pt;
    color: #33302a;
    direction: rtl;
    text-align: right;
    padding: 11mm 13mm;
}

/* ── HEADER ── */
table.head { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
.title-cell, .brand-cell { vertical-align: middle; border: none; padding: 0; }

.title-cell { text-align: left; width: 40%; }
.title-cell .word { font-size: 20pt; font-weight: 700; color: #2e2a20; letter-spacing: 1px; }
.title-cell .num  { font-size: 10pt; font-weight: 700; color: #b8973a; margin-top: 3px; }
.title-cell .date { font-size: 8.5pt; color: #9c9484; margin-top: 1px; }

.brand-cell { text-align: right; }
table.brand { width: 100%; border-collapse: collapse; }
table.brand td { border: none; padding: 0; vertical-align: middle; }
table.brand td.brand-name { width: 100%; }
table.brand td.brand-logo { width: 64px; text-align: left; }
.brand-name { text-align: right; padding-left: 10px; }
.hotel-ar { font-size: 16pt; font-weight: 700; color: #9a7d2e; line-height: 1.2; }
.hotel-en { font-size: 8pt; color: #b8973a; letter-spacing: 2px; }
.brand-logo img { height: 58px; width: auto; display: block; }

/* خط فاصل كحلي بلمسة ذهبية أسفله */
.rule { border: none; border-top: 2.5px solid #2e2a20; border-bottom: 1px solid #c9a84e; height: 2px; margin: 5px 0 13px; }

/* ── META ROW (guest + stay) ── */
table.meta { width: 100%; border-collapse: collapse; margin-bottom: 13px; }
table.meta td {
    width: 50%;
    vertical-align: top;
    padding: 9px 11px;
    border: 1px solid #e7e0ce;
    background: #faf8f1;
    text-align: right;
}
.meta-h {
    font-size: 8pt; font-weight: 700; color: #2e2a20;
    margin-bottom: 6px; padding-bottom: 4px; border-bottom: 1.5px solid #c9a84e;
    text-align: right;
}
/* كل حقل = صفّ بعمودين: القيمة (يسار) ثم التسمية (يمين) لضمان ترتيب RTL صحيح */
table.kvt { width: 100%; border-collapse: collapse; }
table.kvt td { border: none; padding: 2px 0; font-size: 9pt; vertical-align: top; }
td.kv-k { color: #8f8779; text-align: right; white-space: nowrap; width: 1%; padding-left: 7px; }
td.kv-v { color: #33302a; font-weight: 700; text-align: right; }

/* ── SECTION LABEL (شريط بلمسة ذهبية على اليمين) ── */
.label {
    font-size: 9pt; font-weight: 700; color: #2e2a20;
    margin-bottom: 6px; text-align: right;
    padding: 5px 9px 5px 0;
    background: #f5f2ea;
    border-right: 4px solid #b8973a;
}

/* ── DATA TABLE ── */
table.data { width: 100%; border-collapse: collapse; margin-bottom: 13px; font-size: 9pt; }
table.data th {
    background: #2e2a20; color: #fff;
    padding: 7px 9px; font-weight: 700; font-size: 8.5pt; text-align: right;
    border-bottom: 2px solid #c9a84e;
}
table.data td { padding: 6px 9px; border-bottom: 1px solid #efe9da; text-align: right; }
table.data tbody tr:nth-child(even) td { background: #faf6ec; }
table.data tbody tr:last-child td { border-bottom: 1.5px solid #ddd4c1; }
/* أعمدة رقمية/قصيرة → توسيط */
.c { text-align: center !important; white-space: nowrap; }

/* ── TOTALS ── */
table.totals-wrap { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
.tw-cell { border: none; padding: 0; vertical-align: top; }
table.totals { width: 100%; border-collapse: collapse; border: 1px solid #e7e0ce; }
table.totals td { padding: 6px 11px; font-size: 9.5pt; }
table.totals td.lbl { text-align: right; color: #6b6557; }
table.totals td.amt { text-align: left; font-weight: 700; white-space: nowrap; color: #33302a; }
table.totals tr.line td { border-bottom: 1px solid #efe9da; }
table.totals tr.grand td {
    background: #2e2a20; color: #fff; font-weight: 700; font-size: 11pt;
    border-top: 2px solid #c9a84e;
}
table.totals tr.paid td  { color: #15803d; }
table.totals tr.due  td  { color: #b91c1c; font-weight: 700; background: #fdf4f4; }

/* ── NOTES ── */
.notes {
    border: 1px solid #ecdfb4; border-right: 4px solid #c9a84e; background: #fdfaf0;
    padding: 7px 11px; font-size: 8.5pt;
    color: #7a5c00; margin-bottom: 12px; text-align: right;
}

/* ── SIGN ── */
table.sign { width: 100%; border-collapse: collapse; margin-top: 20px; }
table.sign td {
    width: 45%; text-align: center; font-size: 8.5pt; color: #6b6557;
    padding-top: 28px; border-top: 1px solid #c8bda2;
}
table.sign td.gap { width: 10%; border: none; }

/* ── FOOTER ── */
.foot {
    margin-top: 15px; padding-top: 8px; border-top: 1.5px solid #c9a84e;
    text-align: center; font-size: 7.5pt; color: #9c9484;
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
            <div class="num">رقم: #{{ $invNo }}</div>
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
            {{-- كل حقل صفّ بعمودين معكوسين: القيمة يسار، التسمية يمين (لأن dompdf لا يعيد ترتيب bidi) --}}
            <table class="kvt">
                <tr>
                    <td class="kv-v">{{ $reservation->display_room_number }}@if($reservation->room?->roomType?->name) ({{ $reservation->room->roomType->name }})@endif</td>
                    <td class="kv-k">الغرفة:</td>
                </tr>
                <tr>
                    <td class="kv-v">{{ $reservation->check_in_date?->format('Y/m/d') }}@if($reservation->check_in_time) — {{ $reservation->check_in_time }}@endif</td>
                    <td class="kv-k">الدخول:</td>
                </tr>
                <tr>
                    <td class="kv-v">{{ $reservation->check_out_date?->format('Y/m/d') }}@if($reservation->check_out_time) — {{ $reservation->check_out_time }}@endif</td>
                    <td class="kv-k">الخروج:</td>
                </tr>
                <tr>
                    <td class="kv-v">{{ $nights }} {{ $nights == 1 ? 'ليلة' : 'ليالٍ' }}</td>
                    <td class="kv-k">المدة:</td>
                </tr>
            </table>
        </td>
        {{-- right column: guest details --}}
        <td>
            <div class="meta-h">بيانات النزيل</div>
            <table class="kvt">
                <tr>
                    <td class="kv-v">{{ $reservation->guest?->full_name ?? '—' }}</td>
                    <td class="kv-k">الاسم:</td>
                </tr>
                @if($reservation->guest?->id_number)
                <tr>
                    <td class="kv-v">{{ $reservation->guest->id_number }}</td>
                    <td class="kv-k">رقم الهوية:</td>
                </tr>
                @endif
                @if($reservation->guest?->nationality)
                <tr>
                    <td class="kv-v">{{ $reservation->guest->nationality }}</td>
                    <td class="kv-k">الجنسية:</td>
                </tr>
                @endif
                @if($reservation->guest?->phone)
                <tr>
                    <td class="kv-v">{{ $reservation->guest->phone }}</td>
                    <td class="kv-k">الجوال:</td>
                </tr>
                @endif
            </table>
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
            <th>ملاحظة</th>
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
            <td style="font-size:9px;color:#555;">{{ $p->notes ?: '—' }}</td>
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
