<?php
namespace App\Exports;

use App\Models\User;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StaffReportExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    public function __construct(private string $from, private string $to) {}

    public function collection(): Collection
    {
        return User::with(['reservations' => function ($q) {
            $q->whereDate('check_in_date', '>=', $this->from)
              ->whereDate('check_in_date', '<=', $this->to);
        }])->where('is_active', true)->get()
        ->map(fn($u) => (object)[
            'name'        => $u->name,
            'employee_id' => $u->employee_id ?? '',
            'role'        => $u->roles->first()?->name ?? '',
            'checkins'    => $u->reservations->count(),
            'revenue'     => $u->payments()
                ->whereDate('payment_date', '>=', $this->from)
                ->whereDate('payment_date', '<=', $this->to)
                ->sum('amount'),
        ]);
    }

    public function headings(): array
    {
        return ['اسم الموظف', 'رقم الموظف', 'الدور الوظيفي', 'تسجيلات الدخول', 'إجمالي المستلم (ر.ي)'];
    }

    public function map($row): array
    {
        return [
            $row->name,
            (string) $row->employee_id,
            $row->role,
            $row->checkins,
            number_format($row->revenue, 0),
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
