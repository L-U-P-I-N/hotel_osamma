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
  --leading-relaxed: 1.8;

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
  --padding-card:          12px 15px;
  --padding-table-cell:    9px 12px;
  --padding-section-head:  8px 13px;
  --padding-pill:          3px 14px;
  --padding-page:          10mm 14mm;
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

/* dompdf لا يطبّق هامش @page هنا (تحقّقنا منه بمعزل عن باقي الأنماط) — الهامش
   الفعلي في هذا الكود يُضبط عبر padding على body، بنفس النمط المستخدم في بقية
   قوالب PDF بالمشروع (مثل payments/slip.blade.php). بدون هذا الـpadding يلتصق
   المحتوى بحافة الورقة تماماً عند الطباعة. */
/* dompdf يرتّب أعمدة أي <table> من اليسار لليمين حسب ترتيبها في الكود دائماً،
   ولا يعتد بـ dir="rtl" لعكس هذا الترتيب (تحقّقنا منه بعزل الحالة في اختبار
   منفصل). لذلك كل جدول متعدد الأعمدة في هذا الملف (kv، items، mini،
   bottom-section) يكتب أعمدته بترتيب معكوس: العمود الذي يجب أن يظهر يمين
   القارئ يُكتب أخيراً في الكود. */
* { margin: 0; padding: 0; box-sizing: border-box; }
body {
  font-family: var(--font-family);
  font-size: 9.5pt;
  color: var(--color-text-primary);
  direction: rtl;
  text-align: right;
  background: var(--color-surface);
  padding: var(--padding-page);
}
.page {
  background: var(--color-surface);
}

