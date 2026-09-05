@extends('layouts.app')
@section('title', 'شجرة الحسابات')
@section('page-title', 'شجرة الحسابات (USALI)')

@push('styles')
<style>
    .coa-tree, .coa-children { list-style:none; margin:0; padding:0; }
    .coa-children { border-right:1px dashed #e5e7eb; margin-right:0.75rem; }

    .coa-row {
        display:flex; align-items:center; gap:0.5rem; flex-wrap:wrap;
        padding:0.5rem 0.75rem; padding-right:calc(0.75rem + var(--indent, 0rem));
        border-bottom:1px solid #f3f4f6; transition:background .15s;
    }
    .coa-row:hover { background:#f9fafb; }

    .coa-toggle {
        width:1.5rem; height:1.5rem; flex-shrink:0;
        display:flex; align-items:center; justify-content:center;
        border-radius:0.5rem; border:1px solid #e5e7eb; background:#fff; color:#6b7280;
    }
    .coa-toggle:hover { background:#eff6ff; color:#1d4ed8; border-color:#bfdbfe; }

    .coa-leaf-dot {
        width:1.5rem; height:1.5rem; flex-shrink:0; position:relative;
    }
    .coa-leaf-dot::after {
        content:''; position:absolute; top:50%; right:50%;
        width:5px; height:5px; border-radius:9999px; background:#d1d5db; transform:translate(50%,-50%);
    }

    .coa-code {
        font-family:ui-monospace, SFMono-Regular, Menlo, monospace;
        font-size:0.8125rem; font-weight:700; color:#0F4C75;
        background:#f1f5f9; border-radius:0.375rem; padding:0.125rem 0.5rem; flex-shrink:0;
    }
    .coa-name-ar { font-size:0.875rem; color:#111827; }
    .coa-name-en { font-size:0.75rem; color:#9ca3af; }
    .coa-badges  { display:flex; gap:0.375rem; margin-right:auto; flex-wrap:wrap; }
    .coa-chip {
        font-size:0.6875rem; font-weight:600; border-radius:9999px; padding:0.125rem 0.5rem; white-space:nowrap;
    }
    .coa-chip-muted { background:#f3f4f6; color:#6b7280; }

    @media (max-width: 640px) {
        .coa-name-en { display:none; }
    }
</style>
@endpush

@section('content')
<div class="max-w-5xl mx-auto space-y-5" dir="rtl">

    {{-- ملخص + فلاتر --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <div class="flex items-start justify-between flex-wrap gap-3 mb-4">
            <div>
                <h2 class="text-lg font-bold text-gray-800">شجرة الحسابات</h2>
                <p class="text-sm text-gray-500 mt-1">
                    مبنية على معيار USALI للمنشآت الفندقية —
                    <strong>{{ number_format($totals['all']) }}</strong> حساباً،
                    منها <strong>{{ number_format($totals['posting']) }}</strong> قابل للترحيل.
                    عملة التقارير: <strong>{{ config('hotel.base_currency') }}</strong>.
                </p>
            </div>
            <a href="{{ route('coa.tree', request()->query()) }}" target="_blank"
               class="px-4 py-2 border rounded-lg text-sm font-medium transition hover:bg-gray-50"
               style="border-color:#0F4C75;color:#0F4C75;">
                تصدير JSON
            </a>
        </div>

        <form method="GET" action="{{ route('coa.index') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-3">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">النوع</label>
                <select name="type" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none focus:border-blue-400">
                    <option value="">كل الأنواع</option>
                    @foreach($types as $type)
                    <option value="{{ $type }}" @selected(($filters['type'] ?? null) === $type)>
                        {{ ['asset'=>'أصول','liability'=>'خصوم','equity'=>'حقوق ملكية','revenue'=>'إيرادات','expense'=>'مصروفات'][$type] ?? $type }}
                    </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">القسم</label>
                <select name="department" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none focus:border-blue-400">
                    <option value="">كل الأقسام</option>
                    @foreach($departments as $dept)
                    <option value="{{ $dept }}" @selected(($filters['department'] ?? null) === $dept)>
                        {{ ['rooms'=>'الغرف','fnb'=>'الأطعمة والمشروبات','spa'=>'المنتجع','laundry'=>'المغسلة','parking'=>'المواقف','admin'=>'الإدارة','sales'=>'المبيعات والتسويق','maintenance'=>'الصيانة','utilities'=>'المرافق'][$dept] ?? $dept }}
                    </option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-end">
                <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer">
                    <input type="checkbox" name="posting_only" value="1" @checked($filters['posting_only'] ?? false)
                           class="rounded border-gray-300">
                    القابلة للترحيل فقط
                </label>
            </div>

            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 px-4 py-2 text-white rounded-lg text-sm font-medium" style="background:#0F4C75;">
                    تطبيق
                </button>
                <a href="{{ route('coa.index') }}" class="px-3 py-2 border border-gray-300 text-gray-600 rounded-lg text-sm hover:bg-gray-50">
                    إلغاء
                </a>
            </div>
        </form>
    </div>

    {{-- الشجرة --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden"
         x-data="coaTree()" x-init="init()">

        <div class="flex items-center justify-between gap-3 px-5 py-3 border-b border-gray-100 bg-gray-50 flex-wrap">
            <div class="flex items-center gap-2">
                <button type="button" @click="expandAll()"
                        class="px-3 py-1.5 rounded-lg border border-gray-200 bg-white text-xs font-semibold text-gray-600 hover:border-blue-400 hover:text-blue-600 transition">
                    توسيع الكل
                </button>
                <button type="button" @click="collapseAll()"
                        class="px-3 py-1.5 rounded-lg border border-gray-200 bg-white text-xs font-semibold text-gray-600 hover:border-blue-400 hover:text-blue-600 transition">
                    طيّ الكل
                </button>
            </div>
            <input type="search" x-model="q" @input="filter()" placeholder="ابحث بالكود أو الاسم…"
                   class="flex-1 min-w-48 border border-gray-300 rounded-lg px-3 py-1.5 text-sm outline-none focus:border-blue-400">
        </div>

        @if(empty($tree))
        <p class="py-12 text-center text-sm text-gray-400">لا توجد حسابات مطابقة للفلترة.</p>
        @else
        <ul class="coa-tree" x-ref="tree">
            @foreach($tree as $node)
                @include('partials.coa-node', ['node' => $node])
            @endforeach
        </ul>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
function coaTree() {
    return {
        q: '',

        init() {},

        /** كل عقد Alpine داخل الشجرة — نصل إلى حالتها عبر Alpine.$data */
        nodes() {
            return Array.from(this.$refs.tree?.querySelectorAll('.coa-node') ?? []);
        },

        setAll(open) {
            this.nodes().forEach(el => {
                const data = Alpine.$data(el);
                if (data && typeof data.open !== 'undefined') data.open = open;
            });
        },

        expandAll()   { this.setAll(true); },
        collapseAll() { this.setAll(false); },

        /**
         * بحث نصي: نُخفي الصفوف غير المطابقة ونُبقي آباء المطابق ظاهرين
         * حتى لا ينقطع المسار من الجذر إلى النتيجة.
         */
        filter() {
            const term = this.q.trim().toLowerCase();
            const nodes = this.nodes();

            if (!term) {
                nodes.forEach(el => { el.style.display = ''; });
                this.setAll(false);
                nodes.filter(el => el.parentElement?.classList.contains('coa-tree'))
                     .forEach(el => { const d = Alpine.$data(el); if (d) d.open = true; });
                return;
            }

            nodes.forEach(el => { el.style.display = 'none'; });

            nodes.forEach(el => {
                const text = (el.querySelector('.coa-row')?.textContent ?? '').toLowerCase();
                if (!text.includes(term)) return;

                el.style.display = '';

                // أظهر كل الآباء وافتحهم
                let parent = el.parentElement?.closest('.coa-node');
                while (parent) {
                    parent.style.display = '';
                    const d = Alpine.$data(parent);
                    if (d) d.open = true;
                    parent = parent.parentElement?.closest('.coa-node');
                }
            });
        },
    };
}
</script>
@endpush
