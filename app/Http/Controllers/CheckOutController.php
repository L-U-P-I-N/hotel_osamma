<?php
namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Services\CheckOutService;
use Illuminate\Http\Request;

class CheckOutController extends Controller
{
    public function __construct(private CheckOutService $checkOutService) {}

    public function show(Reservation $reservation)
    {
        $reservation->load(['guest', 'room.roomType', 'payments', 'companions', 'extraCharges', 'roomInspections']);

        // تسوية المغادرة المبكرة تُحسب هنا للعرض فقط؛ الخدمة تُعيد حسابها عند
        // الإرسال فلا يُعتمد على قيمة قادمة من المتصفح.
        $earlyQuote = $this->checkOutService->earlyDepartureQuote($reservation);

        return view('checkout.show', compact('reservation', 'earlyQuote'));
    }

    public function done(Reservation $reservation)
    {
        $reservation->load(['guest', 'room.roomType', 'payments', 'roomInspections', 'companions', 'extraCharges']);
        return view('checkout.done', compact('reservation'));
    }

    public function undo(Reservation $reservation)
    {
        try {
            $this->checkOutService->undoCheckOut($reservation, auth()->user());
            return back()->with('success', 'تم التراجع عن تسجيل الخروج — الحجز مسجَّل دخول الآن.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function process(Request $request, Reservation $reservation)
    {
        $request->validate([
            'has_damage' => 'boolean',
            'damage_description' => 'required_if:has_damage,1|nullable|string',
            'compensation_amount' => 'nullable|numeric|min:0',
            'inspection_images.*' => 'nullable|file|mimes:jpg,jpeg,png|max:5120',
            'remaining_payment' => 'nullable|numeric|min:0',
            'remaining_method' => 'nullable|in:cash,bank_transfer,pos',
            'remaining_bank_receipt' => 'required_if:remaining_method,bank_transfer|nullable|file|mimes:jpg,jpeg,png,pdf|max:10240',
            'left_unpaid' => 'boolean',
            'collect_purchases' => 'boolean',
            'checkout_notes' => 'nullable|string|max:1000',
            'early_departure' => 'boolean',
            'credit_action' => 'nullable|in:carry,payout',
            'credit_method' => 'nullable|in:cash,pos,bank_transfer',
        ], [
            'checkout_notes.max' => 'ملاحظة الخروج طويلة جداً (1000 حرف كحد أقصى)',
        ]);

        try {
            $data = $request->except(['_token', '_method']);
            $data['has_damage'] = $request->boolean('has_damage');
            $data['left_unpaid'] = $request->boolean('left_unpaid');
            $data['collect_purchases'] = $request->boolean('collect_purchases');
            $data['early_departure'] = $request->boolean('early_departure');

            if ($request->hasFile('inspection_images')) {
                $data['inspection_images'] = $request->file('inspection_images');
            }
            if ($request->hasFile('remaining_bank_receipt')) {
                $data['remaining_bank_receipt'] = $request->file('remaining_bank_receipt');
            }
            if ($request->hasFile('compensation_bank_receipt')) {
                $data['compensation_bank_receipt'] = $request->file('compensation_bank_receipt');
            }

            $reservation = $this->checkOutService->processCheckOut($reservation, $data, auth()->user());

            return redirect()->route('checkout.done', $reservation->id);
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
