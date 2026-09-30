<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * حساب واحد في شجرة حسابات الفندق (USALI).
 * A single account in the USALI hotel chart of accounts.
 *
 * @property string $code
 * @property string|null $parent_code
 * @property string $name_en
 * @property string $name_ar
 * @property string $type
 * @property string $subtype
 * @property string|null $department
 * @property bool $is_posting
 * @property string $normal_balance
 * @property bool $is_active
 * @property bool $is_system
 * @property string|null $notes
 * @property int $level
 */
class ChartOfAccount extends Model
{
    use HasFactory;

    protected $table = 'chart_of_accounts';

    /** أنواع الحسابات ذات الرصيد المدين طبيعياً */
    public const DEBIT_TYPES = ['asset', 'expense'];

    /** أنواع الحسابات ذات الرصيد الدائن طبيعياً */
    public const CREDIT_TYPES = ['liability', 'equity', 'revenue'];

    /** أنواع فرعية تعكس الرصيد الطبيعي لنوعها (حسابات مقابلة) */
    public const CONTRA_SUBTYPES = ['contra_asset', 'contra_revenue'];

    public const TYPES = ['asset', 'liability', 'equity', 'revenue', 'expense'];

    public const DEPARTMENTS = [
        'rooms', 'fnb', 'spa', 'laundry', 'parking',
        'admin', 'sales', 'maintenance', 'utilities',
    ];

    protected $fillable = [
        'code', 'parent_code', 'name_en', 'name_ar',
        'type', 'subtype', 'department',
        'is_posting', 'normal_balance', 'is_active', 'level',
        'is_system', 'notes',
    ];

    protected $casts = [
        'is_posting' => 'boolean',
        'is_active'  => 'boolean',
        'is_system'  => 'boolean',
        'level'      => 'integer',
    ];

    /**
     * الرصيد الطبيعي يُشتق دائماً ولا يُترك للإدخال اليدوي، والترحيل يُمنع
     * عن الفروع غير الطرفية — القاعدتان مفروضتان في القاعدة أيضاً، وهنا
     * حتى على sqlite الذي لا يقبل CHECK بعد الإنشاء.
     */
    protected static function booted(): void
    {
        static::saving(function (self $account): void {
            $account->normal_balance = static::normalBalanceFor($account->type, $account->subtype);

            if ($account->level < 3) {
                $account->is_posting = false;
            }
        });
    }

