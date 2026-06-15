<?php
namespace App\Exports;

use App\Models\Reservation;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class GovernmentGuestExport implements FromCollection, WithHeadings, WithTitle
{
    public function __construct(private Reservation $reservation) {}

    public function collection()
    {
        $rows = collect();

        $guest = $this->reservation->guest;
        $rows->push([
            $guest->full_name,
            $guest->nationality,
            $guest->getIdTypeLabel(),
            $guest->id_number,
            $guest->id_issuer,
            $guest->id_issue_date?->format('d/m/Y'),
            $guest->phone,
            $this->reservation->room->room_number,
            $this->reservation->check_in_date->format('d/m/Y'),
            $this->reservation->purpose,
            $this->reservation->origin,
            'نزيل رئيسي',
        ]);

        foreach ($this->reservation->companions as $companion) {
            $rows->push([
                $companion->full_name,
                $companion->nationality,
                $companion->id_type,
                $companion->id_number,
                $companion->id_issuer,
                $companion->id_issue_date?->format('d/m/Y'),
                '',
                $this->reservation->room->room_number,
                $this->reservation->check_in_date->format('d/m/Y'),
                '',
                '',
                $companion->getRelationshipLabel(),
            ]);
        }

        return $rows;
    }

    public function headings(): array
    {
        return [
            'الاسم الرباعي', 'الجنسية', 'نوع الهوية', 'رقم الهوية',
            'الجهة المصدرة', 'تاريخ الإصدار', 'رقم الجوال',
            'رقم الغرفة', 'تاريخ الوصول', 'الغرض', 'جهة القدوم', 'الصفة',
        ];
    }

    public function title(): string
    {
        return 'بيانات النزلاء';
    }
}
