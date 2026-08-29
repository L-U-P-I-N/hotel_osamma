@php
    // الشعار من إعدادات النظام (data URI بقاعدة البيانات) مع سقوط تلقائي للملف
    // القديم في public/images — dompdf يقبل الاثنين في src.
    $__logo = \App\Models\Setting::hotelLogo();
    $__logoHeight = $logoHeight ?? 52;
@endphp
@if($__logo)
<div style="text-align:center;margin-bottom:6px;">
    <img src="{{ $__logo }}" alt="شعار الفندق" style="height:{{ $__logoHeight }}px;">
</div>
@endif
