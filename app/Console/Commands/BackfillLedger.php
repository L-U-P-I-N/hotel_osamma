<?php

namespace App\Console\Commands;

use App\Models\CashWithdrawal;
use App\Models\Expense;
use App\Models\ExtraCharge;
use App\Models\GuestCredit;
use App\Models\JournalEntry;
use App\Models\Payment;
use App\Models\PaymentAccount;
use App\Models\Refund;
use App\Models\Salary;
use App\Services\JournalService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * ترحيل البيانات التاريخية إلى دفتر الأستاذ.
 *
 * الحركات التشغيلية (دفعات، مصروفات، رواتب، استرجاعات…) مسجَّلة في جداولها منذ
 * أشهر، لكن جزءاً منها لم يُرحَّل قيداً — إمّا لأنه أُدخل قبل تفعيل القيود، أو لأن
 * ترحيله فشل وابتُلع فشله. هذا الأمر يمرّ على كل حركة ويُنشئ قيدها الغائب.
 *
 * لا يُعاد تعبئة شيء من الصفر: كل قيد له مفتاح فريد (source_type, source_id,
 * event)، فالحركة المرحَّلة سابقاً تُتخطّى، وتشغيل الأمر مرتين لا يُنتج قيداً
 * مكرراً. ولهذا يصحّ تشغيله على قاعدة إنتاج حيّة.
 *
 *   php artisan hotel:backfill-ledger --dry-run          معاينة بلا كتابة
 *   php artisan hotel:backfill-ledger --from=2026-09-01  من تاريخ معيّن
 */
class BackfillLedger extends Command
{
    protected $signature = 'hotel:backfill-ledger
                            {--from= : أقدم تاريخ يُرحَّل (افتراضياً: كل التاريخ)}
                            {--to=   : أحدث تاريخ يُرحَّل}
                            {--dry-run : معاينة ما سيُرحَّل دون كتابة أي قيد}';

    protected $description = 'ترحيل الحركات التشغيلية التاريخية إلى قيود دفتر الأستاذ (آمن التكرار)';

    private array $stats = [];
    private array $failures = [];

    public function handle(JournalService $journal): int
    {
        $from   = $this->option('from');
        $to     = $this->option('to');
        $dryRun = (bool) $this->option('dry-run');

        $this->info('═══ ترحيل الحركات التاريخية إلى دفتر الأستاذ ═══');
        $this->line('الفترة: ' . ($from ?: 'من البداية') . ' ← ' . ($to ?: 'حتى اليوم'));
        if ($dryRun) {
            $this->warn('وضع المعاينة: لن يُكتب أي قيد.');
        }
        $this->newLine();

        $this->backfillPayments($journal, $from, $to, $dryRun);
        $this->backfillRefunds($journal, $from, $to, $dryRun);
        $this->backfillExpenses($journal, $from, $to, $dryRun);
        $this->backfillSalaries($journal, $from, $to, $dryRun);
        $this->backfillWithdrawals($journal, $from, $to, $dryRun);
        $this->backfillDamageCharges($journal, $from, $to, $dryRun);
        $this->backfillGuestCredits($journal, $from, $to, $dryRun);

        $this->report($dryRun);

        return $this->failures === [] ? self::SUCCESS : self::FAILURE;
    }

    /* ═══════════════════ الحركات ═══════════════════ */

    /** دفعات النزلاء: نقدية الوردية مقابل إيراد الغرف. */
    private function backfillPayments(JournalService $journal, ?string $from, ?string $to, bool $dry): void
    {
        $query = Payment::query()->with('paymentAccount')->where('currency', 'YER');
        $this->applyDates($query, 'payment_date', $from, $to);

        $this->each('دفعات النزلاء', $query, function (Payment $payment) use ($journal, $dry) {
            $this->posted(
                $journal, $dry,
                $payment->payment_date?->toDateString() ?? $payment->created_at->toDateString(),
                'دفعة نزيل — حجز #' . $payment->reservation_id,
                Payment::class, $payment->id, 'payment.received',
                [
                    ['account_code' => $this->containerCode($payment->paymentAccount, $payment->method), 'debit' => $payment->amount],
                    ['account_code' => '4110', 'credit' => $payment->amount],
                ]
            );
        });
    }

