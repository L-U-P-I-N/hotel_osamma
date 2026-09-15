@php
    /**
     * ترويسة رسمية موحَّدة لكل مستندات الفندق المصدَّرة (فواتير، تقارير، كشوف).
     *
     * البناء ثلاثة أشرطة داخل إطار واحد، كترويسات المنشآت الرسمية:
     *   ١) شريط الهوية : اسم الفندق بالعربية يميناً، الشعار وسطاً، الاسم
     *      بالإنجليزية يساراً — الثلاثة على محور أفقي واحد، وتحت كل اسم خط
     *      ذهبي ثم الوصف الفرعي، فيتطابق الجانبان بصرياً.
     *   ٢) شريط التواصل: الهواتف في عمودين متقابلين، وبينهما عمود المستند
     *      (عنوان التصدير وفترته وتاريخ الطباعة) — كل تسمية وقيمتها في
     *      خليّتين مصفوفتين، لا قائمة أسطر متراصّة تحت الاسم.
     *   ٣) سطر العنوان بعرض الصفحة كاملاً.
     *
     * ملاحظات تخصّ dompdf:
     *   • لا يعكس ترتيب أعمدة الجدول تبعاً لـdir="rtl"، فتُكتب الخلايا بترتيب
     *     معكوس (الإنجليزي أولاً) ليظهر العربي في أقصى اليمين.
     *   • لا يدعم flexbox، فالبناء كله جداول.
     *   • النص العربي يُحوَّل لأشكال العرض قبل الطباعة (ترتيب بصري)، ولهذا:
     *     – كل تسمية وقيمتها في خليّتين منفصلتين، وإلا انقلب ترتيبهما والمسافة
     *       بينهما ابتُلعت (و&nbsp; يطبعها dompdf كرمز "Â" مع الخط العربي).
     *     – العنوان يُمنح سطراً بعرض الصفحة كي لا يلتفّ، فالالتفاف يقلب ترتيب
     *       كلماته.
     *
     * الحقول غير المضبوطة في الإعدادات تُحذف بدل طباعة شرطة، فإن لم تُملأ
     * الحقول الإنجليزية بقي عمودها فارغاً — تُملأ من شاشة الإعدادات.
     *
     * المتغيّرات الاختيارية:
     *   $docTitle   عنوان المستند (مثل: تقرير الوردية) — يظهر في العمود الأوسط.
     *   $docMeta    سطر تفصيلي تحته (الفترة، اسم الموظف، الشهر…).
     *   $logoHeight ارتفاع الشعار (افتراضي 66).
     */
    $p    = \App\Models\Setting::hotelProfile();
    $logo = \App\Models\Setting::hotelLogo();
    $h    = $logoHeight ?? 66;

    $phones = array_values(array_filter([$p['hotel_phone'] ?? null, $p['hotel_phone2'] ?? null]));
    $wa     = $p['hotel_whatsapp'] ?? null;

    $contactsAr = [];
    foreach ($phones as $i => $ph) { $contactsAr[] = [count($phones) > 1 ? 'هاتف ' . ($i + 1) : 'هاتف', $ph]; }
    if ($wa)                       { $contactsAr[] = ['واتساب', $wa]; }

    $contactsEn = [];
    foreach ($phones as $i => $ph) { $contactsEn[] = [count($phones) > 1 ? 'Tel ' . ($i + 1) : 'Tel', $ph]; }
    if ($wa)                       { $contactsEn[] = ['WhatsApp', $wa]; }

    $addressAr = $p['hotel_address_ar'] ?? null;
    $addressEn = $p['hotel_address_en'] ?? null;

    // عنوان المستند يوسَّع له العمود الأوسط: الأسطر العربية لا تلتفّ (الالتفاف
    // يقلب ترتيب كلماتها بعد التحويل لأشكال العرض)، فتحتاج عرضاً كافياً.
    $docTitle = trim($docTitle ?? '');
    $docMeta  = trim($docMeta ?? '');
    $sideCol  = $docTitle !== '' ? '33%' : '37%';
    $midCol   = $docTitle !== '' ? '34%' : '26%';

    $lbl = 'font-size:7.5px;color:#8a9099;letter-spacing:0.2px;padding:1px 0;white-space:nowrap;width:54px;';
    // الأرقام تُثبَّت اتجاهها كي لا يقلب الـbidi إشارة + إلى آخر الرقم
    $val = 'font-size:8.5px;color:#2b3440;font-weight:bold;padding:1px 0;white-space:nowrap;direction:ltr;unicode-bidi:bidi-override;';
@endphp

