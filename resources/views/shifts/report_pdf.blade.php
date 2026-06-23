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
        font-size: 10px;
        direction: rtl;
        color: #1a1a1a;
        background: #fff;
        padding: 16px;
    }
    .header {
        text-align: center;
        border-bottom: 2px solid #0F4C75;
        padding-bottom: 10px;
        margin-bottom: 14px;
    }
    .header h1 { font-size: 16px; color: #0F4C75; font-weight: bold; }
    .header .sub { font-size: 9px; color: #555; margin-top: 4px; }

    .meta-table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
    .meta-table td { padding: 4px 8px; font-size: 9.5px; }
    .meta-table .lbl { color: #666; width: 90px; }
    .meta-table .val { font-weight: bold; color: #111; }
    .meta-table .val.ltr { direction: ltr; text-align: left; }

    h2 {
        font-size: 11px;
        font-weight: bold;
        color: #fff;
        background: #0F4C75;
        padding: 5px 10px;
        margin-bottom: 0;
        text-align: right;
    }
    table.data {
        width: 100%;
        border-collapse: collapse;
        font-size: 9px;
        margin-bottom: 14px;
        direction: rtl;
    }
    table.data thead tr { background: #e8f0f7; }
    table.data thead th {
        padding: 4px 6px;
        font-weight: bold;
        border: 1px solid #cdd8e3;
        text-align: right;
        white-space: nowrap;
    }
    table.data tbody tr:nth-child(even) { background: #f9fbfd; }
    table.data tbody td {
        padding: 4px 6px;
        border: 1px solid #e0e7ef;
        text-align: right;
        white-space: nowrap;
    }
    table.data tbody td.ltr { text-align: left; direction: ltr; }
    table.data tbody td.center { text-align: center; }
    .exchange-row td { background: #fff8e1 !important; }
    .total-row td { font-weight: bold; background: #f0f4f8; }

    .summary-box {
        border: 2px solid #0F4C75;
        border-radius: 6px;
        padding: 12px;
        margin-top: 14px;
    }
    .summary-box h3 {
        font-size: 11px;
        font-weight: bold;
        color: #0F4C75;
        text-align: right;
        margin-bottom: 8px;
        border-bottom: 1px solid #cdd8e3;
        padding-bottom: 4px;
    }
    .pos { color: #16a34a; }
    .neg { color: #dc2626; }
    .net { font-weight: bold; color: #1d4ed8; }

    .footer {
        margin-top: 16px;
        border-top: 1px solid #eee;
        padding-top: 6px;
        font-size: 8px;
        color: #aaa;
        text-align: right;
    }
</style>
</head>
<body>

@php
    $curLabels  = ['YER' => 'ر.ي', 'SAR' => 'ر.س', 'USD' => '$'];
    $payLabels  = ['unpaid' => 'غير مدفوع', 'partial' => 'جزئي', 'paid' => 'مدفوع', 'deferred' => 'مؤجل'];

    $payments = $shift->payments ?? collect();
    $recvByCur = [];
    foreach (['YER','SAR','USD'] as $c) {
        $total = $payments->where('currency', $c)->sum(fn($p) => (float)$p->amount);
        if ($total > 0) $recvByCur[$c] = $total;
    }

    $withdrawals = $shift->withdrawals ?? collect();
    $wdrByCur = [];
    foreach (['YER','SAR','USD'] as $c) {
        $total = $withdrawals->where('currency', $c)->sum(fn($w) => (float)$w->amount);
        if ($total > 0) $wdrByCur[$c] = $total;
    }
@endphp

{{-- Header --}}
<div class="header">
    <h1>تقرير الوردية</h1>
    <div class="sub">
        {{ $shift->shift_date->format('d/m/Y') }}
        &nbsp;|&nbsp;
        الموظف: {{ $shift->user?->name }}
    </div>
</div>

{{-- Meta --}}
<table class="meta-table" dir="rtl">
    <tr>
        <td class="lbl">وقت الفتح:</td>
        <td class="val ltr">{{ $shift->started_at?->format('H:i') }}</td>
        <td class="lbl">وقت الإقفال:</td>
        <td class="val ltr">{{ $shift->closed_at?->format('H:i') ?? '—' }}</td>
        <td class="lbl">عدد الإيرادات:</td>
        <td class="val">{{ $payments->count() }}</td>
        <td class="lbl">عدد السحبيات:</td>
        <td class="val">{{ $withdrawals->count() }}</td>
    </tr>
</table>

{{-- Payments section --}}
<h2>الإيرادات المستلمة</h2>
@if($payments->isEmpty())
<table class="data" dir="rtl"><tbody><tr><td style="text-align:center;padding:8px;color:#999;">لا توجد مدفوعات</td></tr></tbody></table>
@else
<table class="data" dir="rtl">
    <thead>
        <tr>
            <th>الوقت</th>
            <th>العملة</th>
            <th>المبلغ</th>
            <th>نوع الدفع</th>
            <th>النزيل</th>
            <th>الغرفة</th>
            <th>#</th>
        </tr>
    </thead>
    <tbody>
        @foreach($payments as $i => $p)
        <tr>
            <td class="ltr">{{ $p->created_at?->format('H:i') }}</td>
            <td class="center">{{ $curLabels[$p->currency] ?? $p->currency }}</td>
            <td class="ltr" style="font-weight:bold;">{{ number_format($p->amount, 0) }}</td>
            <td>{{ $payLabels[$p->payment_type] ?? 'دفعة' }}</td>
            <td>{{ $p->reservation?->guest?->full_name }}</td>
            <td class="center">{{ $p->reservation?->display_room_number ?? '—' }}</td>
            <td class="center">{{ $i + 1 }}</td>
        </tr>
        @endforeach
        @foreach(['YER','SAR','USD'] as $c)
        @php $cTotal = $payments->where('currency', $c)->sum(fn($p) => (float)$p->amount); @endphp
        @if($cTotal > 0)
        <tr class="total-row">
            <td></td>
            <td class="center">{{ $curLabels[$c] }}</td>
            <td class="ltr pos">{{ number_format($cTotal, 0) }}</td>
            <td colspan="4" style="text-align:right;">المجموع ({{ $curLabels[$c] }})</td>
        </tr>
        @endif
        @endforeach
    </tbody>
</table>
@endif

{{-- Withdrawals section --}}
<h2>السحبيات</h2>
@if($withdrawals->isEmpty())
<table class="data" dir="rtl"><tbody><tr><td style="text-align:center;padding:8px;color:#999;">لا توجد سحبيات</td></tr></tbody></table>
@else
<table class="data" dir="rtl">
    <thead>
        <tr>
            <th>الوقت</th>
            <th>بواسطة</th>
            <th>مقابل</th>
            <th>العملة</th>
            <th>المبلغ</th>
            <th>النوع</th>
            <th>البيان / السبب</th>
            <th>المستلم</th>
            <th>#</th>
        </tr>
    </thead>
    <tbody>
        @foreach($withdrawals as $i => $w)
        <tr @if($w->isExchange()) class="exchange-row" @endif>
            <td class="ltr">{{ $w->created_at?->format('H:i') }}</td>
            <td>{{ ($w->handed_by_name && $w->handed_by_name !== '-') ? $w->handed_by_name : '—' }}</td>
            <td class="ltr">
                @if($w->isExchange() && $w->exchange_to_amount)
                {{ number_format($w->exchange_to_amount, 0) }} {{ $curLabels[$w->exchange_to_currency] ?? '' }}
                @else
                —
                @endif
            </td>
            <td class="center">{{ $curLabels[$w->currency] ?? $w->currency }}</td>
            <td class="ltr" style="font-weight:bold;color:#dc2626;">{{ number_format($w->amount, 0) }}</td>
            <td class="center">{{ $w->type_label }}</td>
            <td>{{ $w->notes }}</td>
            <td>{{ $w->withdrawn_by_name }}</td>
            <td class="center">{{ $i + 1 }}</td>
        </tr>
        @endforeach
        @foreach(['YER','SAR','USD'] as $c)
        @php $cTotal = $withdrawals->where('currency', $c)->sum(fn($w) => (float)$w->amount); @endphp
        @if($cTotal > 0)
        <tr class="total-row">
            <td colspan="3"></td>
            <td class="center">{{ $curLabels[$c] }}</td>
            <td class="ltr neg">{{ number_format($cTotal, 0) }}</td>
            <td colspan="4" style="text-align:right;">مجموع السحبيات ({{ $curLabels[$c] }})</td>
        </tr>
        @endif
        @endforeach
    </tbody>
</table>
@endif

{{-- Summary --}}
<div class="summary-box">
    <h3>ملخص الوردية</h3>
    <table style="width:100%;border-collapse:collapse;font-size:9.5px;direction:rtl;" dir="rtl">
        <thead>
            <tr style="background:#e8f0f7;">
                <th style="padding:4px 8px;border:1px solid #cdd8e3;text-align:right;">الصافي المتبقي</th>
                <th style="padding:4px 8px;border:1px solid #cdd8e3;text-align:right;">السحبيات</th>
                <th style="padding:4px 8px;border:1px solid #cdd8e3;text-align:right;">الإيرادات</th>
                <th style="padding:4px 8px;border:1px solid #cdd8e3;text-align:right;">العملة</th>
            </tr>
        </thead>
        <tbody>
            @php
                $summaryRows = [];
                foreach (['YER','SAR','USD'] as $c) {
                    $recv = (float)($shift->{'total_received_'    . strtolower($c)} ?? 0);
                    $wdr  = (float)($shift->{'total_withdrawals_' . strtolower($c)} ?? 0);
                    if ($recv > 0 || $wdr > 0) {
                        $summaryRows[] = ['cur' => $c, 'recv' => $recv, 'wdr' => $wdr, 'net' => $recv - $wdr];
                    }
                }
            @endphp
            @forelse($summaryRows as $row)
            <tr>
                <td style="padding:4px 8px;border:1px solid #ddd;direction:ltr;text-align:left;font-weight:bold;" class="net">
                    {{ number_format($row['net'], 0) }}
                </td>
                <td style="padding:4px 8px;border:1px solid #ddd;direction:ltr;text-align:left;" class="neg">
                    {{ number_format($row['wdr'], 0) }}
                </td>
                <td style="padding:4px 8px;border:1px solid #ddd;direction:ltr;text-align:left;" class="pos">
                    {{ number_format($row['recv'], 0) }}
                </td>
                <td style="padding:4px 8px;border:1px solid #ddd;text-align:right;font-weight:bold;">
                    {{ $curLabels[$row['cur']] }}
                </td>
            </tr>
            @empty
            <tr><td colspan="4" style="text-align:center;padding:8px;color:#999;">لا توجد بيانات</td></tr>
            @endforelse
        </tbody>
    </table>

    @if($shift->notes)
    <div style="margin-top:8px;font-size:9px;color:#555;text-align:right;">
        ملاحظات: {{ $shift->notes }}
    </div>
    @endif
</div>

{{-- Signatures --}}
<table style="width:100%;margin-top:24px;direction:rtl;" dir="rtl">
    <tr>
        <td style="width:50%;text-align:center;padding:0 20px;">
            <div style="border-top:1px dashed #aaa;padding-top:4px;font-size:9px;">
                توقيع الموظف
            </div>
        </td>
        <td style="width:50%;text-align:center;padding:0 20px;">
            <div style="border-top:1px dashed #aaa;padding-top:4px;font-size:9px;">
                توقيع المشرف
            </div>
        </td>
    </tr>
</table>

<div class="footer">
    طُبع في: {{ now()->format('d/m/Y H:i') }}
    &nbsp;|&nbsp;
    رقم الوردية: #{{ $shift->id }}
</div>

</body>
</html>