    /** الاسترجاعات: مسموحات الغرف مقابل خروج نقدية. */
    private function backfillRefunds(JournalService $journal, ?string $from, ?string $to, bool $dry): void
    {
        $query = Refund::query()->with('paymentAccount')->where('currency', 'YER');
        $this->applyDates($query, 'refunded_at', $from, $to);

        $this->each('الاسترجاعات', $query, function (Refund $refund) use ($journal, $dry) {
            $this->posted(
                $journal, $dry,
                $refund->refunded_at->toDateString(),
                'استرجاع لنزيل — حجز #' . $refund->reservation_id,
                Refund::class, $refund->id, 'refund.issued',
                [
                    ['account_code' => '4190', 'debit' => $refund->amount],
                    ['account_code' => $this->containerCode($refund->paymentAccount, $refund->method), 'credit' => $refund->amount],
                ]
            );
        });
    }

    /** المصروفات: حساب الفئة مقابل نقدية الوردية أو ذمة دائنة. */
    private function backfillExpenses(JournalService $journal, ?string $from, ?string $to, bool $dry): void
    {
        $query = Expense::query()->with('paymentAccount')->where('currency', 'YER');
        $this->applyDates($query, 'expense_date', $from, $to);

        $this->each('المصروفات', $query, function (Expense $expense) use ($journal, $dry) {
            $this->posted(
                $journal, $dry,
                $expense->expense_date->toDateString(),
                'مصروف: ' . ($expense->recipient_name ?? '—'),
                Expense::class, $expense->id, 'expense.recorded',
                [
                    ['account_code' => Expense::categoryAccountCode($expense->category), 'debit' => $expense->amount],
                    // «لاحقاً» وحده ذمة دائنة؛ غيره يخرج من وعائه فعلاً
                    ['account_code' => $expense->payment_method === 'later'
                        ? '2150'
                        : $this->containerCode($expense->paymentAccount, $expense->payment_method), 'credit' => $expense->amount],
                ]
            );
        });
    }

    /**
     * الرواتب المصروفة. يُرحَّل قيد الاستحقاق ثم الصرف معاً: لولا الاستحقاق
     * لبقي حساب «رواتب مستحقة» مديناً بلا مقابل، ولاختفى المصروف من قائمة الدخل.
     */
    private function backfillSalaries(JournalService $journal, ?string $from, ?string $to, bool $dry): void
    {
        // لا عمود لتاريخ الصرف على قسيمة الراتب؛ الحالة "paid" هي المؤشّر،
        // وتاريخ القيد آخر يوم في شهر القسيمة لا يوم إدخالها.
        $query = Salary::query()->with('paymentAccount')->where('status', 'paid');
        $this->applyDates($query, 'updated_at', $from, $to);

        $this->each('الرواتب', $query, function (Salary $salary) use ($journal, $dry) {
            $date  = \Carbon\Carbon::createFromDate($salary->year, $salary->month, 1)
                ->endOfMonth()->toDateString();
            $label = ($salary->employee?->name ?? '—') . ' — ' . $salary->month . '/' . $salary->year;

            $this->posted(
                $journal, $dry, $date, 'استحقاق راتب: ' . $label,
                Salary::class, $salary->id, 'payroll.accrued',
                [
                    ['account_code' => '6113', 'debit'  => $salary->net_salary],
                    ['account_code' => '2410', 'credit' => $salary->net_salary],
                ]
            );

            $this->posted(
                $journal, $dry, $date, 'راتب مدفوع: ' . $label,
                Salary::class, $salary->id, 'payroll.paid',
                [
                    ['account_code' => '2410', 'debit'  => $salary->net_salary],
                    ['account_code' => $this->containerCode($salary->paymentAccount, 'cash', '1120'), 'credit' => $salary->net_salary],
                ]
            );
        });
    }

