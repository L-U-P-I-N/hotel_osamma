@php $__logo = public_path('images/hotel-logo.png'); @endphp
@if(file_exists($__logo))
<div style="text-align:center;margin-bottom:6px;">
    <img src="{{ $__logo }}" alt="شعار الفندق" style="height:52px;">
</div>
@endif
