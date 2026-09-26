{{--
    تهيئة الواجهة للشاشات الصغيرة: الجداول كبطاقات، وأشرطة الأزرار تلتفّ.

    جداول النظام مصمَّمة لشاشة حاسوب: على جوال بعرض 390px ينكسر اسم النزيل
    على أربعة أسطر، ويُقتطع التاريخ عند الحافة، وتختفي بقية الأعمدة داخل
    تمرير أفقي لا يلاحظه الموظف. هنا يتحوّل كل صفّ إلى بطاقة مستقلة، كل
    خانة فيها بعنوان عمودها.

    التسمية تلقائية: السكربت يقرأ رؤوس الجدول ويضعها في data-label لكل خانة،
    فلا حاجة لتعديل عشرات الجداول يدوياً. لاستثناء جدول أضف data-no-cards.
--}}
<style>
@media (max-width: 767px) {
    table[data-cards] { display: block; width: 100%; }
    table[data-cards] thead { display: none; }
    table[data-cards] tbody,
    table[data-cards] tr,
    table[data-cards] td { display: block; width: auto; }

    table[data-cards] tbody tr {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        margin-bottom: 10px;
        padding: 4px 2px;
        box-shadow: 0 1px 3px rgba(0,0,0,.05);
        overflow: hidden;
    }
    .dark table[data-cards] tbody tr { background: #1f2937; border-color: #374151; }

    table[data-cards] tbody td {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        padding: 7px 12px !important;
        border: 0 !important;
        text-align: start !important;
        white-space: normal !important;
        min-height: 0;
    }
    table[data-cards] tbody td + td { border-top: 1px solid #f3f4f6 !important; }
    .dark table[data-cards] tbody td + td { border-top-color: #374151 !important; }

    /* عنوان العمود أمام قيمته */
    table[data-cards] tbody td::before {
        content: attr(data-label);
        font-size: 11px;
        font-weight: 700;
        color: #6b7280;
        flex: 0 0 auto;
        max-width: 42%;
    }
    .dark table[data-cards] tbody td::before { color: #9ca3af; }

    /* خانة بلا عنوان (الأزرار عادةً) تأخذ السطر كاملاً */
    table[data-cards] tbody td[data-label=""]::before,
    table[data-cards] tbody td:not([data-label])::before { content: none; }
    table[data-cards] tbody td[data-label=""],
    table[data-cards] tbody td:not([data-label]) { justify-content: flex-start; flex-wrap: wrap; }

    /* خانة فارغة لا تضيف شيئاً للبطاقة وتطيلها بلا داعٍ */
    table[data-cards] tbody td[data-empty] { display: none; }

    /* صفّ «لا توجد بيانات» يبقى سطراً واحداً موسّطاً */
    table[data-cards] tbody td[colspan] { justify-content: center; text-align: center !important; }
    table[data-cards] tbody td[colspan]::before { content: none; }

    /* الجدول داخل حاوية تمرير: لا حاجة للتمرير بعد التحويل لبطاقات */
    .overflow-x-auto:has(> table[data-cards]) { overflow-x: visible; }

    /* مساحة لمس مريحة لكل زر ورابط داخل البطاقات والأشرطة */
    table[data-cards] td a,
    table[data-cards] td button { min-height: 36px; display: inline-flex; align-items: center; }

    /*
        أشرطة الأزرار مكتوبة بـ flex بلا التفاف لأنها صُمّمت لشاشة عريضة،
        فيخرج آخر زر خارج الشاشة على الجوال. الالتفاف أفضل من الاقتطاع دائماً
        على شاشة صغيرة؛ ومن أراد صفاً واحداً يصرّح بـ flex-nowrap.
    */
    main .flex:not(.flex-nowrap):not(.flex-col) { flex-wrap: wrap; }

    /* حقول النموذج تملأ العرض بدل أن تتزاحم */
    main input[type="date"],
    main input[type="datetime-local"],
    main input[type="time"] { min-width: 0; }
}
</style>

<script>
(function () {
    /**
     * وسم الخانات بعناوين أعمدتها. يُنفَّذ مرة عند التحميل، ومرة بعد أي
     * تحديث فوري للجدول (نتائج البحث، تحديث صفّ بعد تجديد) عبر MutationObserver.
     */
    function labelTables(root) {
        (root || document).querySelectorAll('table:not([data-no-cards])').forEach(function (table) {
            var headers = [].map.call(
                table.querySelectorAll('thead th'),
                function (th) {
                    var text = (th.textContent || '').trim();

                    // رأس بلا حروف (# أو أيقونة أو رمز) ليس عنواناً مفيداً في
                    // البطاقة، فنتركه بلا تسمية بدل إظهار رمز غامض
                    return /[\u0600-\u06FFa-zA-Z]/.test(text) ? text : '';
                }
            );

            // جدول بلا رؤوس ليس جدول بيانات (تخطيط أو ملخّص) فنتركه كما هو
            if (!headers.length) return;

            table.setAttribute('data-cards', '');

            [].forEach.call(table.querySelectorAll('tbody tr'), function (row) {
                var index = 0;
                [].forEach.call(row.children, function (cell) {
                    if (cell.tagName !== 'TD') return;

                    if (!cell.hasAttribute('data-label')) {
                        cell.setAttribute('data-label', headers[index] || '');
                    }

                    // خانة بلا محتوى (أو شرطة نائبة) تُخفى في البطاقة كي لا
                    // تطول بصفوف فارغة؛ وجود عنصر داخلها يعني محتوى مرئياً
                    var text = (cell.textContent || '').replace(/[\s—–\-]/g, '');
                    if (!text && !cell.querySelector('*')) {
                        cell.setAttribute('data-empty', '');
                    } else {
                        cell.removeAttribute('data-empty');
                    }

                    index += cell.colSpan || 1;
                });
            });
        });
    }

    function boot() {
        labelTables(document);

        // الجداول التي تُعاد كتابتها بالـ JS (بحث فوري، تحديث صفّ) تُوسم من جديد
        var observer = new MutationObserver(function (mutations) {
            var touched = mutations.some(function (m) { return m.addedNodes.length; });
            if (touched) labelTables(document);
        });

        observer.observe(document.body, { childList: true, subtree: true });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
</script>
