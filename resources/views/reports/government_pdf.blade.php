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
    body { font-family: 'NotoNaskhArabic', sans-serif; font-size: 10px; direction: rtl; color: #1a1a1a; background: #fff; padding: 8mm 7mm; }

    .header { text-align: center; border-bottom: 2px solid #0F4C75; padding-bottom: 10px; margin-bottom: 14px; }
    .header h1 { font-size: 16px; color: #0F4C75; font-weight: bold; }
    .header .sub { font-size: 9px; color: #555; margin-top: 4px; }

    /* لا table-layout:fixed ولا عرض ثابت — عرض كل عمود يتحدد من محتواه الفعلي */
    table.main { width: 100%; border-collapse: collapse; table-layout: auto; direction: rtl; }
    table.main thead { display: table-header-group; }
    table.main tbody { display: table-row-group; }
    table.main thead tr { background: #0F4C75; color: #fff; }
    table.main thead th { padding: 6px 7px; font-weight: bold; border: 1px solid #0a3a5e; text-align: center; }
    table.main tbody tr:nth-child(even) { background: #f4f8fc; }
    table.main tbody tr { page-break-inside: avoid; }
    table.main tbody td {
        padding: 5px 7px; border: 1px solid #e0e0e0; text-align: right;
        word-wrap: break-word; word-break: break-word; vertical-align: top;
    }
    table.main tbody td.c   { text-align: center; }
    table.main tbody td.ltr { text-align: left; direction: ltr; }

    .badge { display: inline-block; padding: 1px 8px; border-radius: 8px; font-size: 8.5px; font-weight: bold; }
    .badge-in  { background: #dcfce7; color: #15803d; }
    .badge-out { background: #f3f4f6; color: #4b5563; }

    .footer { margin-top: 10px; border-top: 1px solid #eee; padding-top: 5px; font-size: 8px; color: #aaa; text-align: right; }
</style>
</head>
<body>

<div class="header">
    @include('partials.pdf-logo')
    <h1>تقرير الجهات الحكومية — سجل النزلاء</h1>
    <div class="sub">الفترة: {{ \Carbon\Carbon::parse($from)->format('d/m/Y') }} — {{ \Carbon\Carbon::parse($to)->format('d/m/Y') }} — عدد الحجوزات: {{ $reservations->count() }}</div>
</div>

@if($reservations->isEmpty())
<p style="text-align:center;color:#999;padding:20px;">لا توجد حجوزات في هذه الفترة</p>
@else
{{--
    dompdf لا يعكس ترتيب أعمدة الجدول بحسب dir="rtl" — فقط اتجاه النص داخل كل خلية.
    نكتب الأعمدة هنا بترتيب معكوس (الأخير منطقياً أولاً في HTML) حتى يظهر
    العمود الأول منطقياً (الغرفة) في أقصى اليمين كما يُقرأ عربياً.
--}}
<table class="main" dir="rtl">
    <thead>
        <tr>
            <th>حالة الإقامة</th>
            <th>بيانات المرافقين</th>
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
        </tr>
    </thead>
    <tbody>
        @foreach($reservations as $res)
        @php
            $inTime   = $res->check_in_time ?: $res->check_in_date?->format('H:i');
            $departed = $res->status === 'checked_out';
        @endphp
        <tr>
            <td class="c"><span class="badge {{ $departed ? 'badge-out' : 'badge-in' }}">{{ $departed ? 'غادروا' : 'لم يغادروا' }}</span></td>
            <td>
                @if($res->companions->isEmpty())
                    —
                @else
                    @foreach($res->companions as $c)
                        {{ $c->full_name }} ({{ $c->getRelationshipLabel() }}){{ !$loop->last ? ' — ' : '' }}
                    @endforeach
                @endif
            </td>
            <td class="ltr c">{{ $res->guest?->id_issue_date?->format('d/m/Y') ?? '—' }}</td>
            <td>{{ $res->guest?->id_issuer ?? '—' }}</td>
            <td class="ltr">{{ $res->guest?->id_number ?? '—' }}</td>
            <td>{{ $res->guest?->getIdTypeLabel() ?? '—' }}</td>
            <td>{{ $res->purpose ?? '—' }}</td>
            <td class="ltr c">{{ $inTime ?? '—' }}</td>
            <td class="ltr c">{{ $res->check_in_date?->format('d/m/Y') ?? '—' }}</td>
            <td>{{ $res->origin ?? '—' }}</td>
            <td>{{ $res->guest?->occupation ?? '—' }}</td>
            <td>{{ $res->guest?->nationality ?? '—' }}</td>
            <td style="font-weight:bold;">{{ $res->guest?->full_name ?? '—' }}</td>
            <td class="c" style="font-weight:bold;">{{ $res->display_room_number }}</td>
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

<div class="footer">طُبع في: {{ now()->format('d/m/Y H:i') }} — عدد الحجوزات المطبوعة: {{ $reservations->count() }}</div>

</body>
</html>
