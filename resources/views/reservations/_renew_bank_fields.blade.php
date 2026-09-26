{{--
    حقول سند التحويل البنكي في نموذج التجديد الفوري.
    مشتركة بين جدول الحجوزات ولوحة التحكم كي لا تفترق صيغتاهما،
    وسبب وجودها هنا أصلاً أن التجديد بتحويل بنكي كان يُلزم الموظف
    بفتح صفحة تفاصيل النزيل لتسجيل السند بعد كل تجديد.

    يتطلّب أن يحمل النموذج x-data فيه: method و advance.
--}}
<template x-if="method === 'bank_transfer' && advance > 0">
    <div class="flex items-end gap-3 flex-wrap w-full rounded-lg bg-blue-50 border border-blue-100 p-2.5">
        <p class="w-full text-xs font-semibold text-blue-700">يجب تقديم واحد على الأقل: رقم المرجع أو صورة السند</p>
        <div class="flex flex-col gap-1">
            <label class="text-xs font-medium text-gray-600">رقم مرجع التحويل</label>
            <input type="text" name="bank_transfer_ref" maxlength="100"
                   class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm w-44 focus:ring-2 focus:ring-blue-400 outline-none bg-white">
        </div>
        <div class="flex flex-col gap-1">
            <label class="text-xs font-medium text-gray-600">صورة سند التحويل</label>
            <input type="file" name="bank_receipt" accept="image/*,.pdf"
                   class="text-xs text-gray-600 file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-blue-100 file:text-blue-700">
        </div>
    </div>
</template>
