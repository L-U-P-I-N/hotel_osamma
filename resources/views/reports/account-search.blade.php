@extends('layouts.app')
@section('title', 'كشف الحسابات')
@section('page-title', 'كشف الحسابات')

@section('content')
<div class="space-y-5" dir="rtl">

    <div>
        <h2 class="text-2xl font-black text-gray-800">كشف الحسابات</h2>
        <p class="text-gray-500 text-sm mt-1">
            ابحث بالاسم عن أي شخص له علاقة مالية بالفندق — نزيل، مرافق، موظف، أو مستخدم نظام —
            وافتح كشف حسابه الكامل (الحجوزات، الفواتير، الدفعات، الراتب والسحوبات، أو حركة الصندوق).
        </p>
    </div>

    @include('reports.partials.account-search-box', [
        'action'      => route('reports.accountSearch'),
        'placeholder' => 'اكتب اسم النزيل، المرافق، الموظف، أو المستخدم...',
    ])

    @include('reports.partials.account-search-results')
</div>
@endsection
