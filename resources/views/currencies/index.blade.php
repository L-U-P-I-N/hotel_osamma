@extends('layouts.app')
@section('title', 'إدارة العملات')
@section('page-title', 'إدارة العملات')

@section('content')
<div class="max-w-3xl mx-auto space-y-5">

@if(session('success'))
<div class="p-4 bg-green-50 border border-green-200 rounded-xl text-green-800 text-sm">{{ session('success') }}</div>
@endif
@if($errors->any())
<div class="p-4 bg-red-50 border border-red-200 rounded-xl text-sm text-red-700">{{ $errors->first() }}</div>
@endif

<!-- Header -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
    <div class="flex items-start gap-3">
        <div class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center flex-shrink-0">
            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div>
            <h3 class="font-semibold text-gray-800">أسعار الصرف</h3>
            <p class="text-sm text-gray-500 mt-0.5">أسعار الصرف للمعلومية فقط — كل عملة تُعرض بشكل مستقل في التقارير بدون تحويل تلقائي</p>
        </div>
    </div>
</div>

<!-- Currencies Table -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    @foreach($currencies as $currency)
    <div class="border-b border-gray-100 last:border-0 p-5">
        <form method="POST" action="{{ route('currencies.update', $currency) }}" class="flex flex-wrap items-end gap-4">
            @csrf @method('PUT')

            <!-- Currency Info -->
            <div class="flex items-center gap-3 flex-shrink-0 w-48">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center font-bold text-sm
                    {{ $currency->is_primary ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                    {{ $currency->symbol }}
                </div>
                <div>
                    <div class="font-semibold text-gray-800 text-sm">{{ $currency->name }}</div>
                    <div class="text-xs text-gray-400">{{ $currency->code }}</div>
                    @if($currency->is_primary)
                    <span class="text-xs text-green-600 font-medium">العملة الأساسية</span>
                    @endif
                </div>
            </div>

            <!-- Exchange Rate -->
            <div class="flex-1 min-w-40">
                <label class="block text-xs font-medium text-gray-600 mb-1">
                    سعر الصرف مقابل الريال اليمني
                </label>
                @if($currency->is_primary)
                <div class="w-full border border-gray-200 bg-gray-50 rounded-lg px-4 py-2.5 text-sm text-gray-400">
                    1.00 (ثابت)
                </div>
                @else
                <input type="number" name="exchange_rate_to_yer"
                       value="{{ number_format($currency->exchange_rate_to_yer, 2, '.', '') }}"
                       step="0.01" min="0.01"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 outline-none">
                @endif
            </div>

            <!-- Active Toggle -->
            <div class="flex items-center gap-2">
                @if(!$currency->is_primary)
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1"
                           {{ $currency->is_active ? 'checked' : '' }}
                           class="w-4 h-4 text-primary-600 rounded">
                    <span class="text-sm text-gray-600">مفعّلة</span>
                </label>
                @else
                <span class="text-xs text-gray-400">مفعّلة دائماً</span>
                @endif
            </div>

            <!-- Status Badge -->
            <div>
                @if($currency->is_active)
                <span class="px-2 py-1 bg-green-100 text-green-700 text-xs rounded-full font-medium">نشطة</span>
                @else
                <span class="px-2 py-1 bg-gray-100 text-gray-500 text-xs rounded-full font-medium">معطّلة</span>
                @endif
            </div>

            @if(!$currency->is_primary)
            <button type="submit"
                    class="px-5 py-2.5 text-white rounded-lg text-sm font-medium transition hover:opacity-90"
                    style="background:#0F4C75;">
                حفظ
            </button>
            @endif
        </form>
    </div>
    @endforeach
</div>

<!-- Info Box -->
<div class="bg-blue-50 border border-blue-200 rounded-xl p-4 text-sm text-blue-800">
    <div class="flex items-start gap-2">
        <svg class="w-4 h-4 text-blue-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
        <div>
            <strong>ملاحظة:</strong> أسعار الصرف للعرض والمعلومية فقط. كل عملة تظهر منفردة في التقارير والإيرادات — لا يتم تحويل العملات تلقائياً.
        </div>
    </div>
</div>

</div>
@endsection
