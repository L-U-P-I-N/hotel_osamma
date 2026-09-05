<?php

namespace App\Services;

use App\Models\ChartOfAccount;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * منطق شجرة الحسابات — طبقة نطاق خالصة لا تعرف شيئاً عن HTTP.
 * Pure domain layer for the chart of accounts: no requests, no responses,
 * no redirects. Controllers and console commands both consume it.
 */
class COAService
{
    /**
     * يبني الشجرة المتداخلة كاملةً باستعلام واحد ثم تجميع في الذاكرة —
     * تفادياً لاستعلام لكل عقدة (N+1) في شجرة من مئات الحسابات.
     *
     * @param  array{type?:string,department?:string,only_active?:bool,posting_only?:bool}  $filters
     * @return array<int,array<string,mixed>>
     */
    public function buildTree(array $filters = []): array
    {
        $accounts = $this->queryAccounts($filters);

        // تجميع الأبناء حسب كود الأب — O(n) بدل بحث متكرر
        $childrenByParent = [];
        foreach ($accounts as $account) {
            $childrenByParent[$account->parent_code ?? '__root__'][] = $account;
        }

        // عند الفلترة قد يغيب الأب عن النتيجة، فيصير ابنه جذراً معلّقاً؛
        // نرفعه إلى المستوى الأعلى بدل إسقاطه من الشجرة.
        $presentCodes = $accounts->pluck('code')->flip();
        $roots        = $childrenByParent['__root__'] ?? [];

        foreach ($accounts as $account) {
            if ($account->parent_code !== null && !$presentCodes->has($account->parent_code)) {
                $roots[] = $account;
            }
        }

        usort($roots, static fn ($a, $b) => strcmp($a->code, $b->code));

        return array_map(
            fn (ChartOfAccount $root) => $this->nodeToArray($root, $childrenByParent),
            $roots
        );
    }

    /**
     * الحسابات التي تقبل القيود، اختيارياً مقيّدة بنوع محاسبي.
     *
     * @return Collection<int,ChartOfAccount>
     */
    public function getPostingAccounts(?string $type = null, ?string $department = null): Collection
    {
        if ($type !== null && !in_array($type, ChartOfAccount::TYPES, true)) {
            throw new InvalidArgumentException("نوع حساب غير معروف: {$type}");
        }

        return ChartOfAccount::query()
            ->postingAccounts()
            ->when($type !== null, fn ($q) => $q->where('type', $type))
            ->when($department !== null, fn ($q) => $q->where('department', $department))
            ->orderBy('code')
            ->get();
    }

    /**
     * يتحقق من توازن قيد اليومية وصلاحية كل سطر فيه.
     *
     * كل سطر: ['account_code' => string, 'debit' => float, 'credit' => float]
     *
     * @param  array<int,array<string,mixed>>  $lines
     * @return array{valid:bool, errors:array<int,string>, total_debit:float, total_credit:float, difference:float}
     */
    public function validateJournalEntry(array $lines): array
    {
        $errors      = [];
        $totalDebit  = 0.0;
        $totalCredit = 0.0;
        $tolerance   = (float) config('hotel.coa.balance_tolerance', 0.005);

        if (count($lines) < 2) {
            $errors[] = 'القيد يجب أن يحتوي على سطرين على الأقل (طرف مدين وطرف دائن).';
        }

        // استعلام واحد لكل الأكواد المستخدمة بدل استعلام لكل سطر
        $codes = array_values(array_filter(array_map(
            static fn ($line) => $line['account_code'] ?? null,
            $lines
        )));

        $accounts = ChartOfAccount::whereIn('code', $codes)->get()->keyBy('code');

        foreach ($lines as $index => $line) {
            $position = $index + 1;
            $code     = $line['account_code'] ?? null;
            $debit    = round((float) ($line['debit'] ?? 0), 2);
            $credit   = round((float) ($line['credit'] ?? 0), 2);

            if ($code === null || $code === '') {
                $errors[] = "السطر {$position}: كود الحساب مطلوب.";
                continue;
            }

            $account = $accounts->get($code);

            if ($account === null) {
                $errors[] = "السطر {$position}: الحساب {$code} غير موجود في شجرة الحسابات.";
                continue;
            }

            if (!$account->is_active) {
                $errors[] = "السطر {$position}: الحساب {$code} موقوف ولا يقبل القيود.";
            }

            if (!$account->is_posting) {
                $errors[] = "السطر {$position}: الحساب {$code} حساب تجميعي — القيد يُرحَّل على الحسابات الطرفية فقط.";
            }

            if ($debit < 0 || $credit < 0) {
                $errors[] = "السطر {$position}: لا يجوز أن يكون المبلغ سالباً.";
            }

            if ($debit > 0 && $credit > 0) {
                $errors[] = "السطر {$position}: لا يجوز أن يكون السطر مديناً ودائناً معاً.";
            }

            if ($debit === 0.0 && $credit === 0.0) {
                $errors[] = "السطر {$position}: يجب إدخال مبلغ مدين أو دائن.";
            }

            $totalDebit  += $debit;
            $totalCredit += $credit;
        }

        $totalDebit  = round($totalDebit, 2);
        $totalCredit = round($totalCredit, 2);
        $difference  = round($totalDebit - $totalCredit, 2);

        if (abs($difference) > $tolerance) {
            $errors[] = sprintf(
                'القيد غير متوازن: مجموع المدين %s ومجموع الدائن %s — الفرق %s.',
                number_format($totalDebit, 2),
                number_format($totalCredit, 2),
                number_format($difference, 2)
            );
        }

        return [
            'valid'        => $errors === [],
            'errors'       => $errors,
            'total_debit'  => $totalDebit,
            'total_credit' => $totalCredit,
            'difference'   => $difference,
        ];
    }

