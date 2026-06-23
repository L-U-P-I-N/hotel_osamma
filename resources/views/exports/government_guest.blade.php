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
* { margin:0; padding:0; box-sizing:border-box; }
body {
    font-family: 'NotoNaskhArabic', sans-serif;
    font-size: 10pt;
    color: #1a1a1a;
    direction: rtl;
    padding: 12mm 14mm;
    background: #fff;
}

/* Header */
.header {
    background: #0F4C75;
    color: #fff;
    text-align: center;
    padding: 14px 20px;
    margin-bottom: 14px;
    border-radius: 4px;
}
.header h1 { font-size: 17pt; font-weight: bold; margin-bottom: 3px; }
.header .sub { font-size: 9pt; opacity: 0.85; }
.header .doc-title {
    font-size: 12pt;
    font-weight: bold;
    margin-top: 10px;
    background: rgba(255,255,255,0.18);
    padding: 5px 22px;
    border-radius: 3px;
    display: inline-block;
}

/* Meta info box */
.meta-box {
    border: 1px solid #e2e8f0;
    border-radius: 4px;
    margin-bottom: 14px;
    overflow: hidden;
}
.meta-box table {
    width: 100%;
    border-collapse: collapse;
}
.meta-box table td {
    padding: 5px 10px;
    font-size: 9.5pt;
    border: 1px solid #e2e8f0;
    vertical-align: middle;
}
.meta-box table td.lbl {
    background: #f8fafc;
    color: #64748b;
    font-weight: bold;
    font-size: 8.5pt;
    width: 12%;
    white-space: nowrap;
}
.meta-box table td.val {
    color: #1e293b;
    width: 13%;
}

/* Section */
.section { margin-bottom: 14px; }
.section-title {
    font-size: 11pt;
    font-weight: bold;
    color: #0F4C75;
    border-bottom: 2px solid #0F4C75;
    padding-bottom: 4px;
    margin-bottom: 8px;
}

