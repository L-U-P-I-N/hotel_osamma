<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>فاتورة #{{ str_pad($reservation->id, 6, '0', STR_PAD_LEFT) }} — الفندق السعودي</title>
<style>
:root {
  --color-navy:          #0F4C75;
  --color-gold:          #C9A84E;
  --color-text-primary:     #333333;
  --color-text-secondary:   #666666;
  --color-text-muted:       #9ca3af;
  --color-surface:              #ffffff;
  --color-border:         #e0e0e0;
  --color-border-section: #cccccc;
}

@font-face {
  font-family: 'Noto Naskh Arabic';
  font-style: normal;
  font-weight: 400;
  src: url("{{ storage_path('fonts') }}/NotoNaskhArabic.ttf") format('truetype');
}
@font-face {
  font-family: 'Noto Naskh Arabic';
  font-style: normal;
  font-weight: 700;
  src: url("{{ storage_path('fonts') }}/NotoNaskhArabic-Bold.ttf") format('truetype');
}

* { margin: 0; padding: 0; box-sizing: border-box; }
body {
  font-family: 'Noto Naskh Arabic', sans-serif;
  font-size: 9.5pt;
  color: var(--color-text-primary);
  direction: rtl;
  text-align: right;
  background: var(--color-surface);
  padding: 10mm 14mm;
}

