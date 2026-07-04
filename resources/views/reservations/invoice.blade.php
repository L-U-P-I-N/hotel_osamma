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
    color: #333333; /* Darker text for better readability */
    direction: rtl;
    text-align: right;
    padding: 10mm 14mm;
    line-height: 1.6; /* Increased line height */
}
.clear { clear: both; height: 0; font-size: 0; line-height: 0; }

/* ─── HEADER ─── */
.brand { float: right; width: 60%; }
.brand .logo { float: right; height: 52px; width: auto; margin-left: 12px; }
.brand .hotel-ar { font-size: 18pt; /* Slightly larger */ font-weight: 700; color: #0F4C75; line-height: 1.15; padding-top: 3px; }
.brand .hotel-en { font-size: 8pt; /* Slightly larger */ color: #C9A84E; /* Using the consistent gold color */ letter-spacing: 2px; }

.invmeta { float: left; width: 38%; text-align: left; }
.invmeta .w { font-size: 22pt; /* More prominent */ font-weight: 700; color: #0F4C75; /* Changed to primary blue */ letter-spacing: 2px; }
.invmeta .n { font-size: 11pt; /* Slightly larger */ font-weight: 700; color: #C9A84E; /* Consistent gold color */ margin-top: 1px; }
.invmeta .d { font-size: 8pt; color: #9ca3af; }
.pill { display: inline-block; margin-top: 7px; /* More space */ padding: 3px 14px; /* Slightly larger padding */ border-radius: 15px; /* More rounded */ font-size: 8.5pt; /* Slightly larger */ font-weight: 700; text-transform: uppercase; /* Make it stand out */ }
.pill-paid { background: #dcfce7; color: #15803d; }
.pill-due  { background: #fee2e2; color: #b91c1c; }

.rule  { clear: both; height: 0; border-top: 3px solid #0F4C75; margin: 12px 0 0; } /* Thicker and more margin */
.rule2 { height: 0; border-top: 1px solid #C9A84E; margin: 0 0 18px; } /* More margin */

/* ─── INFO CARDS (guest + stay) — floats, no nested tables ─── */
.card   { border: 1px solid #e0e0e0; /* Lighter border */ background: #ffffff; /* White background for clean look */ border-radius: 8px; /* More rounded corners */ padding: 12px 15px; /* More padding */ box-shadow: 0 2px 5px rgba(0,0,0,0.05); /* Subtle shadow */ }
.card-r { float: right; width: 49%; }
.card-l { float: left;  width: 49%; }
.card-h { font-size: 9pt; /* Slightly larger */ font-weight: 700; color: #0F4C75; border-bottom: 2px solid #C9A84E; /* Thicker gold border */ padding-bottom: 6px; margin-bottom: 8px; }
.kv { font-size: 9.5pt; line-height: 1.8; } /* Slightly larger font and line height */
.kv .k { color: #666666; } /* Darker key color */
.kv .v { color: #333333; font-weight: 700; } /* Darker value color */

/* ─── SECTION HEADING ─── */
.sec { font-size: 10pt; /* Slightly larger */ font-weight: 700; color: #333333; background: #F0F0F0; /* Lighter background */
       border-right: 5px solid #C9A84E; /* Thicker gold border */ padding: 8px 13px; /* More padding */ margin: 15px 0 10px; /* More margin */ }

/* ─── DATA TABLES ─── */
table.items, table.mini { width: 100%; border-collapse: collapse; }
table.items { font-size: 9pt; }
table.items th { background: #0F4C75; color: #fff; padding: 10px 12px; /* More padding */ font-size: 9pt; /* Slightly larger */ font-weight: 700; text-align: right; }
table.items td { padding: 9px 12px; /* More padding */ border-bottom: 1px solid #eeeeee; text-align: right; }
table.items tbody tr:nth-child(even) td { background: #F8F8F8; /* Lighter alternating row background */ }

table.mini { font-size: 8.5pt; }
table.mini th { background: #efeadd; color: #4b4636; padding: 5px 9px; font-size: 8pt; font-weight: 700; text-align: right; }
table.mini td { padding: 5px 9px; border-bottom: 1px solid #f0ece0; text-align: right; }

.c { text-align: center !important; white-space: nowrap; }
.muted { color: #9a927f; font-size: 8pt; }

/* ─── SUMMARY (totals) — floated left, columns reversed (amount left) ─── */
table.summary { float: left; width: 55%; border-collapse: collapse; border: 1px solid #e0e0e0; /* Lighter border */ border-radius: 8px; /* More rounded corners */ box-shadow: 0 2px 5px rgba(0,0,0,0.05); /* Subtle shadow */ }
table.summary td { padding: 9px 15px; /* More padding */ font-size: 10pt; /* Slightly larger */ border-bottom: 1px solid #f0f0f0; /* Lighter border */ }
table.summary td.sv { text-align: left; font-weight: 700; white-space: nowrap; }
table.summary td.sk { text-align: right; color: #666666; } /* Darker key color */
table.summary tr.grand td { background: #0F4C75; color: #fff; font-size: 12.5pt; /* More prominent */ font-weight: 700; border-top: 2px solid #C9A84E; border-bottom: none; }
table.summary tr.paid td.sv { color: #15803d; }
table.summary tr.due  td { background: #fff5f5; border-bottom: none; } /* Lighter red background */
table.summary tr.due  td.sv { color: #b91c1c; }

/* ─── NOTES ─── */
.notes { clear: both; border-right: 5px solid #C9A84E; /* Thicker gold border */ background: #fffaf0; /* Lighter background */
         padding: 10px 15px; /* More padding */ font-size: 9pt; /* Slightly larger */ color: #7a5c00; margin: 18px 0 0; /* More margin */ border-radius: 4px; /* Slightly rounded corners */ }

/* ─── SIGNATURES ─── */
.signs { margin-top: 25px; } /* More margin */
.sign-r, .sign-l {
    float: right; /* Keep float for RTL */
    width: 45%; /* Slightly wider */
    text-align: center;
    border-top: 1px solid #cccccc; /* Lighter border */
    padding-top: 8px; /* More padding */
    font-size: 9pt; /* Slightly larger */
    color: #666666; /* Darker text */
}
.sign-l { float: left; } /* Keep float for RTL */

/* ─── FOOTER ─── */
.foot { margin-top: 15px; /* More margin */ padding-top: 10px; /* More padding */ border-top: 1px solid #cccccc; /* Lighter border */
        text-align: center; font-size: 8pt; /* Slightly larger */ color: #999999; /* Darker text */ }
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
    <div class="kv"><span class="v">{{ $reservation->guest?->full_name ?? '—' }}</span><span class="k"> :الاسم</span></div>
    @if($reservation->guest?->id_number)
    <div class="kv"><span class="v">{{ $reservation->guest->id_number }}</span><span class="k"> :رقم الهوية</span></div>
    @endif
    @if($reservation->guest?->nationality)
    <div class="kv"><span class="v">{{ $reservation->guest->nationality }}</span><span class="k"> :الجنسية</span></div>
    @endif
    @if($reservation->guest?->phone)
    <div class="kv"><span class="v">{{ $reservation->guest->phone }}</span><span class="k"> :الجوال</span></div>
    @endif
</div>
<div class="card card-l">
    <div class="card-h">تفاصيل الإقامة</div>
    <div class="kv"><span class="v">{{ $reservation->display_room_number }} ({{ $reservation->room_type_label }})</span><span class="k"> :الغرفة</span></div>
    <div class="kv"><span class="v">{{ $reservation->check_in_date?->format('Y/m/d') }}@if($reservation->check_in_time) — {{ $reservation->check_in_time }}@endif</span><span class="k"> :الدخول</span></div>
    <div class="kv"><span class="v">{{ $reservation->check_out_date?->format('Y/m/d') }}@if($reservation->check_out_time) — {{ $reservation->check_out_time }}@endif</span><span class="k"> :الخروج</span></div>
    <div class="kv"><span class="v">{{ $nights }} {{ $nights == 1 ? 'ليلة' : 'ليالٍ' }}</span><span class="k"> :المدة</span></div>
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
