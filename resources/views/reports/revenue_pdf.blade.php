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
    table.data { width: 100%; border-collapse: collapse; font-size: 9px; margin-bottom: 14px; direction: rtl; }
    table.data thead tr { background: #0F4C75; color: #fff; }
    table.data thead th { padding: 5px 6px; font-weight: bold; border: 1px solid #0a3a5e; text-align: right; white-space: nowrap; }
    table.data tbody tr:nth-child(even) { background: #f4f8fc; }
    table.data tbody td { padding: 4px 6px; border: 1px solid #e0e0e0; text-align: right; white-space: nowrap; }
    .stat-box { display: inline-block; border: 1px solid #ddd; padding: 6px 16px; text-align: center; margin-left: 4px; margin-bottom: 14px; }
    .stat-box .num { font-size: 18px; font-weight: bold; color: #0F4C75; }
    .stat-box .lbl { font-size: 8px; color: #666; }
    .footer { margin-top: 12px; border-top: 1px solid #eee; padding-top: 6px; font-size: 8px; color: #aaa; text-align: right; }
    h2 { font-size: 11px; font-weight: bold; color: #fff; background: #0F4C75; padding: 5px 10px; margin-bottom: 0; text-align: right; }
    .note-box { border: 1px solid #fbbf24; background: #fffbeb; padding: 8px 12px; margin-bottom: 14px; font-size: 9px; color: #92400e; }
</style>
</head>
<body>
<div class="header">
    <h1>تقرير الإيرادات</h1>
    <div class="sub">الفترة: {{ \Carbon\Carbon::parse($from)->format('d/m/Y') }} — {{ \Carbon\Carbon::parse($to)->format('d/m/Y') }}</div>
</div>

<div class="stat-box">
    <div class="num">{{ number_format($totalRevenue, 0) }}</div>
    <div class="lbl">إجمالي الإيرادات (ر.ي)</div>
</div>

<br style="clear:both;">

<h2>الإيرادات حسب نوع الغرفة</h2>
<table class="data">
    <thead>
        <tr>
            <th>نوع الغرفة</th>
            <th>الإيرادات ر.ي</th>
        </tr>
    </thead>
    <tbody>
        @forelse($revenueByType as $r)
        <tr>
            <td>{{ $r->name }}</td>
            <td>{{ number_format($r->total, 0) }}</td>
        </tr>
        @empty
        <tr><td colspan="2" style="text-align:center; padding:10px; color:#999;">لا توجد بيانات</td></tr>
        @endforelse
    </tbody>
</table>

@php $methodLabels = ['cash'=>'نقدي','pos'=>'POS','bank_transfer'=>'تحويل بنكي']; @endphp
<h2>الإيرادات حسب طريقة الدفع</h2>
<table class="data">
    <thead>
        <tr>
            <th>طريقة الدفع</th>
            <th>المبلغ ر.ي</th>
        </tr>
    </thead>
    <tbody>
        @forelse($revenueByMethod as $m)
        <tr>
            <td>{{ $methodLabels[$m->method] ?? $m->method }}</td>
            <td>{{ number_format($m->total, 0) }}</td>
        </tr>
        @empty
        <tr><td colspan="2" style="text-align:center; padding:10px; color:#999;">لا توجد بيانات</td></tr>
        @endforelse
    </tbody>
</table>

@if($foreignPayments->isNotEmpty())
<div class="note-box">
    <strong>ملاحظة: مدفوعات بعملة أجنبية (غير مشمولة في الإجمالي)</strong><br>
    @foreach($foreignPayments as $fp)
    @php $sym = $fp->currency === 'SAR' ? 'ر.س' : '$'; @endphp
    — {{ $fp->count }} دفعة بـ {{ number_format($fp->total, 2) }} {{ $sym }} ({{ $fp->currency === 'SAR' ? 'ريال سعودي' : 'دولار أمريكي' }})<br>
    @endforeach
</div>
@endif

<div class="footer">
    طُبع بتاريخ: {{ now()->format('d/m/Y H:i') }}
</div>
</body>
</html>
