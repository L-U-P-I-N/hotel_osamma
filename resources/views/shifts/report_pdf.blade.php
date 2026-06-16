<!DOCTYPE html>
<html lang="ar">
<head>
<meta charset="UTF-8">
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
        font-family: 'NotoNaskhArabic', 'DejaVu Sans', serif;
        font-size: 10px;
        direction: ltr;
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
    .summary-currency {
        display: table;
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 8px;
    }
    .summary-currency-row {
        display: table-row;
    }
    .summary-currency-row td {
        display: table-cell;
        padding: 3px 8px;
        border: 1px solid #ddd;
        text-align: right;
        font-size: 9.5px;
    }
    .summary-currency-row td.ltr { direction: ltr; text-align: left; }
    .summary-currency-row.head td { font-weight: bold; background: #e8f0f7; }
    .pos { color: #16a34a; }
    .neg { color: #dc2626; }
    .net { font-weight: bold; color: #1d4ed8; }

    .footer {
        margin-top: 16px;
        border-top: 1px solid #eee;
        padding-top: 6px;
        font-size: 8px;
        color: #aaa;
        text-align: left;
        direction: ltr;
    }
    .sig-area {
        margin-top: 20px;
        display: table;
        width: 100%;
    }
    .sig-box {
        display: table-cell;
        width: 50%;
        border-top: 1px dashed #aaa;
        padding-top: 4px;
        font-size: 9px;
        text-align: center;
    }
</style>
</head>
<body>

@php
    $typeLabels = ['morning' => 'صباحية', 'evening' => 'مسائية', 'night' => 'ليلية'];
    $curLabels  = ['YER' => 'ر.ي', 'SAR' => 'ر.س', 'USD' => '$'];
    $payLabels  = ['unpaid' => 'غير مدفوع', 'partial' => 'جزئي', 'paid' => 'مدفوع', 'deferred' => 'مؤجل'];

    // Group payments by currency
    $payments = $shift->payments ?? collect();
    $recvByCur = [];
    foreach (['YER','SAR','USD'] as $c) {
        $total = $payments->where('currency', $c)->sum(fn($p) => (float)$p->amount);
        if ($total > 0) $recvByCur[$c] = $total;
    }

    // Group withdrawals by currency
    $withdrawals = $shift->withdrawals ?? collect();
    $wdrByCur = [];
    foreach (['YER','SAR','USD'] as $c) {
        $total = $withdrawals->where('currency', $c)->sum(fn($w) => (float)$w->amount);
        if ($total > 0) $wdrByCur[$c] = $total;
    }

    // All relevant currencies
    $allCurs = array_unique(array_merge(array_keys($recvByCur), array_keys($wdrByCur)));
@endphp

{{-- Header --}}
<div class="header">
    <h1>{{ ar_pdf('تقرير الوردية') }}</h1>
    <div class="sub">
        {{ ar_pdf('وردية') }} {{ ar_pdf($typeLabels[$shift->shift_type] ?? $shift->shift_type) }}
        &nbsp;|&nbsp;
        {{ $shift->shift_date->format('d/m/Y') }}
        &nbsp;|&nbsp;
        {{ ar_pdf('الموظف:') }} {{ ar_pdf($shift->user?->name) }}
    </div>
</div>

{{-- Meta --}}
<table class="meta-table">
    <tr>
        <td class="lbl">{{ ar_pdf('وقت الفتح:') }}</td>
        <td class="val ltr">{{ $shift->started_at?->format('H:i') }}</td>
        <td class="lbl">{{ ar_pdf('وقت الإقفال:') }}</td>
        <td class="val ltr">{{ $shift->closed_at?->format('H:i') ?? '—' }}</td>
        <td class="lbl">{{ ar_pdf('عدد الإيرادات:') }}</td>
        <td class="val">{{ $payments->count() }}</td>
        <td class="lbl">{{ ar_pdf('عدد السحبيات:') }}</td>
        <td class="val">{{ $withdrawals->count() }}</td>
    </tr>
</table>

{{-- Payments section --}}
<h2>{{ ar_pdf('الإيرادات المستلمة') }}</h2>
@if($payments->isEmpty())
<table class="data"><tbody><tr><td style="text-align:center;padding:8px;color:#999;">{{ ar_pdf('لا توجد مدفوعات') }}</td></tr></tbody></table>
@else
<table class="data">
    <thead>
        <tr>
            <th>#</th>
            <th>{{ ar_pdf('الغرفة') }}</th>
            <th>{{ ar_pdf('النزيل') }}</th>
            <th>{{ ar_pdf('نوع الدفع') }}</th>
            <th>{{ ar_pdf('المبلغ') }}</th>
            <th>{{ ar_pdf('العملة') }}</th>
            <th>{{ ar_pdf('الوقت') }}</th>
        </tr>
    </thead>
    <tbody>
        @foreach($payments as $i => $p)
        <tr>
            <td class="center">{{ $i + 1 }}</td>
            <td class="center">{{ $p->reservation?->room?->room_number ?? '—' }}</td>
            <td>{{ ar_pdf($p->reservation?->guest?->full_name) }}</td>
            <td>{{ ar_pdf($payLabels[$p->payment_type] ?? ar_pdf('دفعة')) }}</td>
            <td class="ltr" style="font-weight:bold;">{{ number_format($p->amount, 0) }}</td>
            <td class="center">{{ ar_pdf($curLabels[$p->currency] ?? $p->currency) }}</td>
            <td class="ltr">{{ $p->created_at?->format('H:i') }}</td>
        </tr>
        @endforeach
        @foreach(['YER','SAR','USD'] as $c)
        @php $cTotal = $payments->where('currency', $c)->sum(fn($p) => (float)$p->amount); @endphp
        @if($cTotal > 0)
        <tr class="total-row">
            <td colspan="4" style="text-align:right;">{{ ar_pdf('المجموع') }} ({{ ar_pdf($curLabels[$c]) }})</td>
            <td class="ltr pos">{{ number_format($cTotal, 0) }}</td>
            <td class="center">{{ ar_pdf($curLabels[$c]) }}</td>
            <td></td>
        </tr>
        @endif
        @endforeach
    </tbody>
</table>
@endif

{{-- Withdrawals section --}}
<h2>{{ ar_pdf('السحبيات') }}</h2>
@if($withdrawals->isEmpty())
<table class="data"><tbody><tr><td style="text-align:center;padding:8px;color:#999;">{{ ar_pdf('لا توجد سحبيات') }}</td></tr></tbody></table>
@else
<table class="data">
    <thead>
        <tr>
            <th>#</th>
            <th>{{ ar_pdf('المستلم') }}</th>
            <th>{{ ar_pdf('البيان / السبب') }}</th>
            <th>{{ ar_pdf('النوع') }}</th>
            <th>{{ ar_pdf('المبلغ') }}</th>
            <th>{{ ar_pdf('العملة') }}</th>
            <th>{{ ar_pdf('مقابل') }}</th>
            <th>{{ ar_pdf('الوقت') }}</th>
        </tr>
    </thead>
    <tbody>
        @foreach($withdrawals as $i => $w)
        <tr @if($w->isExchange()) class="exchange-row" @endif>
            <td class="center">{{ $i + 1 }}</td>
            <td>{{ ar_pdf($w->withdrawn_by_name) }}</td>
            <td>{{ ar_pdf($w->notes) }}</td>
            <td class="center">{{ ar_pdf($w->type_label) }}</td>
            <td class="ltr" style="font-weight:bold;color:#dc2626;">{{ number_format($w->amount, 0) }}</td>
            <td class="center">{{ ar_pdf($curLabels[$w->currency] ?? $w->currency) }}</td>
            <td class="ltr">
                @if($w->isExchange() && $w->exchange_to_amount)
                {{ number_format($w->exchange_to_amount, 0) }} {{ ar_pdf($curLabels[$w->exchange_to_currency] ?? '') }}
                @else
                —
                @endif
            </td>
            <td class="ltr">{{ $w->created_at?->format('H:i') }}</td>
        </tr>
        @endforeach
        @foreach(['YER','SAR','USD'] as $c)
        @php $cTotal = $withdrawals->where('currency', $c)->sum(fn($w) => (float)$w->amount); @endphp
        @if($cTotal > 0)
        <tr class="total-row">
            <td colspan="4" style="text-align:right;">{{ ar_pdf('مجموع السحبيات') }} ({{ ar_pdf($curLabels[$c]) }})</td>
            <td class="ltr neg">{{ number_format($cTotal, 0) }}</td>
            <td class="center">{{ ar_pdf($curLabels[$c]) }}</td>
            <td colspan="2"></td>
        </tr>
        @endif
        @endforeach
    </tbody>
</table>
@endif

{{-- Summary --}}
<div class="summary-box">
    <h3>{{ ar_pdf('ملخص الوردية') }}</h3>
    <table style="width:100%;border-collapse:collapse;font-size:9.5px;">
        <thead>
            <tr style="background:#e8f0f7;">
                <th style="padding:4px 8px;border:1px solid #cdd8e3;text-align:right;">{{ ar_pdf('العملة') }}</th>
                <th style="padding:4px 8px;border:1px solid #cdd8e3;text-align:right;">{{ ar_pdf('الإيرادات') }}</th>
                <th style="padding:4px 8px;border:1px solid #cdd8e3;text-align:right;">{{ ar_pdf('السحبيات') }}</th>
                <th style="padding:4px 8px;border:1px solid #cdd8e3;text-align:right;">{{ ar_pdf('الصافي المتبقي') }}</th>
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
                <td style="padding:4px 8px;border:1px solid #ddd;text-align:right;font-weight:bold;">
                    {{ ar_pdf($curLabels[$row['cur']]) }}
                </td>
                <td style="padding:4px 8px;border:1px solid #ddd;direction:ltr;text-align:left;" class="pos">
                    {{ number_format($row['recv'], 0) }}
                </td>
                <td style="padding:4px 8px;border:1px solid #ddd;direction:ltr;text-align:left;" class="neg">
                    {{ number_format($row['wdr'], 0) }}
                </td>
                <td style="padding:4px 8px;border:1px solid #ddd;direction:ltr;text-align:left;font-weight:bold;" class="net">
                    {{ number_format($row['net'], 0) }}
                </td>
            </tr>
            @empty
            <tr><td colspan="4" style="text-align:center;padding:8px;color:#999;">{{ ar_pdf('لا توجد بيانات') }}</td></tr>
            @endforelse
        </tbody>
    </table>

    @if($shift->notes)
    <div style="margin-top:8px;font-size:9px;color:#555;text-align:right;direction:ltr;">
        {{ ar_pdf('ملاحظات:') }} {{ ar_pdf($shift->notes) }}
    </div>
    @endif
</div>

{{-- Signatures --}}
<table style="width:100%;margin-top:24px;">
    <tr>
        <td style="width:50%;text-align:center;padding:0 20px;">
            <div style="border-top:1px dashed #aaa;padding-top:4px;font-size:9px;">
                {{ ar_pdf('توقيع الموظف') }}
            </div>
        </td>
        <td style="width:50%;text-align:center;padding:0 20px;">
            <div style="border-top:1px dashed #aaa;padding-top:4px;font-size:9px;">
                {{ ar_pdf('توقيع المشرف') }}
            </div>
        </td>
    </tr>
</table>

<div class="footer">
    {{ ar_pdf('طُبع في:') }} {{ now()->format('d/m/Y H:i') }}
    &nbsp;|&nbsp;
    {{ ar_pdf('رقم الوردية:') }} #{{ $shift->id }}
</div>

</body>
</html>
