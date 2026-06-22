<?php
namespace App\Http\Controllers;

use App\Models\Guest;
use Illuminate\Http\Request;

class GuestController extends Controller
{
    public function statement(Guest $guest)
    {
        $reservations = $guest->reservations()
            ->with(['room', 'payments.receivedBy', 'extraCharges.addedBy'])
            ->withTrashed()
            ->orderBy('check_in_date', 'desc')
            ->get();

        $totalPaid    = $reservations->sum('paid_amount');
        $totalAmount  = $reservations->sum('total_amount');
        $totalBalance = $totalAmount - $totalPaid;

        $allTransactions = [];
        foreach ($reservations as $res) {
            foreach ($res->payments as $pmt) {
                $allTransactions[] = [
                    'type' => 'payment',
                    'date' => $pmt->payment_date ?? $pmt->created_at,
                    'description' => 'دفعة للحجز #' . $res->id,
                    'amount' => $pmt->amount,
                    'direction' => 'out',
                    'reservation_id' => $res->id,
                    'data' => $pmt,
                ];
            }
            foreach ($res->extraCharges as $charge) {
                $allTransactions[] = [
                    'type' => 'charge',
                    'date' => $charge->charge_date ?? $charge->created_at,
                    'description' => $charge->description ?? $charge->type,
                    'amount' => $charge->amount,
                    'direction' => 'in',
                    'reservation_id' => $res->id,
                    'data' => $charge,
                ];
            }
            $allTransactions[] = [
                'type' => 'reservation',
                'date' => $res->check_in_date,
                'description' => 'حجز غرفة #' . ($res->room->room_number ?? $res->id),
                'amount' => $res->total_amount,
                'direction' => 'in',
                'reservation_id' => $res->id,
                'data' => $res,
            ];
        }

        usort($allTransactions, fn($a, $b) => $b['date']->timestamp <=> $a['date']->timestamp);

        return view('guests.statement', compact('guest', 'reservations', 'totalPaid', 'totalBalance', 'allTransactions'));
    }

    public function statementPdf(Guest $guest)
    {
        $reservations = $guest->reservations()
            ->with(['room', 'payments.receivedBy'])
            ->withTrashed()
            ->orderBy('check_in_date', 'desc')
            ->get();

        $totalPaid    = $reservations->sum('paid_amount');
        $totalAmount  = $reservations->sum('total_amount');
        $totalBalance = $totalAmount - $totalPaid;

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('guests.statement_pdf', compact('guest', 'reservations', 'totalPaid', 'totalBalance'));
        $pdf->setPaper('a4', 'portrait');

        $dompdf = $pdf->getDomPDF();
        $opts   = $dompdf->getOptions();
        $opts->setFontDir(storage_path('fonts'));
        $opts->setFontCache(storage_path('fonts'));
        $dompdf->setOptions($opts);

        return $pdf->stream('statement-' . $guest->id . '.pdf');
    }
}
