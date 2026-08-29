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
        table-layout: fixed;
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
        overflow: hidden;
        text-overflow: ellipsis;
    }
    table.data tbody td.ltr { text-align: left; direction: ltr; }
    table.data tbody td.center { text-align: center; }
    {{-- عمود نص حر (بيان/سبب/ملاحظات) قد يطول جداً — يُسمَح له بالالتفاف ضمن عرضه
         بدل دفع الجدول بالكامل خارج حدود الصفحة (table-layout: fixed يُثبّت عرض
         بقية الأعمدة، وهذا العمود وحده يلتف). --}}
    table.data tbody td.wrap {
        white-space: normal;
        overflow-wrap: break-word;
        word-break: break-word;
        overflow: visible;
        text-overflow: clip;
    }
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
    $methodLabels = ['cash' => 'نقداً', 'bank_transfer' => 'تحويل بنكي', 'pos' => 'شبكة (POS)'];

    // دفعات متعددة لنفس الحجز ونفس المستلم والعملة وطريقة الدفع (مثل دفعة جزئية
    // ثم باقي المبلغ لاحقاً في نفس الوردية) تُجمّع في صف واحد بمجموع المبلغ.
    $payments = $shift->groupedPayments() ?? collect();
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
    @include('partials.pdf-hotel-header')
    <h1>تقرير الوردية</h1>
    <div class="sub">
        {{-- تاريخ العنوان: يوم إنشاء (فتح) الوردية دائماً — هو "اليوم المحاسبي"
             الذي تخصّه أرقام هذا التقرير، بصرف النظر عن وقت إقفالها الفعلي أو
             تاريخ تصدير الـ PDF. --}}
        {{ $shift->shift_date->format('d/m/Y') }}
        <span style="margin:0 6px;">|</span>
        الموظف: {{ $shift->user?->name }}
    </div>
</div>

{{-- Meta --}}
<table class="meta-table" dir="rtl">
    {{-- dompdf يرتّب الأعمدة يسار→يمين بترتيب المصدر، فنعكسها ليُقرأ من اليمين --}}
    <tr>
        <td class="val">{{ $withdrawals->count() }}</td>
        <td class="lbl">عدد السحبيات:</td>
        <td class="val">{{ $payments->count() }}</td>
        <td class="lbl">عدد الإيرادات:</td>
        <td class="val ltr">{{ $shift->closed_at?->format('H:i d/m/Y') ?? '—' }}</td>
        <td class="lbl">وقت الإقفال:</td>
        <td class="val ltr">{{ $shift->started_at?->format('H:i') }}</td>
        <td class="lbl">وقت الفتح:</td>
    </tr>
</table>

