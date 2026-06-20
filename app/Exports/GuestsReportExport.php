<?php
namespace App\Exports;

use App\Models\Guest;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\StringValueBinder;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class GuestsReportExport extends StringValueBinder implements
    FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithCustomValueBinder
{
    public function __construct(private string $from, private string $to) {}

    public function collection()
    {
        return Guest::withCount([
            'reservations as total_reservations',
            'reservations as period_reservations' => fn($q) =>
                $q->whereDate('check_in_date', '>=', $this->from)
                  ->whereDate('check_in_date', '<=', $this->to)
                  ->whereNotIn('status', ['cancelled']),
        ])
        ->having('period_reservations', '>', 0)
        ->orderByDesc('period_reservations')
        ->get();
    }

    public function headings(): array
    {
        return ['اسم النزيل', 'الجنسية', 'المهنة', 'رقم الهوية', 'رقم الجوال', 'الحجوزات في الفترة', 'إجمالي الحجوزات'];
    }

    public function map($guest): array
    {
        return [
            $guest->full_name,
            $guest->nationality ?? '',
            $guest->occupation ?? '',
            $guest->id_number ?? '',
            $guest->phone ?? '',
            $guest->period_reservations ?? 0,
            $guest->total_reservations ?? 0,
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
