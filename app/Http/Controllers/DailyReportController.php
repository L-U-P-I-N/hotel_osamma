<?php
namespace App\Http\Controllers;

use App\Exports\DailyReportExport;
use App\Models\Reservation;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class DailyReportController extends Controller
{
    private function getReservations(string $date)
    {
        return Reservation::with(['guest', 'room.roomType', 'companions', 'payments', 'createdBy'])
            ->whereDate('check_in_date', '<=', $date)
            ->whereDate('check_out_date', '>=', $date)
            ->where('status', 'checked_in')
            ->orderBy('room_id')
            ->get();
    }

    public function index(Request $request)
    {
        $date = $request->input('date', today()->toDateString());
        $reservations = $this->getReservations($date);
        return view('reports.daily', compact('reservations', 'date'));
    }

    public function exportPdf(Request $request)
    {
        $date = $request->input('date', today()->toDateString());
        $reservations = $this->getReservations($date);

        // نُصدّر بنفس قالب تقرير الحجوزات (reservations_pdf) تماماً — الفرق الوحيد
        // أن الفترة يومٌ واحد (from == to) بدل فلتر شهر، والبيانات نزلاء ذلك اليوم
        // المقيمون. نُمرّر نفس المتغيّرات التي يتوقّعها القالب مع كل الأعمدة.
        $from = $to = $date;
        $total      = $reservations->count();
        $checkedIn  = $reservations->count(); // كلهم مقيمون في اليومية
        $checkedOut = 0;
        $printedCount = $reservations->count();
        $status = 'checked_in';
        $selectedColumns = array_keys(\App\Http\Controllers\ReportController::RESERVATIONS_PDF_COLUMNS);

        $pdf = pdf_load_view('reports.reservations_pdf', compact(
            'reservations', 'from', 'to', 'total', 'checkedIn', 'checkedOut', 'printedCount', 'selectedColumns', 'status'
        ));
        // A3 عرضي — الطابعة المستخدمة تطبع A3 فعلياً، فاستخدام A4 كان يترك جزءاً كبيراً من الورقة فارغاً
        $pdf->setPaper('a3', 'landscape');

        // Point DomPDF at our fonts directory so it can find NotoNaskhArabic
        $dompdf = $pdf->getDomPDF();
        $options = $dompdf->getOptions();
        $options->setFontDir(storage_path('fonts'));
        $options->setFontCache(storage_path('fonts'));
        $options->setIsRemoteEnabled(false);
        $dompdf->setOptions($options);

        return $pdf->download('daily-report-' . $date . '.pdf');
    }

    public function exportExcel(Request $request)
    {
        $date = $request->input('date', today()->toDateString());
        return Excel::download(new DailyReportExport($date), 'daily-report-' . $date . '.xlsx');
    }
}
