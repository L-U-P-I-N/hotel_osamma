<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
@php
    $monthNames = [1=>'يناير',2=>'فبراير',3=>'مارس',4=>'أبريل',5=>'مايو',6=>'يونيو',
                   7=>'يوليو',8=>'أغسطس',9=>'سبتمبر',10=>'أكتوبر',11=>'نوفمبر',12=>'ديسمبر'];
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
    @include('partials.pdf-hotel-header-full')
    <h1>كشف مسحوبات موظف</h1>
    <div class="sub">شهر: {{ $monthNames[$month] ?? $month }} {{ $year }}</div>
</div>

<table class="info-grid" dir="rtl">
    <tr>
        <td class="lbl">الاسم</td>
        <td class="val">{{ $employee->name }}</td>
        <td class="lbl">الوظيفة</td>
        <td class="val">{{ $employee->position ?: '—' }}</td>
    </tr>
    <tr>
        <td class="lbl">الجوال</td>
        <td class="val ltr">{{ $employee->phone ?: '—' }}</td>
        <td class="lbl">رقم الهوية</td>
        <td class="val ltr">{{ $employee->national_id ?: '—' }}</td>
    </tr>
    <tr>
        <td class="lbl">تاريخ التعيين</td>
        <td class="val">{{ $employee->hire_date?->format('Y/m/d') ?: '—' }}</td>
        <td class="lbl">الحالة</td>
        <td class="val">{{ $employee->is_active ? 'نشط' : 'غير نشط' }}</td>
    </tr>
</table>

<div class="cards">
    <div class="card"><div class="card-inner">
        <div class="card-label">الراتب الأساسي</div>
        <div class="card-value">{{ number_format($baseSalary, 0) }}</div>
    </div></div>
    <div class="card"><div class="card-inner">
        <div class="card-label">مسحوبات الشهر ({{ $withdrawals->count() }} عملية)</div>
        <div class="card-value amber">{{ number_format($monthTotal, 0) }}</div>
    </div></div>
    <div class="card"><div class="card-inner">
        <div class="card-label">المخصوم من الراتب</div>
        <div class="card-value red">{{ number_format($salaryChargeable, 0) }}</div>
    </div></div>
    <div class="card"><div class="card-inner">
        <div class="card-label">المتبقي من الراتب</div>
        <div class="card-value {{ $remainingSalary < 0 ? 'red' : 'green' }}">{{ number_format($remainingSalary, 0) }}</div>
    </div></div>
</div>

@if($foodSummary['allowance'] > 0)
{{-- صرفية الطعام بند مستقل: لا يُخصم من الراتب إلا ما تجاوز حدّها --}}
<h2 class="sec">صرفية الطعام والشراب</h2>
<table class="info-grid" dir="rtl">
    <tr>
        <td class="lbl">الصرفية الشهرية</td>
        <td class="val">{{ number_format($foodSummary['allowance'], 0) }} ر.ي</td>
        <td class="lbl">المصروف منها</td>
        <td class="val">{{ number_format($foodSummary['spent'], 0) }} ر.ي</td>
        <td class="lbl">المتبقي</td>
        <td class="val">{{ number_format($foodSummary['remaining'], 0) }} ر.ي</td>
        <td class="lbl">تجاوز يُخصم من الراتب</td>
        <td class="val">{{ $foodSummary['overspend'] > 0 ? number_format($foodSummary['overspend'], 0) . ' ر.ي' : '—' }}</td>
    </tr>
</table>
@endif

{{-- dompdf لا يعكس ترتيب أعمدة الجدول بحسب dir="rtl" — نكتبها بترتيب معكوس
     ليظهر أول عمود منطقياً في أقصى اليمين. --}}
<h2 class="sec">تفاصيل المسحوبات</h2>
@if($withdrawals->isEmpty())
<p class="empty">لا توجد مسحوبات لهذا الموظف في هذا الشهر</p>
@else
<table class="data" dir="rtl">
    <thead>
        <tr>
            <th style="width:13%;">الوردية</th>
            <th style="width:13%;">صرفها له</th>
            <th style="width:26%;">البيان</th>
            <th style="width:13%;">التصنيف</th>
            <th style="width:14%;">المبلغ</th>
            <th style="width:16%;">التاريخ</th>
            <th style="width:5%;">#</th>
        </tr>
    </thead>
    <tbody>
        @foreach($withdrawals as $i => $w)
        <tr>
            <td class="c">{{ $w->shift ? ($w->shift->user?->name ?? 'وردية #' . $w->shift->id) : '—' }}</td>
            <td class="c">{{ $w->paidBy?->name ?? '—' }}</td>
            <td>{{ $w->description ?: '—' }}</td>
            <td class="c">{{ \App\Models\Expense::categoryLabel($w->category) }}</td>
            <td class="c" style="font-weight:bold;color:#b45309;">{{ number_format((float) $w->amount, 0) }}</td>
            <td class="c">{{ $w->expense_date?->format('d/m/Y') }}</td>
            <td class="c">{{ $i + 1 }}</td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td colspan="4" style="text-align:right;">الإجمالي</td>
            <td class="c">{{ number_format($monthTotal, 0) }}</td>
            <td colspan="2"></td>
        </tr>
    </tfoot>
</table>
@endif

@if($salary)
<h2 class="sec">قسيمة الراتب</h2>
<table class="info-grid" dir="rtl">
    <tr>
        <td class="lbl">الخصومات المسجَّلة</td>
        <td class="val">{{ number_format((float) $salary->deductions, 0) }} ر.ي</td>
        <td class="lbl">خصم المسحوبات</td>
        <td class="val">{{ number_format((float) $salary->withdrawals_deduction, 0) }} ر.ي</td>
        <td class="lbl">صافي الراتب</td>
        <td class="val">{{ number_format((float) $salary->net_salary, 0) }} ر.ي</td>
        <td class="lbl">الحالة</td>
        <td class="val">{{ $salary->status === 'paid' ? 'مدفوع' : 'غير مدفوع' }}</td>
    </tr>
</table>
@endif

<div class="footer">
    إجمالي المسحوبات لكل الفترات: {{ number_format($allTimeTotal, 0) }} ر.ي — طُبع في: {{ now()->format('d/m/Y H:i') }}
</div>

</body>
</html>
