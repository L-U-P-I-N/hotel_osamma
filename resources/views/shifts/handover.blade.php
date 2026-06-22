<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
    @font-face {
        font-family: 'NotoNaskh';
        font-style: normal; font-weight: normal;
        src: url('{{ storage_path('fonts/NotoNaskhArabic-Regular.ttf') }}') format('truetype');
    }
    @font-face {
        font-family: 'NotoNaskh';
        font-style: normal; font-weight: bold;
        src: url('{{ storage_path('fonts/NotoNaskhArabic-Bold.ttf') }}') format('truetype');
    }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'NotoNaskh', Arial, sans-serif; font-size: 12px; color: #1f2937; direction: rtl; padding: 28px; background: #fff; }
    .header { text-align: center; padding-bottom: 14px; border-bottom: 3px solid #0F4C75; margin-bottom: 18px; }
    .header h1 { font-size: 18px; font-weight: bold; color: #0F4C75; }
    .header p  { font-size: 11px; color: #6b7280; margin-top: 2px; }
    .title { text-align: center; font-size: 15px; font-weight: bold; color: #0F4C75; margin-bottom: 18px; }
    .section { margin-bottom: 16px; }
    .section-title { font-size: 12px; font-weight: bold; color: #0F4C75; border-bottom: 1px solid #dbeafe; padding-bottom: 4px; margin-bottom: 10px; }
    table.info { width: 100%; border-collapse: collapse; }
    table.info td { padding: 5px 8px; font-size: 11px; }
    table.info td:first-child { font-weight: bold; color: #374151; width: 140px; }
    table.info tr:nth-child(odd) td { background: #f9fafb; }
    .amount-grid { display: table; width: 100%; border-collapse: separate; border-spacing: 6px; }
    .amount-cell { display: table-cell; border: 1px solid #e5e7eb; border-radius: 6px; padding: 8px 10px; text-align: center; }
    .amount-cell .val { font-size: 14px; font-weight: bold; }
    .amount-cell .lbl { font-size: 10px; color: #6b7280; margin-top: 2px; }
    table.payments-list { width: 100%; border-collapse: collapse; font-size: 11px; }
    table.payments-list th { background: #e8f0f7; color: #0F4C75; padding: 5px 8px; text-align: right; }
    table.payments-list td { padding: 5px 8px; border-bottom: 1px solid #f3f4f6; }
    table.withdrawals-list { width: 100%; border-collapse: collapse; font-size: 11px; }
    table.withdrawals-list th { background: #fee2e2; color: #991b1b; padding: 5px 8px; text-align: right; }
    table.withdrawals-list td { padding: 5px 8px; border-bottom: 1px solid #f3f4f6; }
    .sig-table { display: table; width: 100%; margin-top: 36px; }
    .sig-cell { display: table-cell; text-align: center; width: 50%; }
    .sig-line { border-top: 1px solid #9ca3af; margin: 0 24px; padding-top: 6px; font-size: 11px; color: #6b7280; margin-top: 44px; }
    .status-badge { display: inline-block; padding: 2px 10px; border-radius: 12px; font-size: 11px; font-weight: bold; }
    .badge-green { background: #dcfce7; color: #166534; }
    .badge-red   { background: #fee2e2; color: #991b1b; }
    .badge-gray  { background: #f3f4f6; color: #374151; }
    .footer { text-align: center; font-size: 10px; color: #9ca3af; margin-top: 20px; padding-top: 10px; border-top: 1px solid #e5e7eb; }
    @media print { body { padding: 15px; } }
</style>
</head>
<body>

<div class="header">
    <h1>الفندق السعودي</h1>
    <p>نظام إدارة الفندق</p>
</div>

<div class="title">مستند تسليم الوردية</div>

{{-- Shift Info --}}
<div class="section">
    <div class="section-title">بيانات الوردية</div>
    <table class="info">
        <tr>
            <td>الموظف:</td>
            <td>{{ $shift->user->name ?? '—' }}</td>
            <td style="font-weight:bold; color:#374151; width:140px;">تاريخ الوردية:</td>
            <td>{{ $shift->shift_date->format('d/m/Y') }}</td>
        </tr>
        <tr>
            <td>بدء الوردية:</td>
            <td>{{ $shift->started_at?->format('H:i') ?? '—' }}</td>
            <td style="font-weight:bold; color:#374151;">إقفال الوردية:</td>
            <td>{{ $shift->closed_at?->format('H:i') ?? 'لم تُقفَل بعد' }}</td>
        </tr>
        <tr>
            <td>حالة الوردية:</td>
            <td>
                <span class="status-badge {{ $shift->is_closed ? 'badge-gray' : 'badge-green' }}">
                    {{ $shift->is_closed ? 'مقفلة' : 'مفتوحة' }}
                </span>
            </td>
            <td style="font-weight:bold; color:#374151;">رقم الوردية:</td>
            <td>#{{ $shift->id }}</td>
        </tr>
    </table>
</div>

{{-- Financial Summary --}}
<div class="section">
    <div class="section-title">ملخص الكاش (ريال يمني)</div>
    <table style="width:100%; border-collapse:collapse;">
        <tr>
            <td style="text-align:center; padding:10px; border:1px solid #e5e7eb; width:25%;">
                <div style="font-size:14px; font-weight:bold; color:#16a34a;">{{ number_format($shift->total_received_yer, 0) }}</div>
                <div style="font-size:10px; color:#6b7280;">المستلمات</div>
            </td>
            <td style="text-align:center; padding:10px; border:1px solid #e5e7eb; width:25%;">
                <div style="font-size:14px; font-weight:bold; color:#dc2626;">{{ number_format($shift->total_withdrawals_yer, 0) }}</div>
                <div style="font-size:10px; color:#6b7280;">السحبيات</div>
            </td>
            <td style="text-align:center; padding:10px; border:1px solid #e5e7eb; width:25%;">
                <div style="font-size:14px; font-weight:bold; color:#0F4C75;">{{ number_format($shift->net_balance_yer, 0) }}</div>
                <div style="font-size:10px; color:#6b7280;">الصافي المتوقع</div>
            </td>
            <td style="text-align:center; padding:10px; border:1px solid #e5e7eb; width:25%;">
                <div style="font-size:14px; font-weight:bold; color:{{ $shift->shortfall < 0 ? '#dc2626' : '#16a34a' }};">
                    {{ $shift->is_closed ? number_format($shift->actual_amount, 0) : '—' }}
                </div>
                <div style="font-size:10px; color:#6b7280;">العد الفعلي</div>
            </td>
        </tr>
    </table>
    @if($shift->is_closed && $shift->shortfall != 0)
    <div style="margin-top:8px; padding:8px 12px; background:{{ $shift->shortfall < 0 ? '#fee2e2' : '#dcfce7' }}; border-radius:6px; font-size:11px; font-weight:bold; color:{{ $shift->shortfall < 0 ? '#dc2626' : '#16a34a' }};">
        {{ $shift->shortfall < 0 ? 'عجز: ' : 'فائض: ' }} {{ number_format(abs($shift->shortfall), 0) }} ر.ي
    </div>
    @endif
</div>

{{-- Payments --}}
<div class="section">
    <div class="section-title">المستلمات ({{ $shift->payments->count() }} دفعة)</div>
    @if($shift->payments->isNotEmpty())
    <table class="payments-list">
        <thead>
            <tr>
                <th>الوقت</th>
                <th>النزيل</th>
                <th>الغرفة</th>
                <th>طريقة الدفع</th>
                <th>المبلغ (ر.ي)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($shift->payments as $pmt)
            <tr>
                <td>{{ $pmt->payment_date?->format('H:i') ?? '—' }}</td>
                <td>{{ $pmt->reservation->guest->full_name ?? '—' }}</td>
                <td>{{ $pmt->reservation->display_room_number ?? '—' }}</td>
                <td>{{ match($pmt->method) {'cash'=>'نقداً','bank_transfer'=>'تحويل','pos'=>'POS',default=>$pmt->method} }}</td>
                <td style="font-weight:bold;">{{ number_format($pmt->amount, 0) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <p style="font-size:11px; color:#9ca3af; text-align:center; padding:8px;">لا توجد مستلمات</p>
    @endif
</div>

{{-- Withdrawals --}}
<div class="section">
    <div class="section-title">السحبيات ({{ $shift->withdrawals->count() }} عملية)</div>
    @if($shift->withdrawals->isNotEmpty())
    <table class="withdrawals-list">
        <thead>
            <tr>
                <th>الوقت</th>
                <th>المستلم</th>
                <th>الملاحظات</th>
                <th>المبلغ</th>
                <th>العملة</th>
            </tr>
        </thead>
        <tbody>
            @foreach($shift->withdrawals as $wd)
            <tr>
                <td>{{ $wd->created_at?->format('H:i') ?? '—' }}</td>
                <td>{{ $wd->withdrawn_by_name ?? '—' }}</td>
                <td>{{ Str::limit($wd->notes ?? '—', 40) }}</td>
                <td style="font-weight:bold; color:#dc2626;">{{ number_format($wd->amount, 0) }}</td>
                <td>{{ $wd->currency }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <p style="font-size:11px; color:#9ca3af; text-align:center; padding:8px;">لا توجد سحبيات</p>
    @endif
</div>

@if($shift->notes)
<div class="section">
    <div class="section-title">ملاحظات الوردية</div>
    <p style="font-size:11px; color:#374151; padding:6px 8px; background:#f9fafb; border-radius:4px;">{{ $shift->notes }}</p>
</div>
@endif

{{-- Signatures --}}
<table class="sig-table">
    <tr>
        <td class="sig-cell">
            <div class="sig-line">توقيع الموظف المسلِّم<br>{{ $shift->user->name ?? '—' }}</div>
        </td>
        <td class="sig-cell">
            <div class="sig-line">توقيع الموظف المستلِم</div>
        </td>
    </tr>
</table>

<div class="footer">
    طُبع في {{ now()->format('d/m/Y H:i') }} — وردية #{{ $shift->id }}
</div>

<script>
if (window.location.search.includes('print=1')) {
    window.onload = () => window.print();
}
</script>

</body>
</html>