    /**
     * السحبيات النقدية المستقلة. المرتبطة بمصروف (expense_id) تُستثنى: مصروفها
     * مُرحَّل أصلاً، وترحيلها ثانيةً يُضاعف المصروف ويُنقص الصندوق مرتين.
     */
    private function backfillWithdrawals(JournalService $journal, ?string $from, ?string $to, bool $dry): void
    {
        $query = CashWithdrawal::query()->with('paymentAccount')
            ->where('currency', 'YER')
            ->where('withdrawal_type', 'expense')
            ->whereNull('expense_id');
        $this->applyDates($query, 'withdrawal_date', $from, $to);

        $this->each('السحبيات النقدية', $query, function (CashWithdrawal $withdrawal) use ($journal, $dry) {
            $fallback = $withdrawal->funding_source === 'general_safe' ? '1120' : '1111';

            $this->posted(
                $journal, $dry,
                ($withdrawal->withdrawal_date ?? $withdrawal->created_at)->toDateString(),
                'مصروف: ' . ($withdrawal->withdrawn_by_name ?? '—'),
                CashWithdrawal::class, $withdrawal->id, 'expense.shift_withdrawal',
                [
                    ['account_code' => Expense::categoryAccountCode('other'), 'debit' => $withdrawal->amount],
                    ['account_code' => $this->containerCode($withdrawal->paymentAccount, 'cash', $fallback), 'credit' => $withdrawal->amount],
                ]
            );
        });
    }

    /** تعويضات الأضرار: تحميل على ذمة النزيل مقابل إيراد تعويض. */
    private function backfillDamageCharges(JournalService $journal, ?string $from, ?string $to, bool $dry): void
    {
        $query = ExtraCharge::query()->where('type', 'damage');
        $this->applyDates($query, 'charge_date', $from, $to);

        $this->each('تعويضات الأضرار', $query, function (ExtraCharge $charge) use ($journal, $dry) {
            $this->posted(
                $journal, $dry,
                ($charge->charge_date ?? $charge->created_at)->toDateString(),
                'تعويض أضرار — حجز #' . $charge->reservation_id,
                ExtraCharge::class, $charge->id, 'charge.damage',
                [
                    ['account_code' => '1210', 'debit'  => $charge->amount],
                    ['account_code' => '4660', 'credit' => $charge->amount],
                ]
            );
        });
    }

    /** أرصدة النزلاء الدائنة القائمة والمصروفة. */
    private function backfillGuestCredits(JournalService $journal, ?string $from, ?string $to, bool $dry): void
    {
        $query = GuestCredit::query()->where('currency', 'YER');
        $this->applyDates($query, 'created_at', $from, $to);

        $this->each('متبقيات النزلاء', $query, function (GuestCredit $credit) use ($journal, $dry) {
            $this->posted(
                $journal, $dry,
                $credit->created_at->toDateString(),
                'ترحيل مبلغ متبقٍّ لنزيل — حجز #' . $credit->reservation_id,
                GuestCredit::class, $credit->id, 'allowance.early_departure',
                [
                    ['account_code' => '4195', 'debit'  => $credit->amount],
                    ['account_code' => '2230', 'credit' => $credit->amount],
                ]
            );

            if ($credit->status === GuestCredit::STATUS_SETTLED && $credit->settled_at) {
                $this->posted(
                    $journal, $dry,
                    $credit->settled_at->toDateString(),
                    'صرف مبلغ متبقٍّ لنزيل — حجز #' . $credit->reservation_id,
                    GuestCredit::class, $credit->id, 'credit.settled',
                    [
                        ['account_code' => '2230', 'debit'  => $credit->amount],
                        ['account_code' => '1111', 'credit' => $credit->amount],
                    ]
                );
            }
        });
    }

    /* ═══════════════════ أدوات ═══════════════════ */

