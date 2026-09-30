@extends('layouts.app')
@section('title', 'صلاحيات ' . $user->name)
@section('page-title', 'إدارة الصلاحيات')
@section('back-url', route('users.index'))

@section('content')
@php
    /**
     * شاشة ضبط صلاحيات موظف.
     *
     * كانت كل صلاحية نموذجاً مستقلاً يعيد تحميل الصفحة، و64 صلاحية تعني
     * عشرات إعادات التحميل مع رجوع الصفحة لأعلاها في كل مرة. صارت التعديلات
     * فورية، ومعها بحث وتصفية وإجراءات جماعية ونسخ من موظف آخر.
     */
    $groups = [];
    foreach ($permissionMap as $key => $permission) {
        $groups[$permission['group']][$key] = $permission;
    }

    $grantedCount = collect($permissionMap)->where('is_granted', true)->count();
    $customCount  = collect($permissionMap)->where('is_custom', true)->count();
    $totalCount   = count($permissionMap);

    /**
     * صلاحيات تمسّ المال أو تحذف بيانات أو تتجاوز ضوابط النظام: تُعلَّم كي
     * لا تُمنح بالنقر السريع مع بقية المجموعة.
     */
    $sensitive = [
        'segment.unlock', 'reservation.discount', 'reservation.reprice', 'reservation.cancel',
        'guests.sensitive', 'withdrawal.delete', 'withdrawal.edit', 'shifts.reopen', 'shifts.delete',
        'settlement.lock', 'rooms.delete', 'room.price.edit', 'users.manage', 'settings.manage',
        'payments.bank_receipt', 'expenses.delete', 'hr.delete', 'reports.integrity',
    ];

    $groupSlugs = [];
    foreach (array_keys($groups) as $index => $groupName) {
        $groupSlugs[$groupName] = 'grp-' . $index;
    }
@endphp

