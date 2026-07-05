<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>فاتورة #{{ str_pad($reservation->id, 6, '0', STR_PAD_LEFT) }} — الفندق السعودي</title>
<style>
:root {
  /* ── Brand ── */
  --color-navy:          #0F4C75;
  --color-navy-dark:     #0a3554;
  --color-gold:          #C9A84E;
  --color-gold-light:    #e0c578;

  /* ── Text ── */
  --color-text-primary:     #333333;
  --color-text-secondary:   #666666;
  --color-text-muted:       #9ca3af;
  --color-text-warm-muted:  #9a927f;
  --color-text-on-navy:     #ffffff;
  --color-text-warning:     #7a5c00;

  /* ── State ── */
  --color-success:          #15803d;
  --color-success-bg:       #dcfce7;
  --color-error:            #b91c1c;
  --color-error-bg:         #fee2e2;
  --color-error-bg-light:   #fff5f5;

  /* ── Surfaces ── */
  --color-surface:              #ffffff;
  --color-surface-alt:          #F8F8F8;
  --color-surface-section:      #F0F0F0;
  --color-surface-warm:         #fffaf0;
  --color-surface-mini-header:  #efeadd;

  /* ── Borders ── */
  --color-border:         #e0e0e0;
  --color-border-light:   #eeeeee;
  --color-border-warm:    #f0ece0;
  --color-border-section: #cccccc;

  /* ── Semantic aliases ── */
  --color-primary:      var(--color-navy);
  --color-accent:       var(--color-gold);
  --color-text:         var(--color-text-primary);
  --color-surface-card: var(--color-surface);
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

:root {
  /* Families */
  --font-arabic:  'Noto Naskh Arabic', sans-serif;
  --font-family:  var(--font-arabic);

  /* Scale (pt — invoice/print compatible) */
  --text-xs:    8pt;
  --text-sm:    8.5pt;
  --text-base:  9.5pt;
  --text-md:    10pt;
  --text-lg:    11pt;
  --text-xl:    12.5pt;
  --text-2xl:   18pt;
  --text-3xl:   22pt;

  /* Line height */
  --leading-tight:   1.15;
  --leading-snug:    1.4;
  --leading-normal:  1.6;
  --leading-relaxed: 1.5;

  /* Letter spacing */
  --tracking-wide: 2px;

  /* Weights */
  --font-normal:   400;
  --font-medium:   400;
  --font-semibold: 700;
  --font-bold:     700;
}

:root {
  /* Base scale */
  --space-1:   4px;
  --space-2:   8px;
  --space-3:   12px;
  --space-4:   16px;
  --space-5:   20px;
  --space-6:   24px;
  --space-8:   32px;
  --space-10:  40px;
  --space-12:  48px;
  --space-16:  64px;

  /* Semantic compound values */
  --padding-card:          8px 15px;
  --padding-table-cell:    4px 12px;
  --padding-section-head:  6px 13px;
  --padding-pill:          3px 14px;
  --padding-page:          8mm 14mm;
}

:root {
  --shadow-card: 0 2px 5px rgba(0, 0, 0, 0.05);
  --shadow-sm:   0 1px 3px rgba(0, 0, 0, 0.06);
  --shadow-md:   0 4px 12px rgba(0, 0, 0, 0.08);
}

:root {
  /* Radius */
  --radius-sm:   4px;
  --radius-md:   8px;
  --radius-lg:   12px;
  --radius-pill: 15px;
  --radius-full: 9999px;

  /* Border widths */
  --border-thin:   1px;
  --border-base:   2px;
  --border-thick:  3px;
  --border-accent: 5px;
}

@page {
  margin: 5mm;
  size: A4;
}

* { margin: 0; padding: 0; box-sizing: border-box; }
body {
  font-family: var(--font-family);
  font-size: 9.5pt;
  color: var(--color-text-primary);
  direction: rtl;
  text-align: right;
  background: var(--color-surface);
}
.page {
  background: var(--color-surface);
}

/* Header */
.brand { float: right; width: 55%; display: flex; flex-direction: column; align-items: flex-start; }
.brand-logo { height: 80px; width: auto; margin-bottom: 6px; }
.brand .hotel-ar { font-size: var(--text-2xl); font-weight: var(--font-bold); color: var(--color-navy); line-height: var(--leading-tight); }
.brand .hotel-en { font-size: var(--text-xs); color: var(--color-gold); letter-spacing: var(--tracking-wide); margin-top: 2px; }
.invmeta { float: left; width: 43%; direction: rtl; background: #f7f9fc; border: 1px solid var(--color-border); border-radius: 10px; padding: 10px 16px; box-shadow: 0 2px 6px rgba(0,0,0,0.05); }
.inv-title { font-size: 20pt; font-weight: var(--font-bold); color: var(--color-navy); line-height: 1; margin-bottom: 10px; }
.inv-divider { border: none; border-top: 2px solid var(--color-gold); margin: 0 0 10px; }
.inv-row { display: flex; justify-content: space-between; align-items: center; padding: 3px 0; font-size: 9pt; }
.inv-row-label { color: var(--color-text-secondary); }
.inv-row-val { font-weight: var(--font-bold); color: var(--color-text-primary); }
.inv-row-val.gold { color: var(--color-gold); font-size: 10pt; }
.pill { display: inline-block; margin-top: 10px; padding: 4px 14px; border-radius: var(--radius-pill); font-size: var(--text-sm); font-weight: var(--font-bold); width: 100%; text-align: center; box-sizing: border-box; }
.pill-paid { background: var(--color-success-bg); color: var(--color-success); }
.pill-due  { background: var(--color-error-bg);   color: var(--color-error); }
.clear { clear: both; }
.rule  { clear: both; height: 0; border-top: 3px solid var(--color-navy); margin: 10px 0 0; }
.rule2 { height: 0; border-top: 1px solid var(--color-gold); margin: 0 0 8px; }

/* Info cards */
.card { border: var(--border-thin) solid var(--color-border); background: var(--color-surface); border-radius: var(--radius-md); padding: var(--padding-card); box-shadow: var(--shadow-card); }
.card-r { float: right; width: 49%; }
.card-l { float: left;  width: 49%; }
.card-h { font-size: var(--text-md); font-weight: var(--font-bold); color: var(--color-navy); border-bottom: var(--border-base) solid var(--color-gold); padding-bottom: 6px; margin-bottom: 8px; }
.kv { font-size: var(--text-base); line-height: var(--leading-relaxed); }
.kv .k { color: var(--color-text-secondary); }
.kv .v { color: var(--color-text-primary); font-weight: var(--font-bold); }

/* Section header */
.sec {
  font-size: var(--text-md); font-weight: var(--font-bold); color: var(--color-text-primary);
  background: var(--color-surface-section);
  border-right: var(--border-accent) solid var(--color-gold);
  padding: var(--padding-section-head); margin: 8px 0 5px;
}

/* Tables */
table.items { width: 100%; border-collapse: collapse; font-size: 9pt; }
table.items th { background: var(--color-navy); color: var(--color-text-on-navy); padding: var(--padding-table-cell); font-size: 9pt; font-weight: var(--font-bold); text-align: right; }
table.items td { padding: var(--padding-table-cell); border-bottom: var(--border-thin) solid var(--color-border-light); text-align: right; }
table.items tbody tr:nth-child(even) td { background: var(--color-surface-alt); }
table.items .c { text-align: center !important; white-space: nowrap; }
.muted { color: var(--color-text-warm-muted); font-size: 8pt; }

table.mini { width: 100%; border-collapse: collapse; font-size: 8.5pt; }
table.mini th { background: var(--color-surface-mini-header); color: #4b4636; padding: 5px 9px; font-size: 8pt; font-weight: var(--font-bold); text-align: right; }
table.mini td { padding: 5px 9px; border-bottom: var(--border-thin) solid var(--color-border-warm); text-align: right; }

/* Summary */
.summary-card { flex: 1; border: 1px solid var(--color-border); border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.07); overflow: hidden; }
.sum-row { display: flex; justify-content: space-between; align-items: center; padding: 4px 16px; border-bottom: 1px solid var(--color-border-light); direction: rtl; }
.sum-label { font-size: 9pt; color: var(--color-text-secondary); }
.sum-value { font-size: 9.5pt; font-weight: var(--font-bold); color: var(--color-text-primary); white-space: nowrap; }
.sum-discount .sum-value { color: var(--color-error); }
.sum-sep { border: none; border-top: 2px solid var(--color-gold); margin: 0; }
.sum-total { background: var(--color-navy); padding: 7px 16px; display: flex; justify-content: space-between; align-items: center; direction: rtl; }
.sum-total .sum-label { color: rgba(255,255,255,0.75); font-size: 9.5pt; }
.sum-total .sum-value { color: #fff; font-size: 14pt; font-weight: var(--font-bold); }
.sum-paid { display: flex; justify-content: space-between; align-items: center; padding: 4px 16px; border-bottom: 1px solid var(--color-border-light); direction: rtl; }
.sum-paid .sum-label { font-size: 9pt; color: var(--color-text-secondary); }
.sum-paid .sum-value { font-size: 9.5pt; font-weight: var(--font-bold); color: var(--color-success); }
.sum-status-paid { background: var(--color-success-bg); padding: 6px 16px; text-align: center; color: var(--color-success); font-weight: var(--font-bold); font-size: 9pt; direction: rtl; }
.sum-status-due { background: var(--color-error-bg); padding: 6px 16px; text-align: center; color: var(--color-error); font-weight: var(--font-bold); font-size: 9pt; direction: rtl; }

/* Notes */
.notes { clear: both; border-right: var(--border-accent) solid var(--color-gold); background: var(--color-surface-warm); padding: 4px 15px; font-size: var(--text-md); color: var(--color-text-warning); margin: 6px 0 0; border-radius: var(--radius-sm); }

/* Bottom section */
.bottom-section { width: 100%; border-collapse: collapse; margin-top: 10px; direction: rtl; }
.bottom-section td { vertical-align: bottom; }
.bottom-section .summary-cell { width: 66%; }
.bottom-section .stamp-cell { width: 34%; padding-right: 20px; }
.stamp-area { text-align: center; border-top: 1px solid var(--color-border-section); padding-top: 10px; font-size: 9pt; color: var(--color-text-secondary); }

/* Footer */
.foot { margin-top: 4px; padding-top: 4px; border-top: var(--border-thin) solid var(--color-border-section); text-align: center; font-size: var(--text-xs); color: var(--color-text-muted); }
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
    $logo      = public_path('images/hotel-logo.png');
@endphp
<div class="page">

  <!-- HEADER -->
  <div class="brand">
    @if(file_exists($logo))
    <img class="brand-logo" src="{{ $logo }}" alt="شعار الفندق">
    @endif
    <div class="hotel-ar">الفندق السعودي</div>
    <div class="hotel-en">THE SAUDI HOTEL</div>
  </div>
  <div class="invmeta">
    <div class="inv-title">فاتورة</div>
    <hr class="inv-divider">
    <div class="inv-row">
      <span class="inv-row-label">رقم الفاتورة</span>
      <span class="inv-row-val gold">#{{ $invNo }}</span>
    </div>
    <div class="inv-row">
      <span class="inv-row-label">تاريخ الإصدار</span>
      <span class="inv-row-val">{{ now()->format('Y/m/d') }}</span>
    </div>
    <span class="pill {{ $isPaid ? 'pill-paid' : 'pill-due' }}">
        {{ $isPaid ? 'مسدّدة بالكامل' : 'متبقي مبلغ' }}
    </span>
  </div>
  <div class="rule"></div>
  <div class="rule2"></div>

  <!-- INFO CARDS -->
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
    <div class="kv"><span class="k">الدخول: </span><span class="v">{{ $reservation->check_in_date?->format('Y/m/d') }}{{ $reservation->check_in_time ? ' — '.$reservation->check_in_time : '' }}</span></div>
    <div class="kv"><span class="k">الخروج: </span><span class="v">{{ $reservation->check_out_date?->format('Y/m/d') }}{{ $reservation->check_out_time ? ' — '.$reservation->check_out_time : '' }}</span></div>
    <div class="kv"><span class="k">المدة: </span><span class="v">{{ $nights }} {{ $nights == 1 ? 'ليلة' : 'ليالٍ' }}</span></div>
  </div>
  <div class="clear"></div>

  <!-- CHARGES -->
  <div class="sec">تفاصيل الرسوم</div>
  <table class="items">
    <thead>
      <tr>
        <th style="width:48%;">البيان</th>
        <th class="c" style="width:14%;">الكمية</th>
        <th class="c" style="width:18%;">سعر الوحدة</th>
        <th class="c" style="width:20%;">الإجمالي</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td>إقامة — غرفة {{ $reservation->display_room_number }}</td>
        <td class="c">{{ $nights }} × ليلة</td>
        <td class="c">{{ number_format($pricePerNight, 0) }} {{ $cur }}</td>
        <td class="c">{{ number_format($roomTotal, 0) }} {{ $cur }}</td>
      </tr>
      @foreach($reservation->extraCharges as $charge)
      <tr>
        <td>{{ $charge->description ?: $charge->type }} <span class="muted">— رسوم إضافية</span></td>
        <td class="c">1</td>
        <td class="c">{{ number_format($charge->amount, 0) }} {{ $cur }}</td>
        <td class="c">{{ number_format($charge->amount, 0) }} {{ $cur }}</td>
      </tr>
      @endforeach
    </tbody>
  </table>

  <!-- PAYMENTS -->
  @if($reservation->payments->count() > 0)
  <div class="sec">سجل المدفوعات</div>
  <table class="mini">
    <thead>
      <tr>
        <th style="width:24%;">التاريخ</th>
        <th style="width:18%;">النوع</th>
        <th style="width:18%;">الطريقة</th>
        <th style="width:22%;">المستلم</th>
        <th class="c" style="width:18%;">المبلغ</th>
      </tr>
    </thead>
    <tbody>
      @foreach($reservation->payments as $p)
      <tr>
        <td>{{ $p->payment_date?->format('Y/m/d H:i') }}</td>
        <td>{{ $typeMap[$p->type] ?? $p->type }}</td>
        <td>{{ $methodMap[$p->method] ?? $p->method }}</td>
        <td>{{ $p->receivedBy?->name ?? '—' }}</td>
        <td class="c" style="color:var(--color-success);font-weight:700;">{{ number_format($p->amount, 0) }} {{ $cur }}</td>
      </tr>
      @endforeach
    </tbody>
  </table>
  @endif

  <!-- NOTES -->
  @if($reservation->notes)
  <div class="notes"><strong>ملاحظات:</strong> {{ $reservation->notes }}</div>
  @endif

  <!-- BOTTOM: SUMMARY + STAMP -->
  <table class="bottom-section">
    <tr>
      <td class="summary-cell">
        <div class="summary-card">
          <div class="sum-row">
            <span class="sum-label">المجموع الفرعي</span>
            <span class="sum-value">{{ number_format($subtotal, 0) }} {{ $cur }}</span>
          </div>
          @if($discount > 0)
          <div class="sum-row sum-discount">
            <span class="sum-label">الخصم</span>
            <span class="sum-value">- {{ number_format($discount, 0) }} {{ $cur }}</span>
          </div>
          @endif
          <hr class="sum-sep">
          <div class="sum-total">
            <span class="sum-label">الإجمالي</span>
            <span class="sum-value">{{ number_format($total, 0) }} {{ $cur }}</span>
          </div>
          <div class="sum-paid">
            <span class="sum-label">المدفوع</span>
            <span class="sum-value">{{ number_format($paid, 0) }} {{ $cur }}</span>
          </div>
          @if($isPaid)
          <div class="sum-status-paid">مسدّدة بالكامل</div>
          @else
          <div class="sum-status-due">المتبقي: {{ number_format(abs($balance), 0) }} {{ $cur }}</div>
          @endif
        </div>
      </td>
      <td class="stamp-cell">
        <div class="stamp-area">ختم وتوقيع الفندق</div>
      </td>
    </tr>
  </table>

  <!-- FOOTER -->
  <div class="foot">الفندق السعودي · فاتورة رقم #{{ $invNo }} · صدرت بتاريخ {{ now()->format('Y/m/d H:i') }}</div>
</div>

</body>
</html>
