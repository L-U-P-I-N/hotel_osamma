<?php
namespace App\Exports;

use App\Models\Shift;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ShiftsReportExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    public function __construct(private string $from, private string $to) {}

    public function collection(): Collection
    {
        return Shift::with(['user', 'payments', 'withdrawals'])
            ->whereDate('shift_date', '>=', $this->from)
            ->whereDate('shift_date', '<=', $this->to)
            ->orderBy('shift_date')
            ->orderBy('started_at')
            ->get();
    }

    public function headings(): array
    {
        return [
            'التاريخ', 'الموظف', 'وقت البداية', 'وقت النهاية', 'الحالة',
            'عدد الدفعات', 'مستلم YER', 'مستلم SAR', 'مستلم USD',
            'سحبيات YER', 'متبقي YER', 'ملاحظات',
        ];
    }

    public function map($shift): array
    {
        return [
            $shift->shift_date->format('Y/m/d'),
            $shift->user?->name ?? '',
            $shift->started_at?->format('H:i') ?? '',
            $shift->ended_at?->format('H:i') ?? '',
            $shift->is_closed ? 'مغلقة' : 'مفتوحة',
            $shift->payments->count(),
            number_format($shift->total_received_yer, 0),
            number_format($shift->total_received_sar, 0),
            number_format($shift->total_received_usd, 2),
            number_format($shift->total_withdrawals_yer, 0),
            number_format($shift->net_balance_yer, 0),
            $shift->notes ?? '',
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