<div x-data="permissionsConsole({
        granted: {{ $grantedCount }},
        total: {{ $totalCount }},
        custom: {{ $customCount }},
     })"
     class="max-w-[1400px] mx-auto">

    {{-- ═══ رأس ثابت: هوية الموظف + الحصيلة + الإجراءات الجماعية ═══ --}}
    <div class="sticky top-0 z-30 -mx-3 sm:-mx-6 px-3 sm:px-6 pt-1 pb-3 bg-gray-50/95 backdrop-blur"
         style="background-color: rgba(249,250,251,.95);">
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">

            <div class="p-4 sm:p-5 flex items-start gap-4 flex-wrap">
                @php
                    $palette = ['#0F4C75','#1a6fa8','#065f46','#92400e','#6d28d9','#be123c'];
                    $avatar  = $palette[crc32($user->name) % count($palette)];
                @endphp
                <div class="w-12 h-12 rounded-2xl flex items-center justify-center text-white font-bold text-lg shadow-sm flex-shrink-0"
                     style="background:{{ $avatar }};">
                    {{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}
                </div>

                <div class="min-w-0 flex-1">
                    <h2 class="font-black text-gray-900 text-lg leading-tight">{{ $user->name }}</h2>
                    <div class="flex items-center gap-2 mt-1.5 flex-wrap">
                        <span class="text-xs px-2 py-0.5 rounded-md font-bold" style="background:var(--ui-accent-bg);color:var(--ui-accent);">
                            {{ $user->roles->first()?->name ?? 'موظف' }}
                        </span>
                        <span class="text-xs text-gray-400 font-mono">{{ $user->employee_id }}</span>
                        <span class="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-md font-semibold
                                     {{ $user->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-600' }}">
                            <span class="w-1.5 h-1.5 rounded-full {{ $user->is_active ? 'bg-emerald-500' : 'bg-red-500' }}"></span>
                            {{ $user->is_active ? 'نشط' : 'معطّل' }}
                        </span>
                    </div>
                </div>

                {{-- الحصيلة: شريط واحد يقول كم مُنح من كم، بدل ثلاثة صناديق رقمية --}}
                <div class="flex-shrink-0 w-full sm:w-64">
                    <div class="flex items-baseline justify-between mb-1.5">
                        <span class="text-xs font-semibold text-gray-500">الصلاحيات الممنوحة</span>
                        <span class="text-sm font-black" style="color:var(--ui-accent);" dir="ltr">
                            <span x-text="summary.granted">{{ $grantedCount }}</span><span class="text-gray-300 font-normal"> / </span><span class="text-gray-400 font-bold" x-text="summary.total">{{ $totalCount }}</span>
                        </span>
                    </div>
                    <div class="h-2 rounded-full bg-gray-100 overflow-hidden">
                        <div class="perm-progress h-full rounded-full transition-all duration-500"
                             :style="`width: ${summary.total ? (summary.granted / summary.total * 100) : 0}%`"></div>
                    </div>
                    <p class="text-[11px] text-gray-400 mt-1.5">
                        <span x-text="summary.custom">{{ $customCount }}</span> صلاحية ضُبطت يدوياً خلافاً لافتراضي الدور
                    </p>
                </div>
            </div>

            {{-- شريط الأدوات: بحث + تصفية + إجراءات --}}
            <div class="px-4 sm:px-5 py-3 border-t border-gray-100 bg-gray-50/70 flex items-center gap-2.5 flex-wrap">
                <div class="relative flex-1 min-w-[200px]">
                    <svg class="w-4 h-4 absolute right-3 top-1/2 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input type="search" x-model="search" placeholder="ابحث في الصلاحيات…"
                           class="w-full pr-9 pl-3 py-2 text-sm rounded-xl border border-gray-300 bg-white outline-none focus:ring-2 focus:ring-blue-200">
                </div>

                <div class="flex items-center gap-1 bg-white rounded-xl border border-gray-300 p-0.5">
                    @foreach(['all' => 'الكل', 'granted' => 'المفعّلة', 'denied' => 'المعطّلة', 'custom' => 'المخصّصة'] as $value => $label)
                    <button type="button" @click="filter = '{{ $value }}'"
                            :class="filter === '{{ $value }}' ? 'text-white' : 'text-gray-500 hover:bg-gray-100'"
                            :style="filter === '{{ $value }}' ? 'background:#0F4C75' : ''"
                            class="px-3 py-1.5 rounded-lg text-xs font-bold transition">{{ $label }}</button>
                    @endforeach
                </div>

                <div class="flex items-center gap-2">
                    @if($copySources->isNotEmpty())
                    <button type="button" @click="copyOpen = true"
                            class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl border border-gray-300 bg-white text-xs font-bold text-gray-600 hover:bg-gray-50 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                        نسخ من موظف
                    </button>
                    @endif
                    <button type="button" @click="resetOpen = true"
                            class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl border border-amber-300 bg-amber-50 text-xs font-bold text-amber-800 hover:bg-amber-100 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        إعادة للافتراضي
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="flex gap-5 mt-4 items-start">

        {{-- ═══ فهرس المجموعات: تنقّل سريع بدل تمرير 14 بطاقة ═══ --}}
        <nav class="hidden lg:block w-56 flex-shrink-0 sticky top-[168px]">
            <div class="bg-white rounded-2xl border border-gray-200 p-2 shadow-sm">
                @foreach($groups as $groupName => $permissions)
                @php
                    $on = collect($permissions)->where('is_granted', true)->count();
                    $of = count($permissions);
                @endphp
                <a href="#{{ $groupSlugs[$groupName] }}"
                   class="flex items-center justify-between gap-2 px-3 py-2 rounded-xl text-xs font-semibold text-gray-600 hover:bg-gray-50 transition">
                    <span class="truncate">{{ $groupName }}</span>
                    <span class="flex-shrink-0 px-1.5 py-0.5 rounded-md text-[10px] font-bold
                                 {{ $on === $of ? 'bg-emerald-50 text-emerald-700' : ($on > 0 ? 'bg-amber-50 text-amber-700' : 'bg-gray-100 text-gray-400') }}">
                        {{ $on }}/{{ $of }}
                    </span>
                </a>
                @endforeach
            </div>
        </nav>

        {{-- ═══ المجموعات ═══ --}}
        <div class="flex-1 min-w-0 space-y-4">
            @foreach($groups as $groupName => $permissions)
            @php
                $on = collect($permissions)->where('is_granted', true)->count();
                $of = count($permissions);
            @endphp
            <section id="{{ $groupSlugs[$groupName] }}"
                     data-group-section="{{ $groupName }}"
                     x-show="groupVisible($el)"
                     class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden scroll-mt-44">

                <header class="px-4 sm:px-5 py-3 border-b border-gray-100 bg-gray-50/70 flex items-center gap-3 flex-wrap">
                    <h3 class="font-bold text-gray-800 text-sm flex-1 min-w-0">{{ $groupName }}</h3>

                    <span data-group-count class="px-2 py-0.5 rounded-lg text-[11px] font-bold
                                 {{ $on === $of ? 'bg-emerald-50 text-emerald-700' : ($on > 0 ? 'bg-amber-50 text-amber-700' : 'bg-gray-100 text-gray-500') }}">
                        {{ $on }}/{{ $of }}
                    </span>

                    {{-- منح المجموعة كاملة بنقرة بدل تسع نقرات --}}
                    <div class="flex items-center gap-1">
                        <button type="button" @click="toggleGroup('{{ $groupName }}', true)"
                                class="px-2.5 py-1 rounded-lg text-[11px] font-bold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 transition">
                            منح الكل
                        </button>
                        <button type="button" @click="toggleGroup('{{ $groupName }}', false)"
                                class="px-2.5 py-1 rounded-lg text-[11px] font-bold text-gray-500 bg-gray-100 hover:bg-gray-200 transition">
                            سحب الكل
                        </button>
                    </div>
                </header>

                <div class="divide-y divide-gray-50">
                    @foreach($permissions as $key => $permission)
                    @php $isSensitive = in_array($key, $sensitive, true); @endphp
                    <div data-permission-row
                         data-key="{{ $key }}"
                         data-label="{{ $permission['label'] }}"
                         data-granted="{{ $permission['is_granted'] ? '1' : '0' }}"
                         data-custom="{{ $permission['is_custom'] ? '1' : '0' }}"
                         x-show="rowVisible($el)"
                         class="flex items-center gap-3 px-4 sm:px-5 py-3 hover:bg-gray-50/70 transition">

                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span data-row-label class="text-sm font-semibold text-gray-800">{{ $permission['label'] }}</span>

                                @if($isSensitive)
                                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-md text-[10px] font-bold bg-rose-50 text-rose-700"
                                      title="صلاحية حسّاسة: تمسّ المال أو تحذف بيانات أو تتجاوز ضوابط النظام">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01M5 19h14a2 2 0 001.84-2.75L13.74 4a2 2 0 00-3.48 0l-7.1 12.25A2 2 0 004.99 19z"/></svg>
                                    حسّاسة
                                </span>
                                @endif

                                <span data-custom-badge
                                      class="{{ $permission['is_custom'] ? 'inline-flex' : 'hidden' }} items-center px-1.5 py-0.5 rounded-md text-[10px] font-bold bg-violet-50 text-violet-700"
                                      title="ضُبطت يدوياً خلافاً لافتراضي الدور">مخصّصة</span>
                            </div>
                            <code class="text-[10px] text-gray-300 font-mono" dir="ltr">{{ $key }}</code>
                        </div>

                        {{-- مفتاح فوري: يُرسل ويعود بإشعار دون مغادرة الصفحة --}}
                        <button type="button"
                                data-toggle
                                @click="togglePermission($el)"
                                :disabled="busy"
                                role="switch"
                                aria-checked="{{ $permission['is_granted'] ? 'true' : 'false' }}"
                                aria-label="{{ $permission['label'] }}"
                                class="relative flex-shrink-0 w-12 h-7 rounded-full transition-colors duration-200 disabled:opacity-40
                                       focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-blue-400
                                       {{ $permission['is_granted'] ? 'perm-on' : 'perm-off' }}">
                            <span class="absolute top-1 w-5 h-5 bg-white rounded-full shadow-sm transition-all duration-200
                                         {{ $permission['is_granted'] ? 'right-1' : 'left-1' }}"></span>
                        </button>
                    </div>
                    @endforeach
                </div>
            </section>
            @endforeach

            {{-- لا نتيجة للبحث --}}
            <div x-show="noResults" x-cloak
                 class="bg-white rounded-2xl border border-gray-200 py-12 text-center">
                <p class="text-gray-500 text-sm">لا صلاحية تطابق «<span class="font-bold" x-text="search"></span>»</p>
                <button type="button" @click="search = ''; filter = 'all'"
                        class="mt-3 px-4 py-2 rounded-xl text-xs font-bold text-white" style="background:var(--ui-accent);color:var(--ui-accent-fg);">
                    عرض الكل
                </button>
            </div>
        </div>
    </div>

    {{-- ═══ نافذة: إعادة للافتراضي ═══ --}}
    <div x-show="resetOpen" x-cloak @click.self="resetOpen = false" @keydown.escape.window="resetOpen = false"
         class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden">
            <div class="px-5 py-4 bg-amber-50 border-b border-amber-100">
                <h3 class="font-bold text-amber-900">إعادة الصلاحيات للافتراضي</h3>
            </div>
            <div class="p-5">
                <p class="text-sm text-gray-600 leading-relaxed">
                    ستُحذف كل التعديلات اليدوية وتعود صلاحيات
                    <span class="font-bold text-gray-800">{{ $user->name }}</span>
                    إلى الافتراضي المرافق لدور
                    <span class="font-bold text-gray-800">{{ $user->roles->first()?->name ?? 'الموظف' }}</span>.
                </p>
                <div class="flex gap-2 mt-5">
                    <button type="button" @click="resetPermissions()" :disabled="busy"
                            class="flex-1 py-2.5 rounded-xl bg-amber-600 hover:bg-amber-700 text-white text-sm font-bold transition disabled:opacity-50">
                        نعم، أعد للافتراضي
                    </button>
                    <button type="button" @click="resetOpen = false"
                            class="px-5 py-2.5 rounded-xl border border-gray-300 text-gray-600 text-sm hover:bg-gray-50">إلغاء</button>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══ نافذة: نسخ من موظف ═══ --}}
    @if($copySources->isNotEmpty())
    <div x-show="copyOpen" x-cloak @click.self="copyOpen = false" @keydown.escape.window="copyOpen = false"
         class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100" style="background:#e8f0f7;">
                <h3 class="font-bold" style="color:var(--ui-accent);">نسخ صلاحيات موظف آخر</h3>
            </div>
            <div class="p-5">
                <p class="text-sm text-gray-600 mb-3">
                    ستُستبدل صلاحيات <span class="font-bold text-gray-800">{{ $user->name }}</span> بصلاحيات الموظف المختار بالكامل.
                </p>
                <select x-model="copySourceId"
                        class="w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm bg-white outline-none focus:ring-2 focus:ring-blue-200">
                    <option value="">— اختر الموظف —</option>
                    @foreach($copySources as $source)
                    <option value="{{ $source->id }}">{{ $source->name }} ({{ $source->roles->first()?->name ?? 'موظف' }})</option>
                    @endforeach
                </select>
                <div class="flex gap-2 mt-5">
                    <button type="button" @click="copyPermissions()" :disabled="busy || !copySourceId"
                            class="flex-1 py-2.5 rounded-xl text-white text-sm font-bold transition disabled:opacity-40"
                            style="background:var(--ui-accent);color:var(--ui-accent-fg);">
                        نسخ الصلاحيات
                    </button>
                    <button type="button" @click="copyOpen = false"
                            class="px-5 py-2.5 rounded-xl border border-gray-300 text-gray-600 text-sm hover:bg-gray-50">إلغاء</button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- ═══ إشعار ═══ --}}
    <div x-show="toast.show" x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-3"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-end="opacity-0 translate-y-3"
         class="fixed bottom-5 left-1/2 -translate-x-1/2 z-[60] flex items-center gap-2.5 px-5 py-3 rounded-xl text-white text-sm font-semibold shadow-2xl max-w-[92vw]"
         :class="toast.ok ? 'bg-emerald-700' : 'bg-red-700'">
        <svg x-show="toast.ok" class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
        <svg x-show="!toast.ok" class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
        <span x-text="toast.message"></span>
    </div>
