@extends('layouts.app')
@section('title', 'الإعدادات')
@section('page-title', 'إعدادات النظام')

@section('content')
<div class="max-w-2xl mx-auto space-y-5" dir="rtl">

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-700 rounded-lg px-4 py-3 text-sm">{{ session('success') }}</div>
    @endif

    @if($errors->any())
    <div class="p-4 bg-red-50 border border-red-200 rounded-lg">
        <ul class="list-disc list-inside space-y-1">
            @foreach($errors->all() as $error)
            <li class="text-sm text-red-600">{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    {{-- شعار الفندق --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="mb-5 pb-4 border-b border-gray-100">
            <h2 class="text-lg font-semibold text-gray-800">شعار الفندق</h2>
            <p class="text-sm text-gray-500 mt-1">
                يظهر في القائمة الجانبية وفي كل تصديرات PDF (التقارير، الفواتير، المستلمات، تقرير الجهات الحكومية…).
            </p>
        </div>

        <div class="flex items-start gap-6 flex-wrap">
            {{-- المعاينة الحالية --}}
            <div class="flex flex-col items-center gap-2">
                <div class="w-32 h-32 rounded-2xl border-2 border-dashed border-gray-200 bg-gray-50 flex items-center justify-center overflow-hidden">
                    @if($hotelLogo)
                    <img src="{{ $hotelLogo }}" alt="شعار الفندق" class="w-full h-full object-contain p-2">
                    @else
                    <div class="text-center text-gray-300 px-2">
                        <svg class="w-8 h-8 mx-auto mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <span class="text-xs">لا يوجد شعار</span>
                    </div>
                    @endif
                </div>
                <span class="text-xs text-gray-400">{{ $hotelLogo ? 'الشعار الحالي' : 'غير مرفوع' }}</span>
            </div>

            {{-- رفع شعار جديد --}}
            <div class="flex-1 min-w-64 space-y-4">
                <form method="POST" action="{{ route('settings.logo.update') }}" enctype="multipart/form-data" class="space-y-3">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">اختر صورة الشعار</label>
                        <input type="file" name="logo" accept="image/png,image/jpeg,image/webp" required
                               class="w-full text-sm text-gray-600 border border-gray-300 rounded-lg px-3 py-2
                                      file:mr-3 file:ml-3 file:py-1.5 file:px-4 file:rounded-lg file:border-0
                                      file:text-sm file:font-semibold file:bg-primary-50 file:text-primary-800
                                      hover:file:bg-primary-100 cursor-pointer">
                        <p class="text-xs text-gray-400 mt-1.5">
                            PNG أو JPG أو WEBP — بحد أقصى 2 ميجابايت. يُفضَّل شعار مربّع بخلفية شفافة (PNG).
                        </p>
                    </div>
                    <button type="submit" class="px-5 py-2.5 text-white rounded-lg text-sm font-semibold transition" style="background:#0F4C75;">
                        حفظ الشعار
                    </button>
                </form>

                @if($hotelLogo)
                <form method="POST" action="{{ route('settings.logo.remove') }}"
                      onsubmit="return confirm('حذف شعار الفندق؟ سيختفي من النظام وكل تصديرات PDF.')">
                    @csrf @method('DELETE')
                    <button type="submit" class="text-sm text-red-600 hover:text-red-700 underline">حذف الشعار الحالي</button>
                </form>
                @endif
            </div>
        </div>

        <div class="mt-5 pt-4 border-t border-gray-100 text-xs text-gray-500 leading-relaxed">
            يُحفظ الشعار داخل قاعدة البيانات لا كملف على القرص — ليبقى بعد كل تحديث للنظام على الاستضافة،
            ويظهر مباشرةً في ملفات PDF المُصدَّرة دون أي إعداد إضافي.
        </div>
    </div>
</div>
@endsection
