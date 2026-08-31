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
    $h    = $logoHeight ?? 62;

    // أرقام التواصل (نفس الأرقام على الجانبين، تختلف التسميات فقط)
    $phones = array_values(array_filter([$p['hotel_phone'] ?? null, $p['hotel_phone2'] ?? null]));
    $wa     = $p['hotel_whatsapp'] ?? null;
@endphp

<table style="width:100%;border-collapse:collapse;margin-bottom:6px;">
    <tr>
        {{-- ————— عربي (يمين) ————— --}}
        <td style="width:36%;vertical-align:top;text-align:right;direction:rtl;">
            @if($p['hotel_name_ar'] ?? null)
            <div style="font-size:12px;font-weight:bold;color:#0F4C75;line-height:1.35;">{{ $p['hotel_name_ar'] }}</div>
            @endif
            @if($p['hotel_tagline_ar'] ?? null)
            <div style="font-size:8px;color:#555;margin-top:1px;">{{ $p['hotel_tagline_ar'] }}</div>
            @endif
            @if($p['hotel_address_ar'] ?? null)
            <div style="font-size:8px;color:#777;margin-top:1px;">{{ $p['hotel_address_ar'] }}</div>
            @endif
            <div style="font-size:8px;color:#555;margin-top:2px;">التاريخ: {{ now()->format('Y/m/d') }}</div>
            @foreach($phones as $ph)
            <div style="font-size:8px;color:#555;">هاتف: <span style="direction:ltr;unicode-bidi:embed;">{{ $ph }}</span></div>
            @endforeach
            @if($wa)
            <div style="font-size:8px;color:#555;">واتساب: <span style="direction:ltr;unicode-bidi:embed;">{{ $wa }}</span></div>
            @endif
        </td>

        {{-- ————— الشعار (وسط) ————— --}}
        <td style="width:28%;vertical-align:middle;text-align:center;">
            @if($logo)
            <img src="{{ $logo }}" alt="شعار الفندق" style="height:{{ $h }}px;">
            @endif
        </td>

        {{-- ————— إنجليزي (يسار) ————— --}}
        <td style="width:36%;vertical-align:top;text-align:left;direction:ltr;">
            @if($p['hotel_name_en'] ?? null)
            <div style="font-size:12px;font-weight:bold;color:#0F4C75;line-height:1.35;">{{ $p['hotel_name_en'] }}</div>
            @endif
            @if($p['hotel_tagline_en'] ?? null)
            <div style="font-size:8px;color:#555;margin-top:1px;">{{ $p['hotel_tagline_en'] }}</div>
            @endif
            @if($p['hotel_address_en'] ?? null)
            <div style="font-size:8px;color:#777;margin-top:1px;">{{ $p['hotel_address_en'] }}</div>
            @endif
            <div style="font-size:8px;color:#555;margin-top:2px;">Date: {{ now()->format('d/m/Y') }}</div>
            @foreach($phones as $ph)
            <div style="font-size:8px;color:#555;">Tel: {{ $ph }}</div>
            @endforeach
            @if($wa)
            <div style="font-size:8px;color:#555;">WhatsApp: {{ $wa }}</div>
            @endif
        </td>
    </tr>
</table>
