<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * وعاء مالٍ حقيقي: درج وردية، خزنة، حساب بنكي بعينه، أو جهاز شبكة.
 *
 * وجوده يجيب سؤالين لم يكن للنظام جواب عنهما: أين ذهب مبلغ هذه الدفعة فعلاً؟
 * وكم في كل حساب بنكي على حِدة؟ الرصيد يُقرأ من دفتر الأستاذ لا من جمعٍ موازٍ،
 * فلا يمكن أن ينحرف عنه.
 */
class PaymentAccount extends Model
{
    use HasFactory;

    public const TYPE_SHIFT_CASH = 'shift_cash';
    public const TYPE_SAFE       = 'safe';
    public const TYPE_BANK       = 'bank';
    public const TYPE_POS        = 'pos';

    public const TYPES = [
        self::TYPE_SHIFT_CASH => 'درج نقدية الوردية',
        self::TYPE_SAFE       => 'خزنة / صندوق',
        self::TYPE_BANK       => 'حساب بنكي',
        self::TYPE_POS        => 'جهاز شبكة (POS)',
    ];

    /** طريقة الدفع التي تُستعمل فيها كل نوع — تربط اختيار الموظف بالوعاء. */
    public const METHOD_TYPES = [
        'cash'          => [self::TYPE_SHIFT_CASH, self::TYPE_SAFE],
        'bank_transfer' => [self::TYPE_BANK],
        'pos'           => [self::TYPE_POS],
    ];

    /** الحساب الأب في شجرة USALI الذي يُنشأ تحته حساب وسيلة جديدة. */
    private const PARENT_CODES = [
        self::TYPE_SHIFT_CASH => '1110',
        self::TYPE_SAFE       => '1100',
        self::TYPE_BANK       => '1130',
        self::TYPE_POS        => '1100',
    ];

    protected $fillable = [
        'name', 'type', 'account_code', 'bank_name', 'account_number', 'iban',
        'currency', 'commission_rate', 'is_active', 'is_default', 'sort_order', 'notes',
    ];

    protected $casts = [
        'commission_rate' => 'decimal:2',
        'is_active'       => 'boolean',
        'is_default'      => 'boolean',
    ];

    public function ledgerAccount()
    {
        return $this->belongsTo(ChartOfAccount::class, 'account_code', 'code');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    /** نقدٌ في اليد فعلاً — يُعدّ عند إقفال الوردية، بخلاف البنك والشبكة. */
    public function getIsCashAttribute(): bool
    {
        return in_array($this->type, [self::TYPE_SHIFT_CASH, self::TYPE_SAFE], true);
    }

    /** الرصيد من دفتر الأستاذ — مصدر واحد للحقيقة لا جمع موازٍ. */
    public function getBalanceAttribute(): float
    {
        return $this->ledgerAccount?->balance ?? 0.0;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForMethod($query, string $method)
    {
        return $query->whereIn('type', self::METHOD_TYPES[$method] ?? []);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    /**
     * الوسيلة التي تُستعمل حين لا يختار الموظف واحدة صراحةً — الوسيلة المعلَّمة
     * افتراضيةً لطريقة الدفع، وإلا أول وسيلة نشطة من نوعها.
     */
    public static function defaultFor(string $method): ?self
    {
        return static::active()->forMethod($method)->where('is_default', true)->ordered()->first()
            ?? static::active()->forMethod($method)->ordered()->first();
    }

    /**
     * يُنشئ حساباً في شجرة USALI لوسيلة جديدة (حساب بنكي إضافي مثلاً) تحت أبيها
     * الصحيح بأول كود شاغر. إنشاؤه هنا يُبقي شجرةً واحدة: لا يضطر المستخدم لتحرير
     * دليل الحسابات يدوياً كلما فتح حساباً بنكياً.
     */
    public static function createLedgerAccount(string $type, string $name): ChartOfAccount
    {
        $parentCode = self::PARENT_CODES[$type] ?? '1100';
        $parent     = ChartOfAccount::where('code', $parentCode)->firstOrFail();

        $base = (int) $parentCode;
        for ($candidate = $base + 1; $candidate < $base + 100; $candidate++) {
            $code = (string) $candidate;
            if (ChartOfAccount::where('code', $code)->exists()) {
                continue;
            }

            return ChartOfAccount::create([
                'code'           => $code,
                'parent_code'    => $parentCode,
                'name_ar'        => $name,
                'name_en'        => $name,
                'type'           => 'asset',
                'subtype'        => 'current',
                'department'     => $parent->department,
                'is_posting'     => true,
                'normal_balance' => 'debit',
                'is_active'      => true,
                'level'          => ($parent->level ?? 2) + 1,
            ]);
        }

        throw new \RuntimeException("لا يوجد كود شاغر تحت الحساب {$parentCode} — راجع دليل الحسابات.");
    }
}
