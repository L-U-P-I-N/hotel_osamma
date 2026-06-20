<?php
namespace App\Exports;

use App\Models\Reservation;
use App\Models\Room;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class OccupancyReportExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    public function __construct(private string $from, private string $to) {}

    public function collection(): Collection
    {
        $totalRooms = Room::count();

        return Reservation::select(
                DB::raw('DATE(check_in_date) as date'),
                DB::raw('COUNT(*) as count')
            )
            ->whereDate('check_in_date', '>=', $this->from)
            ->whereDate('check_in_date', '<=', $this->to)
            ->whereIn('status', ['checked_in', 'checked_out'])
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(fn($r) => (object)[
                'date'       => $r->date,
                'count'      => $r->count,
                'total'      => $totalRooms,
                'percent'    => $totalRooms > 0 ? round(($r->count / $totalRooms) * 100) : 0,
            ]);
    }

    public function headings(): array
    {
        return ['التاريخ', 'عدد الغرف المشغولة', 'إجمالي الغرف', 'نسبة الإشغال %'];
    }

    public function map($row): array
    {
        return [
            \Carbon\Carbon::parse($row->date)->format('Y/m/d'),
            $row->count,
            $row->total,
            $row->percent . '%',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->setRightToLeft(true);
        return [
            1 => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => 'solid', 'color' => ['rgb' => '0F4C75']]],
        ];
    }
}
