<?php
namespace App\Services;

use App\Models\Reservation;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\GovernmentGuestExport;

class GovernmentExportService
{
    public function exportGuestPDF(Reservation $reservation): string
    {
        $reservation->load(['guest', 'companions', 'room.roomType', 'createdBy']);

        $pdf = Pdf::loadView('exports.government_guest', [
            'reservation' => $reservation,
            'hotel' => \App\Models\Hotel::first(),
        ]);
        $pdf->setPaper('a4');

        $filename = 'government_exports/guest_' . $reservation->id . '_' . now()->format('Ymd_His') . '.pdf';
        Storage::disk('private')->put($filename, $pdf->output());

        $reservation->update([
            'government_exported' => true,
            'government_exported_at' => now(),
        ]);

        AuditLogService::log('export', $reservation, null, ['format' => 'pdf', 'file' => $filename]);

        return $filename;
    }

    public function exportGuestExcel(Reservation $reservation): string
    {
        $reservation->load(['guest', 'companions', 'room.roomType', 'createdBy']);

        $filename = 'government_exports/guest_' . $reservation->id . '_' . now()->format('Ymd_His') . '.xlsx';

        Excel::store(new GovernmentGuestExport($reservation), $filename, 'private');

        $reservation->update([
            'government_exported' => true,
            'government_exported_at' => now(),
        ]);

        AuditLogService::log('export', $reservation, null, ['format' => 'excel', 'file' => $filename]);

        return $filename;
    }
}
