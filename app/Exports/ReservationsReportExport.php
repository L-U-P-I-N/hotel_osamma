<?php
namespace App\Exports;

use App\Models\Reservation;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ReservationsReportExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    public function __construct(private string $from, private string $to) {}

    public function collection()
    {
        return Reservation::with(['guest', 'room', 'payments'])
            ->whereDate('check_in_date', '>=', $this->from)
            ->whereDate('check_in_date', '<=', $this->to)
            ->whereNotIn('status', ['cancelled'])
            ->orderBy('check_in_date', 'desc')
            ->get();
    }

    public function headings(): array
    {
        return [
            'رقم الغرفة', 'اسم النزيل', 'الجنسية', 'المهنة',
            'جهة القدوم', 'تاريخ الدخول', 'الوقت', 'الغرض',
            'نوع الهوية', 'رقم الهوية', 'صادر من', 'تاريخ الإصدار',
            'رقم الجوال', 'حالة الدفع', 'المدفوع', 'الإجمالي',
        ];
    }

    public function map($res): array
    {
        return [
            $res->room?->room_number,
            $res->guest?->full_name,
            $res->guest?->nationality,
            $res->guest?->occupation,
            $res->origin,
            $res->check_in_date?->format('Y-m-d'),
            $res->check_in_date?->format('H:i'),
            $res->purpose,
            $res->guest?->id_type,
            $res->guest?->id_number,
            $res->guest?->id_issuer,
            $res->guest?->id_issue_date?->format('Y-m-d'),
            $res->guest?->phone,
            $res->payment_status,
            $res->paid_amount,
            $res->total_amount,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['color' => ['rgb' => 'FFFFFF'], 'bold' => true], 'fill' => ['fillType' => 'solid', 'color' => ['rgb' => '0F4C75']]],
        ];
    }
}
