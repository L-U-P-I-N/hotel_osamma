{{-- حقول مرافق مشتركة بين نافذتَي "إضافة مرافق" و"تعديل مرافق" — $prefix يميّز
     معرّفات كل نافذة (add_/edit_) حتى لا تتعارض عند وجود النافذتين في نفس الصفحة. --}}
<div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">الاسم الكامل <span class="text-red-500">*</span></label>
    <input type="text" id="{{ $prefix }}_full_name" name="full_name" required maxlength="255"
           class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-violet-400 outline-none">
</div>

<div class="grid grid-cols-2 gap-3">
    <div>
        <label class="block text-sm font-semibold text-gray-700 mb-2">صلة القرابة</label>
        <select id="{{ $prefix }}_relationship" name="relationship"
                onchange="document.getElementById('{{ $prefix }}_marriage_doc_wrap').style.display = this.value === 'wife' ? 'block' : 'none'"
                class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-violet-400 outline-none bg-white">
            <option value="wife">زوجة</option>
            <option value="son">ابن</option>
            <option value="daughter">ابنة</option>
            <option value="brother">أخ</option>
            <option value="sister">أخت</option>
            <option value="father">أب</option>
            <option value="mother">أم</option>
            <option value="other" selected>أخرى</option>
        </select>
    </div>
    <div>
        <label class="block text-sm font-semibold text-gray-700 mb-2">الجنسية</label>
        <input type="text" id="{{ $prefix }}_nationality" name="nationality" maxlength="100"
               class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-violet-400 outline-none">
    </div>
</div>

<div class="grid grid-cols-2 gap-3">
    <div>
        <label class="block text-sm font-semibold text-gray-700 mb-2">نوع الهوية</label>
        <select id="{{ $prefix }}_id_type" name="id_type"
                class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-violet-400 outline-none bg-white">
            <option value="national_id" selected>هوية وطنية</option>
            <option value="passport">جواز سفر</option>
            <option value="residence">إقامة</option>
        </select>
    </div>
    <div>
        <label class="block text-sm font-semibold text-gray-700 mb-2">رقم الهوية</label>
        <input type="text" id="{{ $prefix }}_id_number" name="id_number" maxlength="50"
               class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-violet-400 outline-none">
    </div>
</div>

<div class="grid grid-cols-2 gap-3">
    <div>
        <label class="block text-sm font-semibold text-gray-700 mb-2">جهة الإصدار</label>
        <input type="text" id="{{ $prefix }}_id_issuer" name="id_issuer" maxlength="100"
               class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-violet-400 outline-none">
    </div>
    <div>
        <label class="block text-sm font-semibold text-gray-700 mb-2">تاريخ الإصدار</label>
        <input type="date" id="{{ $prefix }}_id_issue_date" name="id_issue_date"
               class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-violet-400 outline-none">
    </div>
</div>

<div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">صورة الهوية <span class="text-gray-400 font-normal">(اختياري)</span></label>
    <input type="file" name="id_image" accept="image/*,.pdf"
           class="w-full text-sm text-gray-600 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:bg-violet-50 file:text-violet-700 hover:file:bg-violet-100">
</div>

<div id="{{ $prefix }}_marriage_doc_wrap" style="display:none">
    <label class="block text-sm font-semibold text-gray-700 mb-2">وثيقة الزواج <span class="text-gray-400 font-normal">(للزوجة، اختياري)</span></label>
    <input type="file" name="marriage_doc" accept="image/*,.pdf"
           class="w-full text-sm text-gray-600 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:bg-red-50 file:text-red-700 hover:file:bg-red-100">
</div>
