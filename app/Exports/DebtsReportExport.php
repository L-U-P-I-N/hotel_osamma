<?php
namespace App\Exports;

use App\Models\Reservation;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DebtsReportExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    public function __construct() {}

    public function collection()
    {
        return Reservation::with(['guest', 'room'])
            ->whereIn('status', ['checked_in', 'checked_out'])
            ->whereRaw('paid_amount < total_amount')
            ->orderByRaw('(total_amount - paid_amount) DESC')
            ->get();
    }

    public function headings(): array
    {
        return [
            'النزيل', 'الغرفة', 'الحالة', 'الإجمالي', 'المدفوع', 'المتبقي', 'تاريخ الخروج',
        ];
    }

    public function map($res): array
    {
        return [
            $res->guest?->full_name,
            $res->room?->room_number,
            $res->status,
            $res->total_amount,
            $res->paid_amount,
            $res->total_amount - $res->paid_amount,
            $res->check_out_date?->format('Y-m-d'),
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['color' => ['rgb' => 'FFFFFF'], 'bold' => true], 'fill' => ['fillType' => 'solid', 'color' => ['rgb' => '0F4C75']]],
        ];
    }
}
