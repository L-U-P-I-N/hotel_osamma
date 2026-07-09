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
        $reservation->load(['guest', 'room.roomType', 'payments', 'companions']);
        return view('checkout.show', compact('reservation'));
    }

    public function done(Reservation $reservation)
    {
        $reservation->load(['guest', 'room.roomType', 'payments', 'roomInspections', 'companions', 'extraCharges']);
        return view('checkout.done', compact('reservation'));
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
        ]);

        try {
            $data = $request->except(['_token', '_method']);
            $data['has_damage'] = $request->boolean('has_damage');
            $data['left_unpaid'] = $request->boolean('left_unpaid');

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
