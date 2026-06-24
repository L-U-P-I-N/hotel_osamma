<?php
namespace App\Exports;

use App\Models\Expense;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ExpenseExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    public function __construct(
        private ?string $dateFrom,
        private ?string $dateTo,
        private ?string $category,
        private ?string $paymentMethod,
        private ?string $search,
        private ?string $shiftId,
    ) {}

    public function collection(): Collection
    {
        $query = Expense::with('paidBy')->orderBy('expense_date', 'desc')->orderBy('id', 'desc');

        if ($this->dateFrom)     { $query->whereDate('expense_date', '>=', $this->dateFrom); }
        if ($this->dateTo)       { $query->whereDate('expense_date', '<=', $this->dateTo); }
        if ($this->category)     { $query->where('category', $this->category); }
        if ($this->paymentMethod){ $query->where('payment_method', $this->paymentMethod); }
        if ($this->shiftId)      { $query->where('shift_id', $this->shiftId); }
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('recipient_name', 'like', '%' . $this->search . '%')
                  ->orWhere('description', 'like', '%' . $this->search . '%');
            });
        }

        return $query->get();
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
            number_format($expense->amount, 0),
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
            1 => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => 'solid', 'color' => ['rgb' => '0F4C75']]],
        ];
    }
}
