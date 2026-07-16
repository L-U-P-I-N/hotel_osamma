<?php
namespace App\Http\Controllers;

use App\Models\CashWithdrawal;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\Shift;

/**
 * فحص تكامل البيانات المالية — للقراءة فقط، لا يُعدّل أو يحذف أي شيء. يكتشف
 * الحالات الشائعة التي تُنتج "لخبطة" في الورديات والحسابات والسحبيات: سجلات
 * يتيمة بلا وردية، سجلات منسوبة لوردية تاريخها مختلف عن تاريخها هي، مجاميع
 * ورديات غير محدَّثة (لم تُعَد حسابها بعد آخر تعديل)، وحالات إقفال متضاربة.
 * يُستخدم كخطوة أولى آمنة قبل أي تصحيح فعلي — كل الأدوات اللازمة للتصحيح
 * (نقل مستلمة/سحب إلى وردية محدَّدة، إعادة الاحتساب) موجودة مسبقاً في الشاشات
 * الأخرى؛ هذا التقرير فقط يحدّد أين تُستخدَم.
 */
class FinancialIntegrityController extends Controller
{
    /** هامش تقريب مسموح عند مقارنة المجاميع المحفوظة بالمجاميع الفعلية. */
    private const TOLERANCE = 0.5;

    public function index()
    {
        if (!auth()->user()->isAdmin()) {
            abort(403, 'هذا التقرير مقصور على المدير فقط');
        }

        $orphanPayments = Payment::whereNull('shift_id')
            ->with(['reservation.guest', 'receivedBy'])
            ->orderByDesc('payment_date')
            ->get();

        $orphanWithdrawals = CashWithdrawal::whereNull('shift_id')
            ->orderByDesc('withdrawal_date')
            ->get();

        $orphanExpenses = Expense::where('payment_method', 'cash')
            ->whereNull('shift_id')
            ->with('paidBy')
            ->orderByDesc('expense_date')
            ->get();

        $misattributedPayments = Payment::whereNotNull('shift_id')
            ->with(['shift', 'reservation.guest', 'receivedBy'])
            ->get()
            ->filter(fn ($p) => $p->shift && $p->payment_date
                && $p->payment_date->toDateString() !== $p->shift->shift_date->toDateString());

        $misattributedWithdrawals = CashWithdrawal::whereNotNull('shift_id')
            ->with('shift.user')
            ->get()
            ->filter(fn ($w) => $w->shift && $w->withdrawal_date
                && $w->withdrawal_date->toDateString() !== $w->shift->shift_date->toDateString());

        $misattributedExpenses = Expense::whereNotNull('shift_id')
            ->with(['shift.user', 'paidBy'])
            ->get()
            ->filter(fn ($e) => $e->shift && $e->expense_date
                && $e->expense_date->toDateString() !== $e->shift->shift_date->toDateString());

        $staleShifts = $this->findStaleShifts();

        $corruptedShifts = Shift::with('user')
            ->where(function ($q) {
                $q->where(function ($q2) { $q2->where('is_closed', true)->whereNull('closed_at'); })
                  ->orWhere(function ($q2) { $q2->where('is_closed', false)->whereNotNull('closed_at'); });
            })
            ->orderByDesc('shift_date')
            ->get();

        $duplicateOpenUserIds = Shift::where('is_closed', false)
            ->select('user_id')
            ->groupBy('user_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('user_id');
        $duplicateOpenShifts = $duplicateOpenUserIds->isEmpty() ? collect() : Shift::whereIn('user_id', $duplicateOpenUserIds)
            ->where('is_closed', false)
            ->with('user')
            ->orderBy('user_id')->orderBy('id')
            ->get();

        $totalIssues = $orphanPayments->count() + $orphanWithdrawals->count() + $orphanExpenses->count()
            + $misattributedPayments->count() + $misattributedWithdrawals->count() + $misattributedExpenses->count()
            + $staleShifts->count() + $corruptedShifts->count() + $duplicateOpenShifts->count();

        return view('reports.financial_integrity', compact(
            'orphanPayments', 'orphanWithdrawals', 'orphanExpenses',
            'misattributedPayments', 'misattributedWithdrawals', 'misattributedExpenses',
            'staleShifts', 'corruptedShifts', 'duplicateOpenShifts', 'totalIssues'
        ));
    }

    /**
     * الورديات التي مجاميعها المحفوظة (total_received_*, total_withdrawals_*,
     * total_refunds_*) لا تطابق المجموع الفعلي لسجلاتها المرتبطة — تعني أن
     * computeTotals() لم يُستدعَ بعد آخر تعديل (نقل/حذف/إضافة سجل) فبقيت
     * الأرقام المعروضة قديمة. لا يُحفَظ أي تصحيح هنا (قراءة فقط)؛ التصحيح
     * الفعلي يتم من خلال إعادة تنفيذ العملية التي تستدعي computeTotals (مثال:
     * أي عملية نقل من شاشة الورديات).
     */
    private function findStaleShifts()
    {
        $recvSums = Payment::selectRaw('shift_id, currency, SUM(amount) as total')
            ->whereNotNull('shift_id')->groupBy('shift_id', 'currency')->get()
            ->groupBy('shift_id');
        $wdrSums = CashWithdrawal::selectRaw('shift_id, currency, SUM(amount) as total')
            ->whereNotNull('shift_id')->groupBy('shift_id', 'currency')->get()
            ->groupBy('shift_id');
        $rfdSums = Refund::selectRaw('shift_id, currency, SUM(amount) as total')
            ->whereNotNull('shift_id')->groupBy('shift_id', 'currency')->get()
            ->groupBy('shift_id');

        $currencyCols = ['YER' => 'yer', 'SAR' => 'sar', 'USD' => 'usd'];
        $stale = collect();

        Shift::with('user')->orderByDesc('shift_date')->chunk(200, function ($shifts) use (&$stale, $recvSums, $wdrSums, $rfdSums, $currencyCols) {
            foreach ($shifts as $shift) {
                $diffs = [];
                $this->compareSums($shift, $recvSums->get($shift->id), 'total_received_', 'استلام', $currencyCols, $diffs);
                $this->compareSums($shift, $wdrSums->get($shift->id), 'total_withdrawals_', 'سحب', $currencyCols, $diffs);
                $this->compareSums($shift, $rfdSums->get($shift->id), 'total_refunds_', 'استرجاع', $currencyCols, $diffs);

                if (!empty($diffs)) {
                    $stale->push(['shift' => $shift, 'diffs' => $diffs]);
                }
            }
        });

        return $stale;
    }

    private function compareSums(Shift $shift, $sumsForShift, string $columnPrefix, string $label, array $currencyCols, array &$diffs): void
    {
        $byCurrency = collect($sumsForShift ?? [])->keyBy('currency');
        foreach ($currencyCols as $currency => $col) {
            $expected = round((float) ($byCurrency->get($currency)->total ?? 0), 2);
            $stored   = round((float) $shift->{$columnPrefix . $col}, 2);
            if (abs($expected - $stored) > self::TOLERANCE) {
                $diffs[] = "{$label} {$currency}: محفوظ " . number_format($stored, 0) . " ≠ فعلي " . number_format($expected, 0);
            }
        }
    }
}
