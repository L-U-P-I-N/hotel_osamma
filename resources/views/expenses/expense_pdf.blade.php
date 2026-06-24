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
    body { font-family: 'NotoNaskhArabic', sans-serif; font-size: 8px; direction: rtl; color: #1a1a1a; background: #fff; padding: 10px 12px; }

    .header { text-align: center; border-bottom: 2px solid #0F4C75; padding-bottom: 7px; margin-bottom: 8px; }
    .header h1 { font-size: 14px; color: #0F4C75; font-weight: bold; }
    .header .sub { font-size: 7.5px; color: #555; margin-top: 2px; }

    .stats { display: table; width: auto; margin: 0 auto 8px; border-collapse: collapse; }
    .stats td { border: 1px solid #ddd; padding: 3px 14px; text-align: center; }
    .stats .num   { font-size: 13px; font-weight: bold; color: #0F4C75; }
    .stats .num-r { font-size: 13px; font-weight: bold; color: #dc2626; }
    .stats .lbl   { font-size: 6.5px; color: #666; }

    table.main {
        width: 100%;
        border-collapse: collapse;
        font-size: 7.5px;
        direction: rtl;
        table-layout: fixed;
    }
    table.main thead { display: table-header-group; }
    table.main tbody { display: table-row-group; }
    table.main tfoot { display: table-footer-group; }
    table.main thead tr { background: #0F4C75; color: #fff; }
    table.main thead th {
        padding: 3px 3px;
        font-weight: bold;
        border: 1px solid #0a3a5e;
        text-align: center;
        overflow: hidden;
    }
    table.main tbody tr:nth-child(even) { background: #f4f8fc; }
    table.main tbody tr { page-break-inside: avoid; }
    table.main tbody td {
        padding: 2px 3px;
        border: 1px solid #e0e0e0;
        text-align: right;
        word-wrap: break-word;
        overflow: hidden;
        vertical-align: top;
    }
    table.main tfoot td {
        padding: 4px 5px;
        border: 1.5px solid #ef4444;
        background: #fef2f2;
        font-weight: bold;
        font-size: 8.5px;
        color: #dc2626;
    }
    table.main tbody td.c { text-align: center; }
    table.main tbody td.ltr { text-align: left; direction: ltr; }
    table.main tfoot td.ltr { text-align: left; direction: ltr; }

    .badge { display: inline-block; padding: 1px 5px; border-radius: 9px; font-size: 7px; }
    .badge-cat  { background: #e0e7ff; color: #3730a3; }
    .badge-cash { background: #dcfce7; color: #166534; }
    .badge-bank { background: #dbeafe; color: #1e40af; }
    .badge-later{ background: #f3f4f6; color: #4b5563; }

    .total-box { margin-top: 8px; direction: rtl; }
    .total-box table { border-collapse: collapse; float: left; }
    .total-box td { padding: 5px 16px; border: 1px solid #ddd; font-size: 8.5px; }
    .total-box .lbl { background: #f8fafc; color: #374151; text-align: right; }
    .total-box .val { background: #fff; text-align: center; font-weight: bold; }
    .total-box .lbl-r { background: #fef2f2; color: #991b1b; font-weight: bold; border-color: #ef4444; text-align: right; }
    .total-box .val-r { background: #fef2f2; color: #dc2626; font-size: 13px; font-weight: bold; border-color: #ef4444; text-align: center; }
    .clearfix::after { content: ''; display: table; clear: both; }

    .footer { margin-top: 8px; border-top: 1px solid #eee; padding-top: 3px; font-size: 6.5px; color: #aaa; text-align: right; clear: both; }
</style>
</head>
<body>

@php
    $catLabels = [
        'maintenance' => 'صيانة',
        'electricity' => 'كهرباء/مياه',
        'salary'      => 'رواتب',
        'cleaning'    => 'نظافة',
        'food'        => 'طعام وشراب',
        'other'       => 'أخرى',
    ];
    $pmLabels = ['cash' => 'نقداً', 'bank_transfer' => 'تحويل بنكي', 'later' => 'لاحقاً'];
    $grandTotal = $expenses->sum('amount');
    $count      = $expenses->count();
@endphp

<div class="header">
    <h1>تقرير المصروفات</h1>
    @if($dateFrom || $dateTo)
    <div class="sub">
        الفترة:
        {{ $dateFrom ? \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') : '—' }}
        —
        {{ $dateTo ? \Carbon\Carbon::parse($dateTo)->format('d/m/Y') : '—' }}
    </div>
    @endif
</div>

<table class="stats" dir="rtl">
    <tr>
        <td><div class="num">{{ $count }}</div><div class="lbl">عدد المصروفات</div></td>
        <td><div class="num-r">{{ number_format($grandTotal, 0) }}</div><div class="lbl">الإجمالي (ر.ي)</div></td>
    </tr>
</table>

@if($expenses->isEmpty())
<p style="text-align:center;color:#999;padding:20px;">لا توجد مصروفات في هذه الفترة</p>
@else
<table class="main" dir="rtl">
    <colgroup>
        <col style="width:4%">   {{-- # --}}
        <col style="width:10%">  {{-- التاريخ --}}
        <col style="width:12%">  {{-- الفئة --}}
        <col style="width:12%">  {{-- المبلغ --}}
        <col style="width:14%">  {{-- طريقة الدفع --}}
        <col style="width:18%">  {{-- المستلم --}}
        <col style="width:18%">  {{-- الوصف --}}
        <col style="width:12%">  {{-- سُجِّل بواسطة --}}
    </colgroup>
    <thead>
        <tr>
            <th>#</th>
            <th>التاريخ</th>
            <th>الفئة</th>
            <th>المبلغ (ر.ي)</th>
            <th>طريقة الدفع</th>
            <th>اسم المستلم</th>
            <th>الوصف</th>
            <th>سُجِّل بواسطة</th>
        </tr>
    </thead>
    <tbody>
        @foreach($expenses as $i => $expense)
        @php $pm = $expense->payment_method ?? 'cash'; @endphp
        <tr>
            <td class="c">{{ $i + 1 }}</td>
            <td class="ltr c">{{ $expense->expense_date->format('d/m/Y') }}</td>
            <td class="c"><span class="badge badge-cat">{{ $catLabels[$expense->category] ?? $expense->category }}</span></td>
            <td class="ltr" style="font-weight:bold;color:#dc2626;">{{ number_format($expense->amount, 0) }}</td>
            <td class="c">
                <span class="badge {{ $pm === 'cash' ? 'badge-cash' : ($pm === 'bank_transfer' ? 'badge-bank' : 'badge-later') }}">
                    {{ $pmLabels[$pm] ?? $pm }}
                </span>
            </td>
            <td>{{ $expense->recipient_name ?? '—' }}</td>
            <td>{{ $expense->description ?? '—' }}</td>
            <td>{{ $expense->paidBy?->name ?? '—' }}</td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td colspan="3" style="text-align:right;">الإجمالي الكلي ({{ $count }} مصروف)</td>
            <td class="ltr" style="font-size:10px;">{{ number_format($grandTotal, 0) }}</td>
            <td colspan="4" style="background:#fef2f2;border-color:#ef4444;"></td>
        </tr>
    </tfoot>
</table>

{{-- صندوق الإجمالي المنسق بعد الجدول --}}
<div class="total-box clearfix">
    <table>
        <tr>
            <td class="lbl">عدد المصروفات</td>
            <td class="val" style="color:#0F4C75;font-size:11px;">{{ $count }}</td>
        </tr>
        <tr>
            <td class="lbl-r">الإجمالي الكلي للمصروفات</td>
            <td class="val-r">{{ number_format($grandTotal, 0) }} <span style="font-size:9px;">ر.ي</span></td>
        </tr>
    </table>
</div>
@endif

<script type="text/php">
    if (isset($pdf)) {
        $font  = $fontMetrics->getFont('helvetica');
        $total = $pdf->get_page_count();
        for ($p = 1; $p <= $total; $p++) {
            $pdf->page_text(
                $pdf->get_width() - 55,
                $pdf->get_height() - 12,
                "صفحة {$p} / {$total}",
                $font, 6.5, [0.6, 0.6, 0.6]
            );
        }
    }
</script>

<div class="footer">طُبع في: {{ now()->format('d/m/Y H:i') }}</div>

</body>
</html>
