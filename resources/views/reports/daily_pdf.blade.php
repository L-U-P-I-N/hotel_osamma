<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<style>
    @font-face {
        font-family: 'NotoNaskhArabic';
        font-style: normal;
        font-weight: normal;
        src: url("{{ storage_path('fonts') }}/NotoNaskhArabic.ttf") format('truetype');
    }
    @font-face {
        font-family: 'NotoNaskhArabic';
        font-style: normal;
        font-weight: bold;
        src: url("{{ storage_path('fonts') }}/NotoNaskhArabic-Bold.ttf") format('truetype');
    }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
        font-family: 'NotoNaskhArabic', sans-serif;
        font-size: 9px;
        direction: rtl;
        color: #1a1a1a;
        background: #fff;
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
    }
    .header p {
        font-size: 9px;
        color: #555;
        margin-top: 3px;
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
    .num       { font-size: 15px; font-weight: bold; color: #0F4C75; }
    .num-green { font-size: 15px; font-weight: bold; color: #16a34a; }
    .num-blue  { font-size: 15px; font-weight: bold; color: #2563eb; }
    .num-gray  { font-size: 15px; font-weight: bold; color: #374151; }
    .lbl { font-size: 7.5px; color: #666; }

    table.main {
        width: 100%;
        border-collapse: collapse;
        font-size: 7.5px;
        direction: rtl;
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
        text-align: right;
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
    <h1>القائمة اليومية للنزلاء</h1>
    <p>
        تاريخ: {{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}
        &nbsp;&nbsp;|&nbsp;&nbsp;
        عدد النزلاء: {{ $reservations->count() }}
    </p>
</div>

<table class="summary-table">
    <tr>
        <td>
            <div class="num">{{ $reservations->count() }}</div>
            <div class="lbl">إجمالي النزلاء</div>
        </td>
        <td>
            <div class="num-green">{{ $checkedIn }}</div>
            <div class="lbl">مسجل دخول</div>
        </td>
        <td>
            <div class="num-blue">{{ $confirmed }}</div>
            <div class="lbl">حجز مؤكد</div>
        </td>
        <td>
            <div class="num-gray">{{ $companions }}</div>
            <div class="lbl">مرافق</div>
        </td>
    </tr>
</table>

@if($reservations->isEmpty())
<p style="text-align:center;color:#999;padding:20px;">لا يوجد نزلاء في هذا التاريخ</p>
@else
<table class="main">
    <thead>
        <tr>
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
            <th>المرافقون</th>
            <th>الدفع</th>
            <th>المدفوع/الإجمالي</th>
            <th>الجوال</th>
        </tr>
    </thead>
    <tbody>
        @foreach($reservations as $res)
        @php $cCount = $res->companions->count(); @endphp
        <tr>
            <td style="font-weight:bold;text-align:center;">
                {{ $res->room?->room_number }}
                @if($res->status === 'confirmed')
                <span class="badge-confirmed">(محجوز)</span>
                @endif
            </td>
            <td>{{ $res->guest?->full_name }}</td>
            <td>{{ $res->guest?->nationality }}</td>
            <td>{{ $res->guest?->occupation }}</td>
            <td>{{ $res->origin }}</td>
            <td class="ltr-val">{{ $res->check_in_date?->format('d/m/Y') }}</td>
            <td class="ltr-val">{{ $res->check_in_time ?? '—' }}</td>
            <td>{{ $res->purpose }}</td>
            <td>{{ $idTypeMap[$res->guest?->id_type] ?? $res->guest?->id_type }}</td>
            <td class="ltr-val">{{ $res->guest?->id_number }}</td>
            <td>{{ $res->guest?->id_issuer }}</td>
            <td class="ltr-val">{{ $res->guest?->id_issue_date?->format('Y/m/d') }}</td>
            <td>{{ $cCount > 0 ? $cCount . ' مرافق' : 'لوحده' }}</td>
            <td>{{ $payLabels[$res->payment_status] ?? $res->payment_status }}</td>
            <td class="ltr-val">{{ number_format($res->paid_amount, 0) }} / {{ number_format($res->total_amount, 0) }}</td>
            <td class="ltr-val">{{ $res->guest?->phone }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

<div class="footer">
    طُبع في: {{ now()->format('d/m/Y H:i') }}
</div>

</body>
</html>
