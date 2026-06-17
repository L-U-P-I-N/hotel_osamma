<?php
namespace App\Services;

use App\Models\CashSettlement;
use App\Models\CashWithdrawal;
use App\Models\Payment;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Support\Facades\DB;

class CashSettlementService
{
    public function getOrCreateTodaySettlement(User $user): CashSettlement
    {
        $settlement = CashSettlement::firstOrCreate(
            ['user_id' => $user->id, 'shift_date' => today()],
            ['status' => 'open', 'total_received' => 0, 'total_withdrawals' => 0, 'net_balance' => 0]
        );

        $this->computeTotals($settlement);
        return $settlement->fresh();
    }

    public function addWithdrawal(CashSettlement $settlement, array $data): CashWithdrawal
    {
        if ($settlement->status === 'locked') {
            throw new \RuntimeException('لا يمكن إضافة سحب لحساب مقفل');
        }

        $withdrawal = CashWithdrawal::create([
            'cash_settlement_id'  => $settlement->id,
            'amount'              => $data['amount'],
            'currency'            => $data['currency'] ?? 'YER',
            'withdrawal_date'     => $data['withdrawal_date'] ?? now(),
            'withdrawn_by_name'   => $data['withdrawn_by_name'],
            'handed_by_name'      => $data['handed_by_name'],
            'notes'               => $data['notes'] ?? null,
            'withdrawal_type'     => $data['withdrawal_type'] ?? 'expense',
            'exchange_to_currency'=> $data['exchange_to_currency'] ?? null,
            'exchange_to_amount'  => $data['exchange_to_amount'] ?? null,
        ]);

        $this->computeTotals($settlement);
        return $withdrawal;
    }

    public function saveSignatures(CashSettlement $settlement, string $empSig, string $adminSig): void
    {
        $settlement->update([
            'employee_signature' => $empSig,
            'admin_signature' => $adminSig,
        ]);
    }

    public function lockSettlement(CashSettlement $settlement, User $lockedBy): void
    {
        if ($settlement->status === 'locked') {
            throw new \RuntimeException('الحساب مقفل مسبقاً');
        }

        $settlement->update([
            'status' => 'locked',
            'locked_by' => $lockedBy->id,
            'locked_at' => now(),
        ]);

        AuditLogService::log('update', $settlement, ['status' => 'open'], ['status' => 'locked'], $lockedBy);
    }

    public function computeTotals(CashSettlement $settlement): void
    {
        $totalReceived = Payment::whereHas('reservation', function ($q) use ($settlement) {
            $q->where('created_by', $settlement->user_id);
        })->whereDate('payment_date', $settlement->shift_date)
          ->where('currency', 'YER')
          ->where('method', 'cash')
          ->sum('amount');

        $totalWithdrawals = CashWithdrawal::where('cash_settlement_id', $settlement->id)
            ->where('currency', 'YER')
            ->sum('amount');

        $settlement->update([
            'total_received'    => $totalReceived,
            'total_withdrawals' => $totalWithdrawals,
            'net_balance'       => $totalReceived - $totalWithdrawals,
        ]);
    }

    public function getPerCurrencyTotals(CashSettlement $settlement): array
    {
        $currencies = ['YER', 'SAR', 'USD'];

        // Payments per currency (cash only)
        $paymentsRaw = Payment::whereHas('reservation', function ($q) use ($settlement) {
            $q->where('created_by', $settlement->user_id);
        })
        ->whereDate('payment_date', $settlement->shift_date)
        ->where('method', 'cash')
        ->selectRaw('currency, SUM(amount) as total')
        ->groupBy('currency')
        ->pluck('total', 'currency')
        ->toArray();

        // Regular expenses per currency
        $expensesRaw = CashWithdrawal::where('cash_settlement_id', $settlement->id)
        ->where('withdrawal_type', 'expense')
        ->selectRaw('currency, SUM(amount) as total')
        ->groupBy('currency')
        ->pluck('total', 'currency')
        ->toArray();

        // Currency exchanges (e.g. gave 20,000 YER -> received 50 SAR)
        $exchanges = CashWithdrawal::where('cash_settlement_id', $settlement->id)
        ->where('withdrawal_type', 'currency_exchange')
        ->get();

        // Payment details per currency
        $paymentDetails = Payment::whereHas('reservation', function ($q) use ($settlement) {
            $q->where('created_by', $settlement->user_id);
        })
        ->whereDate('payment_date', $settlement->shift_date)
        ->where('method', 'cash')
        ->with('reservation.room')
        ->get();

        $byCurrency = [];
        foreach ($currencies as $cur) {
            $received    = (float)($paymentsRaw[$cur] ?? 0);
            $expenses    = (float)($expensesRaw[$cur] ?? 0);
            $exchangeOut = (float)$exchanges->where('currency', $cur)->sum('amount');
            $totalOut    = $expenses + $exchangeOut;

            $byCurrency[$cur] = [
                'received'     => $received,
                'expenses'     => $expenses,
                'exchange_out' => $exchangeOut,
                'total_out'    => $totalOut,
                'net'          => $received - $totalOut,
                'has_data'     => ($received > 0 || $totalOut > 0),
            ];
        }

        return [
            'by_currency'     => $byCurrency,
            'exchanges'       => $exchanges,
            'payment_details' => $paymentDetails,
        ];
    }
}
