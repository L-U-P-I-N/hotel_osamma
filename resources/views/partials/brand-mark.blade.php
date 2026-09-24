@php
    /**
     * شعار الفندق واسمه من الإعدادات — مصدر واحد لكل شاشات النظام (تسجيل
     * الدخول، استعادة كلمة المرور، القائمة الجانبية) بدل حرف ثابت واسم مكتوب
     * يدوياً في كل صفحة.
     *
     * الشعار مخزَّن data URI في قاعدة البيانات فيعمل مباشرةً في الوسم <img>؛
     * وعند غيابه يُعرض أول حرف من اسم الفندق بدل حرف ثابت لا يخصّه.
     *
     * المتغيّرات الاختيارية:
     *   $size      قياس المربّع (افتراضي w-20 h-20)
     *   $rounded   استدارة الحواف (افتراضي rounded-2xl)
     *   $letterCls صنف حجم الحرف البديل (افتراضي text-4xl)
     */
    $__logo = \App\Models\Setting::hotelLogo();
    // مسار ملف على القرص (النسخ القديمة) لا يصلح داخل <img> في المتصفح
    if ($__logo && !str_starts_with($__logo, 'data:') && !str_starts_with($__logo, 'http')) {
        $__logo = file_exists(public_path('images/hotel-logo.png')) ? asset('images/hotel-logo.png') : null;
    }
    $__name    = \App\Models\Setting::hotelName();
    $__size    = $size ?? 'w-20 h-20';
    $__rounded = $rounded ?? 'rounded-2xl';
    $__letter  = $letterCls ?? 'text-4xl';
@endphp

@if($__logo)
<div class="inline-flex items-center justify-center {{ $__size }} {{ $__rounded }} bg-white overflow-hidden shadow-lg">
    <img src="{{ $__logo }}" alt="شعار {{ $__name }}" class="w-full h-full object-contain">
</div>
@else
<div class="inline-flex items-center justify-center {{ $__size }} {{ $__rounded }} shadow-lg font-bold text-primary-900 {{ $__letter }}"
     style="background: linear-gradient(135deg, #D4A574, #c08d5a);">{{ mb_substr($__name, 0, 1) }}</div>
@endif
