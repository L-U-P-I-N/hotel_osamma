@php
    /**
     * رأس تصدير كامل ثلاثي الأعمدة:
     *   يمين  : اسم الفندق والعنوان والتاريخ وأرقام التواصل — بالعربية
     *   وسط   : شعار الفندق
     *   يسار  : الاسم والعنوان والتاريخ وأرقام التواصل — بالإنجليزية
     *
     * مبني بجدول لا بـflex لأن dompdf لا يدعم flexbox. الحقول غير المضبوطة
     * في الإعدادات تُحذف بدل طباعة شرطة، فلا يتضخّم الرأس ببيانات فارغة.
     *
     * $logoHeight اختياري (افتراضي 62).
     * جزئية منفصلة عن pdf-hotel-header المختصرة لأن هذا الشكل مطلوب في
     * قوالب بعينها فقط.
     */
    $p    = \App\Models\Setting::hotelProfile();
    $logo = \App\Models\Setting::hotelLogo();
    $h    = $logoHeight ?? 72;

    // أرقام التواصل (نفس الأرقام على الجانبين، تختلف التسميات فقط)
    $phones = array_values(array_filter([$p['hotel_phone'] ?? null, $p['hotel_phone2'] ?? null]));
    $wa     = $p['hotel_whatsapp'] ?? null;
@endphp

{{-- إطار مستطيل حول الرأس — يعطيه شكل ترويسة رسمية (letterhead) بدل نص عائم
     بلا حدود، متسق عبر كل مستندات الفندق المصدَّرة (فواتير، تقارير، إيصالات). --}}
<table style="width:100%;border-collapse:collapse;margin-bottom:10px;border:2px solid #0F4C75;border-radius:6px;background:#fbfdff;">
    <tr><td style="padding:12px 16px;">
<table style="width:100%;border-collapse:collapse;">
    <tr>
        {{-- ————— عربي (يمين) ————— --}}
        <td style="width:34%;vertical-align:top;text-align:right;direction:rtl;">
            @if($p['hotel_name_ar'] ?? null)
            <div style="font-size:20px;font-weight:bold;color:#0F4C75;line-height:1.25;letter-spacing:0.3px;">{{ $p['hotel_name_ar'] }}</div>
            <div style="border-top:2px solid #C9A84E;width:70%;margin:3px 0 4px auto;"></div>
            @endif
            @if($p['hotel_tagline_ar'] ?? null)
            <div style="font-size:9.5px;color:#555;margin-top:1px;">{{ $p['hotel_tagline_ar'] }}</div>
            @endif
            @if($p['hotel_address_ar'] ?? null)
            <div style="font-size:9px;color:#777;margin-top:1px;">{{ $p['hotel_address_ar'] }}</div>
            @endif
            <div style="font-size:9px;color:#555;margin-top:2px;">التاريخ: {{ now()->format('Y/m/d') }}</div>
            @foreach($phones as $ph)
            <div style="font-size:9px;color:#555;">هاتف: <span style="direction:ltr;unicode-bidi:embed;">{{ $ph }}</span></div>
            @endforeach
            @if($wa)
            <div style="font-size:9px;color:#555;">واتساب: <span style="direction:ltr;unicode-bidi:embed;">{{ $wa }}</span></div>
            @endif
        </td>

        {{-- ————— الشعار (وسط) ————— --}}
        <td style="width:32%;vertical-align:middle;text-align:center;">
            @if($logo)
            <img src="{{ $logo }}" alt="شعار الفندق" style="height:{{ $h }}px;">
            @endif
        </td>

        {{-- ————— إنجليزي (يسار) ————— --}}
        <td style="width:34%;vertical-align:top;text-align:left;direction:ltr;">
            @if($p['hotel_name_en'] ?? null)
            <div style="font-size:18px;font-weight:bold;color:#0F4C75;line-height:1.25;letter-spacing:1px;text-transform:uppercase;">{{ $p['hotel_name_en'] }}</div>
            <div style="border-top:2px solid #C9A84E;width:70%;margin:3px auto 4px 0;"></div>
            @endif
            @if($p['hotel_tagline_en'] ?? null)
            <div style="font-size:9.5px;color:#555;margin-top:1px;">{{ $p['hotel_tagline_en'] }}</div>
            @endif
            @if($p['hotel_address_en'] ?? null)
            <div style="font-size:9px;color:#777;margin-top:1px;">{{ $p['hotel_address_en'] }}</div>
            @endif
            <div style="font-size:9px;color:#555;margin-top:2px;">Date: {{ now()->format('d/m/Y') }}</div>
            @foreach($phones as $ph)
            <div style="font-size:9px;color:#555;">Tel: {{ $ph }}</div>
            @endforeach
            @if($wa)
            <div style="font-size:9px;color:#555;">WhatsApp: {{ $wa }}</div>
            @endif
        </td>
    </tr>
</table>
    </td></tr>
</table>
