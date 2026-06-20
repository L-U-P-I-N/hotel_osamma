<?php
namespace App\Exports;

use App\Models\Payment;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RevenueReportExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    public function __construct(private string $from, private string $to) {}

    public function collection()
    {
        return Payment::with(['reservation.room.roomType', 'reservation.guest'])
            ->whereDate('payment_date', '>=', $this->from)
            ->whereDate('payment_date', '<=', $this->to)
            ->where('currency', 'YER')
            ->orderBy('payment_date')
            ->get();
    }

    public function headings(): array
    {
        return ['تاريخ الدفع', 'الغرفة', 'نوع الغرفة', 'النزيل', 'طريقة الدفع', 'المبلغ (ر.ي)', 'ملاحظات'];
    }

    public function map($p): array
    {
        $methodLabels = ['cash' => 'نقدي', 'pos' => 'POS', 'bank_transfer' => 'تحويل بنكي'];

        return [
            $p->payment_date?->format('Y/m/d') ?? '',
            $p->reservation?->room?->room_number ?? '',
            $p->reservation?->room?->roomType?->name ?? '',
            $p->reservation?->guest?->full_name ?? '',
            $methodLabels[$p->method] ?? $p->method ?? '',
            number_format($p->amount, 2),
            $p->notes ?? '',
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
