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
    ];

    protected $casts = [
        'is_posting' => 'boolean',
        'is_active'  => 'boolean',
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

    // ───────────────────────── العرض / Presentation ─────────────────────────

    /** الاسم حسب لغة الواجهة الحالية */
    public function getNameAttribute(): string
    {
        return app()->getLocale() === 'ar' ? $this->name_ar : $this->name_en;
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