    /**
     * فحص سلامة الشجرة: أب مفقود، عمق مخالف لمستوى الأب، ترحيل على فرع،
     * ورصيد طبيعي مخالف لطبيعة الحساب.
     *
     * @return array<int,string>  قائمة المخالفات — فارغة تعني شجرة سليمة
     */
    public function findIntegrityIssues(): array
    {
        $issues   = [];
        $accounts = ChartOfAccount::orderBy('code')->get();
        $byCode   = $accounts->keyBy('code');
        $maxLevel = (int) config('hotel.coa.max_level', 4);

        foreach ($accounts as $account) {
            if ($account->parent_code !== null && !$byCode->has($account->parent_code)) {
                $issues[] = "الحساب {$account->code}: أبوه {$account->parent_code} غير موجود.";
                continue;
            }

            if ($account->parent_code === null && $account->level !== 1) {
                $issues[] = "الحساب {$account->code}: جذر بلا أب لكن مستواه {$account->level} بدل 1.";
            }

            if ($account->parent_code !== null) {
                $parent = $byCode->get($account->parent_code);

                if ($account->level !== $parent->level + 1) {
                    $issues[] = "الحساب {$account->code}: مستواه {$account->level} "
                              . "بينما مستوى أبيه {$parent->code} هو {$parent->level}.";
                }

                if ($account->type !== $parent->type) {
                    $issues[] = "الحساب {$account->code}: نوعه ({$account->type}) "
                              . "يخالف نوع أبيه {$parent->code} ({$parent->type}).";
                }

                if ($parent->is_posting) {
                    $issues[] = "الحساب {$parent->code}: حساب أب لكنه مفتوح للترحيل.";
                }
            }

            if ($account->level < 1 || $account->level > $maxLevel) {
                $issues[] = "الحساب {$account->code}: مستوى غير صالح ({$account->level}).";
            }

            if ($account->is_posting && $account->level < 3) {
                $issues[] = "الحساب {$account->code}: مفتوح للترحيل رغم أن مستواه {$account->level}.";
            }

            $expected = ChartOfAccount::normalBalanceFor($account->type, $account->subtype);
            if ($account->normal_balance !== $expected) {
                $issues[] = "الحساب {$account->code}: رصيده الطبيعي {$account->normal_balance} "
                          . "والمتوقع {$expected}.";
            }
        }

        return $issues;
    }

    /**
     * مسار الحساب من الجذر إليه — للعرض في التقارير (1000 › 1100 › 1110).
     */
    public function getAccountPath(string $code, string $separator = ' › '): string
    {
        $account = ChartOfAccount::where('code', $code)->first();

        if ($account === null) {
            return $code;
        }

        $names = array_map(
            static fn (ChartOfAccount $a) => $a->name,
            array_reverse($account->ancestors())
        );

        $names[] = $account->name;

        return implode($separator, $names);
    }

    /** كل الأكواد الطرفية تحت حساب أب — لتجميع الأرصدة في التقارير */
    public function getLeafCodesUnder(string $code): array
    {
        $root = ChartOfAccount::where('code', $code)->first();

        if ($root === null) {
            return [];
        }

        if ($root->is_posting) {
            return [$root->code];
        }

        return $root->descendants()
            ->filter(static fn (ChartOfAccount $a) => $a->is_posting)
            ->pluck('code')
            ->values()
            ->all();
    }

    // ───────────────────────── داخلي / Internal ─────────────────────────

    /** @return Collection<int,ChartOfAccount> */
    private function queryAccounts(array $filters): Collection
    {
        return ChartOfAccount::query()
            ->when($filters['only_active'] ?? true, fn ($q) => $q->where('is_active', true))
            ->when($filters['posting_only'] ?? false, fn ($q) => $q->where('is_posting', true))
            ->when(!empty($filters['type']), fn ($q) => $q->where('type', $filters['type']))
            ->when(!empty($filters['department']), fn ($q) => $q->where('department', $filters['department']))
            ->orderBy('code')
            ->get();
    }

    /**
     * @param  array<string,array<int,ChartOfAccount>>  $childrenByParent
     * @return array<string,mixed>
     */
    private function nodeToArray(ChartOfAccount $account, array $childrenByParent): array
    {
        $children = $childrenByParent[$account->code] ?? [];

        return [
            'code'           => $account->code,
            'parent_code'    => $account->parent_code,
            'name_en'        => $account->name_en,
            'name_ar'        => $account->name_ar,
            'type'           => $account->type,
            'subtype'        => $account->subtype,
            'department'     => $account->department,
            'is_posting'     => $account->is_posting,
            'normal_balance' => $account->normal_balance,
            'is_active'      => $account->is_active,
            'level'          => $account->level,
            'children'       => array_map(
                fn (ChartOfAccount $child) => $this->nodeToArray($child, $childrenByParent),
                $children
            ),
        ];
    }
}