    /**
     * حساب الوعاء الذي دخله المبلغ (أو خرج منه). الحركات القديمة قد تكون بلا
     * وعاء إن سبقت ربط الأوعية، فنستنتجه من طريقتها بدل افتراض النقدية —
     * فتحويل بنكي قديم لا يُسجَّل في درج الوردية.
     */
    private function containerCode(?PaymentAccount $account, ?string $method, string $fallback = '1111'): string
    {
        return $account?->account_code
            ?? PaymentAccount::defaultFor($method ?? 'cash')?->account_code
            ?? $fallback;
    }

    private function applyDates($query, string $column, ?string $from, ?string $to): void
    {
        if ($from) { $query->whereDate($column, '>=', $from); }
        if ($to)   { $query->whereDate($column, '<=', $to); }
    }

    /** يمرّ على السجلات على دفعات كي لا تُحمَّل قاعدة كبيرة في الذاكرة دفعةً. */
    private function each(string $label, $query, callable $handler): void
    {
        $total = (clone $query)->count();
        $this->stats[$label] = ['total' => $total, 'posted' => 0, 'skipped' => 0, 'failed' => 0];

        if ($total === 0) {
            $this->line("— {$label}: لا سجلات");
            return;
        }

        $bar = $this->output->createProgressBar($total);
        $this->currentLabel = $label;

        $query->orderBy('id')->chunkById(200, function ($rows) use ($handler, $bar) {
            foreach ($rows as $row) {
                $handler($row);
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();

        $s = $this->stats[$label];
        $this->line("— {$label}: رُحِّل {$s['posted']} · موجود سابقاً {$s['skipped']} · فشل {$s['failed']}");
    }

    private string $currentLabel = '';

    /**
     * يُرحّل قيداً واحداً ويُحصي النتيجة. الترحيل آمن التكرار: الحدث المرحَّل
     * سابقاً يُعاد قيده كما هو فيُحسب "موجوداً" لا مُرحَّلاً من جديد.
     */
    private function posted(
        JournalService $journal, bool $dry, string $date, string $description,
        string $sourceType, int $sourceId, string $event, array $lines
    ): void {
        $label = $this->currentLabel;

        $exists = JournalEntry::where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->where('event', $event)
            ->exists();

        if ($exists) {
            $this->stats[$label]['skipped']++;
            return;
        }

        if ($dry) {
            $this->stats[$label]['posted']++;
            return;
        }

        try {
            $journal->postOrFail($date, $description, $sourceType, $sourceId, $lines, null, $event);
            $this->stats[$label]['posted']++;
        } catch (\Throwable $e) {
            $this->stats[$label]['failed']++;
            $this->failures[] = "{$sourceType}#{$sourceId} ({$event}): " . $e->getMessage();
        }
    }

    private function report(bool $dry): void
    {
        $this->newLine();
        $this->info('═══ الخلاصة ═══');

        $rows = [];
        foreach ($this->stats as $label => $s) {
            $rows[] = [$label, $s['total'], $s['posted'], $s['skipped'], $s['failed']];
        }
        $this->table(['الحركة', 'الإجمالي', $dry ? 'سيُرحَّل' : 'رُحِّل', 'موجود سابقاً', 'فشل'], $rows);

        // ميزان المراجعة يجب أن يتوازن بعد الترحيل — وإلا فالدفاتر مكسورة
        $debit  = (float) DB::table('journal_lines')->sum('debit');
        $credit = (float) DB::table('journal_lines')->sum('credit');

        $this->line('مجموع المدين:  ' . number_format($debit, 2));
        $this->line('مجموع الدائن: ' . number_format($credit, 2));

        if (round($debit, 2) === round($credit, 2)) {
            $this->info('✔ ميزان المراجعة متوازن.');
        } else {
            $this->error('✘ ميزان المراجعة غير متوازن — راجع القيود قبل الاعتماد على أي تقرير.');
        }

        if ($this->failures !== []) {
            $this->newLine();
            $this->error('حركات لم تُرحَّل (' . count($this->failures) . '):');
            foreach (array_slice($this->failures, 0, 20) as $failure) {
                $this->line('  · ' . $failure);
            }
            if (count($this->failures) > 20) {
                $this->line('  … و' . (count($this->failures) - 20) . ' أخرى');
            }
        }
    }
}
