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
    body { font-family: 'NotoNaskhArabic', sans-serif; font-size: 11px; direction: rtl; color: #1a1a1a; background: #fff; padding: 8mm 7mm; }

    .header { text-align: center; border-bottom: 2px solid #0F4C75; padding-bottom: 10px; margin-bottom: 14px; }
    .header h1 { font-size: 16px; color: #0F4C75; font-weight: bold; }
    .header .sub { font-size: 9px; color: #555; margin-top: 4px; }

    .stats { display: table; width: auto; margin: 0 auto 14px; border-collapse: collapse; }
    .stats td { border: 1px solid #ddd; padding: 8px 20px; text-align: center; }
    .stats .num   { font-size: 16px; font-weight: bold; color: #0F4C75; }
    .stats .num-r { font-size: 16px; font-weight: bold; color: #dc2626; }
    .stats .lbl   { font-size: 8px; color: #666; margin-top: 2px; }

    /* لا table-layout:fixed ولا عرض ثابت — عرض كل عمود يتحدد من محتواه الفعلي */
    table.main { width: 100%; border-collapse: collapse; table-layout: auto; direction: rtl; }
    table.main thead { display: table-header-group; }
    table.main tbody { display: table-row-group; }
    table.main thead tr { background: #0F4C75; color: #fff; }
    table.main thead th { padding: 6px 8px; font-weight: bold; border: 1px solid #0a3a5e; text-align: center; }
    table.main tbody tr:nth-child(even) { background: #f4f8fc; }
    table.main tbody tr { page-break-inside: avoid; }
    table.main tbody td {
        padding: 5px 8px; border: 1px solid #e0e0e0; text-align: right;
        word-wrap: break-word; word-break: break-word; vertical-align: top;
    }
    table.main tbody td.c   { text-align: center; }
    table.main tbody td.ltr { text-align: left; direction: ltr; }

    .footer { margin-top: 10px; border-top: 1px solid #eee; padding-top: 5px; font-size: 8px; color: #aaa; text-align: right; }
</style>
</head>
<body>

<div class="header">
    @include('partials.pdf-logo')
    <h1>تقرير أسباب إلغاء الحجوزات</h1>
    <div class="sub">الفترة: {{ \Carbon\Carbon::parse($from)->format('d/m/Y') }} — {{ \Carbon\Carbon::parse($to)->format('d/m/Y') }}</div>
</div>

<table class="stats" dir="rtl">
    <tr>
        <td><div class="num">{{ $totalCancelled }}</div><div class="lbl">حجز ملغى في الفترة</div></td>
        <td><div class="num-r">{{ number_format($totalUnpaidBalance, 0) }}</div><div class="lbl">متأخرات لم تُحصَّل قبل الإلغاء (ر.ي)</div></td>
    </tr>
</table>

@if($reservations->isEmpty())
<p style="text-align:center;color:#999;padding:20px;">لا توجد حجوزات ملغاة في هذه الفترة</p>
@else
{{--
    dompdf لا يعكس ترتيب أعمدة الجدول بحسب dir="rtl" — فقط اتجاه النص داخل كل خلية.
    نكتب الأعمدة هنا بترتيب معكوس (الأخير منطقياً أولاً في HTML) حتى يظهر
    العمود الأول منطقياً (اسم النزيل) في أقصى اليمين كما يُقرأ عربياً.
--}}
<table class="main" dir="rtl">
    <thead>
        <tr>
            <th>المتأخرات وقت الإلغاء</th>
            <th>ألغاه</th>
            <th>سبب الإلغاء</th>
            <th>تاريخ الحجز الأصلي</th>
            <th>الغرفة</th>
            <th>تاريخ الإلغاء</th>
            <th>اسم النزيل</th>
        </tr>
    </thead>
    <tbody>
        @foreach($reservations as $res)
        @php
            $balance = max(0, (float)$res->total_amount - (float)$res->paid_amount);
            $strip   = fn($s) => $s ? preg_replace('/[^\x{0000}-\x{FFFF}]/u', '', $s) : null;
        @endphp
        <tr>
            <td class="ltr" style="{{ $balance > 0 ? 'font-weight:bold;color:#dc2626;' : '' }}">
                {{ $balance > 0 ? number_format($balance, 0) : '—' }}
            </td>
            <td>{{ $res->cancelledBy?->name ?? '—' }}</td>
            <td>{{ $strip($res->cancellation_reason) ?: '—' }}</td>
            <td class="ltr c">{{ $res->check_in_date?->format('d/m/Y') ?? '—' }} — {{ $res->check_out_date?->format('d/m/Y') ?? '—' }}</td>
            <td class="c" style="font-weight:bold;">{{ $res->display_room_number }}</td>
            <td class="ltr">{{ $res->cancelled_at?->format('d/m/Y H:i') ?? '—' }}</td>
            <td>{{ $res->guest?->full_name ?? '—' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

<script type="text/php">
    if (isset($pdf)) {
        $font  = $fontMetrics->getFont('helvetica');
        $total = $pdf->get_page_count();
        for ($p = 1; $p <= $total; $p++) {
            $pdf->page_text(
                $pdf->get_width() - 55,
                $pdf->get_height() - 12,
                "صفحة {$p} / {$total}",
                $font, 6.5, [0.6, 0.6, 0.6]
            );
        }
    }
</script>

<div class="footer">طُبع في: {{ now()->format('d/m/Y H:i') }} — عدد الحجوزات الملغاة المطبوعة: {{ $reservations->count() }}</div>

</body>
</html>
