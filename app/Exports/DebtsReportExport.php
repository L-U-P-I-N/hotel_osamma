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
        return ['النزيل', 'رقم الغرفة', 'الحالة', 'الإجمالي (ر.ي)', 'المدفوع (ر.ي)', 'المتبقي (ر.ي)', 'تاريخ الخروج'];
    }

    public function map($res): array
    {
        return [
            $res->guest?->full_name ?? '',
            $res->room?->room_number ?? '',
            $res->status === 'checked_in' ? 'داخل' : 'خرج',
            number_format($res->total_amount, 0),
            number_format($res->paid_amount, 0),
            number_format($res->total_amount - $res->paid_amount, 0),
            $res->check_out_date?->format('Y/m/d') ?? '',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => 'solid', 'color' => ['rgb' => 'DC2626']]],
        ];
    }
}
