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
    body { font-family: 'NotoNaskhArabic', sans-serif; font-size: 8px; direction: rtl; color: #1a1a1a; background: #fff; padding: 12px; }
    .header { text-align: center; border-bottom: 2px solid #0F4C75; padding-bottom: 8px; margin-bottom: 10px; }
    .header h1 { font-size: 14px; color: #0F4C75; font-weight: bold; }
    .header .sub { font-size: 8px; color: #555; margin-top: 3px; }
    .stats { display: table; width: auto; margin: 0 auto 10px; border-collapse: collapse; }
    .stats td { border: 1px solid #ddd; padding: 4px 14px; text-align: center; }
    .stats .num { font-size: 14px; font-weight: bold; color: #0F4C75; }
    .stats .num-g { font-size: 14px; font-weight: bold; color: #16a34a; }
    .stats .num-b { font-size: 14px; font-weight: bold; color: #2563eb; }
    .stats .lbl { font-size: 7px; color: #666; }
    table.main { width: 100%; border-collapse: collapse; font-size: 7.5px; direction: rtl; }
    table.main thead tr { background: #0F4C75; color: #fff; }
    table.main thead th { padding: 4px 3px; font-weight: bold; border: 1px solid #0a3a5e; text-align: center; white-space: nowrap; }
    table.main tbody tr:nth-child(even) { background: #f4f8fc; }
    table.main tbody td { padding: 3px 3px; border: 1px solid #e0e0e0; text-align: right; white-space: nowrap; }
    table.main tbody td.ltr { text-align: left; direction: ltr; }
    .footer { margin-top: 8px; border-top: 1px solid #eee; padding-top: 4px; font-size: 7px; color: #aaa; text-align: right; }
</style>
</head>
<body>

@php
    $idTypeMap = ['national_id' => 'بطاقة', 'passport' => 'جواز', 'residence' => 'إقامة'];
    $psLabels  = ['paid' => 'مدفوع', 'partial' => 'جزئي', 'pending' => 'معلق'];
@endphp

<div class="header">
    <h1>تقرير الحجوزات</h1>
    <div class="sub">
        الفترة: {{ \Carbon\Carbon::parse($from)->format('d/m/Y') }} — {{ \Carbon\Carbon::parse($to)->format('d/m/Y') }}
    </div>
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
<table class="main" dir="rtl">
    <thead>
        <tr>
            <th>#</th>
            <th>الغرفة</th>
            <th>اسم النزيل</th>
            <th>الجنسية</th>
            <th>المهنة</th>
            <th>جهة القدوم</th>
            <th>تاريخ الدخول</th>
            <th>الوقت</th>
            <th>الغرض</th>
            <th>نوع الهوية</th>
            <th>رقم الهوية</th>
            <th>صادر من</th>
            <th>تاريخ الإصدار</th>
            <th>رقم الجوال</th>
            <th>حالة الدفع</th>
            <th>المدفوع/الإجمالي</th>
            <th>ملاحظات</th>
        </tr>
    </thead>
    <tbody>
        @foreach($reservations as $r)
        @php
            $g = $r->guest;
            $ps = $r->payment_status ?? 'pending';
            $payNote  = $r->payments->first(fn($p) => $p->notes)?->notes;
            $strip    = fn($s) => $s ? preg_replace('/[^\x{0000}-\x{FFFF}]/u', '', $s) : null;
            $rNote    = $strip($r->notes);
            $payNote  = $strip($payNote);
        @endphp
        <tr>
            <td style="text-align:center;">{{ $r->id }}</td>
            <td style="font-weight:bold;text-align:center;">{{ $r->display_room_number }}</td>
            <td>{{ $g?->full_name ?? '—' }}</td>
            <td>{{ $g?->nationality ?? '—' }}</td>
            <td>{{ $g?->occupation ?? '—' }}</td>
            <td>{{ $r->origin ?? '—' }}</td>
            <td class="ltr">{{ $r->check_in_date?->format('d/m/Y') ?? '—' }}</td>
            <td class="ltr">{{ $r->check_in_time ?? '—' }}</td>
            <td>{{ $r->purpose ?? '—' }}</td>
            <td>{{ $idTypeMap[$g?->id_type] ?? $g?->id_type ?? '—' }}</td>
            <td class="ltr">{{ $g?->id_number ?? '—' }}</td>
            <td>{{ $g?->id_issuer ?? '—' }}</td>
            <td class="ltr">{{ $g?->id_issue_date?->format('d/m/Y') ?? '—' }}</td>
            <td class="ltr">{{ $g?->phone ?? '—' }}</td>
            <td style="text-align:center;">{{ $psLabels[$ps] ?? $ps }}</td>
            <td class="ltr">{{ number_format($r->paid_amount, 0) }} / {{ number_format($r->total_amount, 0) }}</td>
            <td>
                @if($rNote){{ $rNote }}@endif
                @if($rNote && $payNote)<br/>@endif
                @if($payNote)[دفع] {{ $payNote }}@endif
                @if(!$rNote && !$payNote)—@endif
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

<div class="footer">طُبع في: {{ now()->format('d/m/Y H:i') }}</div>

</body>
</html>
