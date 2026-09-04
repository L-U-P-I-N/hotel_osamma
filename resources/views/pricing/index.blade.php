@extends('layouts.app')
@section('title', 'إعدادات التسعير')
@section('page-title', 'إعدادات التسعير')

@section('content')
<div class="max-w-4xl mx-auto space-y-5">

    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 text-sm text-blue-800">
        <p class="font-semibold mb-1">ما فائدة هذه الصفحة؟</p>
        <p>النطاق الذي تحدده هنا هو الوحيد المسموح للموظف عند إدخال سعر الليلة —
           في الحجز الجديد، وتعديل الحجز، والتجديد، وإعادة التسعير، وتعديل فترات الإقامة.
           أي مبلغ خارج النطاق يرفضه الخادم.</p>
        <p class="mt-1">الموظف الذي لا يملك صلاحية <strong>«تعديل سعر الغرفة»</strong> لا يستطيع تغيير السعر أصلاً،
           ويُلزَم بالسعر الافتراضي للغرفة.</p>
        <p class="mt-1">قسم الجناح (A أو B) غرفة كأي غرفة ويأخذ نطاق نوعها من الجدول أدناه.
           أما الجناح كاملاً (غرفتان) فله نطاق واحد يسري على كل الأجنحة — تضبطه في البطاقة التالية،
           لأن الأقسام تُصنَّف غرفاً عادية فلا ينفع ربط النطاق بنوع الغرفة.</p>
    </div>

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 rounded-xl p-4 text-sm text-green-800">{{ session('success') }}</div>
    @endif

    @if($errors->any())
    <div class="bg-red-50 border border-red-200 rounded-xl p-4">
        <ul class="list-disc list-inside space-y-1">
            @foreach($errors->all() as $error)
            <li class="text-sm text-red-600">{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h3 class="font-semibold text-gray-700 mb-1">نطاق سعر الجناح كاملاً (غرفتان)</h3>
        <p class="text-xs text-gray-500 mb-4">
            يُفرض عند حجز الجناح بقسميه معاً، ويسري على <strong>كل</strong> الأجنحة مهما كان تصنيف أقسامها.
            @unless($suiteRange)
            <span class="text-amber-700">غير مضبوط حالياً — يُحتسب مؤقتاً كضِعف نطاق نوع الغرفة.</span>
            @endunless
        </p>

        <form method="POST" action="{{ route('pricing.suiteRange.update') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-3">
            @csrf @method('PUT')
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">أقل سعر للجناح / ليلة</label>
                <input type="number" name="suite_min_price" step="0.01" min="1" required
                       value="{{ old('suite_min_price', $suiteRange[0] ?? '') }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 outline-none">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">أعلى سعر للجناح / ليلة</label>
                <input type="number" name="suite_max_price" step="0.01" min="1" required
                       value="{{ old('suite_max_price', $suiteRange[1] ?? '') }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 outline-none">
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full px-4 py-2 text-white rounded-lg text-sm font-medium transition" style="background:#0F4C75;">
                    حفظ نطاق الجناح
                </button>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h3 class="font-semibold text-gray-700 mb-1">نطاق سعر الليلة لكل نوع</h3>
        <p class="text-xs text-gray-500 mb-4">
            «السعر الأساسي» هو ما يُقترح على الموظف، ويجب أن يقع داخل النطاق.
            لتثبيت السعر تماماً اجعل أقل سعر = أعلى سعر.
        </p>

        @if($roomTypes->isEmpty())
        <p class="text-sm text-gray-400 py-6 text-center">لا توجد أنواع غرف معرّفة بعد.</p>
        @endif

        <div class="space-y-4">
            @foreach($roomTypes as $type)
            <form method="POST" action="{{ route('pricing.roomType.update', $type) }}"
                  class="border border-gray-200 rounded-xl p-4">
                @csrf @method('PUT')
                <div class="flex items-center justify-between mb-3 flex-wrap gap-2">
                    <div>
                        <span class="font-semibold text-gray-800">{{ $type->name }}</span>
                        <span class="text-xs text-gray-400 mr-2">{{ $type->rooms_count }} غرفة</span>
                    </div>
                    @unless($type->hasExplicitBounds())
                    <span class="text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-2 py-1">
                        النطاق غير مضبوط — السعر مثبّت على الأساسي حالياً
                    </span>
                    @endunless
                </div>

                @php $isSuiteType = in_array($type->id, $suiteTypeIds ?? []); @endphp

                <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">أقل سعر / ليلة</label>
                        <input type="number" name="min_price" step="0.01" min="1" required
                               value="{{ old('min_price', $type->effective_min_price) }}"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">السعر الأساسي</label>
                        <input type="number" name="base_price" step="0.01" min="1" required
                               value="{{ old('base_price', $type->base_price) }}"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">أعلى سعر / ليلة</label>
                        <input type="number" name="max_price" step="0.01" min="1" required
                               value="{{ old('max_price', $type->effective_max_price) }}"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 outline-none">
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="w-full px-4 py-2 text-white rounded-lg text-sm font-medium transition" style="background:#0F4C75;">
                            حفظ
                        </button>
                    </div>
                </div>

                @if($isSuiteType)
                <p class="mt-3 text-xs text-gray-500 bg-gray-50 border border-gray-200 rounded-lg px-3 py-2">
                    هذا النوع فيه أقسام أجنحة — هذا النطاق يحكم حجز القسم الواحد.
                    حجز الجناح كاملاً يحكمه نطاق الجناح في البطاقة أعلى الصفحة.
                </p>
                @endif
            </form>
            @endforeach
        </div>
    </div>

</div>
@endsection
