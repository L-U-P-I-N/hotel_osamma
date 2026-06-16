<!DOCTYPE html>
<html lang="ar">
<head>
<meta charset="UTF-8">
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
        font-family: 'NotoNaskhArabic', 'DejaVu Sans', serif;
        font-size: 9px;
        direction: ltr;
        color: #1a1a1a;
        background: #fff;
    }
    .rtl {
        direction: ltr;
        text-align: right;
        unicode-bidi: bidi-override;
    }
    .header {
        text-align: center;
        padding: 8px 0 6px;
        border-bottom: 2px solid #0F4C75;
        margin-bottom: 8px;
    }
    .header h1 {
        font-size: 15px;
        color: #0F4C75;
        font-weight: bold;
        direction: ltr;
        text-align: center;
    }
    .header p {
        font-size: 9px;
        color: #555;
        margin-top: 3px;
        direction: ltr;
        text-align: center;
    }
    .summary-table {
        width: auto;
        border-collapse: collapse;
        margin: 0 auto 8px;
    }
    .summary-table td {
        border: 1px solid #ddd;
        padding: 4px 14px;
        text-align: center;
        min-width: 80px;
    }
    .summary-table .num { font-size: 15px; font-weight: bold; color: #0F4C75; }
    .summary-table .num-green { font-size: 15px; font-weight: bold; color: #16a34a; }
    .summary-table .num-blue  { font-size: 15px; font-weight: bold; color: #2563eb; }
    .summary-table .num-gray  { font-size: 15px; font-weight: bold; color: #374151; }
    .summary-table .lbl { font-size: 7.5px; color: #666; }

    table.main {
        width: 100%;
        border-collapse: collapse;
        font-size: 7.5px;
        direction: ltr;
    }
    table.main thead tr { background: #0F4C75; color: #fff; }
    table.main thead th {
        padding: 4px 3px;
        font-weight: bold;
        white-space: nowrap;
        border: 1px solid #0a3a5e;
        text-align: center;
    }
    table.main tbody tr:nth-child(even) { background: #f4f8fc; }
    table.main tbody td {
        padding: 3px 3px;
        border: 1px solid #e0e0e0;
        white-space: nowrap;
        text-align: right;
    }
    table.main tbody td.ltr-val {
        text-align: left;
        direction: ltr;
    }
    .footer {
        margin-top: 10px;
        text-align: left;
        font-size: 7.5px;
        color: #aaa;
        border-top: 1px solid #eee;
        padding-top: 4px;
    }
    .badge-confirmed { color: #2563eb; font-size: 7px; }
</style>
</head>
<body>

@php
    $idTypeMap = ['national_id' => 'بطاقة', 'passport' => 'جواز', 'residence' => 'إقامة'];
    $payLabels  = ['unpaid' => 'غير مدفوع', 'partial' => 'جزئي', 'paid' => 'مدفوع', 'deferred' => 'مؤجل'];
    $checkedIn  = $reservations->where('status', 'checked_in')->count();
    $confirmed  = $reservations->where('status', 'confirmed')->count();
    $companions = $reservations->sum(fn($r) => $r->companions->count());
@endphp

<div class="header">
    <h1>{{ ar_pdf('القائمة اليومية للنزلاء') }}</h1>
    <p>
        {{ ar_pdf('تاريخ:') }} {{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}
        &nbsp;&nbsp;|&nbsp;&nbsp;
        {{ ar_pdf('عدد النزلاء:') }} {{ $reservations->count() }}
    </p>
</div>

<table class="summary-table">
    <tr>
        <td>
            <div class="num">{{ $reservations->count() }}</div>
            <div class="lbl">{{ ar_pdf('إجمالي النزلاء') }}</div>
        </td>
        <td>
            <div class="num-green">{{ $checkedIn }}</div>
            <div class="lbl">{{ ar_pdf('مسجل دخول') }}</div>
        </td>
        <td>
            <div class="num-blue">{{ $confirmed }}</div>
            <div class="lbl">{{ ar_pdf('حجز مؤكد') }}</div>
        </td>
        <td>
            <div class="num-gray">{{ $companions }}</div>
            <div class="lbl">{{ ar_pdf('مرافق') }}</div>
        </td>
    </tr>
</table>

@if($reservations->isEmpty())
<p style="text-align:center;color:#999;padding:20px;">{{ ar_pdf('لا يوجد نزلاء في هذا التاريخ') }}</p>
@else
<table class="main">
    <thead>
        <tr>
            <th>{{ ar_pdf('الغرفة') }}</th>
            <th>{{ ar_pdf('اسم النزيل') }}</th>
            <th>{{ ar_pdf('الجنسية') }}</th>
            <th>{{ ar_pdf('المهنة') }}</th>
            <th>{{ ar_pdf('جهة القدوم') }}</th>
            <th>{{ ar_pdf('تاريخ الدخول') }}</th>
            <th>{{ ar_pdf('الوقت') }}</th>
            <th>{{ ar_pdf('الغرض') }}</th>
            <th>{{ ar_pdf('نوع الهوية') }}</th>
            <th>{{ ar_pdf('رقم الهوية') }}</th>
            <th>{{ ar_pdf('صادر من') }}</th>
            <th>{{ ar_pdf('تاريخ الإصدار') }}</th>
            <th>{{ ar_pdf('المرافقون') }}</th>
            <th>{{ ar_pdf('الدفع') }}</th>
            <th>{{ ar_pdf('المدفوع/الإجمالي') }}</th>
            <th>{{ ar_pdf('الجوال') }}</th>
        </tr>
    </thead>
    <tbody>
        @foreach($reservations as $res)
        @php $cCount = $res->companions->count(); @endphp
        <tr>
            <td style="font-weight:bold;text-align:center;">
                {{ $res->room?->room_number }}
                @if($res->status === 'confirmed')
                <span class="badge-confirmed">({{ ar_pdf('محجوز') }})</span>
                @endif
            </td>
            <td>{{ ar_pdf($res->guest?->full_name) }}</td>
            <td>{{ ar_pdf($res->guest?->nationality) }}</td>
            <td>{{ ar_pdf($res->guest?->occupation) }}</td>
            <td>{{ ar_pdf($res->origin) }}</td>
            <td class="ltr-val">{{ $res->check_in_date?->format('d/m/Y') }}</td>
            <td class="ltr-val">{{ $res->check_in_time ?? '—' }}</td>
            <td>{{ ar_pdf($res->purpose) }}</td>
            <td>{{ ar_pdf($idTypeMap[$res->guest?->id_type] ?? $res->guest?->id_type) }}</td>
            <td class="ltr-val">{{ $res->guest?->id_number }}</td>
            <td>{{ ar_pdf($res->guest?->id_issuer) }}</td>
            <td class="ltr-val">{{ $res->guest?->id_issue_date?->format('Y/m/d') }}</td>
            <td>{{ $cCount > 0 ? ar_pdf($cCount . ' مرافق') : ar_pdf('لوحده') }}</td>
            <td>{{ ar_pdf($payLabels[$res->payment_status] ?? $res->payment_status) }}</td>
            <td class="ltr-val">{{ number_format($res->paid_amount, 0) }} / {{ number_format($res->total_amount, 0) }}</td>
            <td class="ltr-val">{{ $res->guest?->phone }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

<div class="footer">
    {{ ar_pdf('طُبع في:') }} {{ now()->format('d/m/Y H:i') }}
</div>

</body>
</html>
