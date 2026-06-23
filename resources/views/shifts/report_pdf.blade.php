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
    body {
        font-family: 'NotoNaskhArabic', sans-serif;
        font-size: 10px;
        direction: rtl;
        color: #1a1a1a;
        background: #fff;
        padding: 14px;
    }

    /* Header */
    .header {
        text-align: center;
        border-bottom: 2px solid #0F4C75;
        padding-bottom: 8px;
        margin-bottom: 12px;
    }
    .header h1 { font-size: 17px; color: #0F4C75; font-weight: bold; margin-bottom: 3px; }
    .header .sub { font-size: 9.5px; color: #444; }

    /* Meta info row */
    .meta-box {
        border: 1px solid #d1dde8;
        border-radius: 4px;
        margin-bottom: 12px;
        overflow: hidden;
    }
    .meta-box table { width: 100%; border-collapse: collapse; }
    .meta-box table td {
        padding: 5px 8px;
        font-size: 9.5px;
        border: 1px solid #d1dde8;
        vertical-align: middle;
    }
    .meta-box .lbl { background: #f0f5fa; color: #4a6080; font-weight: bold; width: 12%; white-space: nowrap; }
    .meta-box .val { color: #111; font-weight: bold; width: 14%; }
    .meta-box .val.ltr { direction: ltr; text-align: left; }

    /* Section title */
    .section-title {
        font-size: 11px;
        font-weight: bold;
        color: #fff;
        background: #0F4C75;
        padding: 5px 10px;
        margin-bottom: 0;
        text-align: right;
    }

    /* Data tables */
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
        vertical-align: middle;
    }
    table.data tbody td.ltr { text-align: left; direction: ltr; }
    table.data tbody td.center { text-align: center; }
    .exchange-row td { background: #fff8e1 !important; }
    .total-row td { font-weight: bold; background: #edf2f8; }
    .pos { color: #16a34a; }
    .neg { color: #dc2626; }
    .net-blue { font-weight: bold; color: #1d4ed8; }

    /* Summary box */
    .summary-box {
        border: 2px solid #0F4C75;
        border-radius: 5px;
        padding: 10px;
        margin-bottom: 14px;
    }
    .summary-title {
        font-size: 11px;
        font-weight: bold;
        color: #0F4C75;
        border-bottom: 1px solid #cdd8e3;
        padding-bottom: 4px;
        margin-bottom: 8px;
        text-align: right;
    }

    /* Deficit box */
    .deficit-box {
        border: 2px solid #dc2626;
        border-radius: 5px;
        padding: 10px;
        margin-bottom: 14px;
        background: #fff5f5;
    }
    .deficit-title {
        font-size: 11px;
        font-weight: bold;
        color: #dc2626;
        border-bottom: 1px solid #fca5a5;
        padding-bottom: 4px;
        margin-bottom: 8px;
        text-align: right;
    }
    .info-row { margin-bottom: 4px; font-size: 9.5px; }
    .info-row .lbl { color: #555; }
    .info-row .val { font-weight: bold; }

    /* Signatures */
    .sig-table { width: 100%; margin-top: 22px; border-collapse: collapse; direction: rtl; }
    .sig-table td { text-align: center; padding: 0 20px; width: 50%; }
    .sig-line { border-top: 1px dashed #aaa; padding-top: 5px; font-size: 9px; color: #555; margin-top: 36px; }

    /* Footer */
    .footer {
        margin-top: 14px;
        border-top: 1px solid #eee;
        padding-top: 5px;
        font-size: 8px;
        color: #999;
        text-align: right;
    }
</style>
</head>
<body>

@php
    use Carbon\Carbon;

    // Helper: format datetime to 12h AM/PM in Arabic
    $ampm = fn($dt) => $dt
        ? $dt->format('h:i') . ' ' . ($dt->format('A') === 'AM' ? 'ص' : 'م')
        : '—';

    $curLabels = ['YER' => 'ر.ي', 'SAR' => 'ر.س', 'USD' => '$'];
    $typeLabels = [
        'reservation'   => 'دفعة حجز',
        'renewal'       => 'دفعة تجديد',
        'compensation'  => 'تعويض',
        'extra_service' => 'خدمة إضافية',
    ];

    $payments    = $shift->payments ?? collect();
    $withdrawals = $shift->withdrawals ?? collect();

    // Totals per currency
    $recvByCur = [];
    $wdrByCur  = [];
    foreach (['YER','SAR','USD'] as $c) {
        $r = $payments->where('currency', $c)->sum(fn($p) => (float)$p->amount);
        $w = $withdrawals->where('currency', $c)->sum(fn($x) => (float)$x->amount);
        if ($r > 0) $recvByCur[$c] = $r;
        if ($w > 0) $wdrByCur[$c]  = $w;
    }

    $netYer = (float)($shift->total_received_yer ?? 0) - (float)($shift->total_withdrawals_yer ?? 0);
    // shortfall = actual_amount - netBalance → negative means deficit
    $hasShortfall = $shift->is_closed && $shift->actual_amount !== null && $shift->shortfall !== null && $shift->shortfall < 0;
    $deficitAmount = $hasShortfall ? abs((float)$shift->shortfall) : 0;
@endphp

{{-- Header --}}
<div class="header">
    <h1>تقرير الوردية</h1>
    <div class="sub">
        {{ $shift->shift_date->format('d/m/Y') }}
        &mdash;
        الموظف: {{ $shift->user?->name }}
        @if($shift->is_closed)
        &mdash; <strong style="color:#16a34a;">مغلقة</strong>
        @else
        &mdash; <strong style="color:#d97706;">مفتوحة</strong>
        @endif
    </div>
</div>

{{-- Meta Box --}}
<div class="meta-box">
    <table>
        <tr>
            <td class="val ltr">{{ $ampm($shift->started_at) }}</td>
            <td class="lbl">وقت الفتح</td>
            <td class="val ltr">{{ $ampm($shift->closed_at) }}</td>
            <td class="lbl">وقت الإقفال</td>
            <td class="val center">{{ $payments->count() }}</td>
            <td class="lbl">الإيرادات</td>
            <td class="val center">{{ $withdrawals->count() }}</td>
            <td class="lbl">السحبيات</td>
        </tr>
    </table>
</div>

{{-- Payments Section --}}
<div class="section-title">الإيرادات المستلمة ({{ $payments->count() }})</div>
@if($payments->isEmpty())
<table class="data" dir="rtl"><tbody>
    <tr><td colspan="10" style="text-align:center;padding:10px;color:#999;">لا توجد مدفوعات في هذه الوردية</td></tr>
</tbody></table>
@else
<table class="data" dir="rtl">
    <thead>
        <tr>
            <th>الوقت</th>
            <th>العملة</th>
            <th style="color:#dc2626;">الناقص</th>
            <th>الإجمالي</th>
            <th>المبلغ</th>
            <th>نوع الدفع</th>
            <th>تاريخ الدخول</th>
            <th>النزيل</th>
            <th>الغرفة</th>
            <th>#</th>
        </tr>
    </thead>
    <tbody>
        @foreach($payments as $i => $p)
        @php
            $checkIn   = $p->reservation?->check_in_date;
            $total     = (float)($p->reservation?->total_amount ?? 0);
            $paid      = (float)($p->reservation?->paid_amount  ?? 0);
            $remaining = $total - $paid;
        @endphp
        <tr>
            <td class="ltr">{{ $ampm($p->payment_date ?? $p->created_at) }}</td>
            <td class="center">{{ $curLabels[$p->currency] ?? $p->currency }}</td>
            <td class="ltr" style="font-weight:bold; color:{{ $remaining > 0 ? '#dc2626' : '#16a34a' }};">
                {{ $remaining > 0 ? number_format($remaining, 0) : 'مسدد' }}
            </td>
            <td class="ltr" style="color:#374151;">{{ $total > 0 ? number_format($total, 0) : '—' }}</td>
            <td class="ltr pos" style="font-weight:bold;">{{ number_format($p->amount, 0) }}</td>
            <td>{{ $typeLabels[$p->type] ?? 'دفعة' }}</td>
            <td class="ltr center">{{ $checkIn?->format('d/m/Y') ?? '—' }}</td>
            <td>{{ $p->reservation?->guest?->full_name ?? '—' }}</td>
            <td class="center" style="font-weight:bold;">{{ $p->reservation?->display_room_number ?? '—' }}</td>
            <td class="center">{{ $i + 1 }}</td>
        </tr>
        @endforeach
        @foreach(['YER','SAR','USD'] as $c)
        @php $cTotal = $payments->where('currency', $c)->sum(fn($p) => (float)$p->amount); @endphp
        @if($cTotal > 0)
        <tr class="total-row">
            <td></td>
            <td class="center">{{ $curLabels[$c] }}</td>
            <td></td>
            <td></td>
            <td class="ltr pos">{{ number_format($cTotal, 0) }}</td>
            <td colspan="5" style="text-align:right;">المجموع ({{ $curLabels[$c] }})</td>
        </tr>
        @endif
        @endforeach
    </tbody>
</table>
@endif

{{-- Withdrawals Section --}}
<div class="section-title">السحبيات ({{ $withdrawals->count() }})</div>
@if($withdrawals->isEmpty())
<table class="data" dir="rtl"><tbody>
    <tr><td colspan="9" style="text-align:center;padding:10px;color:#999;">لا توجد سحبيات في هذه الوردية</td></tr>
</tbody></table>
@else
<table class="data" dir="rtl">
    <thead>
        <tr>
            <th>الوقت</th>
            <th>سلّمه</th>
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
            <td class="ltr">{{ $ampm($w->withdrawal_date ?? $w->created_at) }}</td>
            <td>{{ ($w->handed_by_name && $w->handed_by_name !== '-') ? $w->handed_by_name : '—' }}</td>
            <td class="ltr">
                @if($w->isExchange() && $w->exchange_to_amount)
                    {{ number_format($w->exchange_to_amount, 0) }} {{ $curLabels[$w->exchange_to_currency] ?? '' }}
                @else
                    —
                @endif
            </td>
            <td class="center">{{ $curLabels[$w->currency] ?? $w->currency }}</td>
            <td class="ltr neg" style="font-weight:bold;">{{ number_format($w->amount, 0) }}</td>
            <td class="center">{{ $w->type_label }}</td>
            <td>{{ $w->notes ?: '—' }}</td>
            <td style="font-weight:bold;">{{ $w->withdrawn_by_name ?: '—' }}</td>
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
    <div class="summary-title">ملخص الوردية</div>
    <table style="width:100%;border-collapse:collapse;font-size:9.5px;direction:rtl;" dir="rtl">
        <thead>
            <tr style="background:#e8f0f7;">
                <th style="padding:5px 8px;border:1px solid #cdd8e3;text-align:right;">الصافي المتبقي</th>
                <th style="padding:5px 8px;border:1px solid #cdd8e3;text-align:right;">السحبيات</th>
                <th style="padding:5px 8px;border:1px solid #cdd8e3;text-align:right;">الإيرادات</th>
                <th style="padding:5px 8px;border:1px solid #cdd8e3;text-align:right;">العملة</th>
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
                <td style="padding:5px 8px;border:1px solid #ddd;direction:ltr;text-align:left;font-weight:bold;" class="net-blue">
                    {{ number_format($row['net'], 0) }}
                </td>
                <td style="padding:5px 8px;border:1px solid #ddd;direction:ltr;text-align:left;" class="neg">
                    {{ number_format($row['wdr'], 0) }}
                </td>
                <td style="padding:5px 8px;border:1px solid #ddd;direction:ltr;text-align:left;" class="pos">
                    {{ number_format($row['recv'], 0) }}
                </td>
                <td style="padding:5px 8px;border:1px solid #ddd;text-align:right;font-weight:bold;">
                    {{ $curLabels[$row['cur']] }}
                </td>
            </tr>
            @empty
            <tr><td colspan="4" style="text-align:center;padding:8px;color:#999;">لا توجد بيانات</td></tr>
            @endforelse
        </tbody>
    </table>

    @if($shift->notes)
    <div style="margin-top:8px;font-size:9px;color:#555;text-align:right;padding-top:5px;border-top:1px solid #e2e8f0;">
        <strong>ملاحظات:</strong> {{ $shift->notes }}
    </div>
    @endif
</div>

{{-- Settlement / Deficit Section --}}
@if($shift->is_closed && $shift->actual_amount !== null)
<div class="deficit-box" style="{{ $hasShortfall ? '' : 'border-color:#0F4C75;background:#f0f5ff;' }}">
    <div class="deficit-title" style="{{ $hasShortfall ? '' : 'color:#0F4C75;border-color:#93c5fd;' }}">
        {{ $hasShortfall ? 'عجز الوردية' : 'تسوية الوردية' }}
    </div>
    <table style="width:100%;border-collapse:collapse;font-size:9.5px;direction:rtl;" dir="rtl">
        {{-- Row 1: Computed vs Actual --}}
        <tr>
            <td style="padding:5px 8px;border:1px solid #cdd8e3;background:#f8fafc;font-size:8.5px;color:#555;">المبلغ المحسوب (الصافي)</td>
            <td style="padding:5px 8px;border:1px solid #cdd8e3;font-weight:bold;direction:ltr;text-align:left;" class="net-blue">
                {{ number_format($netYer, 0) }} ر.ي
            </td>
            <td style="padding:5px 8px;border:1px solid #cdd8e3;background:#f8fafc;font-size:8.5px;color:#555;">المبلغ الفعلي عند الإقفال</td>
            <td style="padding:5px 8px;border:1px solid #cdd8e3;font-weight:bold;direction:ltr;text-align:left;" class="{{ $hasShortfall ? 'neg' : 'pos' }}">
                {{ number_format($shift->actual_amount, 0) }} ر.ي
            </td>
        </tr>
        {{-- Row 2: Deficit amount (if any) --}}
        @if($hasShortfall)
        <tr style="background:#fff0f0;">
            <td style="padding:5px 8px;border:1px solid #fca5a5;background:#fef2f2;font-size:8.5px;font-weight:bold;" class="neg">العجز (الفرق المخصوم)</td>
            <td style="padding:5px 8px;border:1px solid #fca5a5;font-weight:bold;font-size:12px;direction:ltr;text-align:left;" class="neg">
                {{ number_format($deficitAmount, 0) }} ر.ي
            </td>
            <td style="padding:5px 8px;border:1px solid #fca5a5;background:#fef2f2;font-size:8.5px;color:#555;">خصم من الراتب</td>
            <td style="padding:5px 8px;border:1px solid #fca5a5;font-size:8.5px;">
                @if($shift->salary_deducted_at)
                    <span class="pos" style="font-weight:bold;">تم الخصم</span>
                    — {{ $shift->salary_deducted_at->format('d/m/Y') }}
                @else
                    <span style="color:#d97706;">لم يُخصم بعد</span>
                @endif
            </td>
        </tr>
        @endif
        {{-- Row 3: Reason/Notes (if any) --}}
        @if($shift->notes)
        <tr>
            <td style="padding:5px 8px;border:1px solid #cdd8e3;background:#f8fafc;font-size:8.5px;color:#555;white-space:nowrap;">سبب / ملاحظات الإقفال</td>
            <td colspan="3" style="padding:5px 8px;border:1px solid #cdd8e3;font-size:9px;color:#374151;">
                {{ $shift->notes }}
            </td>
        </tr>
        @endif
    </table>
</div>
@endif

{{-- Signatures --}}
<table class="sig-table" dir="rtl">
    <tr>
        <td>
            <div class="sig-line">توقيع الموظف — {{ $shift->user?->name }}</div>
        </td>
        <td>
            <div class="sig-line">توقيع المشرف</div>
        </td>
    </tr>
</table>

<div class="footer">
    رقم الوردية: #{{ $shift->id }} — طُبع في: {{ now()->format('d/m/Y') }} {{ $ampm(now()) }}
</div>

</body>
</html>
