<?php

namespace Tests\Feature;

use App\Models\CashWithdrawal;
use App\Models\PaymentAccount;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ملخّص حركة النقدية في شاشة الصندوق العام.
 *
 * كانت الشاشة تعرض «الحالة الآن» وحركة حساب 1120 وحدها، فيبدو الصندوق كأنه
 * مصروفات بلا إيراد — والإيراد يدخل أدراج الورديات أولاً ولا يمرّ بالحساب إلا
 * عند التسليم. فصار للفترة بيانٌ واحد يجمع الطرفين.
 */
class GeneralSafePeriodSummaryTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function admin(): User
    {
        return User::role('admin')->firstOrFail();
    }

    /** وردية بأرقامها محفوظة مباشرةً — الملخّص يقرأها كما هي */
    private function shift(array $totals, $date = null): Shift
    {
        return Shift::create([
            'user_id'    => $this->admin()->id,
            'shift_date' => $date ?? today(),
            'started_at' => now()->subHours(3),
            'is_closed'  => true,
            'closed_at'  => now()->subHour(),
            'opening_balance_yer' => 0,
        ] + $totals);
    }

    private function safeExpense(float $amount, $date = null): CashWithdrawal
    {
        return CashWithdrawal::create([
            'shift_id'           => null,
            'funding_source'     => 'general_safe',
            'payment_account_id' => PaymentAccount::where('type', PaymentAccount::TYPE_SAFE)->value('id'),
            'amount'             => $amount,
            'currency'           => 'YER',
            'withdrawal_date'    => $date ?? today(),
            'withdrawn_by_name'  => 'مورّد',
            'handed_by_name'     => $this->admin()->name,
            'withdrawal_type'    => 'expense',
        ]);
    }

    private function summary(string $from = null, string $to = null): array
    {
        $query = http_build_query(array_filter([
            'from' => $from ?? today()->toDateString(),
            'to'   => $to   ?? today()->toDateString(),
        ]));

        return $this->actingAs($this->admin())
            ->get('/reports/general-safe?' . $query)
            ->assertOk()
            ->viewData('period');
    }

    public function test_the_summary_adds_shift_revenue_outflows_and_safe_expenses(): void
    {
        $this->shift([
            'total_received_yer'          => 120000,
            'total_received_cash_yer'     => 100000,
            'total_received_noncash_yer'  => 20000,
            'total_withdrawals_yer'       => 15000,
            'total_refunds_yer'           => 5000,
        ]);
        $this->safeExpense(7500);

        $period = $this->summary();

        $this->assertSame(100000.0, $period['received_cash']);
        $this->assertSame(20000.0,  $period['received_other']);
        $this->assertSame(15000.0,  $period['withdrawals']);
        $this->assertSame(5000.0,   $period['refunds']);
        $this->assertSame(7500.0,   $period['safe_out']);

        // 100,000 − 15,000 − 5,000 − 7,500
        $this->assertSame(72500.0, $period['net_cash'], 'الصافي لا يطابق البيان');
    }

    /** الإيراد غير النقدي يدخل البنك مباشرةً فلا يُضخّم النقد الموجود */
    public function test_non_cash_revenue_stays_out_of_the_net(): void
    {
        $this->shift([
            'total_received_yer'         => 90000,
            'total_received_cash_yer'    => 10000,
            'total_received_noncash_yer' => 80000,
        ]);

        $this->assertSame(10000.0, $this->summary()['net_cash']);
    }

    /**
     * مصروف الصندوق العام يُطرح مرة واحدة: سحبه shift_id = null فلا يدخل
     * مجاميع الورديات، ويُجمع هنا مستقلاً.
     */
    public function test_a_safe_expense_is_not_counted_twice(): void
    {
        $this->shift([
            'total_received_yer'      => 50000,
            'total_received_cash_yer' => 50000,
            'total_withdrawals_yer'   => 0,
        ]);
        $this->safeExpense(9000);

        $period = $this->summary();

        $this->assertSame(0.0, $period['withdrawals'], 'دخل مصروف الخزنة مجاميع الوردية');
        $this->assertSame(41000.0, $period['net_cash']);
    }

    /** وما خرج عن الفترة لا يدخلها */
    public function test_the_summary_is_bounded_by_the_period(): void
    {
        $this->shift(['total_received_yer' => 30000, 'total_received_cash_yer' => 30000], today()->subDays(10));
        $this->safeExpense(4000, today()->subDays(10));

        $period = $this->summary(today()->toDateString(), today()->toDateString());

        $this->assertSame(0.0, $period['received_cash']);
        $this->assertSame(0.0, $period['safe_out']);
        $this->assertSame(0, $period['shifts_count']);
    }
}
