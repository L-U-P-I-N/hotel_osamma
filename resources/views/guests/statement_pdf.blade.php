<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<style>
    @font-face {
        font-family: 'NotoNaskh';
        font-style: normal; font-weight: normal;
        src: url('{{ storage_path('fonts/NotoNaskhArabic.ttf') }}') format('truetype');
    }
    @font-face {
        font-family: 'NotoNaskh';
        font-style: normal; font-weight: bold;
        src: url('{{ storage_path('fonts/NotoNaskhArabic-Bold.ttf') }}') format('truetype');
    }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'NotoNaskh', Arial, sans-serif; font-size: 12px; color: #1f2937; direction: rtl; padding: 28px; }
    .header { text-align: center; padding-bottom: 14px; border-bottom: 3px solid #0F4C75; margin-bottom: 18px; }
    .header h1 { font-size: 18px; font-weight: bold; color: #0F4C75; }
    .header p  { font-size: 11px; color: #6b7280; }
    .title { text-align: center; font-size: 15px; font-weight: bold; color: #0F4C75; margin-bottom: 18px; }
    /* شبكة (تسمية / قيمة) بدل سطر واحد بفواصل: كل بيان في خليّته فلا تتداخل
       القيم عند الانتقال بين العربي والأرقام في dompdf. */
    table.info-grid { width: 100%; border-collapse: collapse; margin-bottom: 16px; font-size: 11px; }
    table.info-grid td { border: 1px solid #e5e7eb; padding: 5px 9px; }
    table.info-grid td.lbl { background: #f8fafc; color: #64748b; font-weight: bold; white-space: nowrap; width: 1%; }
    table.info-grid td.val { color: #1e293b; font-weight: bold; }
    /* الأرقام اللاتينية داخل نص عربي يقلبها الـ bidi، فنثبّت اتجاهها */
    table.info-grid td.val.ltr { direction: ltr; text-align: right; }
    .summary { display: table; width: 100%; border-collapse: separate; border-spacing: 8px; margin-bottom: 18px; }
    .summary-cell { display: table-cell; border: 1px solid #e5e7eb; border-radius: 6px; padding: 10px; text-align: center; width: 33%; }
    .summary-cell .val { font-size: 15px; font-weight: bold; }
    .summary-cell .lbl { font-size: 10px; color: #6b7280; margin-top: 2px; }
    .res-header { background: #0F4C75; color: white; padding: 6px 10px; font-size: 12px; font-weight: bold; margin-bottom: 0; }
    table.payments { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
    table.payments th { background: #e8f0f7; color: #0F4C75; padding: 6px 8px; text-align: right; font-size: 11px; }
    table.payments td { padding: 6px 8px; border-bottom: 1px solid #f3f4f6; font-size: 11px; }
    .balance-row td { font-weight: bold; background: #fef2f2; color: #dc2626; }
    .footer { text-align: center; font-size: 10px; color: #9ca3af; margin-top: 24px; padding-top: 10px; border-top: 1px solid #e5e7eb; }
</style>
</head>
<body>

@php
    // الغرفة أولاً في الكشف: أول ما يبحث عنه المحاسب. الحجز الحالي إن كان
    // مقيماً، وإلا آخر غرفة نزل فيها.
    $activeStay  = $reservations->firstWhere('status', 'checked_in') ?? $reservations->first();
    $roomNumber  = $activeStay?->display_room_number;
    $roomLabel   = $activeStay
        ? $roomNumber . ($activeStay->status === 'checked_in' ? ' (مقيم حالياً)' : ' (آخر إقامة)')
        : '—';
@endphp

<div class="header">
    @include('partials.pdf-hotel-header-full')
</div>

<div class="title">كشف حساب النزيل</div>

{{-- البيانات الشخصية مرتَّبة "تسمية: قيمة" وبترتيب ثابت — الغرفة أولاً --}}
<table class="info-grid" dir="rtl">
    <tr>
        <td class="lbl">رقم الغرفة</td>
        <td class="val">{{ $roomLabel }}</td>
        <td class="lbl">الاسم</td>
        <td class="val">{{ $guest->full_name }}</td>
    </tr>
    <tr>
        <td class="lbl">الجنسية</td>
        <td class="val">{{ $guest->nationality ?: '—' }}</td>
        <td class="lbl">المهنة</td>
        <td class="val">{{ $guest->occupation ?: '—' }}</td>
    </tr>
    <tr>
        <td class="lbl">نوع الهوية</td>
        <td class="val">{{ $guest->getIdTypeLabel() }}</td>
        <td class="lbl">رقم الهوية</td>
        <td class="val ltr">{{ $guest->id_number ?: '—' }}</td>
    </tr>
    <tr>
        <td class="lbl">جهة الإصدار</td>
        <td class="val">{{ $guest->id_issuer ?: '—' }}</td>
        <td class="lbl">تاريخ إصدار الهوية</td>
        <td class="val ltr">{{ $guest->id_issue_date?->format('d/m/Y') ?: '—' }}</td>
    </tr>
    <tr>
        <td class="lbl">رقم الجوال</td>
        <td class="val ltr">{{ $guest->phone ?: '—' }}</td>
        <td class="lbl">تاريخ الكشف</td>
        <td class="val ltr">{{ now()->format('d/m/Y') }}</td>
    </tr>
</table>

<table style="width:100%; border-collapse:collapse; margin-bottom:18px;">
    <tr>
        <td style="text-align:center; padding:10px; border:1px solid #e5e7eb; border-radius:4px; width:33%;">
            <div style="font-size:16px; font-weight:bold; color:#1f2937;">{{ $reservations->count() }}</div>
            <div style="font-size:10px; color:#6b7280;">إجمالي الحجوزات</div>
        </td>
        <td style="text-align:center; padding:10px; border:1px solid #e5e7eb; width:33%;">
            <div style="font-size:16px; font-weight:bold; color:#16a34a;">{{ number_format($totalPaid, 0) }} ر.ي</div>
            <div style="font-size:10px; color:#6b7280;">إجمالي المدفوع</div>
        </td>
        <td style="text-align:center; padding:10px; border:1px solid #e5e7eb; width:33%;">
            <div style="font-size:16px; font-weight:bold; color:{{ $totalBalance > 0 ? '#dc2626' : '#6b7280' }};">{{ number_format($totalBalance, 0) }} ر.ي</div>
            <div style="font-size:10px; color:#6b7280;">الرصيد المتبقي</div>
        </td>
    </tr>
</table>

@foreach($reservations as $res)
@php $balance = $res->total_amount - $res->paid_amount; $nights = $res->check_in_date->diffInDays($res->check_out_date); @endphp
<div style="margin-bottom:16px; border:1px solid #e5e7eb; border-radius:6px; overflow:hidden;">
    <div style="background:#0F4C75; color:white; padding:7px 10px; font-size:12px; font-weight:bold;">
        حجز #{{ $res->id }} — غرفة {{ $res->display_room_number }} —
        {{ $res->check_in_date->format('d/m/Y') }} إلى {{ $res->check_out_date->format('d/m/Y') }}
        ({{ $nights }} ليلة)
    </div>
    <table style="width:100%; border-collapse:collapse; font-size:11px;">
        <tr style="background:#e8f0f7;">
            <th style="padding:6px 10px; text-align:right; color:#0F4C75;">الموظف</th>
            <th style="padding:6px 10px; text-align:right; color:#0F4C75;">المبلغ (ر.ي)</th>
            <th style="padding:6px 10px; text-align:right; color:#0F4C75;">طريقة الدفع</th>
            <th style="padding:6px 10px; text-align:right; color:#0F4C75;">تاريخ الدفع</th>
        </tr>
        @forelse($res->payments as $pmt)
        <tr style="border-bottom:1px solid #f3f4f6;">
            <td style="padding:6px 10px; color:#6b7280;">{{ $pmt->receivedBy->name ?? '—' }}</td>
            <td style="padding:6px 10px; font-weight:bold;">{{ number_format($pmt->amount, 0) }}</td>
            <td style="padding:6px 10px;">{{ match($pmt->method) {'cash'=>'نقداً','bank_transfer'=>'تحويل بنكي','pos'=>'POS',default=>$pmt->method} }}</td>
            <td style="padding:6px 10px;">{{ $pmt->payment_date?->format('d/m/Y H:i') ?? '—' }}</td>
        </tr>
        @empty
        <tr><td colspan="4" style="padding:8px 10px; color:#9ca3af; text-align:center;">لا توجد دفعات</td></tr>
        @endforelse
        <tr style="background:#f9fafb; font-weight:bold;">
            <td style="padding:6px 10px; color:{{ $balance > 0 ? '#dc2626' : '#16a34a' }};">متبقي: {{ number_format($balance, 0) }}</td>
            <td style="padding:6px 10px; color:#16a34a;">مدفوع: {{ number_format($res->paid_amount, 0) }}</td>
            <td colspan="2" style="padding:6px 10px; color:#374151;">إجمالي الحجز: {{ number_format($res->total_amount, 0) }} ر.ي</td>
        </tr>
    </table>
</div>
@endforeach

<div class="footer">
    صدر في {{ now()->format('d/m/Y H:i') }}
</div>

</body>
</html>
