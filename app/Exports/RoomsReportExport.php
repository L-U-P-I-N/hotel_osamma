<?php
namespace App\Exports;

use App\Models\Room;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RoomsReportExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    public function __construct(private string $from, private string $to) {}

    public function collection()
    {
        // كل الغرف مرتّبة تصاعدياً حسب رقمها — رقم الغرفة وحالتها فقط (دون إيرادات
        // أو عدد حجوزات).
        return Room::orderByRaw('CAST(room_number AS UNSIGNED) ASC')
            ->orderBy('room_number')
            ->get();
    }

    public function headings(): array
    {
        return ['رقم الغرفة', 'الحالة'];
    }

    public function map($room): array
    {
        $statusLabels = ['available' => 'متاحة', 'occupied' => 'مشغولة', 'maintenance' => 'صيانة', 'under_inspection' => 'فحص'];

        return [
            $room->room_number,
            $statusLabels[$room->status] ?? $room->status,
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
