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
    body { font-family: 'NotoNaskhArabic', sans-serif; font-size: 10px; direction: rtl; color: #1a1a1a; background: #fff; padding: 16px; }
    .header { text-align: center; border-bottom: 2px solid #0F4C75; padding-bottom: 10px; margin-bottom: 14px; }
    .header h1 { font-size: 16px; color: #0F4C75; font-weight: bold; }
    .header .sub { font-size: 9px; color: #555; margin-top: 4px; }
    .total-card { border: 2px solid #0F4C75; border-radius: 6px; padding: 12px; margin-bottom: 16px; text-align: center; background: #e8f0f7; }
    .total-card .num { font-size: 22px; font-weight: bold; color: #0F4C75; }
    .total-card .lbl { font-size: 9px; color: #5b90c5; margin-top: 2px; }
    h2 { font-size: 11px; font-weight: bold; color: #fff; background: #0F4C75; padding: 5px 10px; margin-bottom: 0; text-align: right; }
    table.data { width: 100%; border-collapse: collapse; font-size: 9.5px; margin-bottom: 16px; direction: rtl; }
    table.data thead tr { background: #e8f0f7; }
    table.data thead th { padding: 5px 8px; font-weight: bold; border: 1px solid #cdd8e3; text-align: right; }
    table.data tbody tr:nth-child(even) { background: #f9fbfd; }
    table.data tbody td { padding: 5px 8px; border: 1px solid #e0e7ef; text-align: right; }
    table.data tbody td.ltr { text-align: left; direction: ltr; }
    .total-row td { font-weight: bold; background: #e8f0f7 !important; color: #0F4C75; }
    .warning { border: 1px solid #f59e0b; background: #fffbeb; border-radius: 4px; padding: 8px 12px; margin-bottom: 14px; font-size: 9px; color: #92400e; }
    .footer { margin-top: 12px; border-top: 1px solid #eee; padding-top: 6px; font-size: 8px; color: #aaa; text-align: right; }
</style>
</head>
<body>

@php $methodLabels = ['cash' => 'نقدي', 'pos' => 'POS', 'bank_transfer' => 'تحويل بنكي']; @endphp

<div class="header">
    <h1>تقرير الإيرادات</h1>
    <div class="sub">الفترة: {{ \Carbon\Carbon::parse($from)->format('d/m/Y') }} — {{ \Carbon\Carbon::parse($to)->format('d/m/Y') }}</div>
</div>

<div class="total-card">
    <div class="num">{{ number_format($totalRevenue, 2) }} ر.ي</div>
    <div class="lbl">إجمالي الإيرادات (ريال يمني)</div>
</div>

@if($foreignPayments->isNotEmpty())
<div class="warning">
    <strong>ملاحظة:</strong> يوجد مدفوعات بعملة أجنبية في هذه الفترة (غير مشمولة في الإجمالي):
    @foreach($foreignPayments as $fp)
    &nbsp;|&nbsp; {{ $fp->count }} دفعة بـ {{ number_format($fp->total, 2) }} {{ $fp->currency === 'SAR' ? 'ر.س' : '$' }}
    @endforeach
</div>
@endif

<h2>الإيرادات حسب نوع الغرفة</h2>
<table class="data">
    <thead><tr><th>نوع الغرفة</th><th>الإيرادات (ر.ي)</th></tr></thead>
    <tbody>
        @forelse($revenueByType as $row)
        <tr><td>{{ $row->name }}</td><td class="ltr">{{ number_format($row->total, 2) }}</td></tr>
        @empty
        <tr><td colspan="2" style="text-align:center;color:#999;padding:8px;">لا توجد بيانات</td></tr>
        @endforelse
        @if($revenueByType->isNotEmpty())
        <tr class="total-row"><td>الإجمالي</td><td class="ltr">{{ number_format($totalRevenue, 2) }}</td></tr>
        @endif
    </tbody>
</table>

<h2>الإيرادات حسب طريقة الدفع</h2>
<table class="data">
    <thead><tr><th>طريقة الدفع</th><th>المبلغ (ر.ي)</th></tr></thead>
    <tbody>
        @forelse($revenueByMethod as $row)
        <tr><td>{{ $methodLabels[$row->method] ?? $row->method }}</td><td class="ltr">{{ number_format($row->total, 2) }}</td></tr>
        @empty
        <tr><td colspan="2" style="text-align:center;color:#999;padding:8px;">لا توجد بيانات</td></tr>
        @endforelse
        @if($revenueByMethod->isNotEmpty())
        <tr class="total-row"><td>الإجمالي</td><td class="ltr">{{ number_format($totalRevenue, 2) }}</td></tr>
        @endif
    </tbody>
</table>

<div class="footer">طُبع في: {{ now()->format('d/m/Y H:i') }}</div>

</body>
</html>
