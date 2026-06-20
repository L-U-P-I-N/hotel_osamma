<?php
namespace App\Http\Controllers;

use App\Services\CashSettlementService;
use Illuminate\Http\Request;

class SettlementController extends Controller
{
    public function __construct(private CashSettlementService $service) {}

    public function index()
    {
        $settlement = $this->service->getOrCreateTodaySettlement(auth()->user());
        $settlement->load(['withdrawals.expense', 'user', 'lockedBy']);
        $perCurrency = $this->service->getPerCurrencyTotals($settlement);

        return view('settlement.index', compact('settlement', 'perCurrency'));
    }

    public function addWithdrawal(Request $request)
    {
        $request->validate([
            'amount'               => 'required|numeric|min:0.01',
            'currency'             => 'required|in:YER,SAR,USD',
            'withdrawal_date'      => 'required|date',
            'withdrawn_by_name'    => 'required|string',
            'handed_by_name'       => 'required|string',
            'notes'                => 'nullable|string',
            'withdrawal_type'      => 'required|in:currency_exchange',
            'exchange_to_currency' => 'required|in:YER,SAR,USD',
            'exchange_to_amount'   => 'required|numeric|min:0.01',
        ]);

        $settlement = $this->service->getOrCreateTodaySettlement(auth()->user());
        $this->service->addWithdrawal($settlement, $request->all());

        return back()->with('success', 'تم تسجيل صرف العملة بنجاح');
    }

    public function saveSignatures(Request $request)
    {
        $request->validate([
            'employee_signature' => 'required|string',
            'admin_signature'    => 'required|string',
        ]);

        $settlement = $this->service->getOrCreateTodaySettlement(auth()->user());
        $this->service->saveSignatures($settlement, $request->employee_signature, $request->admin_signature);

        return back()->with('success', 'تم حفظ التوقيعات بنجاح');
    }

    public function lock(Request $request)
    {
        try {
            $settlement = $this->service->getOrCreateTodaySettlement(auth()->user());
            $this->service->lockSettlement($settlement, auth()->user());
            return back()->with('success', 'تم إقفال الحساب بنجاح');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
