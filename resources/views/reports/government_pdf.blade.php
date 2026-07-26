<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
@php $hotel = \App\Models\Hotel::first(); @endphp
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
    body { font-family: 'NotoNaskhArabic', sans-serif; font-size: 9.5px; direction: rtl; color: #1a1a1a; background: #fff; margin: 40mm 6mm 12mm 6mm; }

    /* رأس وتذييل ثابتان يتكرران في كل صفحة (تقنية dompdf القياسية) */
    .pdf-header { position: fixed; top: -38mm; left: 0; right: 0; text-align: center; }
    .pdf-header .date { text-align: right; font-weight: bold; font-size: 11px; margin-bottom: 4px; }
    .pdf-header h1 { font-size: 15px; font-weight: bold; margin-bottom: 8px; }
    .pdf-header .info { text-align: right; font-size: 10px; line-height: 1.5; }
    .pdf-header .info b { display: inline-block; width: 60px; }

    .pdf-footer { position: fixed; bottom: -10mm; left: 0; right: 0; font-size: 8px; color: #444; border-top: 1px solid #999; padding-top: 3px; text-align: right; }

    table.main { width: 100%; border-collapse: collapse; table-layout: auto; direction: rtl; }
    table.main thead { display: table-header-group; }
    table.main tbody { display: table-row-group; }
    table.main thead tr { background: #e5e7eb; }
    table.main thead th { padding: 5px 4px; font-weight: bold; border: 1px solid #333; text-align: center; }
    table.main tbody tr { page-break-inside: avoid; }
    table.main tbody td {
        padding: 4px; border: 1px solid #333; text-align: right;
        word-wrap: break-word; word-break: break-word; vertical-align: top;
    }
    table.main tbody td.c { text-align: center; }
    .plus { text-align: center; color: #888; }
</style>
</head>
<body>

<div class="pdf-header">
    <div class="date">{{ now()->format('Y/n/j') }}</div>
    <h1>حجوزات الفندق</h1>
    <div class="info">
        <div><b>الاسم:</b> {{ $hotel?->name ?? '—' }}</div>
        <div><b>العنوان:</b> {{ $hotel?->address ?? '—' }}</div>
        <div><b>التلفون:</b> {{ $hotel?->phone ?? '—' }}</div>
    </div>
</div>

<div class="pdf-footer">{{ $hotel?->name ?? '' }} -: رقم الهاتف: {{ $hotel?->phone ?? '' }}</div>

@if($reservations->isEmpty())
<p style="text-align:center;color:#999;padding:20px;">لا توجد حجوزات في هذه الفترة</p>
@else
{{--
    dompdf لا يعكس ترتيب أعمدة الجدول بحسب dir="rtl" — فقط اتجاه النص داخل كل خلية.
    نكتب الأعمدة هنا بترتيب معكوس (الأخير منطقياً أولاً في HTML) حتى يظهر
    العمود الأول منطقياً (رقم الغرفة) في أقصى اليمين كما يُقرأ عربياً.
--}}
<table class="main" dir="rtl">
    <thead>
        <tr>
            <th>أسماء المرافقين</th>
            <th>ملاحظة</th>
            <th>تاريخ الاصدار</th>
            <th>صادر من</th>
            <th>رقم الهوية</th>
            <th>نوع الهوية</th>
            <th>الغرض من القدوم</th>
            <th>تاريخ القدوم</th>
            <th>جهة القدوم</th>
            <th>المهنة</th>
            <th>الجنسية</th>
            <th>اسم النزيل ثلاثيا</th>
            <th>رقم الغرفة</th>
        </tr>
    </thead>
    <tbody>
        @foreach($reservations as $res)
        <tr>
            <td>
                @forelse($res->companions as $c)
                    {{ $c->full_name }}
                    @if(!$loop->last)<div class="plus">+</div>@endif
                @empty
                    —
                @endforelse
            </td>
            <td>
                @foreach(explode("\n", $res->government_note) as $line)
                    <div>{{ $line }}</div>
                @endforeach
            </td>
            <td class="c">{{ $res->guest?->id_issue_date?->format('Y/n/j') ?? '—' }}</td>
            <td>{{ $res->guest?->id_issuer ?? '—' }}</td>
            <td>{{ $res->guest?->id_number ?? '—' }}</td>
            <td>{{ $res->guest?->getIdTypeLabel() ?? '—' }}</td>
            <td>{{ $res->purpose ?? '—' }}</td>
            <td class="c">{{ $res->check_in_date?->format('n/j') ?? '—' }}</td>
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
                $pdf->get_height() - 10,
                "صفحة {$p} / {$total}",
                $font, 6.5, [0.4, 0.4, 0.4]
            );
        }
    }
</script>

</body>
</html>
