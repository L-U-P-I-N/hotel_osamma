@extends('layouts.app')
@section('title', 'نسب الأداء المالي')
@section('page-title', 'نسب الأداء المالي')

@section('content')
<div dir="rtl">

{{-- Filter --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-5">
    <form method="GET" class="flex gap-2 items-end flex-wrap">
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">الفترة</label>
            <select name="period" class="border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-blue-400">
                <option value="month" {{ request('period', 'month') == 'month' ? 'selected' : '' }}>الشهر الحالي</option>
                <option value="quarter" {{ request('period') == 'quarter' ? 'selected' : '' }}>الربع الحالي</option>
                <option value="year" {{ request('period') == 'year' ? 'selected' : '' }}>السنة الحالية</option>
                <option value="all" {{ request('period') == 'all' ? 'selected' : '' }}>كل الوقت</option>
            </select>
        </div>
        <button type="submit" class="px-4 py-2 text-white rounded-lg text-sm bg-blue-600 hover:bg-blue-700">عرض</button>
    </form>
</div>

{{-- Main Ratios --}}
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-5">
    {{-- Profit Margin --}}
    <div class="bg-white rounded-xl shadow-sm border border-blue-100 p-5">
        <div class="flex items-center justify-between mb-3">
            <h3 class="font-semibold text-gray-700 text-sm">هامش الربح</h3>
            <div class="text-2xl">📈</div>
        </div>
        <p class="text-3xl font-bold text-blue-700">{{ $profitMargin }}%</p>
        <p class="text-xs text-gray-500 mt-2">كم من كل ريال هو ربح؟</p>
        <div class="mt-3 h-2 bg-gray-100 rounded-full overflow-hidden">
            <div class="h-full rounded-full bg-blue-500" style="width:{{ min($profitMargin, 100) }}%"></div>
        </div>
    </div>

    {{-- Cost Ratio --}}
    <div class="bg-white rounded-xl shadow-sm border border-red-100 p-5">
        <div class="flex items-center justify-between mb-3">
            <h3 class="font-semibold text-gray-700 text-sm">نسبة التكاليف</h3>
            <div class="text-2xl">💰</div>
        </div>
        <p class="text-3xl font-bold text-red-600">{{ $costRatio }}%</p>
        <p class="text-xs text-gray-500 mt-2">المصروفات من الإيراد</p>
        <div class="mt-3 h-2 bg-gray-100 rounded-full overflow-hidden">
            <div class="h-full rounded-full bg-red-500" style="width:{{ min($costRatio, 100) }}%"></div>
        </div>
    </div>

    {{-- Occupancy Rate --}}
    <div class="bg-white rounded-xl shadow-sm border border-green-100 p-5">
        <div class="flex items-center justify-between mb-3">
            <h3 class="font-semibold text-gray-700 text-sm">معدل الإشغال</h3>
            <div class="text-2xl">🏨</div>
        </div>
        <p class="text-3xl font-bold text-green-700">{{ $occupancyRate }}%</p>
        <p class="text-xs text-gray-500 mt-2">استخدام الطاقة الفندقية</p>
        <div class="mt-3 h-2 bg-gray-100 rounded-full overflow-hidden">
            <div class="h-full rounded-full bg-green-500" style="width:{{ $occupancyRate }}%"></div>
        </div>
    </div>

    {{-- Revenue per Room --}}
    <div class="bg-white rounded-xl shadow-sm border border-purple-100 p-5">
        <div class="flex items-center justify-between mb-3">
            <h3 class="font-semibold text-gray-700 text-sm">متوسط إيراد الغرفة</h3>
            <div class="text-2xl">🔑</div>
        </div>
        <p class="text-3xl font-bold text-purple-700">{{ number_format($revenuePerRoom, 0) }}</p>
        <p class="text-xs text-gray-500 mt-2">ر.ي لكل غرفة</p>
    </div>
</div>

{{-- Secondary Ratios --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-5">
    {{-- Financial Health Metrics --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <h3 class="font-semibold text-gray-700 text-sm mb-4">مؤشرات الصحة المالية</h3>
        <div class="space-y-3">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-700">متوسط إيراد الليلة (ADR)</p>
                    <p class="text-xs text-gray-500">متوسط سعر الليلة</p>
                </div>
                <p class="text-lg font-bold text-gray-800">{{ number_format($adr, 0) }} ر.ي</p>
            </div>

            <div class="h-px bg-gray-100"></div>

            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-700">الإيراد لكل نزيل</p>
                    <p class="text-xs text-gray-500">متوسط إنفاق النزيل</p>
                </div>
                <p class="text-lg font-bold text-gray-800">{{ number_format($revenuePerGuest, 0) }} ر.ي</p>
            </div>

            <div class="h-px bg-gray-100"></div>

            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-700">متوسط البقاء</p>
                    <p class="text-xs text-gray-500">عدد الليالي المتوسطة</p>
                </div>
                <p class="text-lg font-bold text-gray-800">{{ number_format($avgStay, 1) }} ليلة</p>
            </div>

            <div class="h-px bg-gray-100"></div>

            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-700">عدد الضيوف</p>
                    <p class="text-xs text-gray-500">في الفترة المحددة</p>
                </div>
                <p class="text-lg font-bold text-gray-800">{{ $guestCount }}</p>
            </div>
        </div>
    </div>

    {{-- Operational Metrics --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <h3 class="font-semibold text-gray-700 text-sm mb-4">مؤشرات التشغيل</h3>
        <div class="space-y-3">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-700">الإيراد اليومي المتوسط</p>
                    <p class="text-xs text-gray-500">دخل يومي متوسط</p>
                </div>
                <p class="text-lg font-bold text-green-700">{{ number_format($dailyRevenue, 0) }} ر.ي</p>
            </div>

            <div class="h-px bg-gray-100"></div>

            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-700">المصروف اليومي المتوسط</p>
                    <p class="text-xs text-gray-500">نفقات يومية متوسطة</p>
                </div>
                <p class="text-lg font-bold text-red-600">{{ number_format($dailyExpense, 0) }} ر.ي</p>
            </div>

            <div class="h-px bg-gray-100"></div>

            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-700">الربح اليومي المتوسط</p>
                    <p class="text-xs text-gray-500">الصافي اليومي</p>
                </div>
                <p class="text-lg font-bold {{ $dailyProfit >= 0 ? 'text-emerald-700' : 'text-red-700' }}">{{ number_format($dailyProfit, 0) }} ر.ي</p>
            </div>

            <div class="h-px bg-gray-100"></div>

            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-700">عدد الغرف المحجوزة</p>
                    <p class="text-xs text-gray-500">متوسط يومي</p>
                </div>
                <p class="text-lg font-bold text-blue-700">{{ number_format($avgOccupiedRooms, 1) }}</p>
            </div>
        </div>
    </div>
</div>

{{-- Performance Gauge --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
    <h3 class="font-semibold text-gray-700 text-sm mb-4">تقييم الأداء العام</h3>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        {{-- Profitability Score --}}
        <div class="text-center p-4 rounded-lg bg-gradient-to-br from-blue-50 to-blue-100">
            <p class="text-xs text-gray-600 mb-2">تصنيف الربحية</p>
            <div class="text-4xl font-bold text-blue-700">{{ $profitabilityScore }}/10</div>
            <p class="text-xs text-gray-600 mt-2">
                @if($profitabilityScore >= 8)
                ممتاز 🌟
                @elseif($profitabilityScore >= 6)
                جيد ✓
                @elseif($profitabilityScore >= 4)
                متوسط ⚠
                @else
                يحتاج تحسين ⚡
                @endif
            </p>
        </div>

        {{-- Efficiency Score --}}
        <div class="text-center p-4 rounded-lg bg-gradient-to-br from-green-50 to-green-100">
            <p class="text-xs text-gray-600 mb-2">تصنيف الكفاءة</p>
            <div class="text-4xl font-bold text-green-700">{{ $efficiencyScore }}/10</div>
            <p class="text-xs text-gray-600 mt-2">
                @if($efficiencyScore >= 8)
                ممتاز 🌟
                @elseif($efficiencyScore >= 6)
                جيد ✓
                @elseif($efficiencyScore >= 4)
                متوسط ⚠
                @else
                يحتاج تحسين ⚡
                @endif
            </p>
        </div>

        {{-- Overall Health --}}
        <div class="text-center p-4 rounded-lg bg-gradient-to-br from-purple-50 to-purple-100">
            <p class="text-xs text-gray-600 mb-2">الصحة المالية</p>
            <div class="text-4xl font-bold text-purple-700">{{ $overallHealth }}/10</div>
            <p class="text-xs text-gray-600 mt-2">
                @if($overallHealth >= 8)
                صحة ممتازة 🌟
                @elseif($overallHealth >= 6)
                صحة جيدة ✓
                @elseif($overallHealth >= 4)
                يحتاج عناية ⚠
                @else
                حالة حرجة ⚡
                @endif
            </p>
        </div>
    </div>
</div>

{{-- Insights --}}
<div class="bg-blue-50 border border-blue-200 rounded-xl p-5 mt-5">
    <h3 class="font-semibold text-blue-900 text-sm mb-3">💡 الرؤى والتوصيات</h3>
    <ul class="space-y-2 text-sm text-blue-800">
        @if($profitMargin < 15)
        <li>⚠️ هامش الربح منخفض. تفحص المصروفات وقم بتحسينها.</li>
        @endif
        @if($occupancyRate < 50)
        <li>⚠️ معدل الإشغال متدني. ركّز على تحسين التسويق والحجوزات.</li>
        @endif
        @if($costRatio > 60)
        <li>⚠️ نسبة التكاليف عالية جداً. قلّل النفقات أو زيّد الإيراد.</li>
        @endif
        @if($profitMargin >= 20 && $occupancyRate >= 70)
        <li>✅ الأداء ممتاز! حافظ على هذا المستوى من الكفاءة.</li>
        @endif
    </ul>
</div>

</div>
@endsection
