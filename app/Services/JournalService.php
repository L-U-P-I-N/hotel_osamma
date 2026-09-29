<?php
namespace App\Services;

use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

/**
 * ترحيل القيود إلى دفتر الأستاذ الموحّد (chart_of_accounts وفق USALI).
 *
 * كل قيد يحمل مفتاحاً فريداً (source_type, source_id, event): إعادة ترحيل نفس
 * الحدث لا تُنتج قيداً ثانياً بل تُعيد القيد القائم. هذا ما يجعل ترحيل البيانات
 * التاريخية وإعادة تشغيل عملية فاشلة آمنَين بلا ازدواج.
 */
class JournalService
{
    /**
     * يُرحِّل قيداً متوازناً. الفشل يُسجَّل ولا يُسقِط العملية التشغيلية.
     *
     * تنبيه: هذا السلوك المتساهل موروث ويبقى للمسارات التي لا تحتمل التوقف؛
     * المسارات المالية الجديدة تستدعي postOrFail() داخل معاملتها كي لا تنجح
     * عملية بلا قيدها.
     *
     * @param array<int, array{account_code: string, debit?: float, credit?: float, notes?: string}> $lines
     */
    public function post(
        string $date,
        string $description,
        string $sourceType,
        int $sourceId,
        array $lines,
        ?int $userId = null,
        string $event = 'legacy',
        ?string $batchRef = null
    ): ?JournalEntry {
        try {
            return $this->postOrFail($date, $description, $sourceType, $sourceId, $lines, $userId, $event, $batchRef);
        } catch (Throwable $e) {
            Log::error('JournalService::post failed', [
                'source_type' => $sourceType,
                'source_id'   => $sourceId,
                'event'       => $event,
                'description' => $description,
                'error'       => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * نفس post() لكن ترمي الاستثناء بدل ابتلاعه — للمسارات التي يجب أن تسقط
     * كاملةً إن تعذّر ترحيل قيدها.
     */
    public function postOrFail(
        string $date,
        string $description,
        string $sourceType,
        int $sourceId,
        array $lines,
        ?int $userId = null,
        string $event = 'legacy',
        ?string $batchRef = null
    ): JournalEntry {
        $this->assertBalanced($lines);

        return DB::transaction(function () use ($date, $description, $sourceType, $sourceId, $lines, $userId, $event, $batchRef) {
            // نفس الحدث مرحَّل سابقاً ⇒ نُعيد قيده كما هو بدل إنشاء ثانٍ
            $existing = JournalEntry::where('source_type', $sourceType)
                ->where('source_id', $sourceId)
                ->where('event', $event)
                ->first();

            if ($existing) {
                return $existing;
            }

            $entry = JournalEntry::create([
                'entry_date'  => $date,
                'description' => $description,
                'source_type' => $sourceType,
                'source_id'   => $sourceId,
                'event'       => $event,
                'batch_ref'   => $batchRef,
                'created_by'  => $userId,
            ]);

            foreach ($lines as $line) {
                $account = $this->resolveAccount($line['account_code']);

                $entry->lines()->create([
                    'account_code' => $account->code,
                    'debit'        => $line['debit'] ?? 0,
                    'credit'       => $line['credit'] ?? 0,
                    'notes'        => $line['notes'] ?? null,
                ]);
            }

            return $entry;
        });
    }

    /**
     * الحساب يجب أن يكون ورقةً نشطة في الشجرة: الترحيل على حساب أب يُفسد كل
     * تجميع لاحق (يُحتسب المبلغ مرتين: في الأب وفي مجموع أبنائه).
     */
    private function resolveAccount(string $code): ChartOfAccount
    {
        $account = ChartOfAccount::where('code', $code)->first();

        if (!$account) {
            throw new ModelNotFoundException("حساب غير موجود بالكود: {$code}");
        }

        if (!$account->is_active) {
            throw new InvalidArgumentException("الحساب {$code} ({$account->name_ar}) موقوف — لا يقبل قيوداً.");
        }

        if (!$account->is_posting) {
            throw new InvalidArgumentException(
                "الحساب {$code} ({$account->name_ar}) حسابٌ أب تجميعي — الترحيل يكون على حساب فرعي منه."
            );
        }

        return $account;
    }

    /** @param array<int, array<string, mixed>> $lines */
    private function assertBalanced(array $lines): void
    {
        if (count($lines) < 2) {
            throw new InvalidArgumentException('القيد يحتاج سطرين على الأقل.');
        }

        $totalDebit = $totalCredit = 0;
        foreach ($lines as $line) {
            $debit  = (float) ($line['debit'] ?? 0);
            $credit = (float) ($line['credit'] ?? 0);

            if ($debit < 0 || $credit < 0) {
                throw new InvalidArgumentException('لا يُقبل مبلغ سالب في سطر قيد — استخدم الطرف المقابل.');
            }
            if ($debit > 0 && $credit > 0) {
                throw new InvalidArgumentException('سطر القيد الواحد إمّا مدين أو دائن، لا الاثنين معاً.');
            }

            $totalDebit  += $debit;
            $totalCredit += $credit;
        }

        if (round($totalDebit, 2) !== round($totalCredit, 2)) {
            throw new InvalidArgumentException("القيد غير متوازن: مدين {$totalDebit} != دائن {$totalCredit}");
        }
    }
}
