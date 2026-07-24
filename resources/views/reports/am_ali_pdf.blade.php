<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<style>
    @font-face {
        font-family: 'NotoNaskhArabic';
        font-style: normal; font-weight: normal;
        src: url("{{ storage_path('fonts') }}/NotoNaskhArabic.ttf") format('truetype');
    }
    @font-face {
        font-family: 'NotoNaskhArabic';
        font-style: normal; font-weight: bold;
        src: url("{{ storage_path('fonts') }}/NotoNaskhArabic-Bold.ttf") format('truetype');
    }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'NotoNaskhArabic', sans-serif; font-size: 14px; direction: rtl; color: #1a1a1a; background: #fff; padding: 16px; }
    .header { text-align: center; border-bottom: 2px solid #0F4C75; padding-bottom: 10px; margin-bottom: 14px; }
    .header h1 { font-size: 17px; color: #0F4C75; font-weight: bold; }
    .header .sub { font-size: 10px; color: #555; margin-top: 4px; }
    h2.section { font-size: 14px; color: #0F4C75; font-weight: bold; margin: 16px 0 6px; }
    table.data { width: 100%; border-collapse: collapse; font-size: 14px; direction: rtl; }
    table.data thead tr { background: #1f2937; color: #fff; }
    table.data thead th { padding: 6px 8px; font-weight: bold; border: 1px solid #111827; text-align: right; }
    table.data tbody tr:nth-child(even) { background: #f4f8fc; }
    table.data tbody td { padding: 5px 8px; border: 1px solid #ccc; text-align: right; word-wrap: break-word; word-break: break-word; }
    table.data tbody td.ltr { text-align: left; direction: ltr; }
    .total-row td { font-weight: bold; background: #eef2f7 !important; }
    .empty { text-align: center; color: #999; padding: 16px; }
    .footer { margin-top: 12px; border-top: 1px solid #eee; padding-top: 6px; font-size: 8px; color: #aaa; text-align: right; }
</style>
</head>
<body>

<div class="header">
    @include('partials.pdf-logo')
    <h1>تقرير عم علي</h1>
    <div class="sub">الغرف المستأجَرة حالياً — ودفعات يوم {{ \Carbon\Carbon::parse($date)->format('Y/m/d') }}</div>
</div>

{{-- dompdf يتجاهل dir="rtl" في ترتيب الأعمدة، فنكتبها بترتيب معكوس (الأخير منطقياً
     أولاً) ليظهر أول عمود منطقياً في أقصى اليمين. --}}

{{-- ═══ القسم (أ): الغرف المستأجَرة حالياً ═══ --}}
<h2 class="section">الغرف المستأجَرة حالياً ({{ $reservations->count() }})</h2>
@if($reservations->isEmpty())
<p class="empty">لا توجد غرف مستأجَرة حالياً</p>
@else
<table class="data" dir="rtl">
    <thead>
        <tr>
            <th>المتبقي</th>
            <th>المدفوع</th>
            <th>المبلغ الإجمالي</th>
            <th>الإقامة قبل التجديد</th>
            <th>النزيل</th>
            <th>الغرفة</th>
        </tr>
    </thead>
    <tbody>
        @foreach($reservations as $res)
        @php
            $remaining = (float) $res->total_amount - (float) $res->paid_amount;
            $segs = $res->segments;
            $hasRenewal = $segs->where('type', 'renewal')->count() > 0;
            $prevAccommodation = $hasRenewal && $segs->count() > 1
                ? (float) $segs->slice(0, $segs->count() - 1)->sum('amount')
                : null;
        @endphp
        <tr>
            <td class="ltr" style="font-weight:bold;color:#dc2626;">{{ number_format($remaining, 0) }}</td>
            <td class="ltr" style="color:#16a34a;">{{ number_format((float) $res->paid_amount, 0) }}</td>
            <td class="ltr">{{ number_format((float) $res->total_amount, 0) }}</td>
            <td class="ltr">{{ $prevAccommodation !== null ? number_format($prevAccommodation, 0) : '—' }}</td>
            <td>{{ $res->guest?->full_name ?? '—' }}</td>
            <td style="font-weight:bold;text-align:center;">{{ $res->display_room_number ?? $res->room?->room_number ?? '—' }}</td>
        </tr>
        @endforeach
        <tr class="total-row">
            <td class="ltr" style="color:#dc2626;">{{ number_format($totals['remaining'], 0) }}</td>
            <td class="ltr" style="color:#16a34a;">{{ number_format($totals['paid'], 0) }}</td>
            <td class="ltr">{{ number_format($totals['total'], 0) }}</td>
            <td colspan="3">الإجمالي (ر.ي)</td>
        </tr>
    </tbody>
</table>
@endif

{{-- ═══ القسم (ب): دفعات اليوم ═══ --}}
<h2 class="section">دفعات يوم {{ \Carbon\Carbon::parse($date)->format('Y/m/d') }} ({{ $dayPayments->count() }})</h2>
@if($dayPayments->isEmpty())
<p class="empty">لا توجد دفعات في هذا اليوم</p>
@else
<table class="data" dir="rtl">
    <thead>
        <tr>
            <th>الموظف المستلِم</th>
            <th>المبلغ المستلَم</th>
            <th>نوع الدفعة</th>
            <th>النزيل</th>
            <th>الغرفة</th>
            <th>الوقت</th>
        </tr>
    </thead>
    <tbody>
        @foreach($dayPayments as $p)
        @php $typeLabel = ['reservation' => 'حجز', 'renewal' => 'تجديد', 'compensation' => 'تعويض', 'extra_service' => 'خدمة إضافية'][$p->type] ?? $p->type; @endphp
        <tr>
            <td>{{ $p->receivedBy?->name ?? 'غير معروف' }}</td>
            <td class="ltr" style="font-weight:bold;color:#16a34a;">{{ number_format((float) $p->amount, 0) }} {{ $p->currency }}</td>
            <td style="text-align:center;">{{ $typeLabel }}</td>
            <td>{{ $p->reservation?->guest?->full_name ?? '—' }}</td>
            <td style="font-weight:bold;text-align:center;">{{ $p->reservation?->display_room_number ?? $p->reservation?->room?->room_number ?? '—' }}</td>
            <td class="ltr" style="text-align:center;">{{ $p->payment_date?->format('H:i') ?? '—' }}</td>
        </tr>
        @endforeach
        <tr class="total-row">
            <td colspan="4" style="text-align:left;">إجمالي دفعات اليوم</td>
            <td class="ltr" colspan="2" style="color:#16a34a;">
                @foreach($dayTotals as $cur => $amt){{ number_format($amt, 0) }} {{ $cur }}@if(!$loop->last) — @endif @endforeach
            </td>
        </tr>
    </tbody>
</table>

{{-- ملخص كل موظف --}}
<h2 class="section">إجمالي ما استلمه كل موظف في اليوم</h2>
<table class="data" dir="rtl">
    <thead>
        <tr>
            <th>إجمالي المستلَم</th>
            <th>الموظف</th>
        </tr>
    </thead>
    <tbody>
        @foreach($byEmployee as $name => $currencies)
        <tr>
            <td class="ltr" style="font-weight:bold;color:#16a34a;">
                @foreach($currencies as $cur => $amt){{ number_format($amt, 0) }} {{ $cur }}@if(!$loop->last) — @endif @endforeach
            </td>
            <td>{{ $name }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

<div class="footer">طُبع في: {{ now()->format('d/m/Y H:i') }}</div>

</body>
</html>
