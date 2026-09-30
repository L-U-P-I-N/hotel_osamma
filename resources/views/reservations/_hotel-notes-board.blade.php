{{--
    لوحة الملاحظات العامة أعلى صفحة الحجوزات.

    ملاحظات الحجوزات مرتبطة بغرفة ونزيل، ولا مكان فيها لتنبيه يخصّ الفندق كله
    («المصعد معطّل»، «سلّم المفاتيح لوردية الليل»). هذه اللوحة لذلك: يكتبها موظف
    فيقرأها كل من يفتح الصفحة بعده، وتبقى حتى تُعلَّم منتهية.

    المتغيرات: $hotelNotes (الملاحظات القائمة)
--}}
@php
    $boardSeed = $hotelNotes->map(fn (\App\Models\HotelNote $n) => [
        'id'         => $n->id,
        'type'       => $n->type,
        'type_label' => $n->type_label,
        'color'      => $n->color,
        'body'       => $n->body,
        'pinned'     => (bool) $n->is_pinned,
        'resolved'   => $n->is_resolved,
        'author'     => $n->createdBy?->name,
        'created_at' => $n->created_at?->format('d/m/Y H:i'),
    ])->values();
@endphp

{{-- الملاحظات القائمة تُسلَّم كبيانات JSON مقروءة (لا مهروبة الحروف) كي تظهر
     نصوصها في مصدر الصفحة أيضاً، فتُقرأ عند الطباعة وفي أي معاينة لا تُشغّل JS --}}
<script type="application/json" id="hotelNotesSeed">@json($boardSeed, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)</script>

