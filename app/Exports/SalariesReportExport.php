<?php
namespace App\Exports;

use App\Models\Salary;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SalariesReportExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    public function __construct(private int $year) {}

    public function collection()
    {
        return Salary::with('employee')
            ->where('year', $this->year)
            ->orderBy('month', 'desc')
            ->get();
    }

    public function headings(): array
    {
        return [
            'الموظف', 'الشهر', 'السنة', 'الراتب الأساسي', 'المكافآت', 'الخصومات', 'الصافي', 'الحالة',
        ];
    }

    public function map($salary): array
    {
        return [
            $salary->employee?->full_name,
            $salary->month,
            $salary->year,
            $salary->base_salary,
            $salary->bonuses,
            $salary->deductions,
            $salary->net_salary,
            $salary->status,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['color' => ['rgb' => 'FFFFFF'], 'bold' => true], 'fill' => ['fillType' => 'solid', 'color' => ['rgb' => '0F4C75']]],
        ];
    }
}
