{{--
    نموذج الحساب — «دليل الحسابات» بترتيبه المتعارف عليه في برامج المحاسبة:
    شريط أوامر، ثم بيانات الحساب، ثم بياناته الإضافية.

    الحقول المشتقّة (النوع، الطبيعة، الحساب الختامي) تُعرض ولا تُدخَل: كلها
    تتبع الحساب الأب أو نوعه، وإدخالها يدوياً يفتح باب حسابٍ يخالف أصله.

    $editing  : ChartOfAccount|null — حساب يُعدَّل
    $creating : array|null          — ['parent' => ?ChartOfAccount, 'suggested' => ?string]
    $parents  : Collection<ChartOfAccount>
    $lockedCodes : array<string>    — أكواد لا تُوقَف (مستعملة في الترحيل)
--}}
@php
    $isNew   = $creating !== null;
    $account = $editing;
    $parent  = $isNew ? ($creating['parent'] ?? null) : $account?->parent;

    $typeLabels = [
        'asset' => 'أصول', 'liability' => 'خصوم', 'equity' => 'حقوق ملكية',
        'revenue' => 'إيرادات', 'expense' => 'مصروفات',
    ];
    $deptLabels = [
        'rooms' => 'الغرف', 'fnb' => 'الأطعمة والمشروبات', 'spa' => 'المنتجع',
        'laundry' => 'المغسلة', 'parking' => 'المواقف', 'admin' => 'الإدارة',
        'sales' => 'المبيعات والتسويق', 'maintenance' => 'الصيانة', 'utilities' => 'المرافق',
    ];

    $locked     = $account !== null && in_array($account->code, $lockedCodes, true);
    $blocker    = $account?->deletionBlocker();
    $fieldClass = 'w-full border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none focus:border-blue-400';
    $readClass  = 'w-full border border-gray-200 bg-gray-50 text-gray-600 rounded-lg px-3 py-2 text-sm';
@endphp

