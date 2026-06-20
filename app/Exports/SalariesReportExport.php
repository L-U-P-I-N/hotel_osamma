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
        return ['الموظف', 'الشهر', 'السنة', 'الراتب الأساسي (ر.ي)', 'المكافآت (ر.ي)', 'الخصومات (ر.ي)', 'الصافي (ر.ي)', 'الحالة'];
    }

    public function map($sal): array
    {
        $monthNames = [1=>'يناير',2=>'فبراير',3=>'مارس',4=>'أبريل',5=>'مايو',6=>'يونيو',
                       7=>'يوليو',8=>'أغسطس',9=>'سبتمبر',10=>'أكتوبر',11=>'نوفمبر',12=>'ديسمبر'];

        return [
            $sal->employee?->full_name ?? '',
            $monthNames[$sal->month] ?? $sal->month,
            $sal->year,
            number_format($sal->base_salary, 0),
            number_format($sal->bonuses, 0),
            number_format($sal->deductions, 0),
            number_format($sal->net_salary, 0),
            $sal->status === 'paid' ? 'مدفوع' : 'معلق',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => 'solid', 'color' => ['rgb' => '0F4C75']]],
        ];
    }
}
