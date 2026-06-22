<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 11px; color: #1a1a1a; direction: rtl; }
    .header { background: #0F4C75; color: white; padding: 18px 24px; text-align: center; margin-bottom: 16px; }
    .header h1 { font-size: 18px; font-weight: bold; margin-bottom: 3px; }
    .header p { font-size: 11px; opacity: 0.85; }
    .header .doc-title { font-size: 14px; font-weight: bold; margin-top: 10px; background: rgba(255,255,255,0.15); padding: 5px 20px; border-radius: 4px; display: inline-block; }
    .section { margin: 0 18px 16px; }
    .section h2 { font-size: 12px; font-weight: bold; color: #0F4C75; border-bottom: 2px solid #0F4C75; padding-bottom: 4px; margin-bottom: 8px; }
    table { width: 100%; border-collapse: collapse; }
    th { background: #f1f5f9; color: #374151; font-size: 10px; padding: 5px 8px; text-align: right; border: 1px solid #cbd5e1; font-weight: bold; }
    td { padding: 5px 8px; border: 1px solid #e2e8f0; font-size: 10px; }
    tr:nth-child(even) td { background: #f9fafb; }
    .meta { margin: 0 18px 14px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 4px; padding: 10px 14px; }
    .meta-grid { display: grid; grid-template-columns: auto 1fr auto 1fr; gap: 4px 16px; }
    .meta-label { color: #64748b; font-size: 10px; font-weight: bold; }
    .meta-value { font-size: 10px; color: #1e293b; }
    .footer { margin: 18px; border-top: 1px solid #e2e8f0; padding-top: 14px; }
    .footer-grid { display: flex; justify-content: space-between; align-items: flex-start; }
    .sig-box { text-align: center; }
    .sig-line { border-bottom: 1px solid #374151; width: 150px; margin: 28px auto 5px; }
    .sig-label { font-size: 10px; color: #64748b; }
    .sig-name { font-size: 10px; font-weight: bold; color: #1e293b; margin-top: 3px; }
    .center-info { text-align: center; font-size: 9px; color: #94a3b8; }
    .stamp { background: #0F4C75; color: white; padding: 3px 10px; border-radius: 4px; font-size: 9px; display: inline-block; margin-top: 6px; }
</style>
</head>
<body>

<div class="header">
    <h1>{{ $hotel->name ?? 'الفندق السعودي' }}</h1>
    <p>{{ $hotel->address ?? '' }}{{ $hotel->phone ? ' | ' . $hotel->phone : '' }}</p>
    <div class="doc-title">نموذج بيانات النزلاء للجهات الحكومية</div>
</div>

<div class="meta">
    <div class="meta-grid">
        <span class="meta-label">رقم الحجز:</span>
        <span class="meta-value">#{{ $reservation->id }}</span>
        <span class="meta-label">تاريخ الإصدار:</span>
        <span class="meta-value">{{ now()->format('d/m/Y H:i') }}</span>

        <span class="meta-label">رقم الغرفة:</span>
        <span class="meta-value">{{ $reservation->display_room_number }}</span>
        <span class="meta-label">نوع الغرفة:</span>
        <span class="meta-value">{{ $reservation->room?->roomType?->name ?? '—' }}</span>

        <span class="meta-label">تاريخ الوصول:</span>
        <span class="meta-value">{{ $reservation->check_in_date?->format('d/m/Y') ?? '—' }}</span>
        <span class="meta-label">تاريخ المغادرة:</span>
        <span class="meta-value">{{ $reservation->check_out_date?->format('d/m/Y') ?? '—' }}</span>

        <span class="meta-label">الغرض من الزيارة:</span>
        <span class="meta-value">{{ $reservation->purpose ?? '-' }}</span>
        <span class="meta-label">جهة القدوم:</span>
        <span class="meta-value">{{ $reservation->origin ?? '-' }}</span>
    </div>
</div>

<div class="section">
    <h2>بيانات النزيل الرئيسي</h2>
    <table>
        <thead>
            <tr>
                <th>الاسم الرباعي</th>
                <th>الجنسية</th>
                <th>نوع الهوية</th>
                <th>رقم الهوية</th>
                <th>الجهة المصدرة</th>
                <th>تاريخ الإصدار</th>
                <th>رقم الجوال</th>
                <th>المهنة</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>{{ $reservation->guest?->full_name ?? '—' }}</strong></td>
                <td>{{ $reservation->guest?->nationality ?? '-' }}</td>
                <td>{{ $reservation->guest?->getIdTypeLabel() ?? '—' }}</td>
                <td>{{ $reservation->guest?->id_number ?? '—' }}</td>
                <td>{{ $reservation->guest?->id_issuer ?? '-' }}</td>
                <td>{{ $reservation->guest?->id_issue_date?->format('d/m/Y') ?? '-' }}</td>
                <td>{{ $reservation->guest?->phone ?? '-' }}</td>
                <td>{{ $reservation->guest?->occupation ?? '-' }}</td>
            </tr>
        </tbody>
    </table>
</div>

@if($reservation->companions->count() > 0)
<div class="section">
    <h2>بيانات المرافقين ({{ $reservation->companions->count() }} مرافق)</h2>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>الاسم الكامل</th>
                <th>الجنسية</th>
                <th>نوع الهوية</th>
                <th>رقم الهوية</th>
                <th>الجهة المصدرة</th>
                <th>تاريخ الإصدار</th>
                <th>صلة القرابة</th>
            </tr>
        </thead>
        <tbody>
            @foreach($reservation->companions as $i => $comp)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td><strong>{{ $comp->full_name }}</strong></td>
                <td>{{ $comp->nationality ?? '-' }}</td>
                <td>{{ $comp->id_type }}</td>
                <td>{{ $comp->id_number ?? '-' }}</td>
                <td>{{ $comp->id_issuer ?? '-' }}</td>
                <td>{{ $comp->id_issue_date?->format('d/m/Y') ?? '-' }}</td>
                <td>{{ $comp->getRelationshipLabel() }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@else
<div class="section">
    <h2>المرافقون</h2>
    <p style="color:#94a3b8; font-size:10px; padding:8px 0;">لا يوجد مرافقون</p>
</div>
@endif

<div class="footer">
    <div class="footer-grid">
        <div class="sig-box">
            <div class="sig-line"></div>
            <div class="sig-label">توقيع موظف الاستقبال</div>
            <div class="sig-name">{{ $reservation->createdBy?->name ?? '-' }}</div>
        </div>
        <div class="center-info">
            <p>صدر بواسطة: {{ auth()->user()?->name ?? $reservation->createdBy?->name ?? 'النظام' }}</p>
            <p style="margin-top:3px;">{{ now()->format('d/m/Y H:i:s') }}</p>
            <div class="stamp">{{ $hotel->name ?? 'الفندق السعودي' }} — نظام إدارة الفندق</div>
        </div>
        <div class="sig-box">
            <div class="sig-line"></div>
            <div class="sig-label">ختم الفندق الرسمي</div>
        </div>
    </div>
</div>

</body>
</html>
