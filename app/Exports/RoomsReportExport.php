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
        return Room::with('roomType')
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
            ->orderByDesc('total_revenue')
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
        return [
            1 => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => 'solid', 'color' => ['rgb' => '0F4C75']]],
        ];
    }
}
