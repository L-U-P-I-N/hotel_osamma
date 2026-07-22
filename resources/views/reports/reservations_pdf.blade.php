@php
    $fontSize = 16;
@endphp
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
    /*
       الحشوة بوحدة mm بدل px: أغلب طابعات A3 لا تطبع حتى حافة الورقة الفعلية
       (هامش غير قابل للطباعة ~4-5mm)، والحشوة القديمة (10px/12px ≈ 3mm) كانت
       أضيق من ذلك فتُقصّ الأعمدة الطرفية (اليمنى/اليسرى) عند الطباعة الفعلية.
    */
    body { font-family: 'NotoNaskhArabic', sans-serif; font-size: {{ $fontSize }}px; direction: rtl; color: #1a1a1a; background: #fff; padding: 8mm 7mm; }

    .header { text-align: center; border-bottom: 2px solid #0F4C75; padding-bottom: 7px; margin-bottom: 8px; }
    .header h1 { font-size: 15px; color: #0F4C75; font-weight: bold; }
    .header .sub { font-size: 9px; color: #555; margin-top: 2px; }

    .stats { display: table; width: auto; margin: 0 auto 8px; border-collapse: collapse; }
    .stats td { border: 1px solid #ddd; padding: 3px 12px; text-align: center; }
    .stats .num   { font-size: 14px; font-weight: bold; color: #0F4C75; }
    .stats .num-g { font-size: 14px; font-weight: bold; color: #16a34a; }
    .stats .num-b { font-size: 14px; font-weight: bold; color: #2563eb; }
    .stats .lbl   { font-size: 7.5px; color: #666; }

    /* ── Main table ── */
    table.main {
        width: 100%;
        border-collapse: collapse;
        font-size: {{ $fontSize }}px;
        direction: rtl;
        table-layout: auto;   /* عرض كل عمود يتحدد تلقائياً من أطول محتوى فيه — مرن حسب البيانات الفعلية */
    }

    /* Repeat header on every page */
    table.main thead { display: table-header-group; }
    table.main tbody { display: table-row-group; }

    table.main thead tr { background: #0F4C75; color: #fff; }
    table.main thead th {
        padding: 4px 3px;
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
        padding: 3px 3px;
        border: 1px solid #e0e0e0;
        text-align: right;
        word-wrap: break-word;
        word-break: break-all;  /* أرقام/نصوص متلاصقة بلا مسافات تنكسر داخل الخلية بدل تجاوز حدودها */
        overflow: hidden;
        vertical-align: top;
    }
    table.main tbody td.c   { text-align: center; }
    table.main tbody td.ltr { text-align: left; direction: ltr; }

    /* Payment status badges — تمييز واضح دون الحاجة لقراءة النص */
    .badge { display: inline-block; padding: 1px 5px; border-radius: 3px; font-weight: bold; }
    .badge-paid    { background: #dcfce7; color: #166534; }
    .badge-partial { background: #fef9c3; color: #854d0e; }
    .badge-pending { background: #fee2e2; color: #991b1b; }
    .badge-deferred{ background: #e0e7ff; color: #3730a3; }

    /* حالة الإقامة: لم يغادر (مقيم) / غادر */
    .badge-stay-in  { background: #dcfce7; color: #166534; }
    .badge-stay-out { background: #e5e7eb; color: #374151; }

    .footer { margin-top: 6px; border-top: 1px solid #eee; padding-top: 3px; font-size: 6.5px; color: #aaa; text-align: right; }
</style>
</head>
<body>

@php
    $idTypeMap = ['national_id' => 'بطاقة', 'passport' => 'جواز', 'residence' => 'إقامة'];
    $psLabels  = ['paid' => 'مدفوع', 'partial' => 'جزئي', 'pending' => 'معلق', 'unpaid' => 'غير مدفوع', 'deferred' => 'مؤجل'];
    $psBadge   = ['paid' => 'badge-paid', 'partial' => 'badge-partial', 'pending' => 'badge-pending', 'unpaid' => 'badge-pending', 'deferred' => 'badge-deferred'];
    $strip     = fn($s) => $s ? preg_replace('/[^\x{0000}-\x{FFFF}]/u', '', $s) : null;

    // تعريف الأعمدة كلها بترتيبها المنطقي الصحيح (الأول = أقصى اليمين عربياً).
    // لا وزن/عرض ثابت هنا — عرض كل عمود يُحسب تلقائياً من محتواه الفعلي
    // (table-layout: auto)، فالعمود يتّسع أو يضيق حسب البيانات الحقيقية بدل
    // نسبة مُقدَّرة يدوياً قد لا تُناسب كل تصدير.
    $columnDefs = [
        'id'             => ['label' => '#',              'class' => 'c',
            'render' => fn($r, $g) => $r->id],
        'room'           => ['label' => 'الغرفة',          'class' => 'c', 'bold' => true,
            'render' => fn($r, $g) => $r->display_room_number],
        'guest_name'     => ['label' => 'اسم النزيل',      'class' => '',
            'render' => fn($r, $g) => $g?->full_name ?? '—'],
        'nationality'    => ['label' => 'الجنسية',         'class' => '',
            'render' => fn($r, $g) => $g?->nationality ?? '—'],
        'occupation'     => ['label' => 'المهنة',          'class' => '',
            'render' => fn($r, $g) => $g?->occupation ?? '—'],
        'origin'         => ['label' => 'جهة القدوم',      'class' => '',
            'render' => fn($r, $g) => $r->origin ?? '—'],
        'check_in_date'  => ['label' => 'تاريخ الدخول',    'class' => 'ltr',
            'render' => fn($r, $g) => $r->check_in_date?->format('d/m/Y') ?? '—'],
        'check_in_time'  => ['label' => 'الوقت',           'class' => 'ltr c',
            'render' => fn($r, $g) => $r->check_in_time ?? '—'],
        'stay_status'    => ['label' => 'حالة الإقامة',    'class' => 'c', 'raw' => true,
            'render' => function ($r, $g) {
                $isOut = $r->status === 'checked_out';
                $cls   = $isOut ? 'badge-stay-out' : 'badge-stay-in';
                // "غادر"/"لم يغادر" مشتقة من حالة الحجز الفعلية، وليست إدخال مستخدم — آمنة دون تهريب
                return '<span class="badge ' . $cls . '">' . ($isOut ? 'غادر' : 'لم يغادر') . '</span>';
            }],
        'purpose'        => ['label' => 'الغرض',           'class' => '',
            'render' => fn($r, $g) => $r->purpose ?? '—'],
        'id_type'        => ['label' => 'نوع الهوية',      'class' => 'c',
            'render' => fn($r, $g) => $idTypeMap[$g?->id_type] ?? $g?->id_type ?? '—'],
        'id_number'      => ['label' => 'رقم الهوية',      'class' => 'ltr',
            'render' => fn($r, $g) => $g?->id_number ?? '—'],
        'id_issuer'      => ['label' => 'صادر من',         'class' => '',
            'render' => fn($r, $g) => $g?->id_issuer ?? '—'],
        'id_issue_date'  => ['label' => 'تاريخ الإصدار',   'class' => 'ltr',
            'render' => fn($r, $g) => $g?->id_issue_date?->format('d/m/Y') ?? '—'],
        'phone'          => ['label' => 'رقم الجوال',      'class' => 'ltr',
            'render' => fn($r, $g) => $g?->phone ?? '—'],
        'payment_status' => ['label' => 'حالة الدفع',      'class' => 'c', 'raw' => true,
            'render' => function ($r, $g) use ($psLabels, $psBadge) {
                $ps = $r->payment_status ?? 'pending';
                $cls = $psBadge[$ps] ?? 'badge-pending';
                // القيم هنا من قائمة ثابتة (لا تأتي من إدخال المستخدم) فالمخرج HTML آمن دون تهريب
                return '<span class="badge ' . $cls . '">' . e($psLabels[$ps] ?? $ps) . '</span>';
            }],
        'paid_amount'    => ['label' => 'المدفوع (ر.ي)',   'class' => 'ltr',
            'render' => fn($r, $g) => number_format($r->paid_amount, 0)],
        'total_amount'   => ['label' => 'الإجمالي (ر.ي)',  'class' => 'ltr',
            'render' => fn($r, $g) => number_format($r->total_amount, 0)],
        'balance'        => ['label' => 'المتبقي (ر.ي)',   'class' => 'ltr',
            'render' => fn($r, $g) => number_format(max(0, (float)$r->total_amount - (float)$r->paid_amount), 0)],
        'created_by'     => ['label' => 'تم بواسطة',       'class' => '',
            'render' => fn($r, $g) => e($r->createdBy?->name ?? '—')],
        'checked_out_by' => ['label' => 'مغادرة بواسطة',   'class' => '',
            'render' => fn($r, $g) => e($r->checkedOutBy?->name ?? '—')],
        'notes'          => ['label' => 'ملاحظات',         'class' => '',
            'render' => function ($r, $g) use ($strip) {
                // نستبعد أسطر التجديد (اليدوي والتلقائي) من الملاحظات — يريد المستخدم
                // عرض بيانات الدفع والملاحظات الأخرى فقط دون ضجيج نصوص التجديد.
                $rawNote = $strip($r->notes);
                $rNote = null;
                if ($rawNote) {
                    $kept = collect(preg_split('/\r?\n/', $rawNote))
                        ->map(fn($line) => trim($line))
                        ->filter(fn($line) => $line !== '' && !str_starts_with($line, '[تجديد'))
                        ->implode(' | ');
                    $rNote = $kept !== '' ? $kept : null;
                }
                $payNote = $strip($r->payments->first(fn($p) => $p->notes)?->notes);
                if (!$rNote && !$payNote) return '—';
                return trim(($rNote ?? '') . ($rNote && $payNote ? ' | ' : '') . ($payNote ? '[دفع] ' . $payNote : ''));
            }],
    ];

    // الأعمدة التي اختارها الأدمن (أو كل الأعمدة افتراضياً إن لم يُحدَّد شيء)
    $selected = collect($selectedColumns ?? array_keys($columnDefs))
        ->filter(fn($k) => isset($columnDefs[$k]))
        ->values();
    if ($selected->isEmpty()) {
        $selected = collect(array_keys($columnDefs));
    }
    $activeColumns = collect($columnDefs)->only($selected->all());

    // dompdf لا يعكس ترتيب أعمدة الجدول بحسب dir="rtl" — فقط اتجاه النص داخل كل خلية.
    // لذا نكتب الأعمدة هنا بترتيب معكوس (الأخير منطقياً أولاً في HTML) حتى يظهر
    // العمود الأول منطقياً (#) في أقصى اليمين كما يُقرأ عربياً، بدل أقصى اليسار.
    $htmlOrder = $activeColumns->reverse();
@endphp

@php
    $statusFilterLabel = match($status ?? 'all') {
        'checked_in'  => 'المقيمون فقط (لم يغادروا)',
        'checked_out' => 'المغادرون فقط',
        default       => 'كل الحجوزات (المقيمون والمغادرون)',
    };
@endphp
@php $isSingleDay = \Carbon\Carbon::parse($from)->isSameDay(\Carbon\Carbon::parse($to)); @endphp
<div class="header">
    @include('partials.pdf-logo')
    <h1>{{ $isSingleDay ? 'قائمة اليومية' : 'تقرير الحجوزات' }}</h1>
    <div class="sub">
        @if($isSingleDay)
        التاريخ: {{ \Carbon\Carbon::parse($from)->format('d/m/Y') }}
        @else
        الفترة: {{ \Carbon\Carbon::parse($from)->format('d/m/Y') }} — {{ \Carbon\Carbon::parse($to)->format('d/m/Y') }}
        @endif
        — الفلتر: <strong>{{ $statusFilterLabel }}</strong>
    </div>
</div>

<table class="stats" dir="rtl">
    <tr>
        <td><div class="num">{{ $total }}</div><div class="lbl">إجمالي الحجوزات في الفترة</div></td>
        <td><div class="num-g">{{ $checkedIn }}</div><div class="lbl">مقيم حالياً (لم يغادر)</div></td>
        <td><div class="num-b">{{ $checkedOut }}</div><div class="lbl">غادر</div></td>
    </tr>
</table>

@if($reservations->isEmpty())
<p style="text-align:center;color:#999;padding:20px;">لا توجد حجوزات في هذه الفترة</p>
@else
<table class="main" dir="rtl">
    <thead>
        <tr>
            @foreach($htmlOrder as $key => $col)
            <th>{{ $col['label'] }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @foreach($reservations as $r)
        @php $g = $r->guest; @endphp
        <tr>
            @foreach($htmlOrder as $key => $col)
            <td class="{{ $col['class'] }}" @if(!empty($col['bold'])) style="font-weight:bold;" @endif>@if(!empty($col['raw'])){!! $col['render']($r, $g) !!}@else{{ $col['render']($r, $g) }}@endif</td>
            @endforeach
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

<div class="footer">طُبع في: {{ now()->format('d/m/Y H:i') }} — عدد الحجوزات المطبوعة: {{ $printedCount ?? $reservations->count() }}</div>

</body>
</html>
