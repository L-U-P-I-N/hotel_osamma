<?php
namespace App\Exports;

use App\Models\Guest;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class GuestsReportExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    public function __construct(private string $from, private string $to) {}

    public function collection()
    {
        return Guest::withCount(['reservations as period_reservations' => fn($q) => $q->whereDate('check_in_date', '>=', $this->from)->whereDate('check_in_date', '<=', $this->to)->whereNotIn('status', ['cancelled'])])
            ->having('period_reservations', '>', 0)
            ->orderByDesc('period_reservations')
            ->get();
    }

    public function headings(): array
    {
        return [
            'الاسم', 'الجنسية', 'رقم الجوال', 'عدد الحجوزات',
        ];
    }

    public function map($guest): array
    {
        return [
            $guest->full_name,
            $guest->nationality,
            $guest->phone,
            $guest->period_reservations,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['color' => ['rgb' => 'FFFFFF'], 'bold' => true], 'fill' => ['fillType' => 'solid', 'color' => ['rgb' => '0F4C75']]],
        ];
    }
}
