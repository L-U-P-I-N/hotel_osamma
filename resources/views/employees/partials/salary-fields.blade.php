{{-- حقول الراتب: الأساسي وصرفية الطعام، ومعهما الإجمالي محسوباً لحظياً —
     فالمالك يرى حزمة راتب الموظف كاملة قبل الحفظ لا رقمين منفصلين.
     المتغيرات: $base و $food (القيم الحالية). --}}
<div class="md:col-span-2 grid grid-cols-1 md:grid-cols-3 gap-4 rounded-xl border border-gray-200 bg-gray-50 p-4"
     x-data="{ base: {{ (float) $base }}, food: {{ (float) $food }},
               get total() { return (parseFloat(this.base) || 0) + (parseFloat(this.food) || 0) },
               fmt(n) { return new Intl.NumberFormat('en-US').format(Math.round(n)) }">
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">الراتب الأساسي *</label>
        <input type="number" name="base_salary" x-model.number="base" value="{{ $base }}" step="0.01" min="0" required
               class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm outline-none focus:border-blue-400">
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">صرفية الطعام والشراب (شهرياً)</label>
        {{-- الحقل اختياري: تركه فارغاً يعني صفراً، ويُحوَّل في الخادم صراحةً --}}
        <input type="number" name="food_allowance" x-model.number="food" value="{{ $food }}" step="0.01" min="0"
               placeholder="0"
               class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm outline-none focus:border-blue-400">
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">الراتب الإجمالي</label>
        <div class="w-full rounded-lg px-4 py-2.5 text-sm font-black border"
             style="background:#ecfdf5;border-color:#a7f3d0;color:#065f46;">
            <span x-text="fmt(total)">{{ number_format((float) $base + (float) $food, 0) }}</span>
            <span class="text-xs font-normal">ر.ي</span>
        </div>
        <p class="text-xs text-gray-500 mt-1.5">الأساسي + الصرفية</p>
    </div>
    <p class="md:col-span-3 text-xs text-gray-500 -mt-1">
        صرفية الطعام والشراب مبلغ مستقل يتجدد كل شهر ويُصرف يومياً، ولا يُخصم من الراتب الأساسي إلا ما تجاوزها.
    </p>
</div>
