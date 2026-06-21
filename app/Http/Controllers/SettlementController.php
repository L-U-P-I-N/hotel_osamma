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

    public function lock(Request $request)
    {
        try {
            $request->validate(['actual_amount' => 'required|numeric|min:0']);
            $settlement = $this->service->getOrCreateTodaySettlement(auth()->user());
            $this->service->lockSettlement($settlement, auth()->user(), (float)$request->actual_amount);
            return back()->with('success', 'تم إقفال الحساب بنجاح');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
