<?php
namespace App\Services;

use App\Models\Account;
use App\Models\JournalEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

class JournalService
{
    /**
     * يُرحِّل قيد يومية متوازن (مجموع المدين = مجموع الدائن) للحسابات المحددة
     * بكودها. أي فشل بالترحيل يُسجَّل بـLog::error فقط ولا يُسقِط العملية
     * التشغيلية الأصلية التي استدعت هذه الدالة.
     *
     * @param array<int, array{account_code: string, debit?: float, credit?: float, notes?: string}> $lines
     */
    public function post(
        string $date,
        string $description,
        string $sourceType,
        int $sourceId,
        array $lines,
        ?int $userId = null
    ): ?JournalEntry {
        try {
            return $this->postOrFail($date, $description, $sourceType, $sourceId, $lines, $userId);
        } catch (Throwable $e) {
            Log::error('JournalService::post failed', [
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'description' => $description,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * نفس post() لكن ترمي الاستثناء بدل ابتلاعه — للاستخدام حيث يُراد التحقق
     * الصارم (كالاختبارات).
     */
    public function postOrFail(
        string $date,
        string $description,
        string $sourceType,
        int $sourceId,
        array $lines,
        ?int $userId = null
    ): JournalEntry {
        if (count($lines) < 2) {
            throw new InvalidArgumentException('القيد يحتاج سطرين على الأقل.');
        }

        $totalDebit = 0;
        $totalCredit = 0;
        foreach ($lines as $line) {
            $totalDebit += (float) ($line['debit'] ?? 0);
            $totalCredit += (float) ($line['credit'] ?? 0);
        }

        if (round($totalDebit, 2) !== round($totalCredit, 2)) {
            throw new InvalidArgumentException("القيد غير متوازن: مدين {$totalDebit} != دائن {$totalCredit}");
        }

        return DB::transaction(function () use ($date, $description, $sourceType, $sourceId, $lines, $userId) {
            $entry = JournalEntry::create([
                'entry_date' => $date,
                'description' => $description,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'created_by' => $userId,
            ]);

            foreach ($lines as $line) {
                $account = Account::where('code', $line['account_code'])->first();

                if (! $account) {
                    throw new InvalidArgumentException("حساب غير موجود بالكود: {$line['account_code']}");
                }

                $entry->lines()->create([
                    'account_id' => $account->id,
                    'debit' => $line['debit'] ?? 0,
                    'credit' => $line['credit'] ?? 0,
                    'notes' => $line['notes'] ?? null,
                ]);
            }

            return $entry;
        });
    }
}