<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden" id="account-form">

    @if(!$isNew && $account === null)
        {{-- لا حساب مختار: إرشاد بدل نموذج فارغ يوحي بأنه جاهز للحفظ --}}
        <div class="px-5 py-3 border-b border-gray-100 bg-gray-50">
            <h3 class="text-sm font-bold text-gray-700">بيانات الحساب</h3>
        </div>
        <div class="p-8 text-center">
            <p class="text-sm text-gray-500 mb-4">اختر حساباً من الشجرة لعرض بياناته وتعديلها،<br>أو أضف حساباً فرعياً جديداً.</p>
            <a href="{{ route('coa.index', ['new' => 1]) }}"
               class="inline-block px-4 py-2 text-white rounded-lg text-sm font-semibold" style="background:#0F4C75;">
                + حساب جديد
            </a>
        </div>
    @else
    <form method="POST"
          action="{{ $isNew ? route('coa.store') : route('coa.update', $account->code) }}"
          class="divide-y divide-gray-100">
        @csrf
        @unless($isNew) @method('PUT') @endunless

        {{-- شريط الأوامر --}}
        <div class="flex items-center gap-2 px-4 py-2.5 bg-gray-50 flex-wrap">
            <button type="submit"
                    class="px-4 py-1.5 rounded-lg text-white text-sm font-semibold" style="background:#0F4C75;">
                حفظ
            </button>

            <a href="{{ route('coa.index', ['new' => 1] + ($account ? ['parent' => $account->code] : [])) }}"
               class="px-3 py-1.5 rounded-lg border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
                جديد
            </a>

            @if(!$isNew && $blocker === null)
            <button type="submit" form="delete-{{ $account->code }}"
                    onclick="return confirm('حذف الحساب {{ $account->code }} نهائياً؟')"
                    class="px-3 py-1.5 rounded-lg border border-red-200 bg-white text-sm font-medium text-red-600 hover:bg-red-50">
                حذف
            </button>
            @elseif(!$isNew)
            <span class="px-3 py-1.5 rounded-lg border border-gray-200 bg-white text-sm text-gray-400 cursor-not-allowed"
                  title="{{ $blocker }}">حذف</span>
            @endif

            <a href="{{ route('coa.index') }}"
               class="px-3 py-1.5 rounded-lg border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 mr-auto">
                إغلاق
            </a>
        </div>

        @if($errors->any())
        <div class="px-4 py-3 bg-red-50 border-r-4 border-red-400">
            <ul class="text-sm text-red-700 space-y-1">
                @foreach($errors->all() as $error)
                <li>• {{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        {{-- بيانات الحساب --}}
        <div class="p-4 grid grid-cols-1 sm:grid-cols-2 gap-4">

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">الرقم التسلسلي</label>
                <input type="text" class="{{ $readClass }}" value="{{ $isNew ? '—' : $account->id }}" readonly>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">
                    رقم الحساب @if($isNew)<span class="text-red-500">*</span>@endif
                </label>
                @if($isNew)
                <input type="text" name="code" inputmode="numeric" maxlength="4" required
                       class="{{ $fieldClass }} font-mono"
                       value="{{ old('code', $creating['suggested']) }}"
                       placeholder="أربع خانات">
                <p class="text-[11px] text-gray-400 mt-1">مقترح تلقائياً من موضعه تحت الأب — يمكن تغييره.</p>
                @else
                <input type="text" class="{{ $readClass }} font-mono" value="{{ $account->code }}" readonly>
                @endif
            </div>

            <div class="sm:col-span-2">
                <label class="block text-xs font-medium text-gray-600 mb-1">
                    اسم الحساب <span class="text-red-500">*</span>
                </label>
                <input type="text" name="name_ar" required maxlength="150" class="{{ $fieldClass }}"
                       value="{{ old('name_ar', $account?->name_ar) }}">
            </div>

            <div class="sm:col-span-2">
                <label class="block text-xs font-medium text-gray-600 mb-1">الاسم بالإنجليزية</label>
                <input type="text" name="name_en" maxlength="150" class="{{ $fieldClass }}" dir="ltr"
                       value="{{ old('name_en', $account?->name_en) }}"
                       placeholder="يُنسخ من العربي إن تُرك فارغاً">
            </div>

            <div class="sm:col-span-2">
                <label class="block text-xs font-medium text-gray-600 mb-1">
                    الحساب الأب @if($isNew)<span class="text-red-500">*</span>@endif
                </label>
                @if($isNew && ($creating['parent_locked'] ?? false))
                    <input type="hidden" name="parent_code" value="{{ $parent->code }}">
                    <input type="text" class="{{ $readClass }}" readonly
                           value="{{ $parent->code }} — {{ $parent->name_ar }}">
                @elseif($isNew)
                    <select name="parent_code" required class="{{ $fieldClass }}">
                        <option value="">— اختر الحساب الأب —</option>
                        @foreach($parents as $candidate)
                        <option value="{{ $candidate->code }}" @selected(old('parent_code') === $candidate->code)>
                            {{ str_repeat('—', $candidate->level - 1) }}
                            {{ $candidate->code }} — {{ $candidate->name_ar }}
                        </option>
                        @endforeach
                    </select>
                    <p class="text-[11px] text-gray-400 mt-1">
                        لا تظهر هنا الحسابات التي رُحِّلت عليها قيود ولا حسابات المستوى الرابع.
                    </p>
                @else
                    <input type="text" class="{{ $readClass }}" readonly
                           value="{{ $parent ? $parent->code . ' — ' . $parent->name_ar : 'حساب رئيسي (بلا أب)' }}">
                @endif
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">نوع الحساب</label>
                <input type="text" class="{{ $readClass }}" readonly
                       value="{{ $typeLabels[$parent?->type ?? $account?->type] ?? '—' }}">
                @if($isNew)
                <p class="text-[11px] text-gray-400 mt-1">يتبع الحساب الأب.</p>
                @endif
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">طبيعة الحساب</label>
                @php
                    $balance = $account?->normal_balance
                        ?? ($parent ? \App\Models\ChartOfAccount::normalBalanceFor($parent->type, $parent->subtype) : null);
                @endphp
                <input type="text" class="{{ $readClass }}" readonly
                       value="{{ $balance === 'debit' ? 'مدين' : ($balance === 'credit' ? 'دائن' : '—') }}">
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">الحساب الختامي</label>
                <input type="text" class="{{ $readClass }}" readonly
                       value="{{ $account?->closing_statement ?? ($parent ? (in_array($parent->type, ['revenue','expense'], true) ? 'قائمة الدخل' : 'الميزانية العمومية') : '—') }}">
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">القسم (مركز التكلفة)</label>
                <select name="department" class="{{ $fieldClass }}">
                    <option value="">بلا قسم</option>
                    @foreach($deptLabels as $value => $label)
                    <option value="{{ $value }}" @selected(old('department', $account?->department ?? $parent?->department) === $value)>
                        {{ $label }}
                    </option>
                    @endforeach
                </select>
            </div>

            <div class="sm:col-span-2">
                <label class="block text-xs font-medium text-gray-600 mb-1">ملاحظات</label>
                <input type="text" name="notes" maxlength="500" class="{{ $fieldClass }}"
                       value="{{ old('notes', $account?->notes) }}">
            </div>
        </div>

        {{-- بيانات إضافية --}}
        @unless($isNew)
        <div class="p-4 space-y-3">
            <h4 class="text-xs font-bold text-gray-500">بيانات إضافية</h4>

            <label class="flex items-center gap-2 text-sm {{ $locked ? 'text-gray-400 cursor-not-allowed' : 'text-gray-700 cursor-pointer' }}">
                <input type="checkbox" name="suspended" value="1" class="rounded border-gray-300"
                       @checked(old('suspended', !$account->is_active)) @disabled($locked)>
                إيقاف الحساب
            </label>

            @if($locked)
            <p class="text-[11px] text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
                هذا الحساب مستعمل في ترحيل العمليات اليومية، فإيقافه يُعطّل تسجيلها — ولذلك لا يقبل الإيقاف.
            </p>
            @endif

            <dl class="grid grid-cols-2 gap-3 text-xs pt-2 border-t border-gray-100">
                <div>
                    <dt class="text-gray-500">المستوى</dt>
                    <dd class="font-semibold text-gray-700">{{ $account->level }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">قابل للترحيل</dt>
                    <dd class="font-semibold text-gray-700">{{ $account->is_posting ? 'نعم' : 'لا (تجميعي)' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">المصدر</dt>
                    <dd class="font-semibold text-gray-700">{{ $account->is_system ? 'شجرة USALI الأساسية' : 'مُضاف يدوياً' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">الرصيد الحالي</dt>
                    <dd class="font-semibold text-gray-700">{{ number_format($account->balance_with_children, 2) }}</dd>
                </div>
            </dl>
        </div>
        @endunless
    </form>

    @if(!$isNew && $blocker === null)
    <form id="delete-{{ $account->code }}" method="POST" action="{{ route('coa.destroy', $account->code) }}" class="hidden">
        @csrf @method('DELETE')
    </form>
    @endif
    @endif
</div>