/* Header */
.brand { float: right; width: 55%; display: flex; flex-direction: column; align-items: flex-start; }
.brand-logo { height: 72px; width: auto; margin-bottom: 4px; }
.brand .hotel-ar { font-size: 16pt; font-weight: var(--font-bold); color: var(--color-navy); line-height: var(--leading-tight); }
.brand .hotel-en { font-size: var(--text-xs); color: var(--color-gold); letter-spacing: var(--tracking-wide); margin-top: 2px; }
.invmeta { float: left; width: 43%; direction: rtl; background: #f7f9fc; border: 1px solid var(--color-border); border-radius: 10px; padding: 10px 13px; box-shadow: 0 2px 6px rgba(0,0,0,0.05); }
.inv-title { font-size: 16pt; font-weight: var(--font-bold); color: var(--color-navy); line-height: 1; margin-bottom: 6px; }
.inv-divider { border: none; border-top: 2px solid var(--color-gold); margin: 0 0 7px; }
.inv-row { display: flex; justify-content: space-between; align-items: center; padding: 3px 0; font-size: 9pt; }
.inv-row-label { color: var(--color-text-secondary); }
.inv-row-val { font-weight: var(--font-bold); color: var(--color-text-primary); }
.inv-row-val.gold { color: var(--color-gold); font-size: 10pt; }
.pill { display: inline-block; margin-top: 10px; padding: 4px 14px; border-radius: var(--radius-pill); font-size: var(--text-sm); font-weight: var(--font-bold); width: 100%; text-align: center; box-sizing: border-box; }
.pill-paid { background: var(--color-success-bg); color: var(--color-success); }
.pill-due  { background: var(--color-error-bg);   color: var(--color-error); }
.clear { clear: both; }
.rule  { clear: both; height: 0; border-top: 3px solid var(--color-navy); margin: 9px 0 0; }
.rule2 { height: 0; border-top: 1px solid var(--color-gold); margin: 0 0 12px; }

/* Info cards */
.card { border: var(--border-thin) solid var(--color-border); background: var(--color-surface); border-radius: var(--radius-md); padding: 9px 12px; box-shadow: var(--shadow-card); }
.card-r { float: right; width: 49%; }
.card-l { float: left;  width: 49%; }
.card-h { font-size: var(--text-md); font-weight: var(--font-bold); color: var(--color-navy); border-bottom: var(--border-base) solid var(--color-gold); padding-bottom: 5px; margin-bottom: 6px; }
.kv { width: 100%; border-collapse: collapse; font-size: var(--text-base); }
.kv td { padding: 1px 0; line-height: var(--leading-snug); text-align: right; }
.kv .k { color: var(--color-text-secondary); width: 34%; }
.kv .v { color: var(--color-text-primary); font-weight: var(--font-bold); width: 66%; }

/* Section header */
.sec {
  font-size: var(--text-md); font-weight: var(--font-bold); color: var(--color-text-primary);
  background: var(--color-surface-section);
  border-right: var(--border-accent) solid var(--color-gold);
  padding: 6px 13px; margin: 12px 0 7px;
}

/* Tables */
/* dompdf silently drops a table cell's content when its background comes from
   a CSS custom property (var()) — confirmed in isolation, and unrelated to RTL
   or the Arabic font. Table cell backgrounds below use literal hex values. */
table.items { width: 100%; border-collapse: collapse; font-size: 9pt; }
table.items th { background: #0F4C75; color: #ffffff; padding: 4px 12px; font-size: 9pt; font-weight: 700; text-align: right; }
table.items td { padding: 4px 12px; border-bottom: 1px solid #eeeeee; text-align: right; }
table.items tbody tr:nth-child(even) td { background: #F8F8F8; }
table.items .c { text-align: center !important; white-space: nowrap; }
.muted { color: var(--color-text-warm-muted); font-size: 8pt; }

table.mini { width: 100%; border-collapse: collapse; font-size: 8.5pt; }
table.mini th { background: #efeadd; color: #4b4636; padding: 4px 9px; font-size: 8pt; font-weight: 700; text-align: right; }
table.mini td { padding: 3px 9px; border-bottom: 1px solid #f0ece0; text-align: right; }
table.mini tr { page-break-inside: avoid; }

/* Summary */
.summary-card { flex: 1; border: 1px solid var(--color-border); border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.07); overflow: hidden; }
.sum-row { display: flex; justify-content: space-between; align-items: center; padding: 6px 16px; border-bottom: 1px solid var(--color-border-light); direction: rtl; }
.sum-label { font-size: 9pt; color: var(--color-text-secondary); }
.sum-value { font-size: 9.5pt; font-weight: var(--font-bold); color: var(--color-text-primary); white-space: nowrap; }
.sum-discount .sum-value { color: var(--color-error); }
.sum-sep { border: none; border-top: 2px solid var(--color-gold); margin: 0; }
.sum-total { background: var(--color-navy); padding: 10px 16px; display: flex; justify-content: space-between; align-items: center; direction: rtl; }
.sum-total .sum-label { color: rgba(255,255,255,0.75); font-size: 9.5pt; }
.sum-total .sum-value { color: #fff; font-size: 14pt; font-weight: var(--font-bold); }
.sum-paid { display: flex; justify-content: space-between; align-items: center; padding: 6px 16px; border-bottom: 1px solid var(--color-border-light); direction: rtl; }
.sum-paid .sum-label { font-size: 9pt; color: var(--color-text-secondary); }
.sum-paid .sum-value { font-size: 9.5pt; font-weight: var(--font-bold); color: var(--color-success); }
.sum-status-paid { background: var(--color-success-bg); padding: 9px 16px; text-align: center; color: var(--color-success); font-weight: var(--font-bold); font-size: 9pt; direction: rtl; }
.sum-status-due { background: var(--color-error-bg); padding: 9px 16px; text-align: center; color: var(--color-error); font-weight: var(--font-bold); font-size: 9pt; direction: rtl; }

/* Notes */
.notes { clear: both; border-right: var(--border-accent) solid var(--color-gold); background: var(--color-surface-warm); padding: 10px 15px; font-size: var(--text-md); color: var(--color-text-warning); margin: 20px 0 0; border-radius: var(--radius-sm); }

/* Bottom section */
.bottom-section { width: 100%; border-collapse: collapse; margin-top: 8px; direction: rtl; page-break-inside: avoid; }
.bottom-section td { vertical-align: bottom; }
.bottom-section .summary-cell { width: 66%; }
.bottom-section .stamp-cell { width: 34%; padding-right: 20px; }
.stamp-area { text-align: center; border-top: 1px solid var(--color-border-section); padding-top: 10px; font-size: 9pt; color: var(--color-text-secondary); }

/* Footer */
.foot { margin-top: 6px; padding-top: 5px; border-top: var(--border-thin) solid var(--color-border-section); text-align: center; font-size: var(--text-xs); color: var(--color-text-muted); }
</style>
</head>
<body>
@php
    $nights        = $reservation->nights;
    // سعر الليلة والمجموع الفرعي يُحسبان على الإجمالي قبل الخصم (gross) حتى يظهر
    // السعر المتفاوَض عليه فعلاً ويتطابق (المجموع الفرعي − الخصم = الإجمالي).
    $grossTotal    = $reservation->gross_total;
    $pricePerNight = $nights > 0 ? round($grossTotal / $nights, 0) : 0;
    $roomTotal     = $grossTotal;
    // سعر مختلف لليلة الأولى (إن وُجد): نعرض سطرَين منفصلين في بيان الإقامة.
    $hasFirstNight   = $reservation->first_night_price !== null && $nights > 1;
    $firstNightPrice = (float) $reservation->first_night_price;
    $otherNightPrice = $hasFirstNight
        ? round(($grossTotal - $firstNightPrice) / ($nights - 1), 0)
        : $pricePerNight;
    // فترات الغرفة المفصّلة (الحجز الأولي + التجديدات) — تُعرض كبنود منفصلة إذا
    // تطابق مجموعها مع إجمالي الغرفة قبل الخصم.
    $roomSegments = $reservation->segments;
    $showSegments = $roomSegments->isNotEmpty()
        && abs(round((float) $roomSegments->sum('amount'), 2) - (float) $grossTotal) <= 1.0;
    // أوقات الفترات: أول فترة بوقت الوصول، آخرها بوقت الخروج، والحدود الداخلية 1 ظهراً
    $segBoundaryTime = sprintf('%02d:00', \App\Models\Reservation::AUTO_RENEW_BOUNDARY_HOUR);
    $segCheckInTime  = $reservation->check_in_time  ? \Illuminate\Support\Str::substr($reservation->check_in_time, 0, 5)  : $segBoundaryTime;
    $segCheckOutTime = $reservation->check_out_time ? \Illuminate\Support\Str::substr($reservation->check_out_time, 0, 5) : $segBoundaryTime;
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
    <table class="kv" dir="rtl">
      <tr><td class="v">{{ $reservation->guest?->full_name ?? '—' }}</td><td class="k">الاسم:</td></tr>
      @if($reservation->guest?->id_number)
      <tr><td class="v">{{ $reservation->guest->id_number }}</td><td class="k">رقم الهوية:</td></tr>
      @endif
      @if($reservation->guest?->nationality)
      <tr><td class="v">{{ $reservation->guest->nationality }}</td><td class="k">الجنسية:</td></tr>
      @endif
      @if($reservation->guest?->phone)
      <tr><td class="v">{{ $reservation->guest->phone }}</td><td class="k">الجوال:</td></tr>
      @endif
    </table>
  </div>
  <div class="card card-l">
    <div class="card-h">تفاصيل الإقامة</div>
    <table class="kv" dir="rtl">
      <tr><td class="v">{{ $reservation->display_room_number }} ({{ $reservation->room_type_label }})</td><td class="k">الغرفة:</td></tr>
      <tr><td class="v">{{ $reservation->check_in_date?->format('Y/m/d') }}{{ $reservation->check_in_time ? ' — '.$reservation->check_in_time : '' }}</td><td class="k">الدخول:</td></tr>
      <tr><td class="v">{{ $reservation->check_out_date?->format('Y/m/d') }}{{ $reservation->check_out_time ? ' — '.$reservation->check_out_time : '' }}</td><td class="k">الخروج:</td></tr>
      <tr><td class="v">{{ $nights }} {{ $nights == 1 ? 'ليلة' : 'ليالٍ' }}</td><td class="k">المدة:</td></tr>
      {{-- سعر الليلة ضمن تفاصيل الإقامة (بدل جدول تفاصيل الرسوم المحذوف) --}}
      @if($hasFirstNight)
      <tr><td class="v">{{ number_format($firstNightPrice, 0) }} {{ $cur }}</td><td class="k">سعر الليلة الأولى:</td></tr>
      <tr><td class="v">{{ number_format($otherNightPrice, 0) }} {{ $cur }}</td><td class="k">سعر بقية الليالي:</td></tr>
      @else
      <tr><td class="v">{{ number_format($pricePerNight, 0) }} {{ $cur }}</td><td class="k">سعر الليلة:</td></tr>
      @endif
    </table>
  </div>
  <div class="clear"></div>

  <!-- PAYMENTS -->
  @if($reservation->payments->count() > 0)
  <div class="sec">سجل المدفوعات</div>
  <table class="mini">
    <thead>
      <tr>
        <th class="c" style="width:18%;">المبلغ</th>
        <th style="width:22%;">المستلم</th>
        <th style="width:18%;">الطريقة</th>
        <th style="width:18%;">النوع</th>
        <th style="width:24%;">التاريخ</th>
      </tr>
    </thead>
    <tbody>
      @foreach($reservation->payments as $p)
      <tr>
        <td class="c" style="color:var(--color-success);font-weight:700;">{{ number_format($p->amount, 0) }} {{ $cur }}</td>
        <td>{{ $p->receivedBy?->name ?? '—' }}</td>
        <td>{{ $methodMap[$p->method] ?? $p->method }}</td>
        <td>{{ $typeMap[$p->type] ?? $p->type }}</td>
        <td>{{ $p->payment_date?->format('Y/m/d H:i') }}</td>
      </tr>
      @endforeach
    </tbody>
  </table>
  @endif

  <!-- BOTTOM: SUMMARY + STAMP -->
  <table class="bottom-section">
    <tr>
      <td class="stamp-cell">
        <div class="stamp-area">ختم وتوقيع الفندق</div>
      </td>
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
    </tr>
  </table>

  <!-- FOOTER -->
  <div class="foot">الفندق السعودي · فاتورة رقم #{{ $invNo }} · صدرت بتاريخ {{ now()->format('Y/m/d H:i') }}</div>
</div>

</body>
</html>
