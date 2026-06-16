<?php
namespace App\Http\Controllers;

use App\Exports\DailyReportExport;
use App\Models\Reservation;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class DailyReportController extends Controller
{
    private function getReservations(string $date)
    {
        return Reservation::with(['guest', 'room.roomType', 'companions'])
            ->whereDate('check_in_date', '<=', $date)
            ->whereDate('check_out_date', '>=', $date)
            ->whereIn('status', ['checked_in', 'confirmed'])
            ->orderBy('room_id')
            ->get();
    }

    public function index(Request $request)
    {
        $date = $request->input('date', today()->toDateString());
        $reservations = $this->getReservations($date);
        return view('reports.daily', compact('reservations', 'date'));
    }

    public function exportPdf(Request $request)
    {
        $date = $request->input('date', today()->toDateString());
        $reservations = $this->getReservations($date);
        $pdf = Pdf::loadView('reports.daily_pdf', compact('reservations', 'date'));
        $pdf->setPaper('a4', 'landscape');
        return $pdf->download('daily-report-' . $date . '.pdf');
    }

    public function exportExcel(Request $request)
    {
        $date = $request->input('date', today()->toDateString());
        return Excel::download(new DailyReportExport($date), 'daily-report-' . $date . '.xlsx');
    }
}
