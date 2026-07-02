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
    body { font-family: 'NotoNaskhArabic', sans-serif; font-size: 7.5px; direction: rtl; color: #1a1a1a; background: #fff; padding: 10px 12px; }

    .header { text-align: center; border-bottom: 2px solid #0F4C75; padding-bottom: 7px; margin-bottom: 8px; }
    .header h1 { font-size: 13px; color: #0F4C75; font-weight: bold; }
    .header .sub { font-size: 7.5px; color: #555; margin-top: 2px; }

    .stats { display: table; width: auto; margin: 0 auto 8px; border-collapse: collapse; }
    .stats td { border: 1px solid #ddd; padding: 3px 12px; text-align: center; }
    .stats .num   { font-size: 13px; font-weight: bold; color: #0F4C75; }
    .stats .num-g { font-size: 13px; font-weight: bold; color: #16a34a; }
    .stats .num-b { font-size: 13px; font-weight: bold; color: #2563eb; }
    .stats .lbl   { font-size: 6.5px; color: #666; }

    /* ── Main table ── */
    table.main {
        width: 100%;
        border-collapse: collapse;
        font-size: 7px;
        direction: rtl;
        table-layout: fixed;   /* honour explicit column widths */
    }

    /* Repeat header on every page */
    table.main thead { display: table-header-group; }
    table.main tbody { display: table-row-group; }

    table.main thead tr { background: #0F4C75; color: #fff; }
    table.main thead th {
        padding: 3px 2px;
        font-weight: bold;
        border: 1px solid #0a3a5e;
        text-align: center;
        overflow: hidden;
    }

    /* Even-row tint */
    table.main tbody tr:nth-child(even) { background: #f4f8fc; }

    /* Keep each row on one page when possible */
    table.main tbody tr { page-break-inside: avoid; }

    table.main tbody td {
        padding: 2px 2px;
        border: 1px solid #e0e0e0;
        text-align: right;
        word-wrap: break-word;   /* allow wrapping — no nowrap */
        overflow: hidden;
        vertical-align: top;
    }
    table.main tbody td.c   { text-align: center; }
    table.main tbody td.ltr { text-align: left; direction: ltr; }

    .footer { margin-top: 6px; border-top: 1px solid #eee; padding-top: 3px; font-size: 6.5px; color: #aaa; text-align: right; }
</style>
</head>
<body>

@php
    $idTypeMap = ['national_id' => 'بطاقة', 'passport' => 'جواز', 'residence' => 'إقامة'];
    $psLabels  = ['paid' => 'مدفوع', 'partial' => 'جزئي', 'pending' => 'معلق'];
@endphp

<div class="header">
    @include('partials.pdf-logo')
    <h1>تقرير الحجوزات</h1>
    <div class="sub">الفترة: {{ \Carbon\Carbon::parse($from)->format('d/m/Y') }} — {{ \Carbon\Carbon::parse($to)->format('d/m/Y') }}</div>
</div>

<table class="stats" dir="rtl">
    <tr>
        <td><div class="num">{{ $total }}</div><div class="lbl">إجمالي الحجوزات</div></td>
        <td><div class="num-g">{{ $checkedIn }}</div><div class="lbl">مقيم حالياً</div></td>
        <td><div class="num-b">{{ $checkedOut }}</div><div class="lbl">غادر</div></td>
    </tr>
</table>

@if($reservations->isEmpty())
<p style="text-align:center;color:#999;padding:20px;">لا توجد حجوزات في هذه الفترة</p>
@else
{{--
    dompdf لا يعكس ترتيب أعمدة الجدول بحسب dir="rtl" — فقط اتجاه النص داخل كل خلية.
    لذا نكتب الأعمدة هنا بترتيب معكوس (الأخير منطقياً أولاً في HTML) حتى يظهر
    العمود الأول منطقياً (#) في أقصى اليمين كما يُقرأ عربياً، بدل أقصى اليسار.
--}}
<table class="main" dir="rtl">
    <colgroup>
        <col style="width:11%">  {{-- ملاحظات --}}
        <col style="width:8%">   {{-- حالة الدفع --}}
        <col style="width:7%">   {{-- رقم الجوال --}}
        <col style="width:6%">   {{-- تاريخ الإصدار --}}
        <col style="width:7%">   {{-- صادر من --}}
        <col style="width:7%">   {{-- رقم الهوية --}}
        <col style="width:4%">   {{-- نوع الهوية --}}
        <col style="width:5%">   {{-- الغرض --}}
        <col style="width:4%">   {{-- الوقت --}}
        <col style="width:6%">   {{-- تاريخ الدخول --}}
        <col style="width:6%">   {{-- جهة القدوم --}}
        <col style="width:6%">   {{-- المهنة --}}
        <col style="width:6%">   {{-- الجنسية --}}
        <col style="width:10%">  {{-- الاسم --}}
        <col style="width:4%">   {{-- الغرفة --}}
        <col style="width:3%">   {{-- # --}}
    </colgroup>
    <thead>
        <tr>
            <th>ملاحظات</th>
            <th>حالة الدفع</th>
            <th>رقم الجوال</th>
            <th>تاريخ الإصدار</th>
            <th>صادر من</th>
            <th>رقم الهوية</th>
            <th>نوع الهوية</th>
            <th>الغرض</th>
            <th>الوقت</th>
            <th>تاريخ الدخول</th>
            <th>جهة القدوم</th>
            <th>المهنة</th>
            <th>الجنسية</th>
            <th>اسم النزيل</th>
            <th>الغرفة</th>
            <th>#</th>
        </tr>
    </thead>
    <tbody>
        @foreach($reservations as $r)
        @php
            $g        = $r->guest;
            $ps       = $r->payment_status ?? 'pending';
            $payNote  = $r->payments->first(fn($p) => $p->notes)?->notes;
            $strip    = fn($s) => $s ? preg_replace('/[^\x{0000}-\x{FFFF}]/u', '', $s) : null;
            $rNote    = $strip($r->notes);
            $payNote  = $strip($payNote);
        @endphp
        <tr>
            <td>
                @if($rNote){{ $rNote }}@endif
                @if($rNote && $payNote) | @endif
                @if($payNote)[دفع] {{ $payNote }}@endif
                @if(!$rNote && !$payNote)—@endif
            </td>
            <td class="c">
                {{ $psLabels[$ps] ?? $ps }}
                <div style="font-size:6px;color:#888;direction:ltr;text-align:left;margin-top:1px;">{{ number_format($r->paid_amount,0) }}/{{ number_format($r->total_amount,0) }}</div>
            </td>
            <td class="ltr">{{ $g?->phone ?? '—' }}</td>
            <td class="ltr">{{ $g?->id_issue_date?->format('d/m/Y') ?? '—' }}</td>
            <td>{{ $g?->id_issuer ?? '—' }}</td>
            <td class="ltr">{{ $g?->id_number ?? '—' }}</td>
            <td class="c">{{ $idTypeMap[$g?->id_type] ?? $g?->id_type ?? '—' }}</td>
            <td>{{ $r->purpose ?? '—' }}</td>
            <td class="ltr c">{{ $r->check_in_time ?? '—' }}</td>
            <td class="ltr">{{ $r->check_in_date?->format('d/m/Y') ?? '—' }}</td>
            <td>{{ $r->origin ?? '—' }}</td>
            <td>{{ $g?->occupation ?? '—' }}</td>
            <td>{{ $g?->nationality ?? '—' }}</td>
            <td>{{ $g?->full_name ?? '—' }}</td>
            <td class="c" style="font-weight:bold;">{{ $r->display_room_number }}</td>
            <td class="c">{{ $r->id }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

{{-- Page numbers via DomPDF inline script --}}
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

<div class="footer">طُبع في: {{ now()->format('d/m/Y H:i') }} — إجمالي الحجوزات: {{ $total }}</div>

</body>
</html>
