@extends('layouts.app')
@section('title', 'إعدادات التسعير')
@section('page-title', 'إعدادات التسعير والخصومات')

@section('content')
<div class="max-w-4xl mx-auto space-y-5">

    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 text-sm text-blue-800">
        <p class="font-semibold mb-1">لماذا هذه الصفحة؟</p>
        <p>الأسعار التي تحددها هنا هي الوحيدة المسموح بها للموظف عند إضافة حجز أو تعديله.
           أي مبلغ خارج النطاق يُرفض من الخادم، ولا يستطيع الموظف تعديل السعر أصلاً
           إلا إذا مُنح صلاحية «تعديل سعر الليلة» من صفحة صلاحيات المستخدم.</p>
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

    <!-- Discount ceiling -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h3 class="font-semibold text-gray-700 mb-1">سقف الخصم</h3>
        <p class="text-xs text-gray-500 mb-4">
            أقصى نسبة خصم يمكن للموظف منحها على الحجز الواحد. القيمة <strong>0</strong> توقف زر الخصم تماماً.
        </p>
        <form method="POST" action="{{ route('pricing.discount.update') }}" class="flex flex-wrap items-end gap-3">
            @csrf @method('PUT')
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">النسبة القصوى (%)</label>
                <input type="number" name="max_discount_percent" step="0.01" min="0" max="100" required
                       value="{{ old('max_discount_percent', $hotel->max_discount_percent) }}"
                       class="w-40 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 outline-none">
            </div>
            <button type="submit" class="px-5 py-2 text-white rounded-lg text-sm font-medium transition" style="background:#0F4C75;">
                حفظ سقف الخصم
            </button>
            <p class="text-xs text-gray-400 basis-full">
                الحالة الآن:
                @if((float)$hotel->max_discount_percent > 0)
                    <span class="text-green-600 font-medium">الخصم مفعّل حتى {{ rtrim(rtrim(number_format($hotel->max_discount_percent, 2), '0'), '.') }}%</span>
                @else
                    <span class="text-red-600 font-medium">الخصم موقوف</span>
                @endif
            </p>
        </form>
    </div>

    <!-- Price ranges per room type -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h3 class="font-semibold text-gray-700 mb-1">نطاق سعر الليلة لكل نوع</h3>
        <p class="text-xs text-gray-500 mb-4">
            «السعر الأساسي» هو ما يُقترح تلقائياً على الموظف، ويجب أن يقع بين أقل وأعلى سعر.
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
                    <span class="text-xs text-gray-400">السعة القصوى: {{ $type->max_capacity }}</span>
                </div>

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
            </form>
            @endforeach
        </div>
    </div>

</div>
@endsection