/* Data table */
table.data { width: 100%; border-collapse: collapse; }
table.data thead tr { background: #0F4C75; color: #fff; }
table.data thead th {
    padding: 6px 8px;
    text-align: right;
    font-size: 8.5pt;
    font-weight: bold;
    border: 1px solid #0a3a5e;
}
table.data tbody td {
    padding: 5px 8px;
    border: 1px solid #e2e8f0;
    font-size: 9pt;
    vertical-align: middle;
}
table.data tbody tr:nth-child(even) td { background: #f9fafb; }

/* Footer */
.footer {
    margin-top: 18px;
    border-top: 1px solid #e2e8f0;
    padding-top: 12px;
}
.footer-table { width: 100%; border-collapse: collapse; }
.footer-table td { vertical-align: top; text-align: center; width: 33%; padding: 0 8px; }
.sig-line { border-bottom: 1px solid #374151; width: 150px; margin: 26px auto 6px; }
.sig-label { font-size: 9pt; color: #64748b; }
.sig-name { font-size: 9pt; font-weight: bold; color: #1e293b; margin-top: 3px; }
.center-meta { font-size: 9pt; color: #374151; }
.center-meta p { margin-bottom: 3px; }
.stamp {
    background: #0F4C75;
    color: #fff;
    padding: 4px 12px;
    border-radius: 3px;
    font-size: 8.5pt;
    display: inline-block;
    margin-top: 6px;
}
</style>
</head>
<body>

{{-- Header --}}
<div class="header">
    <h1>{{ $hotel->name ?? 'فندق السعودي' }}</h1>
    <div class="sub">{{ $hotel->address ?? '' }}{{ $hotel->phone ? ' | ' . $hotel->phone : '' }}</div>
    <div class="doc-title">نموذج بيانات النزلاء للجهات الحكومية</div>
</div>

{{-- Reservation Meta --}}
<div class="meta-box">
    <table>
        <tr>
            <td class="val">{{ $reservation->room?->roomType?->name ?? '—' }}</td>
            <td class="lbl">نوع الغرفة</td>
            <td class="val">{{ $reservation->display_room_number }}</td>
            <td class="lbl">رقم الغرفة</td>
            <td class="val">{{ now()->format('d/m/Y H:i') }}</td>
            <td class="lbl">تاريخ الإصدار</td>
            <td class="val">#{{ $reservation->id }}</td>
            <td class="lbl">رقم الحجز</td>
        </tr>
        <tr>
            <td class="val">{{ $reservation->origin ?? '—' }}</td>
            <td class="lbl">جهة القدوم</td>
            <td class="val">{{ $reservation->purpose ?? '—' }}</td>
            <td class="lbl">الغرض</td>
            <td class="val">{{ $reservation->check_out_date?->format('d/m/Y') ?? '—' }}</td>
            <td class="lbl">تاريخ المغادرة</td>
            <td class="val">{{ $reservation->check_in_date?->format('d/m/Y') ?? '—' }}</td>
            <td class="lbl">تاريخ الوصول</td>
        </tr>
    </table>
</div>

{{-- Main Guest --}}
<div class="section">
    <div class="section-title">بيانات النزيل الرئيسي</div>
    <table class="data">
        <thead>
            <tr>
                <th>المهنة</th>
                <th>رقم الجوال</th>
                <th>تاريخ الإصدار</th>
                <th>الجهة المصدرة</th>
                <th>رقم الهوية</th>
                <th>نوع الهوية</th>
                <th>الجنسية</th>
                <th>الاسم الرباعي</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $reservation->guest?->occupation ?? '—' }}</td>
                <td>{{ $reservation->guest?->phone ?? '—' }}</td>
                <td>{{ $reservation->guest?->id_issue_date?->format('d/m/Y') ?? '—' }}</td>
                <td>{{ $reservation->guest?->id_issuer ?? '—' }}</td>
                <td>{{ $reservation->guest?->id_number ?? '—' }}</td>
                <td>{{ $reservation->guest?->getIdTypeLabel() ?? '—' }}</td>
                <td>{{ $reservation->guest?->nationality ?? '—' }}</td>
                <td><strong>{{ $reservation->guest?->full_name ?? '—' }}</strong></td>
            </tr>
        </tbody>
    </table>
</div>

{{-- Companions --}}
@if($reservation->companions->count() > 0)
<div class="section">
    <div class="section-title">بيانات المرافقين ({{ $reservation->companions->count() }} مرافق)</div>
    <table class="data">
        <thead>
            <tr>
                <th>صلة القرابة</th>
                <th>تاريخ الإصدار</th>
                <th>الجهة المصدرة</th>
                <th>رقم الهوية</th>
                <th>نوع الهوية</th>
                <th>الجنسية</th>
                <th>الاسم الكامل</th>
                <th style="width:4%;">#</th>
            </tr>
        </thead>
        <tbody>
            @foreach($reservation->companions as $i => $comp)
            <tr>
                <td>{{ $comp->getRelationshipLabel() }}</td>
                <td>{{ $comp->id_issue_date?->format('d/m/Y') ?? '—' }}</td>
                <td>{{ $comp->id_issuer ?? '—' }}</td>
                <td>{{ $comp->id_number ?? '—' }}</td>
                <td>{{ $comp->getIdTypeLabel() ?? '—' }}</td>
                <td>{{ $comp->nationality ?? '—' }}</td>
                <td><strong>{{ $comp->full_name }}</strong></td>
                <td style="text-align:center;">{{ $i + 1 }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@else
<div class="section">
    <div class="section-title">المرافقون</div>
    <p style="color:#94a3b8; font-size:9pt; padding:8px 0;">لا يوجد مرافقون</p>
</div>
@endif

{{-- Footer --}}
<div class="footer">
    <table class="footer-table">
        <tr>
            <td>
                <div class="sig-line"></div>
                <div class="sig-label">توقيع موظف الاستقبال</div>
                <div class="sig-name">{{ $reservation->createdBy?->name ?? '—' }}</div>
            </td>
            <td>
                <div class="center-meta">
                    <p>صدر بواسطة: {{ $reservation->createdBy?->name ?? 'النظام' }}</p>
                    <p>{{ now()->format('d/m/Y H:i:s') }}</p>
                    <div class="stamp">{{ $hotel->name ?? 'فندق السعودي' }} — نظام إدارة الفندق</div>
                </div>
            </td>
            <td>
                <div class="sig-line"></div>
                <div class="sig-label">ختم الفندق الرسمي</div>
            </td>
        </tr>
    </table>
</div>

</body>
</html>