.brand { float: right; width: 55%; display: flex; flex-direction: column; align-items: flex-start; }
.brand-logo { height: 72px; width: auto; margin-bottom: 4px; }
.brand .hotel-ar { font-size: 16pt; font-weight: 700; color: var(--color-navy); line-height: 1.15; }
.brand .hotel-en { font-size: 8pt; color: var(--color-gold); letter-spacing: 2px; margin-top: 2px; }
.invmeta { float: left; width: 43%; direction: rtl; background: #f7f9fc; border: 1px solid var(--color-border); border-radius: 10px; padding: 10px 13px; }
.inv-title { font-size: 16pt; font-weight: 700; color: var(--color-navy); line-height: 1; margin-bottom: 6px; }
.inv-divider { border: none; border-top: 2px solid var(--color-gold); margin: 0 0 7px; }
.inv-row { display: flex; justify-content: space-between; align-items: center; padding: 3px 0; font-size: 9pt; }
.inv-row-label { color: var(--color-text-secondary); }
.inv-row-val { font-weight: 700; color: var(--color-text-primary); }
.inv-row-val.gold { color: var(--color-gold); font-size: 10pt; }
.pill { display: inline-block; margin-top: 10px; padding: 4px 14px; border-radius: 15px; font-size: 8.5pt; font-weight: 700; width: 100%; text-align: center; box-sizing: border-box; background: #fff7e0; color: #8a6d1f; }
.clear { clear: both; }
.rule  { clear: both; height: 0; border-top: 3px solid var(--color-navy); margin: 9px 0 0; }
.rule2 { height: 0; border-top: 1px solid var(--color-gold); margin: 0 0 12px; }

.card { border: 1px solid var(--color-border); background: var(--color-surface); border-radius: 8px; padding: 9px 12px; }
.card-r { float: right; width: 49%; }
.card-l { float: left;  width: 49%; }
.card-h { font-size: 10pt; font-weight: 700; color: var(--color-navy); border-bottom: 2px solid var(--color-gold); padding-bottom: 5px; margin-bottom: 6px; }
.kv { width: 100%; border-collapse: collapse; font-size: 9.5pt; }
.kv td { padding: 1px 0; line-height: 1.4; text-align: right; }
.kv .k { color: var(--color-text-secondary); width: 34%; }
.kv .v { color: var(--color-text-primary); font-weight: 700; width: 66%; }

.sec {
  font-size: 10pt; font-weight: 700; color: var(--color-text-primary);
  background: #F0F0F0; border-right: 5px solid var(--color-gold);
  padding: 6px 13px; margin: 14px 0 7px;
}

table.items { width: 100%; border-collapse: collapse; font-size: 9pt; }
table.items th { background: #0F4C75; color: #ffffff; padding: 5px 12px; font-size: 9pt; font-weight: 700; text-align: right; }
table.items td { padding: 5px 12px; border-bottom: 1px solid #eeeeee; text-align: right; }
table.items tbody tr:nth-child(even) td { background: #F8F8F8; }
table.items .c { text-align: center !important; white-space: nowrap; }

.subtotal-row { display: flex; justify-content: space-between; align-items: center; background: var(--color-navy); color: #fff; padding: 10px 16px; margin-top: 8px; border-radius: 0 0 8px 8px; }
.subtotal-row .lbl { font-size: 9.5pt; color: rgba(255,255,255,0.8); }
.subtotal-row .val { font-size: 14pt; font-weight: 700; }

.stamp-area { margin-top: 30px; text-align: center; border-top: 1px solid var(--color-border-section); padding-top: 10px; font-size: 9pt; color: var(--color-text-secondary); width: 40%; }

.foot { margin-top: 20px; padding-top: 5px; border-top: 1px solid var(--color-border-section); text-align: center; font-size: 8pt; color: var(--color-text-muted); }
.foot .scope-note { display: block; margin-top: 2px; color: var(--color-text-muted); }
</style>
</head>
<body>
@php
    $cur   = $reservation->currency_symbol;
    $invNo = str_pad($reservation->id, 6, '0', STR_PAD_LEFT);
    $logo  = \App\Models\Setting::hotelLogo();
    $__p       = \App\Models\Setting::hotelProfile();
    $__contact = \App\Models\Setting::contactLine();
    $totalNights = (int) $selectedSegments->sum('nights');
    $periodFrom  = $selectedSegments->first()?->start_date;
    $periodTo    = $selectedSegments->last()?->end_date;
    $roomLabels  = $selectedSegments->pluck('room.room_number')->filter()->unique()->values();
@endphp
<div class="page">

  <!-- HEADER -->
  <div class="brand">
    @if($logo)
    <img class="brand-logo" src="{{ $logo }}" alt="شعار الفندق">
    @endif
    <div class="hotel-ar">{{ $__p['hotel_name_ar'] ?: 'الفندق السعودي' }}</div>
    <div class="hotel-en">{{ $__p['hotel_name_en'] ?: 'THE SAUDI HOTEL' }}</div>
    @if($__p['hotel_address_ar'] ?? null)
    <div style="font-size:8.5px;color:#777;margin-top:3px;">{{ $__p['hotel_address_ar'] }}</div>
    @endif
    @if($__contact)
    <div style="font-size:8px;color:#888;margin-top:2px;">{{ $__contact }}</div>
    @endif
  </div>
  <div class="invmeta">
    <div class="inv-title">فاتورة</div>
    <hr class="inv-divider">
    <div class="inv-row">
      <span class="inv-row-label">رقم الحجز</span>
      <span class="inv-row-val gold">#{{ $invNo }}</span>
    </div>
    <div class="inv-row">
      <span class="inv-row-label">تاريخ الإصدار</span>
      <span class="inv-row-val">{{ now()->format('Y/m/d') }}</span>
    </div>
    <span class="pill">فترة محدَّدة من الإقامة</span>
  </div>
  <div class="rule"></div>
  <div class="rule2"></div>

  <!-- INFO CARDS -->
  <div class="card card-r">
    <div class="card-h">بيانات النزيل</div>
    <table class="kv" dir="rtl">
      <tr><td class="v">{{ $reservation->guest?->full_name ?? '—' }}</td><td class="k">الاسم:</td></tr>
      @if($reservation->guest?->id_number)
      <tr><td class="v">{{ $reservation->guest->id_number }}</td><td class="k">رقم الهوية:</td></tr>
      @endif
      @if($reservation->guest?->nationality)
      <tr><td class="v">{{ $reservation->guest->nationality }}</td><td class="k">الجنسية:</td></tr>
      @endif
    </table>
  </div>
  <div class="card card-l">
    <div class="card-h">الفترة المحتسَبة</div>
    <table class="kv" dir="rtl">
      <tr><td class="v">{{ $roomLabels->map(fn($n) => 'غرفة '.$n)->implode('، ') ?: '—' }}</td><td class="k">الغرفة:</td></tr>
      <tr><td class="v">{{ $periodFrom?->format('Y/m/d') }}</td><td class="k">من:</td></tr>
      <tr><td class="v">{{ $periodTo?->format('Y/m/d') }}</td><td class="k">إلى:</td></tr>
      <tr><td class="v">{{ $totalNights }} {{ $totalNights == 1 ? 'ليلة' : 'ليالٍ' }}</td><td class="k">المدة:</td></tr>
    </table>
  </div>
  <div class="clear"></div>

  <!-- SELECTED SEGMENTS -->
  <div class="sec">تفصيل الأيام المحتسَبة</div>
  <table class="items">
    <thead>
      <tr>
        <th class="c" style="width:16%;">المبلغ</th>
        <th class="c" style="width:14%;">سعر الليلة</th>
        <th class="c" style="width:10%;">الليالي</th>
        <th class="c" style="width:16%;">إلى</th>
        <th class="c" style="width:16%;">من</th>
        <th style="width:28%;">الغرفة</th>
      </tr>
    </thead>
    <tbody>
      @foreach($selectedSegments as $seg)
      <tr>
        <td class="c" style="font-weight:700;">{{ number_format((float) $seg->amount, 0) }} {{ $cur }}</td>
        <td class="c">{{ number_format((float) $seg->price_per_night, 0) }} {{ $cur }}</td>
        <td class="c">{{ $seg->nights }}</td>
        <td class="c">{{ $seg->end_date?->format('Y/m/d') }}</td>
        <td class="c">{{ $seg->start_date?->format('Y/m/d') }}</td>
        <td>
          @if($seg->room)
            غرفة {{ $seg->room->room_number }} @if($seg->room->roomType) ({{ $seg->room->roomType->name }}) @endif
          @else
            —
          @endif
        </td>
      </tr>
      @endforeach
    </tbody>
  </table>
  <div class="subtotal-row">
    <span class="lbl">إجمالي هذه الفترة</span>
    <span class="val">{{ number_format($subtotal, 0) }} {{ $cur }}</span>
  </div>

  <div class="stamp-area">ختم وتوقيع الفندق</div>

  <!-- FOOTER -->
  <div class="foot">
    الفندق السعودي · فاتورة للحجز رقم #{{ $invNo }} · صدرت بتاريخ {{ now()->format('Y/m/d H:i') }}
    <span class="scope-note">تغطي هذه الفاتورة الفترة المذكورة أعلاه فقط من الإقامة</span>
  </div>
</div>

</body>
</html>