</div>

@push('styles')
<style>
    .perm-progress { background: linear-gradient(90deg, #0F4C75, #3d7cbb); }
    .perm-on  { background: #0F4C75; }
    .perm-off { background: #cbd5e1; }
    .perm-on:hover  { background: #1e578f; }
    .perm-off:hover { background: #94a3b8; }
    html.dark .perm-on  { background: #1e578f; }
    html.dark .perm-off { background: #374151; }
</style>
@endpush

@push('scripts')
<script>
function permissionsConsole(initial) {
    return {
        summary: initial,
        search: '',
        filter: 'all',
        busy: false,
        resetOpen: false,
        copyOpen: false,
        copySourceId: '',
        noResults: false,
        toast: { show: false, message: '', ok: true },

        notify(message, ok = true) {
            this.toast = { show: true, message, ok };
            clearTimeout(this._toastTimer);
            this._toastTimer = setTimeout(() => (this.toast.show = false), 3200);
        },

        async send(url, body) {
            this.busy = true;
            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                    body: JSON.stringify(body),
                });
                const data = await response.json().catch(() => ({}));
                if (!response.ok || !data.success) {
                    throw new Error(data.message || 'تعذّر حفظ التغيير');
                }
                if (data.summary) this.summary = data.summary;
                return data;
            } finally {
                this.busy = false;
            }
        },

        /** المفتاح يتغيّر فوراً ثم يُثبّت أو يُرجَع إن رفض الخادم. */
        async togglePermission(button) {
            const row     = button.closest('[data-permission-row]');
            const granted = row.dataset.granted === '1';
            const next    = !granted;

            this.paint(row, next);

            try {
                const data = await this.send('{{ route('users.togglePermission', $user) }}', {
                    permission: row.dataset.key,
                    grant: next,
                });
                row.dataset.custom = '1';
                row.querySelector('[data-custom-badge]')?.classList.replace('hidden', 'inline-flex');
                this.refreshGroupCount(row.closest('[data-group-section]'));
                this.notify(data.message);
            } catch (error) {
                this.paint(row, granted);      // تراجُع بصري عن تغيير لم يُحفظ
                this.notify(error.message, false);
            }
        },

        async toggleGroup(group, grant) {
            try {
                const data = await this.send('{{ route('users.togglePermissionGroup', $user) }}', { group, grant });

                document.querySelectorAll(`[data-group-section="${CSS.escape(group)}"] [data-permission-row]`)
                    .forEach(row => {
                        this.paint(row, grant);
                        row.dataset.custom = '1';
                        row.querySelector('[data-custom-badge]')?.classList.replace('hidden', 'inline-flex');
                    });

                this.refreshGroupCount(document.querySelector(`[data-group-section="${CSS.escape(group)}"]`));
                this.notify(data.message);
            } catch (error) {
                this.notify(error.message, false);
            }
        },

        async resetPermissions() {
            try {
                const data = await this.send('{{ route('users.resetPermissions', $user) }}', {});
                this.notify(data.message);
                setTimeout(() => window.location.reload(), 700);
            } catch (error) {
                this.notify(error.message, false);
                this.resetOpen = false;
            }
        },

        async copyPermissions() {
            try {
                const data = await this.send('{{ route('users.copyPermissions', $user) }}', {
                    source_user_id: this.copySourceId,
                });
                this.notify(data.message);
                setTimeout(() => window.location.reload(), 700);
            } catch (error) {
                this.notify(error.message, false);
                this.copyOpen = false;
            }
        },

        /** حالة المفتاح ولون مقبضه وقيمة إتاحته للقارئ الصوتي. */
        paint(row, granted) {
            row.dataset.granted = granted ? '1' : '0';

            const button = row.querySelector('[data-toggle]');
            const knob   = button.querySelector('span');

            button.classList.toggle('perm-on', granted);
            button.classList.toggle('perm-off', !granted);
            button.setAttribute('aria-checked', granted ? 'true' : 'false');
            knob.classList.toggle('right-1', granted);
            knob.classList.toggle('left-1', !granted);
        },

        refreshGroupCount(section) {
            if (!section) return;
            const rows  = section.querySelectorAll('[data-permission-row]');
            const on    = [...rows].filter(r => r.dataset.granted === '1').length;
            const badge = section.querySelector('[data-group-count]');
            if (!badge) return;

            badge.textContent = `${on}/${rows.length}`;
            badge.className = 'px-2 py-0.5 rounded-lg text-[11px] font-bold ' + (
                on === rows.length ? 'bg-emerald-50 text-emerald-700'
                : on > 0 ? 'bg-amber-50 text-amber-700'
                : 'bg-gray-100 text-gray-500'
            );
        },

        matches(row) {
            const term = this.search.trim();
            if (term && !row.dataset.label.includes(term) && !row.dataset.key.includes(term.toLowerCase())) {
                return false;
            }
            if (this.filter === 'granted') return row.dataset.granted === '1';
            if (this.filter === 'denied')  return row.dataset.granted === '0';
            if (this.filter === 'custom')  return row.dataset.custom === '1';
            return true;
        },

        rowVisible(row) {
            const visible = this.matches(row);
            queueMicrotask(() => this.updateEmptyState());
            return visible;
        },

        groupVisible(section) {
            return [...section.querySelectorAll('[data-permission-row]')].some(row => this.matches(row));
        },

        updateEmptyState() {
            const rows = document.querySelectorAll('[data-permission-row]');
            this.noResults = rows.length > 0 && ![...rows].some(row => this.matches(row));
        },
    };
}
</script>
@endpush
@endsection
