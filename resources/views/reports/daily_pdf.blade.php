<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
<meta charset="UTF-8">
<style>
    @font-face {
        font-family: 'DejaVu Sans';
        src: url('{{ storage_path("fonts/DejaVuSans.ttf") }}');
    }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
        font-family: 'DejaVu Sans', Arial, sans-serif;
        font-size: 9px;
        direction: rtl;
        color: #1a1a1a;
        background: #fff;
    }
    .header {
        text-align: center;
        padding: 10px 0 8px;
        border-bottom: 2px solid #0F4C75;
        margin-bottom: 10px;
    }
    .header h1 { font-size: 16px; color: #0F4C75; font-weight: bold; }
    .header p { font-size: 10px; color: #555; margin-top: 2px; }
    .summary {
        display: flex;
        gap: 8px;
        margin-bottom: 10px;
        justify-content: center;
    }
    .summary-card {
        border: 1px solid #ddd;
        border-radius: 4px;
        padding: 6px 14px;
        text-align: center;
        min-width: 90px;
    }
    .summary-card .num { font-size: 18px; font-weight: bold; color: #0F4C75; }
    .summary-card .lbl { font-size: 8px; color: #666; }
    table {
        width: 100%;
        border-collapse: collapse;
        font-size: 8px;
    }
    thead tr { background: #0F4C75; color: #fff; }
    thead th {
        padding: 5px 4px;
        text-align: right;
        font-weight: bold;
        white-space: nowrap;
        border: 1px solid #0a3a5e;
    }
    tbody tr:nth-child(even) { background: #f4f8fc; }
    tbody td {
        padding: 4px 4px;
        border: 1px solid #e0e0e0;
        white-space: nowrap;
    }
    .footer {
        margin-top: 12px;
        text-align: left;
        font-size: 8px;
        color: #999;
        border-top: 1px solid #eee;
        padding-top: 5px;
    }
</style>
</head>
<body>

<div class="header">
    <h1>القائمة اليومية للنزلاء</h1>
    <p>تاريخ: {{ \Carbon\Carbon::parse($date)->format('d/m/Y') }} &nbsp;|&nbsp; عدد النزلاء: {{ $reservations->count() }}</p>
</div>

@php
    $checkedIn  = $reservations->where('status', 'checked_in')->count();
    $confirmed  = $reservations->where('status', 'confirmed')->count();
    $companions = $reservations->sum(fn($r) => $r->companions->count());
    $idTypeMap  = ['national_id' => 'بطاقة', 'passport' => 'جواز', 'residence' => 'إقامة'];
    $payLabels  = ['unpaid' => 'غير مدفوع', 'partial' => 'جزئي', 'paid' => 'مدفوع', 'deferred' => 'مؤجل'];
@endphp

<table style="width:auto;border:none;margin:0 auto 10px;">
    <tr>
        <td style="border:1px solid #ddd;padding:5px 12px;text-align:center;">
            <div style="font-size:16px;font-weight:bold;color:#0F4C75;">{{ $reservations->count() }}</div>
            <div style="font-size:8px;color:#666;">إجمالي النزلاء</div>
        </td>
        <td style="border:1px solid #ddd;padding:5px 12px;text-align:center;">
            <div style="font-size:16px;font-weight:bold;color:#16a34a;">{{ $checkedIn }}</div>
            <div style="font-size:8px;color:#666;">مسجل دخول</div>
        </td>
        <td style="border:1px solid #ddd;padding:5px 12px;text-align:center;">
            <div style="font-size:16px;font-weight:bold;color:#2563eb;">{{ $confirmed }}</div>
            <div style="font-size:8px;color:#666;">حجز مؤكد</div>
        </td>
        <td style="border:1px solid #ddd;padding:5px 12px;text-align:center;">
            <div style="font-size:16px;font-weight:bold;color:#374151;">{{ $companions }}</div>
            <div style="font-size:8px;color:#666;">مرافق</div>
        </td>
    </tr>
</table>

@if($reservations->isEmpty())
<p style="text-align:center;color:#999;padding:20px;">لا يوجد نزلاء في هذا التاريخ</p>
@else
<table>
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
        @php $companions = $res->companions->count(); @endphp
        <tr>
            <td style="font-weight:bold;">
                {{ $res->room?->room_number }}
                @if($res->status === 'confirmed')<span style="color:#2563eb;"> (محجوز)</span>@endif
            </td>
            <td>{{ $res->guest?->full_name }}</td>
            <td>{{ $res->guest?->nationality }}</td>
            <td>{{ $res->guest?->occupation }}</td>
            <td>{{ $res->origin }}</td>
            <td>{{ $res->check_in_date?->format('d/m/Y') }}</td>
            <td>{{ $res->check_in_time ?? '—' }}</td>
            <td>{{ $res->purpose }}</td>
            <td>{{ $idTypeMap[$res->guest?->id_type] ?? $res->guest?->id_type }}</td>
            <td>{{ $res->guest?->id_number }}</td>
            <td>{{ $res->guest?->id_issuer }}</td>
            <td>{{ $res->guest?->id_issue_date?->format('Y/m/d') }}</td>
            <td>{{ $companions > 0 ? $companions . ' مرافق' : 'لوحده' }}</td>
            <td>{{ $payLabels[$res->payment_status] ?? $res->payment_status }}</td>
            <td>{{ number_format($res->paid_amount, 0) }} / {{ number_format($res->total_amount, 0) }}</td>
            <td>{{ $res->guest?->phone }}</td>
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
