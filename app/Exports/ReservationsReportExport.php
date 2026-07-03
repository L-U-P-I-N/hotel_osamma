<?php
namespace App\Exports;

use App\Models\Reservation;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\StringValueBinder;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ReservationsReportExport extends StringValueBinder implements
    FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithCustomValueBinder
{
    public function __construct(private string $from, private string $to, private string $status = 'all') {}

    public function collection()
    {
        $query = Reservation::with(['guest', 'room', 'payments'])
            ->whereDate('check_in_date', '>=', $this->from)
            ->whereDate('check_in_date', '<=', $this->to)
            ->whereNotIn('status', ['cancelled']);
        if (in_array($this->status, ['checked_in', 'checked_out'], true)) {
            $query->where('status', $this->status);
        }
        return $query->orderBy('check_in_date', 'desc')->get();
    }

    public function headings(): array
    {
        return [
            '#', 'رقم الغرفة', 'اسم النزيل', 'الجنسية', 'المهنة',
            'جهة القدوم', 'تاريخ الدخول', 'وقت الدخول', 'الغرض',
            'نوع الهوية', 'رقم الهوية', 'صادر من', 'تاريخ الإصدار',
            'رقم الجوال', 'حالة الدفع', 'المدفوع', 'الإجمالي', 'ملاحظات',
        ];
    }

    public function map($r): array
    {
        $idTypeMap = ['national_id' => 'بطاقة', 'passport' => 'جواز', 'residence' => 'إقامة'];
        $psLabels  = ['paid' => 'مدفوع', 'partial' => 'جزئي', 'pending' => 'معلق'];
        $g = $r->guest;
        $payNote = $r->payments->first(fn($p) => $p->notes)?->notes;
        $notes = collect([$r->notes, $payNote])->filter()->implode(' | ');

        return [
            $r->id,
            $r->room?->room_number ?? '',
            $g?->full_name ?? '',
            $g?->nationality ?? '',
            $g?->occupation ?? '',
            $r->origin ?? '',
            $r->check_in_date?->format('Y/m/d') ?? '',
            $r->check_in_time ?? '',
            $r->purpose ?? '',
            $idTypeMap[$g?->id_type] ?? $g?->id_type ?? '',
            $g?->id_number ?? '',
            $g?->id_issuer ?? '',
            $g?->id_issue_date?->format('Y/m/d') ?? '',
            $g?->phone ?? '',
            $psLabels[$r->payment_status] ?? $r->payment_status ?? '',
            number_format($r->paid_amount, 0),
            number_format($r->total_amount, 0),
            $notes,
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
