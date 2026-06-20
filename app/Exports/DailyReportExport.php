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

class DailyReportExport extends StringValueBinder implements
    FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithCustomValueBinder
{
    public function __construct(private string $date) {}

    public function collection()
    {
        return Reservation::with(['guest', 'room', 'companions', 'payments'])
            ->whereDate('check_in_date', '<=', $this->date)
            ->whereDate('check_out_date', '>=', $this->date)
            ->where('status', 'checked_in')
            ->orderBy('room_id')
            ->get();
    }

    public function headings(): array
    {
        return [
            'رقم الغرفة', 'اسم النزيل', 'الجنسية', 'المهنة',
            'جهة القدوم', 'تاريخ الدخول', 'وقت الدخول', 'الغرض',
            'نوع الهوية', 'رقم الهوية', 'صادر من', 'تاريخ الإصدار',
            'المرافقون', 'حالة الدفع', 'المدفوع', 'الإجمالي', 'الجوال', 'ملاحظات',
        ];
    }

    public function map($res): array
    {
        $idTypeMap = ['national_id' => 'بطاقة', 'passport' => 'جواز', 'residence' => 'إقامة'];
        $companions = $res->companions->count();
        $rawPayNote = $res->payments->first(fn($p) => $p->notes)?->notes;
        $payNote    = $rawPayNote ? '💱 ' . $rawPayNote : null;

        return [
            $res->room?->room_number ?? '',
            $res->guest?->full_name ?? '',
            $res->guest?->nationality ?? '',
            $res->guest?->occupation ?? '',
            $res->origin ?? '',
            $res->check_in_date?->format('Y/m/d') ?? '',
            $res->check_in_time ?? '',
            $res->purpose ?? '',
            $idTypeMap[$res->guest?->id_type] ?? $res->guest?->id_type ?? '',
            (string) ($res->guest?->id_number ?? ''),
            $res->guest?->id_issuer ?? '',
            $res->guest?->id_issue_date?->format('Y/m/d') ?? '',
            $companions > 0 ? $companions . ' مرافق' : 'لوحده',
            $res->payment_status_label ?? '',
            number_format($res->paid_amount, 0),
            number_format($res->total_amount, 0),
            (string) ($res->guest?->phone ?? ''),
            collect(array_filter([$res->notes, $payNote]))->implode(' | '),
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
