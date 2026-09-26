<script>
/* =========================================================================
   العمل السريع في جدول الحجوزات: تجديد، رسم على الغرفة، وملاحظات فورية.
   كلها طلبات JSON تُحدّث صفّ الجدول مكانه — الهدف أن يُنهي الموظف عدة نزلاء
   دون انتظار تحميل صفحة بين كل واحد والذي يليه.
   ========================================================================= */
(function () {
    const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const fmt  = (n) => new Intl.NumberFormat('en-US').format(Math.round(n));

    // ————— تنبيه عائم —————
    let toastTimer = null;
    window.qaToast = function (message, ok = true) {
        const el = document.getElementById('qaToast');
        el.textContent = message;
        el.style.background = ok ? '#047857' : '#b91c1c';
        el.classList.remove('hidden');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => el.classList.add('hidden'), ok ? 4000 : 7000);
    };

    window.closeQuick = (id) => document.getElementById(id).classList.add('hidden');

    async function postJson(url, body, method = 'POST') {
        const response = await fetch(url, {
            method,
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            body,
        });

        let data = {};
        try { data = await response.json(); } catch (e) {}

        if (!response.ok) {
            // أخطاء التحقق تأتي مجمّعة؛ نعرض أولها كي تكون الرسالة مفهومة
            const first = data.errors ? Object.values(data.errors)[0][0] : null;
            throw new Error(first || data.message || 'تعذّر إتمام العملية');
        }
        return data;
    }

    /**
     * تحديث صفّ الحجز في الجدول بعد عملية ناجحة — دون إعادة تحميل الصفحة.
     * الخلايا تُحدَّد بسِمة data-cell لا بترتيبها، فالجداول تختلف أعمدتها
     * (جدول الإقامات يعرض المتبقي، وجدول الحجوزات يعرض الإجمالي).
     */
    function refreshRow(summary) {
        // ليس بالضرورة صفّ جدول: بطاقة «إقامات تنتهي قريباً» في لوحة التحكم
        // تحمل السِمات نفسها وتُحدَّث بالمنطق نفسه.
        const row = document.querySelector(`[data-row="${summary.id}"]`);
        if (!row) return;

        const set = (cell, html) => {
            const el = row.querySelector(`[data-cell="${cell}"]`);
            if (el) el.innerHTML = html;
        };

        set('checkout', summary.check_out_date ?? '—');
        set('total', summary.total_amount);
        set('paid', summary.paid_amount);

        const balance = Number(String(summary.balance).replace(/,/g, ''));
        set('balance', balance > 0
            ? `<span class="text-red-600 font-semibold text-sm">${summary.balance} ر.ي</span>`
            : '<span class="text-green-600 text-xs">مسدد</span>');

        const badge = row.querySelector('[data-cell="payment"] span');
        if (badge) {
            badge.textContent = summary.payment_status_label;
            badge.className = 'inline-flex px-2 py-0.5 rounded-full text-xs font-medium ' + ({
                unpaid:  'bg-red-100 text-red-800',
                partial: 'bg-yellow-100 text-yellow-800',
                paid:    'bg-green-100 text-green-800',
                deferred:'bg-purple-100 text-purple-800',
            }[summary.payment_status] || 'bg-gray-100 text-gray-700');
        }

        // ومضة خضراء تؤكّد بصرياً أي صفّ تغيّر
        row.style.transition = 'background-color .6s';
        row.style.backgroundColor = '#d1fae5';
        setTimeout(() => { row.style.backgroundColor = ''; }, 1200);
    }

    /* ————————————————— تجديد الإقامة ————————————————— */
    let renewId = null, renewFrom = null;

    window.openRenew = function (id, guest, room, currentCheckout, pricePerNight) {
        renewId   = id;
        renewFrom = currentCheckout;
        document.getElementById('renewQuickWho').textContent =
            `${guest || 'نزيل'} — غرفة ${room} — الخروج الحالي ${currentCheckout}`;
        document.getElementById('renewQuickPrice').value   = pricePerNight || 0;
        document.getElementById('renewQuickNights').value  = 1;
        document.getElementById('renewQuickAdvance').value = '';
        document.getElementById('renewQuickBankRef').value  = '';
        document.getElementById('renewQuickReceipt').value  = '';
        document.getElementById('renewQuickBankBox').classList.add('hidden');
        setNights(1);
        document.getElementById('renewQuickModal').classList.remove('hidden');
        document.getElementById('renewQuickNights').focus();
    };

    function setNights(nights) {
        const base = new Date(renewFrom + 'T00:00:00');
        base.setDate(base.getDate() + Math.max(1, nights));
        document.getElementById('renewQuickDate').value = base.toISOString().slice(0, 10);
        syncRenewQuick();
    }

    window.bumpNights = function (delta) {
        const input = document.getElementById('renewQuickNights');
        input.value = Math.max(1, (parseInt(input.value) || 1) + delta);
        setNights(parseInt(input.value));
    };

    window.syncRenewQuickFromDate = function () {
        const target = new Date(document.getElementById('renewQuickDate').value + 'T00:00:00');
        const from   = new Date(renewFrom + 'T00:00:00');
        const nights = Math.round((target - from) / 86400000);
        document.getElementById('renewQuickNights').value = Math.max(1, nights);
        syncRenewQuick();
    };

    window.syncRenewQuick = function () {
        const nights  = Math.max(1, parseInt(document.getElementById('renewQuickNights').value) || 1);
        const price   = parseFloat(document.getElementById('renewQuickPrice').value) || 0;
        const advance = parseFloat(document.getElementById('renewQuickAdvance').value) || 0;

        document.getElementById('renewQuickTotal').textContent = fmt(nights * price) + ' ر.ي';
        document.getElementById('renewQuickPayBox').classList.toggle('hidden', advance <= 0);

        // حقول السند تظهر فقط عند تحويل بنكي بدفعة فعلية — وتُستكمل هنا،
        // فلا يُضطر الموظف لفتح صفحة التفاصيل لتسجيل السند.
        const needsReceipt = document.getElementById('renewQuickMethod').value === 'bank_transfer' && advance > 0;
        document.getElementById('renewQuickBankBox').classList.toggle('hidden', !needsReceipt);
    };

    document.getElementById('renewQuickForm')?.addEventListener('submit', async function (e) {
        e.preventDefault();
        const button = document.getElementById('renewQuickSubmit');
        button.disabled = true;
        button.textContent = 'جارٍ التجديد…';

        try {
            const data = await postJson(`/reservations/${renewId}/renew`, new FormData(this));
            closeQuick('renewQuickModal');
            qaToast(data.message);
            refreshRow(data.reservation);
        } catch (error) {
            qaToast(error.message, false);
        } finally {
            button.disabled = false;
            button.textContent = 'تأكيد التجديد';
        }
    });

    /**
     * نموذج التجديد المفرود داخل صفّ جدول الإقامات: يُرسَل عبر JSON كذلك،
     * فلا يُنقل الموظف إلى صفحة التفاصيل بعد كل تجديد.
     */
    document.querySelectorAll('form[data-inline-renew]').forEach(function (form) {
        form.addEventListener('submit', async function (e) {
            e.preventDefault();
            const button = form.querySelector('button[type="submit"]');
            const label  = button?.textContent;
            if (button) { button.disabled = true; button.textContent = 'جارٍ التجديد…'; }

            try {
                const data = await postJson(form.action, new FormData(form));
                qaToast(data.message);
                refreshRow(data.reservation);
                form.reset();

                // طيّ لوحة التجديد بعد النجاح كي يظهر الصفّ المحدَّث تحتها.
                // اللوحة صفّ جدول في صفحة الحجوزات وقسم داخل بطاقة في لوحة التحكم.
                const panel = form.closest('[data-renew-panel]') || form.closest('tr');
                if (panel) panel.style.display = 'none';
            } catch (error) {
                qaToast(error.message, false);
            } finally {
                if (button) { button.disabled = false; button.textContent = label; }
            }
        });
    });

    /* ————————————————— رسم على الغرفة ————————————————— */
    let chargeId = null;

    window.openCharge = function (id, guest, room) {
        chargeId = id;
        document.getElementById('chargeQuickWho').textContent = `${guest || 'نزيل'} — غرفة ${room}`;
        document.getElementById('chargeQuickForm').reset();
        document.getElementById('chargeQuickModal').classList.remove('hidden');
    };

    document.getElementById('chargeQuickForm')?.addEventListener('submit', async function (e) {
        e.preventDefault();
        const button = document.getElementById('chargeQuickSubmit');
        button.disabled = true;
        button.textContent = 'جارٍ الإضافة…';

        try {
            const data = await postJson(`/reservations/${chargeId}/hotel-charge`, new FormData(this));
            closeQuick('chargeQuickModal');
            qaToast(data.message);
            refreshRow(data.reservation);
        } catch (error) {
            qaToast(error.message, false);
        } finally {
            button.disabled = false;
            button.textContent = 'إضافة الرسم';
        }
    });

    /* ————————————————— الملاحظات الفورية ————————————————— */
    let notesId = null, notesCollapsed = false;

    window.openNotes = async function (id, button) {
        notesId = id;
        const popover = document.getElementById('notesPopover');
        document.getElementById('notesResId').textContent = '#' + id;

        // يُفتح بمحاذاة الأيقونة، ويُزاح للأعلى إن ضاقت المسافة أسفل الشاشة
        const box = button.getBoundingClientRect();
        popover.classList.remove('hidden');
        const height = popover.offsetHeight || 320;
        popover.style.top  = (box.bottom + height > window.innerHeight ? Math.max(8, box.top - height - 6) : box.bottom + 6) + 'px';
        popover.style.left = Math.max(8, Math.min(box.left, window.innerWidth - 336)) + 'px';

        document.getElementById('notesList').innerHTML =
            '<p class="p-4 text-center text-xs text-gray-400">جارٍ التحميل…</p>';

        try {
            const response = await fetch(`/reservations/${id}/notes`, { headers: { 'Accept': 'application/json' } });
            renderNotes((await response.json()).notes);
        } catch (e) {
            document.getElementById('notesList').innerHTML =
                '<p class="p-4 text-center text-xs text-red-500">تعذّر تحميل الملاحظات</p>';
        }
    };

    window.closeNotes = () => document.getElementById('notesPopover').classList.add('hidden');

    window.toggleNotesCollapse = function () {
        notesCollapsed = !notesCollapsed;
        document.getElementById('notesBody').classList.toggle('hidden', notesCollapsed);
        document.getElementById('notesCollapseBtn').textContent = notesCollapsed ? '+' : '–';
        document.getElementById('notesCollapseBtn').title = notesCollapsed ? 'توسيع' : 'تصغير';
    };

    const NOTE_STYLES = {
        red:   'border-r-4 border-red-400 bg-red-50',
        amber: 'border-r-4 border-amber-400 bg-amber-50',
        blue:  'border-r-4 border-blue-300 bg-blue-50',
    };

    function renderNotes(payload) {
        const list = document.getElementById('notesList');

        list.innerHTML = payload.items.length === 0
            ? '<p class="p-4 text-center text-xs text-gray-400">لا توجد ملاحظات على هذا الحجز</p>'
            : payload.items.map((note) => `
                <div class="p-3 ${note.resolved ? 'opacity-50' : (NOTE_STYLES[note.color] || '')}">
                    <div class="flex items-start justify-between gap-2">
                        <span class="text-[11px] font-bold text-gray-500">${note.type_label}</span>
                        <div class="flex items-center gap-1 flex-shrink-0">
                            <button type="button" onclick="toggleNoteResolved(${note.id}, ${note.resolved ? 'false' : 'true'})"
                                    class="text-[11px] text-emerald-600 hover:underline">${note.resolved ? 'إعادة فتح' : 'تمّت'}</button>
                            <button type="button" onclick="editNote(${note.id}, this)" class="text-[11px] text-blue-600 hover:underline">تعديل</button>
                            <button type="button" onclick="deleteNote(${note.id})" class="text-[11px] text-red-600 hover:underline">حذف</button>
                        </div>
                    </div>
                    <p class="text-xs text-gray-800 mt-1 whitespace-pre-line" data-note-body="${note.id}">${escapeHtml(note.body)}</p>
                    <p class="text-[10px] text-gray-400 mt-1">${note.author || '—'} · ${note.created_at}</p>
                </div>`).join('');

        updateNoteIcon(payload);
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /** لون الأيقونة وعدّادها يتبعان الملاحظات القائمة بعد كل تغيير. */
    function updateNoteIcon(payload) {
        const button = document.querySelector(`[data-notes-btn="${notesId}"]`);
        if (!button) return;

        const classes = {
            red:   'bg-red-100 text-red-600 hover:bg-red-200',
            amber: 'bg-amber-100 text-amber-600 hover:bg-amber-200',
            blue:  'bg-blue-100 text-blue-600 hover:bg-blue-200',
            none:  'text-gray-300 hover:bg-gray-100 hover:text-gray-500',
        }[payload.color];

        button.className = 'relative w-8 h-8 rounded-lg transition inline-flex items-center justify-center ' + classes;
        button.title = payload.preview || 'لا توجد ملاحظات — اضغط لإضافة واحدة';

        button.querySelector('[data-notes-badge]')?.remove();
        if (payload.open_count > 0) {
            const badge = document.createElement('span');
            badge.setAttribute('data-notes-badge', '');
            badge.className = 'absolute -top-1 -left-1 min-w-[16px] h-4 px-1 rounded-full bg-red-600 text-white text-[10px] font-bold leading-4';
            badge.textContent = payload.open_count;
            button.appendChild(badge);
        }
    }

    document.getElementById('noteForm')?.addEventListener('submit', async function (e) {
        e.preventDefault();
        try {
            const data = await postJson(`/reservations/${notesId}/notes`, new FormData(this));
            this.reset();
            renderNotes(data.notes);
            qaToast(data.message);
        } catch (error) {
            qaToast(error.message, false);
        }
    });

    window.toggleNoteResolved = async function (noteId, resolved) {
        const body = new FormData();
        body.append('_method', 'PUT');
        body.append('resolved', resolved ? '1' : '0');
        try {
            const data = await postJson(`/reservations/${notesId}/notes/${noteId}`, body);
            renderNotes(data.notes);
        } catch (error) {
            qaToast(error.message, false);
        }
    };

    window.editNote = async function (noteId, button) {
        const current = document.querySelector(`[data-note-body="${noteId}"]`)?.textContent ?? '';
        const updated = window.prompt('تعديل الملاحظة:', current);
        if (updated === null || updated.trim() === '' || updated === current) return;

        const body = new FormData();
        body.append('_method', 'PUT');
        body.append('body', updated.trim());
        try {
            const data = await postJson(`/reservations/${notesId}/notes/${noteId}`, body);
            renderNotes(data.notes);
            qaToast(data.message);
        } catch (error) {
            qaToast(error.message, false);
        }
    };

    window.deleteNote = async function (noteId) {
        if (!confirm('حذف هذه الملاحظة؟')) return;
        const body = new FormData();
        body.append('_method', 'DELETE');
        try {
            const data = await postJson(`/reservations/${notesId}/notes/${noteId}`, body);
            renderNotes(data.notes);
            qaToast(data.message);
        } catch (error) {
            qaToast(error.message, false);
        }
    };

    // إغلاق نافذة الملاحظات عند النقر خارجها أو بمفتاح Escape
    document.addEventListener('click', function (e) {
        const popover = document.getElementById('notesPopover');
        if (!popover || popover.classList.contains('hidden')) return;
        if (!popover.contains(e.target) && !e.target.closest('[data-notes-btn]')) closeNotes();
    });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeNotes(); });
})();
</script>
