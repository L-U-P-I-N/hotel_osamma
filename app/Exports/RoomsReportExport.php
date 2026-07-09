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
        // الغرف المتاحة/المشغولة/تحت الفحص فقط، مرتّبة تصاعدياً حسب رقم الغرفة
        return Room::with('roomType')
            ->whereIn('status', ['available', 'occupied', 'under_inspection'])
            ->withCount(['reservations as total_reservations' => fn($q) =>
                $q->whereDate('check_in_date', '>=', $this->from)
                  ->whereDate('check_in_date', '<=', $this->to)
                  ->whereNotIn('status', ['cancelled'])
            ])
            ->withSum(['reservations as total_revenue' => fn($q) =>
                $q->whereDate('check_in_date', '>=', $this->from)
                  ->whereDate('check_in_date', '<=', $this->to)
                  ->whereNotIn('status', ['cancelled'])
            ], 'total_amount')
            ->orderByRaw('CAST(room_number AS UNSIGNED) ASC')
            ->orderBy('room_number')
            ->get();
    }

    public function headings(): array
    {
        return ['رقم الغرفة', 'نوع الغرفة', 'الحالة', 'عدد الحجوزات', 'الإيرادات (ر.ي)'];
    }

    public function map($room): array
    {
        $statusLabels = ['available' => 'متاحة', 'occupied' => 'مشغولة', 'maintenance' => 'صيانة', 'under_inspection' => 'فحص'];

        return [
            $room->room_number,
            $room->roomType?->name ?? '',
            $statusLabels[$room->status] ?? $room->status,
            $room->total_reservations ?? 0,
            number_format($room->total_revenue ?? 0, 0),
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
