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
        return Payment::with('reservation.room.roomType')
            ->whereDate('payment_date', '>=', $this->from)
            ->whereDate('payment_date', '<=', $this->to)
            ->where('currency', 'YER')
            ->get();
    }

    public function headings(): array
    {
        return [
            'التاريخ', 'الغرفة', 'نوع الغرفة', 'طريقة الدفع', 'المبلغ (ر.ي)', 'ملاحظات',
        ];
    }

    public function map($payment): array
    {
        return [
            $payment->payment_date?->format('Y-m-d'),
            $payment->reservation?->room?->room_number,
            $payment->reservation?->room?->roomType?->name,
            $payment->method,
            $payment->amount,
            $payment->notes,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['color' => ['rgb' => 'FFFFFF'], 'bold' => true], 'fill' => ['fillType' => 'solid', 'color' => ['rgb' => '0F4C75']]],
        ];
    }
}