{{-- Payments section --}}
<h2>الإيرادات المستلمة</h2>
@if($payments->isEmpty())
<table class="data" dir="rtl"><tbody><tr><td style="text-align:center;padding:8px;color:#999;">لا توجد مدفوعات</td></tr></tbody></table>
@else
<table class="data" dir="rtl">
    {{-- أعمدة معكوسة لتُقرأ من اليمين لليسار (dompdf يرتّبها بترتيب المصدر) --}}
    <thead>
        <tr>
            <th style="width:8%;">الوقت</th>
            <th style="width:7%;">العملة</th>
            <th style="width:11%;">المبلغ</th>
            <th style="width:16%;">طريقة الدفع</th>
            <th style="width:19%;">النزيل</th>
            <th style="width:9%;">الغرفة</th>
            <th style="width:25%;">ملاحظات</th>
            <th style="width:5%;">#</th>
        </tr>
    </thead>
    <tbody>
        @foreach($payments as $i => $p)
        <tr>
            <td class="ltr">{{ $p->created_at?->format('H:i') }}</td>
            <td class="center">{{ $curLabels[$p->currency] ?? $p->currency }}</td>
            <td class="ltr" style="font-weight:bold;">{{ number_format($p->amount, 0) }}</td>
            <td class="wrap">
                {{ $methodLabels[$p->method] ?? $p->method }}
                @if($p->method === 'bank_transfer' && $p->bank_transfer_ref)
                <br><span style="font-size:7.5px;color:#666;">سند: {{ $p->bank_transfer_ref }}</span>
                @endif
            </td>
            <td>{{ $p->reservation?->guest?->full_name }}</td>
            <td class="center">{{ $p->reservation?->display_room_number ?? '—' }}</td>
            <td class="wrap" style="font-size:8px;color:#555;">{{ $p->notes ?: '—' }}</td>
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
            <td colspan="5" style="text-align:right;">المجموع ({{ $curLabels[$c] }})</td>
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
    {{-- أعمدة معكوسة لتُقرأ من اليمين لليسار --}}
    <thead>
        <tr>
            <th style="width:7%;">الوقت</th>
            <th style="width:11%;">بواسطة</th>
            <th style="width:9%;">مقابل</th>
            <th style="width:6%;">العملة</th>
            <th style="width:9%;">المبلغ</th>
            <th style="width:8%;">النوع</th>
            <th style="width:9%;">المصدر</th>
            <th style="width:19%;">البيان / السبب</th>
            <th style="width:14%;">المستلم</th>
            <th style="width:4%;">#</th>
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
            <td class="center" style="{{ $w->funding_source === 'general_safe' ? 'font-weight:bold;color:#b45309;' : '' }}">{{ $w->funding_source_label }}</td>
            <td class="wrap">{{ $w->notes }}</td>
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
            <td colspan="5" style="text-align:right;">مجموع السحبيات ({{ $curLabels[$c] }})</td>
        </tr>
        @endif
        @endforeach
    </tbody>
</table>
@endif

