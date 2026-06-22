<?php
namespace App\Http\Controllers;

use App\Models\Guest;
use Illuminate\Http\Request;

class GuestController extends Controller
{
    public function statement(Guest $guest)
    {
        $reservations = $guest->reservations()
            ->with(['room', 'payments.receivedBy'])
            ->withTrashed()
            ->orderBy('check_in_date', 'desc')
            ->get();

        $totalPaid    = $reservations->sum('paid_amount');
        $totalAmount  = $reservations->sum('total_amount');
        $totalBalance = $totalAmount - $totalPaid;

        return view('guests.statement', compact('guest', 'reservations', 'totalPaid', 'totalBalance'));
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