<div x-data="hotelNotesBoard()" x-init="init()" class="mb-4">
    <div class="bg-white rounded-xl shadow-sm border overflow-hidden"
         :class="urgentCount > 0 ? 'border-red-200' : 'border-gray-100'">

        {{-- رأس اللوحة: يبقى ظاهراً دائماً ليُعرف وجود ملاحظات حتى وهي مطويّة --}}
        <div class="px-4 py-2.5 flex items-center gap-2 flex-wrap border-b border-gray-100">
            <span class="text-lg leading-none">🗒️</span>
            <h3 class="font-semibold text-gray-700 text-sm">ملاحظات عامة للفندق</h3>

            <span x-show="openCount > 0" x-cloak
                  class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold"
                  :class="urgentCount > 0 ? 'bg-red-100 text-red-700' : 'bg-blue-100 text-blue-700'"
                  x-text="openCount + ' قائمة'"></span>
            <span x-show="openCount === 0" x-cloak class="text-xs text-gray-400">لا توجد ملاحظات عامة</span>

            <div class="flex items-center gap-1.5 mr-auto">
                @can('checkin.create')
                <button type="button" @click="showForm = !showForm; if (showForm) collapsed = false"
                        class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-semibold text-white transition"
                        style="background:var(--ui-accent);color:var(--ui-accent-fg);">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                    <span x-text="showForm ? 'إغلاق' : 'إضافة ملاحظة'"></span>
                </button>
                @endcan
                <button type="button" @click="toggleCollapsed()"
                        class="w-7 h-7 rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition text-sm font-bold"
                        :title="collapsed ? 'إظهار الملاحظات' : 'إخفاء الملاحظات'"
                        x-text="collapsed ? '+' : '–'"></button>
            </div>
        </div>

        <div x-show="!collapsed" x-cloak>
            {{-- نموذج الإضافة --}}
            @can('checkin.create')
            <form x-show="showForm" x-cloak @submit.prevent="create()"
                  class="px-4 py-3 border-b border-gray-100 bg-gray-50 space-y-2">
                <div class="flex flex-wrap gap-2">
                    <select x-model="form.type"
                            class="border border-gray-200 rounded-lg px-2 py-1.5 text-xs outline-none bg-white min-w-[150px]">
                        @foreach(\App\Models\HotelNote::TYPES as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <label class="inline-flex items-center gap-1.5 text-xs text-gray-600 px-2">
                        <input type="checkbox" x-model="form.pinned" class="w-3.5 h-3.5 accent-amber-600">
                        تثبيت في الأعلى
                    </label>
                </div>
                <textarea x-model="form.body" rows="2" maxlength="1000" required
                          placeholder="اكتب الملاحظة التي يجب أن يقرأها بقية الموظفين…"
                          class="w-full border border-gray-200 rounded-lg px-2.5 py-2 text-sm outline-none resize-none bg-white"></textarea>
                <div class="flex gap-2">
                    <button type="submit" :disabled="saving"
                            class="px-4 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-bold transition disabled:opacity-50">
                        <span x-text="saving ? 'جارٍ الحفظ…' : 'حفظ الملاحظة'"></span>
                    </button>
                    <button type="button" @click="showForm = false"
                            class="px-4 py-1.5 border border-gray-300 text-gray-600 rounded-lg text-xs hover:bg-white transition">إلغاء</button>
                </div>
            </form>
            @endcan

            {{-- قائمة الملاحظات --}}
            <div x-show="items.length > 0" x-cloak class="divide-y divide-gray-50">
                <template x-for="note in items" :key="note.id">
                    <div class="px-4 py-2.5 flex items-start gap-2.5" :class="cardClass(note)">
                        <span class="text-[10px] font-bold px-1.5 py-0.5 rounded flex-shrink-0 mt-0.5"
                              :class="badgeClass(note)" x-text="note.type_label"></span>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm text-gray-800 whitespace-pre-line break-words" x-text="note.body"></p>
                            <p class="text-[10px] text-gray-400 mt-0.5">
                                <span x-text="note.author || '—'"></span> ·
                                <span x-text="note.created_at"></span>
                                <span x-show="note.pinned" class="text-amber-600 font-bold">· 📌 مثبّتة</span>
                            </p>
                        </div>
                        @can('checkin.create')
                        <div class="flex items-center gap-1 flex-shrink-0">
                            <button type="button" @click="togglePin(note)"
                                    class="text-[11px] px-1.5 py-0.5 rounded hover:bg-white/60 transition"
                                    :class="note.pinned ? 'text-amber-700 font-bold' : 'text-gray-400'"
                                    :title="note.pinned ? 'إلغاء التثبيت' : 'تثبيت'">📌</button>
                            <button type="button" @click="edit(note)" class="text-[11px] text-blue-600 hover:underline">تعديل</button>
                            <button type="button" @click="resolve(note)" class="text-[11px] text-emerald-600 hover:underline">تمّت</button>
                            <button type="button" @click="remove(note)" class="text-[11px] text-red-600 hover:underline">حذف</button>
                        </div>
                        @endcan
                    </div>
                </template>
            </div>
            <div x-show="items.length === 0" x-cloak class="px-4 py-5 text-center text-xs text-gray-400">
                لا توجد ملاحظات عامة قائمة — اضغط «إضافة ملاحظة» لكتابة تنبيه لبقية الموظفين.
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
/* لوحة الملاحظات العامة: كل العمليات JSON فلا تُعاد الصفحة، وحالة الطيّ محفوظة
   في المتصفح كي تبقى كما تركها الموظف بين الصفحات. */
function hotelNotesBoard() {
    const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const STORAGE_KEY = 'hotelNotesCollapsed';

    // بيانات البداية من وسم JSON في الصفحة — تحليلها محمي كي لا تسقط اللوحة
    // كلها لو وصلت بيانات تالفة
    let seed = [];
    try {
        seed = JSON.parse(document.getElementById('hotelNotesSeed')?.textContent || '[]');
    } catch (e) {}

    return {
        items: Array.isArray(seed) ? seed : [],
        collapsed: false,
        showForm: false,
        saving: false,
        form: { type: 'general', body: '', pinned: false },

        init() {
            // القراءة محمية: التخزين المحلي قد يكون معطّلاً (تصفّح خاص)
            try { this.collapsed = localStorage.getItem(STORAGE_KEY) === '1'; } catch (e) {}
        },

        get openCount()   { return this.items.filter(n => !n.resolved).length; },
        get urgentCount() { return this.items.filter(n => !n.resolved && n.type === 'urgent').length; },

        toggleCollapsed() {
            this.collapsed = !this.collapsed;
            try { localStorage.setItem(STORAGE_KEY, this.collapsed ? '1' : '0'); } catch (e) {}
        },

        cardClass(note) {
            return {
                red:   'bg-red-50',
                amber: 'bg-amber-50',
                blue:  'bg-blue-50',
                slate: '',
            }[note.color] || '';
        },
        badgeClass(note) {
            return {
                red:   'bg-red-200 text-red-800',
                amber: 'bg-amber-200 text-amber-900',
                blue:  'bg-blue-200 text-blue-800',
                slate: 'bg-gray-200 text-gray-700',
            }[note.color] || 'bg-gray-200 text-gray-700';
        },

        async send(url, body, method = 'POST') {
            const response = await fetch(url, {
                method,
                headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                body,
            });
            let data = {};
            try { data = await response.json(); } catch (e) {}
            if (!response.ok) {
                const first = data.errors ? Object.values(data.errors)[0][0] : null;
                throw new Error(first || data.message || 'تعذّر إتمام العملية');
            }
            return data;
        },

        notify(message, ok = true) {
            if (window.qaToast) window.qaToast(message, ok);
            else if (!ok) alert(message);
        },

        async create() {
            if (!this.form.body.trim()) return;
            this.saving = true;
            const body = new FormData();
            body.append('type', this.form.type);
            body.append('body', this.form.body.trim());
            body.append('is_pinned', this.form.pinned ? '1' : '0');
            try {
                const data = await this.send('{{ route('hotel-notes.store') }}', body);
                this.items = data.notes.items;
                this.form.body = '';
                this.form.pinned = false;
                this.showForm = false;
                this.notify(data.message);
            } catch (e) {
                this.notify(e.message, false);
            } finally {
                this.saving = false;
            }
        },

        async patch(note, fields) {
            const body = new FormData();
            body.append('_method', 'PUT');
            Object.entries(fields).forEach(([k, v]) => body.append(k, v));
            try {
                const data = await this.send('/hotel-notes/' + note.id, body);
                this.items = data.notes.items;
                this.notify(data.message);
            } catch (e) {
                this.notify(e.message, false);
            }
        },

        togglePin(note) { this.patch(note, { is_pinned: note.pinned ? '0' : '1' }); },
        resolve(note)   { this.patch(note, { resolved: '1' }); },

        edit(note) {
            const updated = window.prompt('تعديل الملاحظة العامة:', note.body);
            if (updated === null || updated.trim() === '' || updated.trim() === note.body) return;
            this.patch(note, { body: updated.trim() });
        },

        async remove(note) {
            if (!confirm('حذف هذه الملاحظة العامة؟')) return;
            const body = new FormData();
            body.append('_method', 'DELETE');
            try {
                const data = await this.send('/hotel-notes/' + note.id, body);
                this.items = data.notes.items;
                this.notify(data.message);
            } catch (e) {
                this.notify(e.message, false);
            }
        },
    };
}
</script>
@endpush