{{-- Withdrawals from the general safe during this shift — informational only,
     not counted in the shift's own cash reconciliation (not from its drawer). --}}
@php $generalSafeWithdrawals = $generalSafeWithdrawals ?? collect(); @endphp
@if($generalSafeWithdrawals->isNotEmpty())
<h2>مصروفات من الصندوق العام خلال هذه الوردية <span style="font-size:8px;color:#999;font-weight:normal;">(لا تُحتسب على درج هذه الوردية)</span></h2>
<table class="data" dir="rtl">
    <thead>
        <tr>
            <th style="width:12%;">الوقت</th>
            <th style="width:12%;">العملة</th>
            <th style="width:15%;">المبلغ</th>
            <th style="width:37%;">البيان / السبب</th>
            <th style="width:24%;">المستلم</th>
        </tr>
    </thead>
    <tbody>
        @foreach($generalSafeWithdrawals as $gw)
        <tr>
            <td class="ltr">{{ $gw->created_at?->format('H:i') }}</td>
            <td class="center">{{ $curLabels[$gw->currency] ?? $gw->currency }}</td>
            <td class="ltr" style="font-weight:bold;color:#b45309;">{{ number_format($gw->amount, 0) }}</td>
            <td class="wrap">{{ $gw->notes }}</td>
            <td>{{ $gw->withdrawn_by_name }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

@php $refunds = $shift->refunds ?? collect(); @endphp
@if($refunds->isNotEmpty())
{{-- Refunds section --}}
<h2>الاسترجاعات</h2>
<table class="data" dir="rtl">
    {{-- أعمدة معكوسة لتُقرأ من اليمين لليسار --}}
    <thead>
        <tr>
            <th style="width:9%;">الوقت</th>
            <th style="width:12%;">الطريقة</th>
            <th style="width:9%;">العملة</th>
            <th style="width:13%;">المبلغ</th>
            <th style="width:32%;">السبب</th>
            <th style="width:20%;">النزيل</th>
            <th style="width:5%;">#</th>
        </tr>
    </thead>
    <tbody>
        @foreach($refunds as $i => $rf)
        <tr>
            <td class="ltr">{{ $rf->refunded_at?->format('H:i') }}</td>
            <td class="center">{{ match($rf->method) { 'cash'=>'نقداً','pos'=>'POS','bank_transfer'=>'تحويل', default=>$rf->method } }}</td>
            <td class="center">{{ $curLabels[$rf->currency] ?? $rf->currency }}</td>
            <td class="ltr" style="font-weight:bold;color:#dc2626;">{{ number_format($rf->amount, 0) }}</td>
            <td class="wrap">{{ $rf->reason }}</td>
            <td>{{ $rf->reservation?->guest?->full_name ?? '—' }}</td>
            <td class="center">{{ $i + 1 }}</td>
        </tr>
        @endforeach
        @foreach(['YER','SAR','USD'] as $c)
        @php $rTotal = $refunds->where('currency', $c)->sum(fn($rf) => (float)$rf->amount); @endphp
        @if($rTotal > 0)
        <tr class="total-row">
            <td colspan="2"></td>
            <td class="center">{{ $curLabels[$c] }}</td>
            <td class="ltr neg">{{ number_format($rTotal, 0) }}</td>
            <td colspan="3" style="text-align:right;">مجموع الاسترجاعات ({{ $curLabels[$c] }})</td>
        </tr>
        @endif
        @endforeach
    </tbody>
</table>
@endif

{{-- النزلاء المسجّلين للدخول (يشمل المؤجَّل/غير المدفوع مع مديونيته) --}}
@php
    $checkedInGuests = $checkedInGuests ?? collect();
    $payStatusLabels = ['unpaid' => 'غير مدفوع', 'partial' => 'جزئي', 'paid' => 'مدفوع', 'deferred' => 'مؤجل'];
    $totalDebt = $checkedInGuests->sum(fn($r) => max(0, (float) $r->balance));
@endphp
@if($checkedInGuests->isNotEmpty())
<h2>النزلاء المسجّلين للدخول</h2>
<table class="data" dir="rtl">
    {{-- أعمدة معكوسة لتُقرأ من اليمين لليسار --}}
    <thead>
        <tr>
            <th style="width:9%;">الدخول</th>
            <th style="width:13%;">المتبقّي (مديونية)</th>
            <th style="width:12%;">حالة الدفع</th>
            <th style="width:13%;">تاريخ الدخول</th>
            <th style="width:11%;">الغرفة</th>
            <th style="width:37%;">النزيل</th>
            <th style="width:5%;">#</th>
        </tr>
    </thead>
    <tbody>
        @foreach($checkedInGuests as $i => $res)
        @php $bal = max(0, (float) $res->balance); @endphp
        <tr>
            <td class="ltr">{{ $res->check_in_time ?? '—' }}</td>
            <td class="ltr" style="font-weight:bold;color:{{ $bal > 0 ? '#dc2626' : '#16a34a' }};">
                {{ $bal > 0 ? number_format($bal, 0) : '—' }}
            </td>
            <td class="center">{{ $payStatusLabels[$res->payment_status] ?? $res->payment_status }}</td>
            <td>{{ $res->check_in_date?->format('d/m/Y') ?? '—' }}</td>
            <td class="center">{{ $res->display_room_number }}</td>
            <td>{{ $res->guest?->full_name ?? '—' }}</td>
            <td class="center">{{ $i + 1 }}</td>
        </tr>
        @endforeach
        @if($totalDebt > 0)
        <tr class="total-row">
            <td></td>
            <td class="ltr neg">{{ number_format($totalDebt, 0) }}</td>
            <td colspan="5" style="text-align:right;">إجمالي مديونية نزلاء هذه الوردية</td>
        </tr>
        @endif
    </tbody>
</table>
@endif

{{-- Summary --}}
<div class="summary-box">
    <h3>ملخص الوردية</h3>
    @php
        $summaryRows = [];
        foreach (['YER','SAR','USD'] as $c) {
            $recv = (float)($shift->{'total_received_'    . strtolower($c)} ?? 0);
            $wdr  = (float)($shift->{'total_withdrawals_' . strtolower($c)} ?? 0);
            $rfd  = (float)($shift->{'total_refunds_'     . strtolower($c)} ?? 0);
            if ($recv > 0 || $wdr > 0 || $rfd > 0) {
                $summaryRows[] = ['cur' => $c, 'recv' => $recv, 'wdr' => $wdr, 'rfd' => $rfd, 'net' => $recv - $wdr - $rfd];
            }
        }
    @endphp
    <table style="width:100%;border-collapse:collapse;font-size:9.5px;" dir="rtl">
        {{-- أعمدة معكوسة لتُقرأ من اليمين لليسار --}}
        <thead>
            <tr style="background:#e8f0f7;">
                <th style="padding:4px 8px;border:1px solid #cdd8e3;text-align:right;width:20%;">الصافي المتبقي</th>
                <th style="padding:4px 8px;border:1px solid #cdd8e3;text-align:right;width:20%;">الاسترجاعات</th>
                <th style="padding:4px 8px;border:1px solid #cdd8e3;text-align:right;width:22%;">السحبيات</th>
                <th style="padding:4px 8px;border:1px solid #cdd8e3;text-align:right;width:22%;">الإيرادات</th>
                <th style="padding:4px 8px;border:1px solid #cdd8e3;text-align:right;width:16%;">العملة</th>
            </tr>
        </thead>
        <tbody>
            @forelse($summaryRows as $row)
            <tr>
                <td style="padding:4px 8px;border:1px solid #ddd;text-align:right;font-weight:bold;" class="net">{{ number_format($row['net'], 0) }}</td>
                <td style="padding:4px 8px;border:1px solid #ddd;text-align:right;" class="neg">{{ number_format($row['rfd'], 0) }}</td>
                <td style="padding:4px 8px;border:1px solid #ddd;text-align:right;" class="neg">{{ number_format($row['wdr'], 0) }}</td>
                <td style="padding:4px 8px;border:1px solid #ddd;text-align:right;" class="pos">{{ number_format($row['recv'], 0) }}</td>
                <td style="padding:4px 8px;border:1px solid #ddd;text-align:right;font-weight:bold;">{{ $curLabels[$row['cur']] }}</td>
            </tr>
            @empty
            <tr><td colspan="5" style="text-align:center;padding:8px;color:#999;">لا توجد بيانات</td></tr>
            @endforelse
        </tbody>
    </table>

    @if($shift->notes)
    <div style="margin-top:8px;font-size:9px;color:#555;text-align:right;padding:4px 0;">
        <strong>ملاحظات الإقفال:</strong> {{ $shift->notes }}
    </div>
    @endif
</div>

{{-- عجز الوردية --}}
@if($shift->actual_amount !== null)
@php
    $sysNet   = $shift->net_balance_yer;
    $actual   = (float) $shift->actual_amount;
    $deficit  = $shift->shortfall; // actual - sysNet
@endphp
<div style="border:2px solid #dc2626;border-radius:6px;padding:12px;margin-top:10px;">
    <div style="font-size:11px;font-weight:bold;color:#dc2626;text-align:right;margin-bottom:8px;border-bottom:1px solid #fca5a5;padding-bottom:4px;">
        عجز الوردية
    </div>
    <table style="width:100%;border-collapse:collapse;font-size:9.5px;" dir="rtl">
        {{-- أعمدة معكوسة لتُقرأ من اليمين لليسار --}}
        <thead>
            <tr style="background:#fef2f2;">
                <th style="padding:5px 8px;border:1px solid #fca5a5;text-align:right;width:33%;">الفرق (ر.ي)</th>
                <th style="padding:5px 8px;border:1px solid #fca5a5;text-align:right;width:33%;">المبلغ الفعلي في الصندوق (ر.ي)</th>
                <th style="padding:5px 8px;border:1px solid #fca5a5;text-align:right;width:34%;">الصافي حسب النظام (ر.ي)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="padding:6px 8px;border:1px solid #fca5a5;text-align:right;font-weight:bold;font-size:11px;">
                    @if($deficit === null)
                        <span style="color:#6b7280;">—</span>
                    @elseif($deficit == 0)
                        <span style="color:#16a34a;">✓ مطابق</span>
                    @elseif($deficit < 0)
                        <span style="color:#dc2626;">▼ {{ number_format(abs($deficit), 0) }} (عجز)</span>
                    @else
                        <span style="color:#d97706;">▲ {{ number_format($deficit, 0) }} (زيادة)</span>
                    @endif
                </td>
                <td style="padding:6px 8px;border:1px solid #fca5a5;text-align:right;font-weight:bold;font-size:11px;color:#374151;">
                    {{ number_format($actual, 0) }}
                </td>
                <td style="padding:6px 8px;border:1px solid #fca5a5;text-align:right;font-weight:bold;font-size:11px;color:#1d4ed8;">
                    {{ number_format($sysNet, 0) }}
                </td>
            </tr>
        </tbody>
    </table>
</div>
@endif

{{-- سجل أحداث الإقفال --}}
@if(!empty($shift->close_events))
@php $closeEvents = $shift->close_events; @endphp
<div style="border:1px solid #cdd8e3;border-radius:6px;padding:10px;margin-top:10px;">
    <div style="font-size:10px;font-weight:bold;color:#0F4C75;text-align:right;margin-bottom:7px;border-bottom:1px solid #e2e8f0;padding-bottom:4px;">
        سجل الإقفال وإعادة الفتح
    </div>
    <table style="width:100%;border-collapse:collapse;font-size:8.5px;" dir="rtl">
        <thead>
            <tr style="background:#e8f0f7;">
                <th style="padding:3px 6px;border:1px solid #cdd8e3;text-align:right;width:18%;">الحدث</th>
                <th style="padding:3px 6px;border:1px solid #cdd8e3;text-align:right;width:22%;">بواسطة</th>
                <th style="padding:3px 6px;border:1px solid #cdd8e3;text-align:right;width:20%;">التاريخ والوقت</th>
                <th style="padding:3px 6px;border:1px solid #cdd8e3;text-align:right;width:15%;">المبلغ الفعلي</th>
                <th style="padding:3px 6px;border:1px solid #cdd8e3;text-align:right;width:25%;">الملاحظات</th>
            </tr>
        </thead>
        <tbody>
            @foreach($closeEvents as $evt)
            @php
                $isClose  = ($evt['event'] ?? '') === 'close';
                $isReopen = ($evt['event'] ?? '') === 'reopen';
                $evtTime  = $isClose  ? ($evt['closed_at']   ?? null) : ($evt['reopened_at'] ?? null);
                $evtAmt   = $isClose  ? ($evt['actual_amount'] ?? null) : null;
                $evtSf    = $isClose  ? ($evt['shortfall']    ?? null) : null;
                $evtNotes = $evt['notes'] ?? null;
                $evtBy    = $evt['closed_by_name'] ?? '—';
            @endphp
            <tr style="background:{{ $isReopen ? '#fff8e1' : '#fff' }};">
                <td style="padding:3px 6px;border:1px solid #e0e7ef;text-align:right;">
                    @if($isClose)
                    <span style="color:#0F4C75;font-weight:bold;">إقفال</span>
                    @else
                    <span style="color:#d97706;font-weight:bold;">إعادة فتح</span>
                    @endif
                </td>
                <td style="padding:3px 6px;border:1px solid #e0e7ef;text-align:right;">{{ $evtBy }}</td>
                <td style="padding:3px 6px;border:1px solid #e0e7ef;text-align:right;">
                    {{ $evtTime ? \Carbon\Carbon::parse($evtTime)->format('d/m/Y H:i') : '—' }}
                </td>
                <td style="padding:3px 6px;border:1px solid #e0e7ef;text-align:right;">
                    @if($evtAmt !== null)
                        <span style="font-weight:bold;">{{ number_format($evtAmt, 0) }}</span>
                        @if($evtSf !== null)
                        <br><span style="font-size:7.5px;color:{{ $evtSf < 0 ? '#dc2626' : ($evtSf > 0 ? '#d97706' : '#16a34a') }};">
                            {{ $evtSf == 0 ? 'مطابق' : ($evtSf < 0 ? '▼'.number_format(abs($evtSf),0) : '▲'.number_format($evtSf,0)) }}
                        </span>
                        @endif
                    @else
                        <span style="color:#9ca3af;">—</span>
                    @endif
                </td>
                <td style="padding:3px 6px;border:1px solid #e0e7ef;text-align:right;color:#555;">{{ $evtNotes ?? '—' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

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
     | 
    رقم الوردية: #{{ $shift->id }}
</div>

</body>
</html>
