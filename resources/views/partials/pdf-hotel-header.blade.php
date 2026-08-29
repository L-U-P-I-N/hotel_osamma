@php
    /**
     * رأس موحّد لكل فواتير وتقارير PDF: البيانات العربية يميناً، الشعار في
     * المنتصف، والإنجليزية يساراً — ثم سطر أرقام التواصل أسفله.
     *
     * يُبنى بجداول لا بـflex لأن dompdf لا يدعم flexbox. الحقول الفارغة تُحذف
     * تماماً بدل طباعة شرطة، ويُخفى العمود الإنجليزي إن لم تُضبط بياناته.
     *
     * $logoHeight اختياري (افتراضي 70).
     */
    $__p    = \App\Models\Setting::hotelProfile();
    $__logo = \App\Models\Setting::hotelLogo();
    $__h    = $logoHeight ?? 70;

    $__hasEn = ($__p['hotel_name_en'] ?? null) || ($__p['hotel_address_en'] ?? null) || ($__p['hotel_tagline_en'] ?? null);
    $__contact = \App\Models\Setting::contactLine();

    $__docs = array_filter([
        ($__p['hotel_license_no'] ?? null) ? 'ترخيص: ' . $__p['hotel_license_no'] : null,
        ($__p['hotel_cr_no'] ?? null)      ? 'س.ت: ' . $__p['hotel_cr_no'] : null,
        ($__p['hotel_tax_no'] ?? null)     ? 'ر.ض: ' . $__p['hotel_tax_no'] : null,
    ]);
@endphp

<table style="width:100%;border-collapse:collapse;margin-bottom:8px;" dir="rtl">
    <tr>
        {{-- عربي (يمين) --}}
        <td style="width:34%;vertical-align:middle;text-align:right;direction:rtl;">
            @if($__p['hotel_name_ar'] ?? null)
            <div style="font-size:13px;font-weight:bold;color:#0F4C75;line-height:1.4;">{{ $__p['hotel_name_ar'] }}</div>
            @endif
            @if($__p['hotel_tagline_ar'] ?? null)
            <div style="font-size:9px;color:#555;margin-top:2px;">{{ $__p['hotel_tagline_ar'] }}</div>
            @endif
            @if($__p['hotel_address_ar'] ?? null)
            <div style="font-size:8.5px;color:#777;margin-top:2px;">{{ $__p['hotel_address_ar'] }}</div>
            @endif
        </td>

        {{-- الشعار (وسط) --}}
        <td style="width:32%;vertical-align:middle;text-align:center;">
            @if($__logo)
            <img src="{{ $__logo }}" alt="شعار الفندق" style="height:{{ $__h }}px;">
            @endif
        </td>

        {{-- إنجليزي (يسار) --}}
        <td style="width:34%;vertical-align:middle;text-align:left;direction:ltr;">
            @if($__hasEn)
                @if($__p['hotel_name_en'] ?? null)
                <div style="font-size:13px;font-weight:bold;color:#0F4C75;line-height:1.4;">{{ $__p['hotel_name_en'] }}</div>
                @endif
                @if($__p['hotel_tagline_en'] ?? null)
                <div style="font-size:9px;color:#555;margin-top:2px;">{{ $__p['hotel_tagline_en'] }}</div>
                @endif
                @if($__p['hotel_address_en'] ?? null)
                <div style="font-size:8.5px;color:#777;margin-top:2px;">{{ $__p['hotel_address_en'] }}</div>
                @endif
            @endif
        </td>
    </tr>
</table>

@if($__contact || !empty($__docs))
<div style="text-align:center;font-size:8.5px;color:#555;border-top:1px solid #e5e7eb;padding-top:4px;margin-bottom:6px;">
    @if($__contact)<div>{{ $__contact }}</div>@endif
    @if(!empty($__docs))<div style="color:#888;margin-top:2px;">{{ implode('  •  ', $__docs) }}</div>@endif
</div>
@endif
