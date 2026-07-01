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
   إعادة هيكلة كاملة: تخطيط قائم على العناصر (float) بدل تكديس الجداول.
   الجداول تُستخدم فقط للبيانات المجدولة (الرسوم/المدفوعات/المرافقون)، ولأن
   dompdf يرتّب أعمدة الجداول يسار→يمين تُكتب الأعمدة معكوسة ليظهر الأول يميناً.
*/
* { margin: 0; padding: 0; box-sizing: border-box; }
@page { margin: 0; }

body {
    font-family: 'Noto', sans-serif;
    font-size: 9.5pt;
    color: #2b2b2b;
    direction: rtl;
    text-align: right;
    padding: 10mm 14mm;
    line-height: 1.4;
}
.clear { clear: both; height: 0; font-size: 0; line-height: 0; }

/* ─── HEADER ─── */
.brand { float: right; width: 60%; }
.brand .logo { float: right; height: 52px; width: auto; margin-left: 12px; }
.brand .hotel-ar { font-size: 17pt; font-weight: 700; color: #0F4C75; line-height: 1.15; padding-top: 3px; }
.brand .hotel-en { font-size: 7.5pt; color: #b8973a; letter-spacing: 2px; }

.invmeta { float: left; width: 38%; text-align: left; }
.invmeta .w { font-size: 21pt; font-weight: 700; color: #1f2937; letter-spacing: 2px; }
.invmeta .n { font-size: 10pt; font-weight: 700; color: #b8973a; margin-top: 1px; }
.invmeta .d { font-size: 8pt; color: #9ca3af; }
.pill { display: inline-block; margin-top: 5px; padding: 2px 12px; border-radius: 11px; font-size: 8pt; font-weight: 700; }
.pill-paid { background: #dcfce7; color: #15803d; }
.pill-due  { background: #fee2e2; color: #b91c1c; }

.rule  { clear: both; height: 0; border-top: 2.5px solid #0F4C75; margin: 10px 0 0; }
.rule2 { height: 0; border-top: 1px solid #c9a84e; margin: 0 0 15px; }

/* ─── INFO CARDS (guest + stay) — floats, no nested tables ─── */
.card   { border: 1px solid #e6e1d5; background: #fbfaf6; border-radius: 5px; padding: 10px 13px; }
.card-r { float: right; width: 49%; }
.card-l { float: left;  width: 49%; }
.card-h { font-size: 8pt; font-weight: 700; color: #0F4C75; border-bottom: 1.5px solid #c9a84e; padding-bottom: 5px; margin-bottom: 6px; }
.kv { font-size: 9pt; line-height: 1.75; }
.kv .k { color: #928a78; }
.kv .v { color: #2b2b2b; font-weight: 700; }

/* ─── SECTION HEADING ─── */
.sec { font-size: 9pt; font-weight: 700; color: #1f2937; background: #f3f4f6;
       border-right: 4px solid #b8973a; padding: 6px 11px; margin: 12px 0 7px; }

/* ─── DATA TABLES ─── */
table.items, table.mini { width: 100%; border-collapse: collapse; }
table.items { font-size: 9pt; }
table.items th { background: #0F4C75; color: #fff; padding: 8px 10px; font-size: 8.5pt; font-weight: 700; text-align: right; }
table.items td { padding: 7px 10px; border-bottom: 1px solid #eee; text-align: right; }
table.items tbody tr:nth-child(even) td { background: #f8fafc; }

table.mini { font-size: 8.5pt; }
table.mini th { background: #efeadd; color: #4b4636; padding: 5px 9px; font-size: 8pt; font-weight: 700; text-align: right; }
table.mini td { padding: 5px 9px; border-bottom: 1px solid #f0ece0; text-align: right; }

.c { text-align: center !important; white-space: nowrap; }
.muted { color: #9a927f; font-size: 8pt; }

/* ─── SUMMARY (totals) — floated left, columns reversed (amount left) ─── */
table.summary { float: left; width: 55%; border-collapse: collapse; border: 1px solid #e6e1d5; border-radius: 5px; }
table.summary td { padding: 7px 13px; font-size: 9.5pt; border-bottom: 1px solid #efeadd; }
table.summary td.sv { text-align: left; font-weight: 700; white-space: nowrap; }
table.summary td.sk { text-align: right; color: #6b6557; }
table.summary tr.grand td { background: #0F4C75; color: #fff; font-size: 11.5pt; font-weight: 700; border-top: 2px solid #c9a84e; border-bottom: none; }
table.summary tr.paid td.sv { color: #15803d; }
table.summary tr.due  td { background: #fdf4f4; border-bottom: none; }
table.summary tr.due  td.sv { color: #b91c1c; }

/* ─── NOTES ─── */
.notes { clear: both; border-right: 4px solid #c9a84e; background: #fdfaf0;
         padding: 8px 12px; font-size: 8.5pt; color: #7a5c00; margin: 15px 0 0; }

/* ─── SIGNATURES ─── */
.signs { margin-top: 20px; }
.sign-r { float: right; width: 42%; text-align: center; border-top: 1px solid #c8bda2; padding-top: 6px; font-size: 8.5pt; color: #6b6557; }
.sign-l { float: left;  width: 42%; text-align: center; border-top: 1px solid #c8bda2; padding-top: 6px; font-size: 8.5pt; color: #6b6557; }

/* ─── FOOTER ─── */
.foot { margin-top: 12px; padding-top: 9px; border-top: 1px solid #c9a84e;
        text-align: center; font-size: 7.5pt; color: #9ca3af; }
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
    $logoPath      = public_path('images/hotel-logo.png');
    $hasLogo       = file_exists($logoPath);
    $methodMap = ['cash'=>'نقدي','pos'=>'POS','bank_transfer'=>'تحويل بنكي'];
    $typeMap   = ['reservation'=>'دفعة حجز','renewal'=>'تجديد','compensation'=>'تعويض','extra_service'=>'خدمة إضافية'];
@endphp

{{-- ═══ HEADER ═══ --}}
<div class="brand">
    @if($hasLogo)<img class="logo" src="{{ $logoPath }}" alt="">@endif
    <div class="hotel-ar">الفندق السعودي</div>
    <div class="hotel-en">THE SAUDI HOTEL</div>
</div>
<div class="invmeta">
    <div class="w">فاتورة</div>
    <div class="n">#{{ $invNo }}</div>
    <div class="d">{{ now()->format('Y/m/d') }}</div>
    <span class="pill {{ $isPaid ? 'pill-paid' : 'pill-due' }}">
        {{ $isPaid ? 'مسدّدة بالكامل' : 'متبقٍ ' . number_format(abs($balance), 0) . ' ' . $cur }}
    </span>
</div>
<div class="rule"></div>
<div class="rule2"></div>

{{-- ═══ GUEST + STAY (floated cards, block key/value) ═══ --}}
<div class="card card-r">
    <div class="card-h">بيانات النزيل</div>
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
</div>
<div class="card card-l">
    <div class="card-h">تفاصيل الإقامة</div>
    <div class="kv"><span class="k">الغرفة: </span><span class="v">{{ $reservation->display_room_number }} ({{ $reservation->room_type_label }})</span></div>
    <div class="kv"><span class="k">الدخول: </span><span class="v">{{ $reservation->check_in_date?->format('Y/m/d') }}@if($reservation->check_in_time) — {{ $reservation->check_in_time }}@endif</span></div>
    <div class="kv"><span class="k">الخروج: </span><span class="v">{{ $reservation->check_out_date?->format('Y/m/d') }}@if($reservation->check_out_time) — {{ $reservation->check_out_time }}@endif</span></div>
    <div class="kv"><span class="k">المدة: </span><span class="v">{{ $nights }} {{ $nights == 1 ? 'ليلة' : 'ليالٍ' }}</span></div>
</div>
<div class="clear"></div>

{{-- ═══ CHARGES (columns reversed for RTL) ═══ --}}
<div class="sec">تفاصيل الرسوم</div>
<table class="items">
    <thead>
        <tr>
            <th class="c" style="width:20%;">الإجمالي</th>
            <th class="c" style="width:18%;">سعر الوحدة</th>
            <th class="c" style="width:14%;">الكمية</th>
            <th style="width:48%;">البيان</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class="c">{{ number_format($roomTotal, 0) }} {{ $cur }}</td>
            <td class="c">{{ number_format($pricePerNight, 0) }} {{ $cur }}</td>
            <td class="c">{{ $nights }} × ليلة</td>
            <td>إقامة — غرفة {{ $reservation->display_room_number }}</td>
        </tr>
        @foreach($reservation->extraCharges as $charge)
        <tr>
            <td class="c">{{ number_format($charge->amount, 0) }} {{ $cur }}</td>
            <td class="c">{{ number_format($charge->amount, 0) }} {{ $cur }}</td>
            <td class="c">1</td>
            <td>{{ $charge->description ?: $charge->type }} <span class="muted">— رسوم إضافية</span></td>
        </tr>
        @endforeach
    </tbody>
</table>

{{-- ═══ SUMMARY (floated left; amount left, label right) ═══ --}}
<table class="summary">
    <tr>
        <td class="sv">{{ number_format($subtotal, 0) }} {{ $cur }}</td>
        <td class="sk">المجموع الفرعي</td>
    </tr>
    @if($discount > 0)
    <tr>
        <td class="sv" style="color:#b91c1c;">- {{ number_format($discount, 0) }} {{ $cur }}</td>
        <td class="sk">الخصم</td>
    </tr>
    @endif
    <tr class="grand">
        <td class="sv">{{ number_format($total, 0) }} {{ $cur }}</td>
        <td class="sk">الإجمالي</td>
    </tr>
    <tr class="paid">
        <td class="sv">{{ number_format($paid, 0) }} {{ $cur }}</td>
        <td class="sk">المدفوع</td>
    </tr>
    @if($isPaid)
    <tr class="paid">
        <td class="sv">✓ مسدّدة بالكامل</td>
        <td class="sk">الحالة</td>
    </tr>
    @else
    <tr class="due">
        <td class="sv">{{ number_format(abs($balance), 0) }} {{ $cur }}</td>
        <td class="sk">المتبقي</td>
    </tr>
    @endif
</table>
<div class="clear"></div>

{{-- ═══ PAYMENTS (columns reversed) ═══ --}}
@if($reservation->payments->count() > 0)
<div class="sec">سجل المدفوعات</div>
<table class="mini">
    <thead>
        <tr>
            <th class="c" style="width:20%;">المبلغ</th>
            <th style="width:22%;">المستلم</th>
            <th style="width:16%;">الطريقة</th>
            <th style="width:18%;">النوع</th>
            <th style="width:24%;">التاريخ</th>
        </tr>
    </thead>
    <tbody>
        @foreach($reservation->payments as $p)
        <tr>
            <td class="c" style="color:#15803d;font-weight:700;">{{ number_format($p->amount, 0) }} {{ $cur }}</td>
            <td>{{ $p->receivedBy?->name ?? '—' }}</td>
            <td>{{ $methodMap[$p->method] ?? $p->method }}</td>
            <td>{{ $typeMap[$p->type] ?? $p->type }}</td>
            <td>{{ $p->payment_date?->format('Y/m/d H:i') }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

{{-- ═══ COMPANIONS (columns reversed) ═══ --}}
@if($reservation->companions->count() > 0)
<div class="sec">المرافقون ({{ $reservation->companions->count() }})</div>
<table class="mini">
    <thead>
        <tr>
            <th style="width:26%;">رقم الهوية</th>
            <th style="width:20%;">الجنسية</th>
            <th style="width:20%;">صلة القرابة</th>
            <th style="width:34%;">الاسم</th>
        </tr>
    </thead>
    <tbody>
        @foreach($reservation->companions as $c)
        <tr>
            <td>{{ $c->id_number ?? '—' }}</td>
            <td>{{ $c->nationality ?: '—' }}</td>
            <td>{{ $c->getRelationshipLabel() }}</td>
            <td>{{ $c->full_name }}</td>
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
<div class="signs">
    <div class="sign-r">ختم وتوقيع الفندق</div>
    <div class="sign-l">توقيع النزيل</div>
    <div class="clear"></div>
</div>

{{-- ═══ FOOTER ═══ --}}
<div class="foot">
    الفندق السعودي · فاتورة رقم #{{ $invNo }} · صدرت بتاريخ {{ now()->format('Y/m/d H:i') }}
</div>

</body>
</html>