    // ───────────────────────── العلاقات / Relationships ─────────────────────────

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_code', 'code');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_code', 'code')->orderBy('code');
    }

    /** الأبناء وأبناؤهم حتى نهاية الشجرة */
    public function childrenRecursive(): HasMany
    {
        return $this->children()->with('childrenRecursive');
    }

    /** سلسلة الآباء حتى الجذر */
    public function parentRecursive(): BelongsTo
    {
        return $this->parent()->with('parentRecursive');
    }

    /** كل الأجداد من الأقرب إلى الجذر */
    public function ancestors(): array
    {
        $chain   = [];
        $current = $this->parent;

        while ($current !== null) {
            $chain[]  = $current;
            $current  = $current->parent;
        }

        return $chain;
    }

    /** كل الأحفاد بأي عمق */
    public function descendants(): \Illuminate\Support\Collection
    {
        $out = collect();

        foreach ($this->children as $child) {
            $out->push($child);
            $out = $out->merge($child->descendants());
        }

        return $out;
    }

    /**
     * سطور القيود المرحّلة على هذا الحساب. الربط بالكود لا بالمعرّف: الكود
     * محاسبيّ ثابت ومعروف (4110، 6330…) فيبقى القيد مقروءاً في أي تصدير أو
     * نسخة احتياطية دون الرجوع لمفتاح داخلي، وهو نفس أسلوب parent_code.
     */
    public function journalLines(): HasMany
    {
        return $this->hasMany(JournalLine::class, 'account_code', 'code');
    }

    /**
     * رصيد الحساب نفسه (دون فروعه) = مجموع المدين − الدائن أو العكس بحسب
     * طبيعته، فيكون الرصيد الطبيعي موجباً دائماً.
     */
    public function getBalanceAttribute(): float
    {
        $sums = $this->journalLines()
            ->selectRaw('COALESCE(SUM(debit), 0) as total_debit, COALESCE(SUM(credit), 0) as total_credit')
            ->first();

        $debit  = (float) ($sums->total_debit ?? 0);
        $credit = (float) ($sums->total_credit ?? 0);

        return $this->normal_balance === 'debit' ? $debit - $credit : $credit - $debit;
    }

    /**
     * رصيد الحساب شاملاً كل فروعه — الحساب الأب لا تُرحَّل عليه قيود مباشرةً،
     * فرصيده هو حاصل جمع أوراقه.
     */
    public function getBalanceWithChildrenAttribute(): float
    {
        $codes = $this->descendants()->pluck('code')->push($this->code)->all();

        $sums = JournalLine::whereIn('account_code', $codes)
            ->selectRaw('COALESCE(SUM(debit), 0) as total_debit, COALESCE(SUM(credit), 0) as total_credit')
            ->first();

        $debit  = (float) ($sums->total_debit ?? 0);
        $credit = (float) ($sums->total_credit ?? 0);

        return $this->normal_balance === 'debit' ? $debit - $credit : $credit - $debit;
    }

    // ───────────────────────── النطاقات / Scopes ─────────────────────────

    /** الحسابات التي تقبل القيود فقط (أوراق الشجرة النشطة) */
    public function scopePostingAccounts(Builder $query): Builder
    {
        return $query->where('is_posting', true)->where('is_active', true);
    }

    public function scopeByDepartment(Builder $query, ?string $dept): Builder
    {
        return $dept === null
            ? $query->whereNull('department')
            : $query->where('department', $dept);
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeRoots(Builder $query): Builder
    {
        return $query->whereNull('parent_code');
    }

    // ───────────────────────── منطق المحاسبة / Accounting logic ─────────────────────────

    /**
     * الرصيد الطبيعي لنوع حساب: الأصول والمصروفات مدينة، والخصوم وحقوق
     * الملكية والإيرادات دائنة — وتنعكس القاعدة في الحسابات المقابلة
     * (مجمّع الإهلاك أصل دائن، والخصومات إيراد مدين).
     */
    public static function normalBalanceFor(string $type, ?string $subtype = null): string
    {
        if ($subtype === 'contra_asset') {
            return 'credit';
        }

        if ($subtype === 'contra_revenue') {
            return 'debit';
        }

        return in_array($type, self::DEBIT_TYPES, true) ? 'debit' : 'credit';
    }

    /** الرصيد الطبيعي لهذا الحساب بعينه */
    public function getNormalBalance(): string
    {
        return static::normalBalanceFor($this->type, $this->subtype);
    }

    public function isDebitBalance(): bool
    {
        return $this->getNormalBalance() === 'debit';
    }

    public function isCreditBalance(): bool
    {
        return $this->getNormalBalance() === 'credit';
    }

    /**
     * أثر مبلغ مدين/دائن على رصيد هذا الحساب: موجب إذا وافق طبيعته.
     * يُستعمل في احتساب الأرصدة من سطور القيود.
     */
    public function signedAmount(float $debit, float $credit): float
    {
        return $this->isDebitBalance()
            ? round($debit - $credit, 2)
            : round($credit - $debit, 2);
    }

    public function isLeaf(): bool
    {
        return $this->children()->count() === 0;
    }

    /** الوعاء المالي (صندوق أو بنك) المرتبط بهذا الحساب، إن وُجد */
    public function paymentAccount(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(PaymentAccount::class, 'account_code', 'code');
    }

    /** هل رُحِّل على هذا الحساب قيد واحد على الأقل؟ */
    public function hasJournalLines(): bool
    {
        return $this->journalLines()->exists();
    }

    /**
     * الحساب الختامي: إلى أين يُرحَّل رصيده في نهاية السنة. الإيرادات
     * والمصروفات تُقفل في قائمة الدخل، وما عداها يُرحَّل في الميزانية.
     */
    public function getClosingStatementAttribute(): string
    {
        return in_array($this->type, ['revenue', 'expense'], true)
            ? 'قائمة الدخل'
            : 'الميزانية العمومية';
    }

    /**
     * أول كود حرّ تحت هذا الحساب، أو null إن امتلأت فروعه أو بلغ آخر مستوى.
     *
     * الترقيم هرمي بأربع خانات: الجذر X000، ثم XY00، ثم XYZ0، ثم XYZW —
     * فابن حسابٍ في المستوى N يختلف عنه في الخانة رقم N وحدها. لذا يُشتق
     * الكود من بنية الشجرة لا من عدّاد، فيبقى الرقم دالاً على موضعه.
     */
    public function nextChildCode(): ?string
    {
        if ($this->level >= 4) {
            return null;
        }

        $taken  = $this->children()->pluck('code')->flip();
        $digits = str_split(str_pad($this->code, 4, '0'));

        for ($digit = 1; $digit <= 9; $digit++) {
            $candidate       = $digits;
            $candidate[$this->level] = (string) $digit;

            // الخانات بعد موضع الابن تبقى أصفاراً — وهي ما يحجزه لأبنائه هو
            for ($i = $this->level + 1; $i < 4; $i++) {
                $candidate[$i] = '0';
            }

            $code = implode('', $candidate);
            if (!$taken->has($code) && !static::where('code', $code)->exists()) {
                return $code;
            }
        }

        return null;
    }

    /**
     * حساب أساسي تجميعي: من بذرة USALI وله فروع.
     *
     * هذه هي عظام الشجرة لا أوراقها. اسمه ليس تسميةً داخلية بل عنوان سطرٍ في
     * الميزانية وقائمة الدخل وتقرير كل مركز تكلفة، وهو لا يحمل رصيداً خاصاً
     * أصلاً — رصيده حاصل جمع فروعه. فتعديله يُعيد تسمية تقارير صدرت من قبل،
     * وإيقافه يُخفي فرعه كاملاً من الشجرة. لذا يُقرأ ولا يُكتب، وتبقى الإضافة
     * تحته مفتوحة لأنها لا تمسّ بنيته.
     */
    public function isLockedStructure(): bool
    {
        return $this->is_system && $this->children()->exists();
    }

    /** سبب منع التعديل بالعربية، أو null */
    public function editBlocker(): ?string
    {
        return $this->isLockedStructure()
            ? 'حساب أساسي في بنية شجرة USALI (' . $this->code . ') — اسمه عنوانُ سطرٍ '
              . 'في التقارير ورصيده حاصل جمع فروعه، فلا يُعدَّل ولا يُوقَف. '
              . 'التعديل يكون على فروعه، والإضافة تحته مسموحة.'
            : null;
    }

    /**
     * سبب منع الحذف بالعربية، أو null إن كان الحذف مسموحاً.
     *
     * الحذف في المحاسبة استثناء لا قاعدة: حساب رُحِّل عليه قيد جزءٌ من سجل
     * التدقيق، وحساب من البذرة كوده مثبّت في شيفرة الترحيل. فلا يُحذف إلا ما
     * أضافه المستخدم ولم يُستعمل بعد؛ وما عداه يُوقَف ولا يُمحى.
     */
    public function deletionBlocker(): ?string
    {
        if ($this->isLockedStructure()) {
            return 'حساب أساسي في بنية شجرة USALI — لا يُحذف ولا يُعدَّل.';
        }

        if ($this->is_system) {
            return 'حساب أساسي من شجرة USALI — يُمكن إيقافه لا حذفه.';
        }

        if ($this->children()->exists()) {
            return 'للحساب فروع — احذفها أولاً أو أوقِف الحساب.';
        }

        if ($this->hasJournalLines()) {
            return 'رُحِّلت على الحساب قيود — سجل التدقيق لا يُحذف منه، والبديل إيقاف الحساب.';
        }

        // حساب وعاءٍ مالي يُدار من صفحته لا من هنا، وحذفه يقطع ارتباط الوعاء به
        if (($vessel = $this->paymentAccount) !== null) {
            return 'هذا حساب الوعاء المالي «' . $vessel->name . '» — يُدار من صفحة الصناديق والبنوك.';
        }

        return null;
    }

    // ───────────────────────── العرض / Presentation ─────────────────────────

    /** الاسم حسب لغة الواجهة الحالية */
    public function getNameAttribute(): string
    {
        return app()->getLocale() === 'ar'
            ? ($this->name_ar ?: $this->name_en)
            : ($this->name_en ?: $this->name_ar);
    }

    public function getLabelAttribute(): string
    {
        return $this->code . ' — ' . $this->name;
    }

    public function getTypeLabelArAttribute(): string
    {
        return match ($this->type) {
            'asset'     => 'أصول',
            'liability' => 'خصوم',
            'equity'    => 'حقوق ملكية',
            'revenue'   => 'إيرادات',
            'expense'   => 'مصروفات',
            default     => $this->type,
        };
    }

    public function getDepartmentLabelArAttribute(): ?string
    {
        return match ($this->department) {
            'rooms'       => 'الغرف',
            'fnb'         => 'الأطعمة والمشروبات',
            'spa'         => 'المنتجع والترفيه',
            'laundry'     => 'المغسلة',
            'parking'     => 'المواقف',
            'admin'       => 'الإدارة والعموميات',
            'sales'       => 'المبيعات والتسويق',
            'maintenance' => 'الصيانة والهندسة',
            'utilities'   => 'المرافق',
            default       => null,
        };
    }
}
