{{--
    موانع نسخ شكلية بطلب المالك: تعطيل الزر الأيمن واختصارات أدوات المطوّر.

    تنبيه صريح: هذه ليست حماية. أي شخص يستطيع حفظ الصفحة أو فتح أدوات
    المطوّر من قائمة المتصفح أو قراءة الصفحة بأمر واحد من الطرفية. الحماية
    الحقيقية في الخادم: كود PHP لا يصل للمتصفح إطلاقاً، والصلاحيات وتحديد
    محاولات الدخول وترويسات الحماية هي ما يمنع الاختراق فعلاً.

    استُثنيت حقول الإدخال والنصوص المحدَّدة كي لا تتعطّل مهام الموظفين
    اليومية (لصق رقم هوية، نسخ رقم حجز لإرساله للنزيل).
--}}
<script>
(function () {
    const isEditable = (el) => el && (
        el.matches('input, textarea, select, [contenteditable="true"]') ||
        el.closest('input, textarea, select, [contenteditable="true"]')
    );

    // الزر الأيمن: يبقى عاملاً داخل الحقول وعلى أي نص يحدّده الموظف
    document.addEventListener('contextmenu', function (e) {
        if (isEditable(e.target)) return;
        if (window.getSelection && String(window.getSelection())) return;
        e.preventDefault();
    });

    document.addEventListener('keydown', function (e) {
        const key = (e.key || '').toLowerCase();

        if (key === 'f12') { e.preventDefault(); return; }

        // Ctrl/Cmd+Shift+I/J/C — أدوات المطوّر
        if ((e.ctrlKey || e.metaKey) && e.shiftKey && ['i', 'j', 'c'].includes(key)) {
            e.preventDefault(); return;
        }

        // Ctrl+U (مصدر الصفحة) و Ctrl+S (حفظ الصفحة) — والنسخ واللصق يبقيان
        if ((e.ctrlKey || e.metaKey) && ['u', 's'].includes(key) && !isEditable(e.target)) {
            e.preventDefault();
        }
    });
})();
</script>
