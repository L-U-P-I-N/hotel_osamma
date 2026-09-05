<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
@php
    $monthNames = [1=>'يناير',2=>'فبراير',3=>'مارس',4=>'أبريل',5=>'مايو',6=>'يونيو',
                   7=>'يوليو',8=>'أغسطس',9=>'سبتمبر',10=>'أكتوبر',11=>'نوفمبر',12=>'ديسمبر'];
    $leaveTypes = ['annual'=>'سنوية','sick'=>'مرضية','unpaid'=>'بدون راتب','emergency'=>'طارئة'];
@endphp
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
    body { font-family: 'NotoNaskhArabic', sans-serif; font-size: 10px; direction: rtl; color: #1a1a1a; background: #fff; padding: 14px; }
    .header { text-align: center; border-bottom: 2px solid #0F4C75; padding-bottom: 10px; margin-bottom: 12px; }
    .header h1 { font-size: 16px; color: #0F4C75; font-weight: bold; }
    .header .sub { font-size: 9px; color: #555; margin-top: 4px; }

    .info-box { border: 1px solid #e2e8f0; border-radius: 5px; padding: 8px 10px; margin-bottom: 12px; font-size: 10px; }
    .info-box b { color: #0F4C75; }

    /* شبكة بيانات (تسمية/قيمة) — أوضح من سطر واحد بفواصل، وتتفادى &nbsp;
       التي يطبعها dompdf مع الخط العربي كرمز "Â". */
    table.info-grid { width: 100%; border-collapse: collapse; margin-bottom: 12px; font-size: 9.5px; }
    table.info-grid td { border: 1px solid #e2e8f0; padding: 5px 8px; }
    table.info-grid td.lbl { background: #f8fafc; color: #64748b; font-weight: bold; white-space: nowrap; width: 1%; }
    table.info-grid td.val { color: #1e293b; font-weight: bold; }
    table.info-grid td.val.ltr { direction: ltr; text-align: right; }

    .cards { display: table; width: 100%; margin-bottom: 12px; }
    .card { display: table-cell; width: 25%; padding: 5px; }
    .card-inner { border: 1px solid #e0e0e0; border-radius: 5px; padding: 7px 8px; text-align: center; }
    .card-label { font-size: 8px; color: #777; margin-bottom: 3px; }
    .card-value { font-size: 12px; font-weight: bold; }
    .green { color: #16a34a; } .red { color: #dc2626; } .amber { color: #b45309; }

    h2.sec { font-size: 11px; font-weight: bold; color: #0F4C75; border-bottom: 1px solid #d0e4f5; padding-bottom: 4px; margin: 12px 0 6px; }
    table.data { width: 100%; border-collapse: collapse; font-size: 9px; direction: rtl; }
    table.data thead tr { background: #1f2937; color: #fff; }
    table.data thead th { padding: 5px 5px; font-weight: bold; border: 1px solid #111827; text-align: center; }
    table.data tbody tr:nth-child(even) { background: #f4f8fc; }
    table.data tbody td { padding: 4px 5px; border: 1px solid #ccc; text-align: right; word-break: normal; vertical-align: top; }
    table.data tbody td.c { text-align: center; }
    table.data tfoot td { padding: 4px 5px; border: 1px solid #ccc; background: #e8f0f7; font-weight: bold; color: #0F4C75; }
    .empty { text-align: center; color: #999; padding: 10px; font-size: 9px; }
    .footer { margin-top: 12px; border-top: 1px solid #eee; padding-top: 6px; font-size: 8px; color: #aaa; text-align: right; }
</style>
</head>
<body>

<div class="header">
    @include('partials.pdf-hotel-header')
    <h1>كشف حساب موظف</h1>
    <div class="sub">
        الفترة: {{ \Carbon\Carbon::parse($from)->format('Y/m/d') }} — {{ \Carbon\Carbon::parse($to)->format('Y/m/d') }}
    </div>
</div>

{{-- بيانات الموظف كجدول مرتّب (لا سطر واحد بفواصل): أوضح للقراءة، ويتفادى
     &nbsp; التي يطبعها dompdf مع الخط العربي كرمز "Â". --}}
<table class="info-grid" dir="rtl">
    <tr>
        <td class="lbl">الاسم</td>
        <td class="val">{{ $employee->name }}</td>
        <td class="lbl">الوظيفة</td>
        <td class="val">{{ $employee->position ?: '—' }}</td>
    </tr>
    <tr>
        <td class="lbl">الراتب الأساسي</td>
        <td class="val">{{ number_format((float) $employee->base_salary, 0) }} ر.ي</td>
    </tr>
    <tr>
        <td class="lbl">صرفية طعام وشراب</td>
        <td class="val">{{ (float) $employee->food_allowance > 0 ? number_format((float) $employee->food_allowance, 0) . ' ر.ي / شهرياً' : 'لا يوجد' }}</td>
        <td class="lbl">تاريخ التعيين</td>
        <td class="val">{{ $employee->hire_date?->format('Y/m/d') ?: '—' }}</td>
    </tr>
    <tr>
        <td class="lbl">الجوال</td>
        <td class="val ltr">{{ $employee->phone ?: '—' }}</td>
        <td class="lbl">الحالة</td>
        <td class="val">{{ $employee->is_active ? 'نشط' : 'غير نشط' }}</td>
    </tr>
</table>

<div class="cards">
    <div class="card"><div class="card-inner">
        <div class="card-label">صافي الرواتب</div>
        <div class="card-value">{{ number_format($totals['salaries_net'], 0) }}</div>
    </div></div>
    <div class="card"><div class="card-inner">
        <div class="card-label">المدفوع</div>
        <div class="card-value green">{{ number_format($totals['salaries_paid'], 0) }}</div>
    </div></div>
    <div class="card"><div class="card-inner">
        <div class="card-label">غير المدفوع (مستحق له)</div>
        <div class="card-value red">{{ number_format($totals['salaries_due'], 0) }}</div>
    </div></div>
    <div class="card"><div class="card-inner">
        <div class="card-label">السلف والمسحوبات</div>
        <div class="card-value amber">{{ number_format($totals['advances'], 0) }}</div>
    </div></div>
</div>

{{-- dompdf لا يعكس ترتيب أعمدة الجدول بحسب dir="rtl" — نكتبها بترتيب معكوس
     ليظهر أول عمود منطقياً في أقصى اليمين. --}}
<h2 class="sec">الرواتب</h2>
@if($salaries->isEmpty())
<p class="empty">لا توجد رواتب مسجَّلة خلال هذه الفترة</p>
@else
<table class="data" dir="rtl">
    <thead>
        <tr>
            <th style="width:11%;">الحالة</th>
            <th style="width:13%;">الصافي</th>
            <th style="width:12%;">خصم غياب</th>
            <th style="width:12%;">خصم سلف</th>
            <th style="width:12%;">خصومات</th>
            <th style="width:11%;">حوافز</th>
            <th style="width:13%;">الأساسي</th>
            <th style="width:16%;">الشهر</th>
        </tr>
    </thead>
    <tbody>
        @foreach($salaries as $s)
        <tr>
            <td class="c">{{ $s->status === 'paid' ? 'مدفوع' : 'غير مدفوع' }}</td>
            <td class="c" style="font-weight:bold;">{{ number_format((float) $s->net_salary, 0) }}</td>
            <td class="c">{{ (float) $s->attendance_deduction > 0 ? number_format((float) $s->attendance_deduction, 0) : '—' }}</td>
            <td class="c">{{ (float) $s->withdrawals_deduction > 0 ? number_format((float) $s->withdrawals_deduction, 0) : '—' }}</td>
            <td class="c">{{ (float) $s->deductions > 0 ? number_format((float) $s->deductions, 0) : '—' }}</td>
            <td class="c">{{ (float) $s->bonuses > 0 ? number_format((float) $s->bonuses, 0) : '—' }}</td>
            <td class="c">{{ number_format((float) $s->base_salary, 0) }}</td>
            <td class="c" style="font-weight:bold;">{{ $monthNames[$s->month] ?? $s->month }} {{ $s->year }}</td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td class="c">—</td>
            <td class="c">{{ number_format($totals['salaries_net'], 0) }}</td>
            <td colspan="6" style="text-align:right;">الإجمالي</td>
        </tr>
    </tfoot>
</table>
@endif

<h2 class="sec">السلف والمسحوبات</h2>
@if($advances->isEmpty())
<p class="empty">لا توجد سلف أو مسحوبات خلال هذه الفترة</p>
@else
<table class="data" dir="rtl">
    <thead>
        <tr>
            <th style="width:42%;">البيان</th>
            <th style="width:18%;">التصنيف</th>
            <th style="width:20%;">المبلغ</th>
            <th style="width:20%;">التاريخ</th>
        </tr>
    </thead>
    <tbody>
        @foreach($advances as $a)
        <tr>
            <td>{{ $a->description ?? '—' }}</td>
            <td class="c">{{ \App\Models\Expense::categoryLabel($a->category) }}</td>
            <td class="c" style="font-weight:bold;color:#b45309;">{{ number_format((float) $a->amount, 0) }} {{ $a->currency }}</td>
            <td class="c">{{ $a->expense_date?->format('d/m/Y') }}</td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td colspan="2" style="text-align:right;">الإجمالي</td>
            <td class="c">{{ number_format($totals['advances'], 0) }}</td>
            <td></td>
        </tr>
    </tfoot>
</table>
@endif

<h2 class="sec">الحضور والإجازات</h2>
<table class="info-grid" dir="rtl">
    <tr>
        <td class="lbl">أيام الحضور</td>
        <td class="val">{{ $totals['present_days'] }}</td>
        <td class="lbl">أيام الغياب</td>
        <td class="val">{{ $totals['absent_days'] }}</td>
        <td class="lbl">أيام التأخير</td>
        <td class="val">{{ $totals['late_days'] }}</td>
        <td class="lbl">أيام الإجازات</td>
        <td class="val">{{ $totals['leave_days'] }}</td>
    </tr>
</table>

@if($leaves->isNotEmpty())
<table class="data" dir="rtl">
    <thead>
        <tr>
            <th style="width:15%;">أيام</th>
            <th style="width:25%;">إلى</th>
            <th style="width:25%;">من</th>
            <th style="width:35%;">النوع</th>
        </tr>
    </thead>
    <tbody>
        @foreach($leaves as $l)
        <tr>
            <td class="c">{{ $l->days }}</td>
            <td class="c">{{ $l->to_date?->format('d/m/Y') }}</td>
            <td class="c">{{ $l->from_date?->format('d/m/Y') }}</td>
            <td>{{ $leaveTypes[$l->type] ?? $l->type }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

<div class="footer">طُبع في: {{ now()->format('d/m/Y H:i') }}</div>

</body>
</html>
