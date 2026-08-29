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
    .title { text-align: center; font-size: 15px; font-weight: bold; color: #0F4C75; margin-bottom: 6px; }
    .subtitle { text-align: center; font-size: 11px; color: #6b7280; margin-bottom: 18px; }
    .section-title { background: #0F4C75; color: #fff; padding: 6px 10px; font-size: 12px; font-weight: bold; margin-bottom: 0; }
    table.data { width: 100%; border-collapse: collapse; margin-bottom: 18px; font-size: 11px; }
    table.data th { background: #e8f0f7; color: #0F4C75; padding: 6px 8px; text-align: right; }
    table.data td { padding: 6px 8px; border-bottom: 1px solid #f3f4f6; }
    .empty-row td { text-align: center; color: #9ca3af; padding: 10px; }
    .footer { text-align: center; font-size: 10px; color: #9ca3af; margin-top: 18px; padding-top: 10px; border-top: 1px solid #e5e7eb; }
</style>
</head>
<body>

<div class="header">
    @include('partials.pdf-hotel-header')
    <h1>الفندق السعودي</h1>
    <p>نظام إدارة الفندق</p>
</div>

<div class="title">كشف حساب الموظف — {{ $user->name }}</div>
<div class="subtitle">الفترة من {{ \Carbon\Carbon::parse($from)->format('d/m/Y') }} إلى {{ \Carbon\Carbon::parse($to)->format('d/m/Y') }}</div>

<table style="width:100%; border-collapse:collapse; margin-bottom:18px;">
    <tr>
        <td style="text-align:center; padding:10px; border:1px solid #e5e7eb; width:25%;">
            <div style="font-size:15px; font-weight:bold; color:#1f2937;">{{ $totals['shifts_count'] }}</div>
            <div style="font-size:10px; color:#6b7280;">عدد الورديات</div>
        </td>
        <td style="text-align:center; padding:10px; border:1px solid #e5e7eb; width:25%;">
            <div style="font-size:15px; font-weight:bold; color:#16a34a;">{{ number_format($totals['received'], 0) }}</div>
            <div style="font-size:10px; color:#6b7280;">إجمالي المستلَم (ر.ي)</div>
        </td>
        <td style="text-align:center; padding:10px; border:1px solid #e5e7eb; width:25%;">
            <div style="font-size:15px; font-weight:bold; color:#dc2626;">{{ number_format($totals['withdrawals'], 0) }}</div>
            <div style="font-size:10px; color:#6b7280;">إجمالي السحبيات (ر.ي)</div>
        </td>
        <td style="text-align:center; padding:10px; border:1px solid #e5e7eb; width:25%;">
            <div style="font-size:15px; font-weight:bold; color:{{ $totals['total_shortfall'] > 0 ? '#d97706' : '#6b7280' }};">{{ number_format($totals['total_shortfall'], 0) }}</div>
            <div style="font-size:10px; color:#6b7280;">إجمالي العجز (ر.ي)</div>
        </td>
    </tr>
</table>

<div class="section-title">الورديات ({{ $shifts->count() }})</div>
<table class="data">
    <thead><tr>
        <th>التاريخ</th><th>المستلمات</th><th>السحبيات</th><th>الصافي</th><th>الفعلي</th><th>الفرق</th><th>الحالة</th>
    </tr></thead>
    <tbody>
        @forelse($shifts as $s)
        <tr>
            <td>{{ $s->shift_date->format('d/m/Y') }}</td>
            <td style="color:#16a34a;font-weight:bold;">{{ number_format($s->total_received_yer, 0) }}</td>
            <td style="color:#dc2626;font-weight:bold;">{{ number_format($s->total_withdrawals_yer, 0) }}</td>
            <td style="font-weight:bold;">{{ number_format($s->net_balance_yer, 0) }}</td>
            <td>{{ $s->actual_amount !== null ? number_format($s->actual_amount, 0) : '—' }}</td>
            <td>{{ $s->shortfall !== null ? number_format($s->shortfall, 0) : '—' }}</td>
            <td>{{ $s->is_closed ? 'مقفلة' : 'مفتوحة' }}</td>
        </tr>
        @empty
        <tr class="empty-row"><td colspan="7">لا توجد ورديات في هذه الفترة</td></tr>
        @endforelse
    </tbody>
</table>

<div class="section-title">المستلمات ({{ $payments->count() }})</div>
<table class="data">
    <thead><tr>
        <th>التاريخ</th><th>الغرفة</th><th>النزيل</th><th>طريقة الدفع</th><th>المبلغ</th>
    </tr></thead>
    <tbody>
        @forelse($payments as $p)
        <tr>
            <td>{{ $p->payment_date?->format('d/m/Y H:i') ?? '—' }}</td>
            <td>{{ $p->reservation?->display_room_number ?? '—' }}</td>
            <td>{{ $p->reservation?->guest?->full_name ?? '—' }}</td>
            <td>{{ match($p->method) { 'cash'=>'نقداً', 'bank_transfer'=>'تحويل بنكي', 'pos'=>'POS', default=>$p->method } }}</td>
            <td style="font-weight:bold;color:#16a34a;">{{ number_format($p->amount, 0) }} {{ $p->currency }}</td>
        </tr>
        @empty
        <tr class="empty-row"><td colspan="5">لا توجد مستلمات في هذه الفترة</td></tr>
        @endforelse
    </tbody>
</table>

<div class="section-title">السحبيات ({{ $withdrawals->count() }})</div>
<table class="data">
    <thead><tr>
        <th>التاريخ</th><th>النوع</th><th>استلمه</th><th>المبلغ</th>
    </tr></thead>
    <tbody>
        @forelse($withdrawals as $w)
        <tr>
            <td>{{ $w->withdrawal_date?->format('d/m/Y H:i') ?? '—' }}</td>
            <td>{{ $w->type_label }}</td>
            <td>{{ $w->withdrawn_by_name }}</td>
            <td style="font-weight:bold;color:#dc2626;">{{ number_format($w->amount, 0) }} {{ $w->currency }}</td>
        </tr>
        @empty
        <tr class="empty-row"><td colspan="4">لا توجد سحبيات في هذه الفترة</td></tr>
        @endforelse
    </tbody>
</table>

<div class="footer">صدر في {{ now()->format('d/m/Y H:i') }}</div>

</body>
</html>
