<?php
namespace App\Exports;

use App\Models\Expense;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class ExpenseExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithEvents
{
    private float $cachedTotal = 0.0;
    private int   $cachedCount = 0;

    public function __construct(
        private ?string $dateFrom,
        private ?string $dateTo,
        private ?string $category,
        private ?string $paymentMethod,
        private ?string $search,
        private ?string $shiftId,
        private ?int $paidBy = null,
    ) {}

    public function collection(): Collection
    {
        $query = Expense::with('paidBy')->orderBy('expense_date', 'desc')->orderBy('id', 'desc');

        if ($this->dateFrom)     { $query->whereDate('expense_date', '>=', $this->dateFrom); }
        if ($this->dateTo)       { $query->whereDate('expense_date', '<=', $this->dateTo); }
        if ($this->category)     { $query->where('category', $this->category); }
        if ($this->paymentMethod){ $query->where('payment_method', $this->paymentMethod); }
        if ($this->shiftId)      { $query->where('shift_id', $this->shiftId); }
        if ($this->paidBy)       { $query->where('paid_by', $this->paidBy); }
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('recipient_name', 'like', '%' . $this->search . '%')
                  ->orWhere('description', 'like', '%' . $this->search . '%');
            });
        }

        $result = $query->get();
        $this->cachedTotal = (float) $result->sum('amount');
        $this->cachedCount = $result->count();
        return $result;
    }

    public function headings(): array
    {
        return ['التاريخ', 'الفئة', 'المبلغ (ر.ي)', 'طريقة الدفع', 'اسم المستلم', 'الوصف', 'سُجِّل بواسطة'];
    }

    public function map($expense): array
    {
        return [
            $expense->expense_date->format('Y/m/d'),
            Expense::categoryLabel($expense->category),
            (float) $expense->amount,
            Expense::paymentMethodLabel($expense->payment_method ?? 'cash'),
            $expense->recipient_name ?? '',
            $expense->description ?? '',
            $expense->paidBy?->name ?? '',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->setRightToLeft(true);
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => 'solid', 'color' => ['rgb' => '0F4C75']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet    = $event->sheet->getDelegate();
                $lastData = $sheet->getHighestRow();

                // Format amount column (C) as integer with thousands separator
                $sheet->getStyle('C2:C' . $lastData)
                    ->getNumberFormat()
                    ->setFormatCode('#,##0');

                // Empty separator row
                $sepRow   = $lastData + 1;
                $totalRow = $lastData + 2;

                // Total label + count
                $sheet->setCellValue('A' . $totalRow, 'الإجمالي الكلي');
                $sheet->setCellValue('B' . $totalRow, $this->cachedCount . ' مصروف');
                $sheet->setCellValue('C' . $totalRow, $this->cachedTotal);

                // Format total amount cell
                $sheet->getStyle('C' . $totalRow)
                    ->getNumberFormat()
                    ->setFormatCode('#,##0');

                // Style the total row
                $sheet->getStyle('A' . $totalRow . ':G' . $totalRow)->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 12,
                        'color' => ['rgb' => 'DC2626'],
                    ],
                    'fill' => [
                        'fillType' => 'solid',
                        'color'    => ['rgb' => 'FEF2F2'],
                    ],
                    'borders' => [
                        'top'    => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => 'EF4444']],
                        'bottom' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => 'EF4444']],
                    ],
                ]);

                // Add currency label next to total
                $sheet->setCellValue('D' . $totalRow, 'ر.ي');
                $sheet->getStyle('D' . $totalRow)->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'DC2626']],
                    'fill' => ['fillType' => 'solid', 'color' => ['rgb' => 'FEF2F2']],
                    'borders' => [
                        'top'    => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => 'EF4444']],
                        'bottom' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => 'EF4444']],
                    ],
                ]);
            },
        ];
    }
}