<table style="width:100%;border-collapse:collapse;margin-bottom:12px;border:1.5px solid #0F4C75;background:#ffffff;">

    {{-- ————— ١) شريط الهوية ————— --}}
    <tr><td style="padding:11px 16px 9px;">
        <table style="width:100%;border-collapse:collapse;">
            <tr>
                {{-- إنجليزي — يُكتب أولاً فيظهر يساراً --}}
                <td style="width:{{ $sideCol }};vertical-align:middle;text-align:left;direction:ltr;">
                    @if($p['hotel_name_en'] ?? null)
                    <div style="font-size:14px;font-weight:bold;color:#0F4C75;line-height:1.2;letter-spacing:1.1px;text-transform:uppercase;">{{ $p['hotel_name_en'] }}</div>
                    <div style="border-top:1.5px solid #C9A84E;width:58%;margin:4px auto 0 0;"></div>
                    @endif
                    @if($p['hotel_tagline_en'] ?? null)
                    <div style="font-size:8.5px;color:#6b7280;margin-top:4px;letter-spacing:0.2px;">{{ $p['hotel_tagline_en'] }}</div>
                    @endif
                </td>

                {{-- الشعار وسطاً --}}
                <td style="width:{{ $midCol }};vertical-align:middle;text-align:center;">
                    @if($logo)
                    <img src="{{ $logo }}" alt="شعار الفندق" style="height:{{ $h }}px;">
                    @endif
                </td>

                {{-- عربي — يُكتب أخيراً فيظهر يميناً --}}
                <td style="width:{{ $sideCol }};vertical-align:middle;text-align:right;direction:rtl;">
                    @if($p['hotel_name_ar'] ?? null)
                    <div style="font-size:19px;font-weight:bold;color:#0F4C75;line-height:1.2;">{{ $p['hotel_name_ar'] }}</div>
                    <div style="border-top:1.5px solid #C9A84E;width:58%;margin:4px 0 0 auto;"></div>
                    @endif
                    @if($p['hotel_tagline_ar'] ?? null)
                    <div style="font-size:8.5px;color:#6b7280;margin-top:4px;letter-spacing:0.2px;">{{ $p['hotel_tagline_ar'] }}</div>
                    @endif
                </td>
            </tr>
        </table>
    </td></tr>


    {{-- خط مزدوج (كحلي فوق ذهبي) يفصل الهوية عن بيانات التواصل --}}
    <tr><td style="padding:0;"><div style="border-top:1.5px solid #0F4C75;"></div><div style="border-top:1px solid #C9A84E;"></div></td></tr>

    {{-- ————— ٢) شريط التواصل ————— --}}
    <tr><td style="padding:6px 16px 7px;background:#f7fafc;">
        <table style="width:100%;border-collapse:collapse;">
            <tr>
                {{-- الهواتف بتسميات إنجليزية (يسار): التسمية أولاً ثم القيمة --}}
                <td style="width:{{ $sideCol }};vertical-align:middle;">
                    <table style="width:100%;border-collapse:collapse;table-layout:fixed;">
                        @foreach($contactsEn as [$label, $value])
                        <tr>
                            <td style="{{ $lbl }}text-align:left;padding-right:8px;">{{ $label }}</td>
                            <td style="{{ $val }}text-align:left;">{{ $value }}</td>
                        </tr>
                        @endforeach
                    </table>
                </td>

                {{-- عمود المستند وسطاً تحت الشعار: عنوان التصدير وفترته وتاريخ
                     الطباعة — موضع بيانات المستند المعتاد في الترويسات الرسمية --}}
                <td style="width:{{ $midCol }};vertical-align:middle;text-align:center;border-right:1px solid #dde5ec;border-left:1px solid #dde5ec;padding:0 8px;">
                    @if($docTitle !== '')
                    <div style="font-size:11.5px;font-weight:bold;color:#0F4C75;line-height:1.25;">{{ $docTitle }}</div>
                    @if($docMeta !== '')
                    <div style="font-size:8px;color:#4b5563;margin-top:2px;line-height:1.3;">{{ $docMeta }}</div>
                    @endif
                    <div style="font-size:7.5px;color:#8a9099;margin-top:2px;direction:ltr;unicode-bidi:bidi-override;">{{ now()->format('Y/m/d') }}</div>
                    @else
                    <div style="font-size:7.5px;color:#8a9099;letter-spacing:0.3px;">التاريخ / DATE</div>
                    <div style="font-size:10px;color:#0F4C75;font-weight:bold;direction:ltr;unicode-bidi:bidi-override;margin-top:1px;">{{ now()->format('Y/m/d') }}</div>
                    @endif
                </td>

                {{-- نفس الهواتف بتسميات عربية (يمين): القيمة أولاً ثم التسمية --}}
                <td style="width:{{ $sideCol }};vertical-align:middle;">
                    <table style="width:100%;border-collapse:collapse;table-layout:fixed;">
                        @foreach($contactsAr as [$label, $value])
                        <tr>
                            <td style="{{ $val }}text-align:right;padding-left:8px;">{{ $value }}</td>
                            <td style="{{ $lbl }}text-align:right;">{{ $label }}</td>
                        </tr>
                        @endforeach
                    </table>
                </td>
            </tr>
        </table>
    </td></tr>


    {{-- ————— ٣) سطر العنوان (بعرض الصفحة كاملاً) ————— --}}
    @if($addressAr || $addressEn)
    <tr><td style="padding:4px 16px 5px;background:#f7fafc;border-top:1px solid #e6edf3;">
        @if($addressAr)
        <table style="width:100%;border-collapse:collapse;">
            <tr>
                <td style="text-align:right;direction:rtl;font-size:8.5px;color:#4b5563;padding:0 0 0 8px;white-space:nowrap;">{{ $addressAr }}</td>
                <td style="{{ $lbl }}text-align:right;">العنوان</td>
            </tr>
        </table>
        @endif
        @if($addressEn)
        <table style="width:100%;border-collapse:collapse;margin-top:1px;">
            <tr>
                <td style="{{ $lbl }}text-align:left;padding-right:8px;">Address</td>
                <td style="text-align:left;direction:ltr;font-size:8.5px;color:#4b5563;padding:0;white-space:nowrap;">{{ $addressEn }}</td>
            </tr>
        </table>
        @endif
    </td></tr>
    @endif
</table>
