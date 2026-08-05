<?php
namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\Salary;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    /**
     * الأعمدة المتاحة لاختيار الأدمن في تقرير الحجوزات (PDF) — المفتاح والتسمية فقط؛
     * الترتيب والعرض والمحتوى الفعلي معرّفة في reports.reservations_pdf.
     */
    public const RESERVATIONS_PDF_COLUMNS = [
        'id'             => '#',
        'room'           => 'الغرفة',
        'guest_name'     => 'اسم النزيل',
        'nationality'    => 'الجنسية',
        'occupation'     => 'المهنة',
        'origin'         => 'جهة القدوم',
        'check_in_date'  => 'تاريخ الدخول',
        'check_in_time'  => 'الوقت',
        'stay_status'    => 'حالة الإقامة',
        'purpose'        => 'الغرض',
        'id_type'        => 'نوع الهوية',
        'id_number'      => 'رقم الهوية',
        'id_issuer'      => 'صادر من',
        'id_issue_date'  => 'تاريخ الإصدار',
        'phone'          => 'رقم الجوال',
        'payment_status' => 'حالة الدفع',
        'paid_amount'    => 'المدفوع',
        'total_amount'   => 'الإجمالي',
        'balance'        => 'المتبقي',
        'created_by'     => 'تم بواسطة',
        'checked_out_by' => 'مغادرة بواسطة',
        'notes'          => 'ملاحظات',
    ];

    public function debts(Request $request)
    {
        $search = $request->input('search', '');
        $bucket = $request->input('bucket', '');

        $allReservations = Reservation::with(['guest', 'room.roomType'])
            ->whereIn('status', ['checked_in', 'checked_out'])
            ->whereRaw('paid_amount < total_amount')
            ->orderByRaw('(total_amount - paid_amount) DESC')
            ->get();

        $totalDebt = $allReservations->sum(fn($r) => $r->total_amount - $r->paid_amount);

        $buckets = ['current' => ['count' => 0, 'total' => 0], '30_60' => ['count' => 0, 'total' => 0], '60_90' => ['count' => 0, 'total' => 0], 'over_90' => ['count' => 0, 'total' => 0]];
        foreach ($allReservations as $res) {
            $refDate = $res->actual_check_out ?? $res->check_out_date;
            $days    = $refDate ? now()->startOfDay()->diffInDays($refDate->startOfDay()) : 0;
            $balance = $res->total_amount - $res->paid_amount;
            if ($days <= 30)     { $buckets['current']['count']++; $buckets['current']['total'] += $balance; }
            elseif ($days <= 60) { $buckets['30_60']['count']++;   $buckets['30_60']['total']   += $balance; }
            elseif ($days <= 90) { $buckets['60_90']['count']++;   $buckets['60_90']['total']   += $balance; }
            else                 { $buckets['over_90']['count']++;  $buckets['over_90']['total']  += $balance; }
        }

        $agedReservations = $allReservations;
        if ($search) {
            $agedReservations = $agedReservations->filter(fn($r) => stripos($r->guest?->full_name ?? '', $search) !== false);
        }
        if ($bucket) {
            $agedReservations = $agedReservations->filter(function ($r) use ($bucket) {
                $refDate = $r->actual_check_out ?? $r->check_out_date;
                $days    = $refDate ? now()->startOfDay()->diffInDays($refDate->startOfDay()) : 0;
                return match ($bucket) {
                    'current' => $days <= 30,
                    '30_60'   => $days > 30 && $days <= 60,
                    '60_90'   => $days > 60 && $days <= 90,
                    'over_90' => $days > 90,
                    default   => true,
                };
            });
        }

        $reservations = $allReservations;
        return view('reports.debts', compact('reservations', 'agedReservations', 'totalDebt', 'buckets', 'search', 'bucket'));
    }

    public function profitLoss(Request $request)
    {
        // فلتر سريع: اليوم / هذا الأسبوع / هذا الشهر / هذه السنة — يبني from/to
        // تلقائياً؛ يبقى اختيار تاريخ مخصّص متاحاً (preset فارغ أو غير معروف).
        $preset = $request->input('preset');
        [$from, $to] = $this->resolvePeriodPreset($preset, $request->input('from'), $request->input('to'));

        $totalRevenueGross = Payment::whereDate('payment_date', '>=', $from)
            ->whereDate('payment_date', '<=', $to)
            ->where('currency', 'YER')
            ->sum('amount');

        // الاسترجاعات (استرجاع مباشر أو ناتج عن إلغاء حجز) تُخصم من الإيراد — فهي
        // مبلغ خرج فعلياً من صندوق الفندق، ولا يجوز تجاهله عند احتساب الربح الحقيقي.
        $totalRefunds = Refund::whereDate('refunded_at', '>=', $from)
            ->whereDate('refunded_at', '<=', $to)
            ->where('currency', 'YER')
            ->sum('amount');

        $totalRevenue = $totalRevenueGross - $totalRefunds;

        // مدفوعات بعملات أجنبية (ر.س/دولار) — تُعرض للشفافية فقط، لا تُحوَّل
        // ولا تُجمَع مع إجمالي الريال اليمني (لا يوجد سعر صرف موحَّد موثوق).
        $foreignRevenue = Payment::whereDate('payment_date', '>=', $from)
            ->whereDate('payment_date', '<=', $to)
            ->whereIn('currency', ['SAR', 'USD'])
            ->select('currency', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy('currency')
            ->get();

        $totalExpenses = \App\Models\Expense::whereDate('expense_date', '>=', $from)
            ->whereDate('expense_date', '<=', $to)
            ->sum('amount');

        $netProfit = $totalRevenue - $totalExpenses;

        $revenueByMethod = Payment::whereDate('payment_date', '>=', $from)
            ->whereDate('payment_date', '<=', $to)
            ->where('currency', 'YER')
            ->select('method', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy('method')
            ->get();

        $expensesByCategory = \App\Models\Expense::whereDate('expense_date', '>=', $from)
            ->whereDate('expense_date', '<=', $to)
            ->select('category', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy('category')
            ->orderByDesc('total')
            ->get();

        $revenueByMonth = Payment::whereDate('payment_date', '>=', $from)
            ->whereDate('payment_date', '<=', $to)
            ->where('currency', 'YER')
            ->get()
            ->groupBy(fn($p) => \Carbon\Carbon::parse($p->payment_date)->format('Y-m'))
            ->map(fn($g) => (float) $g->sum('amount'));

        $expensesByMonth = \App\Models\Expense::whereDate('expense_date', '>=', $from)
            ->whereDate('expense_date', '<=', $to)
            ->get()
            ->groupBy(fn($e) => \Carbon\Carbon::parse($e->expense_date)->format('Y-m'))
            ->map(fn($g) => (float) $g->sum('amount'));

        $monthlyTrend = $revenueByMonth->keys()
            ->merge($expensesByMonth->keys())
            ->unique()
            ->sort()
            ->values()
            ->map(function ($monthKey) use ($revenueByMonth, $expensesByMonth) {
                [$year, $month] = explode('-', $monthKey);
                return (object) [
                    'month_key'   => $monthKey,
                    'revenue'     => $revenueByMonth->get($monthKey, 0),
                    'expenses'    => $expensesByMonth->get($monthKey, 0),
                    'month_label' => \App\Models\Salary::monthName((int) $month) . ' ' . $year,
                ];
            });

        return view('reports.profit-loss', compact(
            'from', 'to', 'preset', 'totalRevenue', 'totalRevenueGross', 'totalRefunds',
            'foreignRevenue', 'totalExpenses', 'netProfit',
            'revenueByMethod', 'expensesByCategory', 'monthlyTrend'
        ));
    }

    /**
     * يحوّل اسم فلتر سريع (today/week/month/year) إلى تاريخَي بداية/نهاية —
     * يُستخدم في تقرير الأرباح والخسائر ونسب الأداء المالي معاً حتى يتوفّر
     * نفس المعنى الدقيق لـ"هذا الأسبوع"/"هذه السنة" في كل الصفحات. عند preset
     * فارغ أو غير معروف، يُستخدم from/to المُرسَلان (أو الشهر الحالي افتراضياً).
     */
    private function resolvePeriodPreset(?string $preset, ?string $from, ?string $to): array
    {
        return match ($preset) {
            'today' => [now()->toDateString(), now()->toDateString()],
            'week'  => [now()->startOfWeek()->toDateString(), now()->toDateString()],
            'month' => [now()->startOfMonth()->toDateString(), now()->toDateString()],
            'year'  => [now()->startOfYear()->toDateString(), now()->toDateString()],
            default => [
                $from ?: now()->startOfMonth()->toDateString(),
                $to ?: now()->toDateString(),
            ],
        };
    }

    public function occupancyPdf(Request $request)
    {
        $from = $request->input('from', now()->subDays(30)->toDateString());
        $to   = $request->input('to', now()->toDateString());
        $totalRooms     = Room::count();
        $dailyOccupancy = \App\Models\Reservation::select(
                \Illuminate\Support\Facades\DB::raw('DATE(check_in_date) as date'),
                \Illuminate\Support\Facades\DB::raw('COUNT(*) as count')
            )
            ->whereDate('check_in_date', '>=', $from)
            ->whereDate('check_in_date', '<=', $to)
            ->whereIn('status', ['checked_in', 'checked_out'])
            ->groupBy('date')->orderBy('date')->get()
            ->map(fn($r) => ['date' => $r->date, 'percent' => $totalRooms > 0 ? round(($r->count / $totalRooms) * 100) : 0]);
        $pdf = $this->pdfOptions(pdf_load_view('reports.occupancy_pdf', compact('dailyOccupancy', 'from', 'to', 'totalRooms')));
        $pdf->setPaper('a4', 'portrait');
        return $pdf->download('occupancy-' . $from . '-' . $to . '.pdf');
    }

    public function occupancyExcel(Request $request)
    {
        $from = $request->input('from', now()->subDays(30)->toDateString());
        $to   = $request->input('to', now()->toDateString());
        return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\OccupancyReportExport($from, $to), 'occupancy-' . $from . '-' . $to . '.xlsx');
    }

    public function staffPdf(Request $request)
    {
        $from = $request->input('from', now()->subDays(30)->toDateString());
        $to   = $request->input('to', now()->toDateString());
        $staffData = User::with(['reservations' => function ($q) use ($from, $to) {
            $q->whereDate('check_in_date', '>=', $from)->whereDate('check_in_date', '<=', $to);
        }])->where('is_active', true)->get()
        ->map(fn($u) => [
            'user'     => $u,
            'checkins' => $u->reservations->count(),
            'revenue'  => $u->payments()->whereDate('payment_date', '>=', $from)->whereDate('payment_date', '<=', $to)->sum('amount'),
        ]);
        $pdf = $this->pdfOptions(pdf_load_view('reports.staff_pdf', compact('staffData', 'from', 'to')));
        $pdf->setPaper('a4', 'portrait');
        return $pdf->download('staff-' . $from . '-' . $to . '.pdf');
    }

    public function staffExcel(Request $request)
    {
        $from = $request->input('from', now()->subDays(30)->toDateString());
        $to   = $request->input('to', now()->toDateString());
        return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\StaffReportExport($from, $to), 'staff-' . $from . '-' . $to . '.xlsx');
    }

    public function shiftsPdf(Request $request)
    {
        $from = $request->input('from', now()->subDays(30)->toDateString());
        $to   = $request->input('to', now()->toDateString());
        $shifts = \App\Models\Shift::with(['user', 'payments', 'withdrawals'])
            ->whereDate('shift_date', '>=', $from)
            ->whereDate('shift_date', '<=', $to)
            ->orderBy('shift_date')->orderBy('started_at')->get();
        $pdf = $this->pdfOptions(pdf_load_view('reports.shifts_pdf', compact('shifts', 'from', 'to')));
        $pdf->setPaper('a3', 'landscape');
        return $pdf->download('shifts-' . $from . '-' . $to . '.pdf');
    }

    public function shiftsExcel(Request $request)
    {
        $from = $request->input('from', now()->subDays(30)->toDateString());
        $to   = $request->input('to', now()->toDateString());
        return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\ShiftsReportExport($from, $to), 'shifts-' . $from . '-' . $to . '.xlsx');
    }

    public function dailyClosePdf(Request $request)
    {
        $date = $request->input('date', today()->toDateString());

        $payments = \App\Models\Payment::whereDate('payment_date', $date)->where('currency', 'YER')->get();
        $totalRevenue     = $payments->sum('amount');
        $paymentCount     = $payments->count();
        $reservationCount = $payments->unique('reservation_id')->count();

        $revenueByMethod = \App\Models\Payment::whereDate('payment_date', $date)
            ->where('currency', 'YER')
            ->select('method', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy('method')->get();

        $expenses = \App\Models\Expense::whereDate('expense_date', $date)->get();
        $totalExpenses = $expenses->sum('amount');

        $expensesByCategory = \App\Models\Expense::whereDate('expense_date', $date)
            ->select('category', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy('category')->get();

        $dailyShifts      = Shift::with('user')->whereDate('shift_date', $date)->get();
        $cashRevenue      = $payments->where('method', 'cash')->sum('amount');
        $cashExpenses     = $expenses->where('payment_method', 'cash')->sum('amount');
        $totalWithdrawals = $dailyShifts->sum('total_withdrawals_yer');
        $expectedCash     = $cashRevenue - $cashExpenses;
        $netDay           = $totalRevenue - $totalExpenses;

        $pdf = $this->pdfOptions(pdf_load_view('reports.daily_close_pdf', compact(
            'date', 'totalRevenue', 'totalExpenses', 'netDay',
            'paymentCount', 'reservationCount',
            'revenueByMethod', 'expensesByCategory',
            'dailyShifts', 'cashRevenue', 'cashExpenses',
            'totalWithdrawals', 'expectedCash'
        )));
        $pdf->setPaper('a4', 'portrait');
        return $pdf->download('daily-close-' . $date . '.pdf');
    }

    public function shiftsHub(Request $request)
    {
        $tab  = $request->input('tab', 'shifts');
        $from = $request->input('from', now()->subDays(30)->toDateString());
        $to   = $request->input('to', now()->toDateString());
        $date = $request->input('date', today()->toDateString());

        $shifts = null;
        $summary = null;
        $totalRevenue = $paymentCount = $reservationCount = 0;
        $revenueByMethod = collect();
        $totalExpenses = $cashRevenue = $cashExpenses = $totalWithdrawals = $expectedCash = $netDay = 0;
        $expensesByCategory = collect();
        $dailyShifts = collect();

        if ($tab === 'shifts') {
            $shifts = Shift::with(['user', 'payments.reservation.room', 'withdrawals'])
                ->whereDate('shift_date', '>=', $from)
                ->whereDate('shift_date', '<=', $to)
                ->orderBy('shift_date', 'desc')
                ->orderBy('started_at', 'desc')
                ->paginate(20)
                ->withQueryString();

        } elseif ($tab === 'deficits') {
            $users = User::with(['shifts' => function ($q) use ($from, $to) {
                $q->where('is_closed', true)
                  ->whereDate('shift_date', '>=', $from)
                  ->whereDate('shift_date', '<=', $to)
                  ->orderBy('shift_date', 'desc');
            }])->whereHas('shifts', function ($q) use ($from, $to) {
                $q->where('is_closed', true)
                  ->whereDate('shift_date', '>=', $from)
                  ->whereDate('shift_date', '<=', $to);
            })->get();

            $summary = $users->map(function (User $user) {
                $userShifts    = $user->shifts;
                $deficitShifts = $userShifts->filter(fn($s) => $s->shortfall !== null && $s->shortfall < 0);
                $surplusShifts = $userShifts->filter(fn($s) => $s->shortfall !== null && $s->shortfall > 0);
                return [
                    'user'            => $user,
                    'shift_count'     => $userShifts->count(),
                    'total_received'  => $userShifts->sum('total_received_yer'),
                    'total_withdrawn' => $userShifts->sum('total_withdrawals_yer'),
                    'total_deficit'   => $deficitShifts->sum(fn($s) => abs($s->shortfall)),
                    'total_surplus'   => $surplusShifts->sum('shortfall'),
                    'deficit_count'   => $deficitShifts->count(),
                    'deducted_count'  => $deficitShifts->whereNotNull('salary_deducted_at')->count(),
                    'shifts'          => $userShifts,
                ];
            })->sortByDesc('total_deficit')->values();

        } elseif ($tab === 'daily') {
            $payments = \App\Models\Payment::whereDate('payment_date', $date)->where('currency', 'YER')->get();
            $totalRevenue     = $payments->sum('amount');
            $paymentCount     = $payments->count();
            $reservationCount = $payments->unique('reservation_id')->count();

            $revenueByMethod = \App\Models\Payment::whereDate('payment_date', $date)
                ->where('currency', 'YER')
                ->select('method', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
                ->groupBy('method')->get();

            $expenses = \App\Models\Expense::whereDate('expense_date', $date)->get();
            $totalExpenses = $expenses->sum('amount');

            $expensesByCategory = \App\Models\Expense::whereDate('expense_date', $date)
                ->select('category', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
                ->groupBy('category')->get();

            $dailyShifts = Shift::with('user')->whereDate('shift_date', $date)->get();

            $cashRevenue      = $payments->where('method', 'cash')->sum('amount');
            $cashExpenses     = $expenses->where('payment_method', 'cash')->sum('amount');
            $totalWithdrawals = $dailyShifts->sum('total_withdrawals_yer');
            $expectedCash     = $cashRevenue - $cashExpenses;
            $netDay           = $totalRevenue - $totalExpenses;
        }

        return view('reports.shifts-hub', compact(
            'tab', 'from', 'to', 'date',
            'shifts', 'summary',
            'totalRevenue', 'paymentCount', 'reservationCount',
            'revenueByMethod', 'totalExpenses', 'expensesByCategory',
            'dailyShifts', 'cashRevenue', 'cashExpenses', 'totalWithdrawals',
            'expectedCash', 'netDay'
        ));
    }

    public function financeHub(Request $request)
    {
        $tab    = $request->input('tab', 'revenue');
        $from   = $request->input('from', now()->startOfMonth()->toDateString());
        $to     = $request->input('to', now()->toDateString());
        $month  = $request->input('month', now()->format('Y-m'));
        $period = $request->input('period', 'month');

        // Revenue tab
        $totalRevenue = $paymentCount = $reservationCount = $avgPayment = 0;
        $revenueByType = $revenueByMethod = $dailyRevenue = $topRooms = $foreignPayments = collect();

        // Expenses tab
        $totalExpenses = $expenseCount = 0;
        $expensesByCategory = collect();
        $monthFrom = $monthTo = '';

        // Payment methods tab
        $byMethod = collect();
        $totalMethodAmount = $totalMethodCount = 0;
        $dailyByMethod = collect();
        $paymentDetails = collect();
        $refundDetails = collect();
        $totalRefundAmount = 0;
        $methodLabels = ['cash' => 'نقداً', 'bank_transfer' => 'تحويل بنكي', 'pos' => 'POS', 'check' => 'شيك', 'credit_card' => 'بطاقة ائتمان'];

        // Financial ratios tab
        $profitMargin = $costRatio = $occupancyRate = $revenuePerRoom = $adr = 0;
        $revenuePerGuest = $avgStay = $guestCount = 0;
        $dailyRevenueAvg = $dailyExpenseAvg = $dailyProfitAvg = $avgOccupiedRooms = 0;
        $profitabilityScore = $efficiencyScore = $overallHealth = 0;

        if ($tab === 'revenue') {
            $bq = fn() => Payment::whereDate('payment_date', '>=', $from)->whereDate('payment_date', '<=', $to)->where('currency', 'YER');
            $totalRevenue     = $bq()->sum('amount');
            $paymentCount     = $bq()->count();
            $reservationCount = $bq()->distinct('reservation_id')->count('reservation_id');
            $avgPayment       = $paymentCount > 0 ? $totalRevenue / $paymentCount : 0;
            $revenueByType = Payment::join('reservations', 'payments.reservation_id', '=', 'reservations.id')
                ->join('rooms', 'reservations.room_id', '=', 'rooms.id')
                ->join('room_types', 'rooms.room_type_id', '=', 'room_types.id')
                ->whereDate('payments.payment_date', '>=', $from)->whereDate('payments.payment_date', '<=', $to)
                ->where('payments.currency', 'YER')
                ->select('room_types.name', DB::raw('SUM(payments.amount) as total'), DB::raw('COUNT(payments.id) as payment_count'), DB::raw('COUNT(DISTINCT payments.reservation_id) as reservation_count'))
                ->groupBy('room_types.name')->orderByDesc('total')->get();
            $revenueByMethod = $bq()->select('method', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))->groupBy('method')->get();
            $dailyRevenue    = $bq()->select(DB::raw('DATE(payment_date) as date'), DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))->groupBy('date')->orderBy('date')->get();
            $topRooms = Payment::join('reservations', 'payments.reservation_id', '=', 'reservations.id')
                ->join('rooms', 'reservations.room_id', '=', 'rooms.id')
                ->join('room_types', 'rooms.room_type_id', '=', 'room_types.id')
                ->whereDate('payments.payment_date', '>=', $from)->whereDate('payments.payment_date', '<=', $to)
                ->where('payments.currency', 'YER')
                ->select('rooms.room_number', 'room_types.name as type_name', DB::raw('SUM(payments.amount) as total'), DB::raw('COUNT(DISTINCT payments.reservation_id) as reservation_count'))
                ->groupBy('rooms.room_number', 'room_types.name')->orderByDesc('total')->limit(10)->get();
            $foreignPayments = Payment::whereDate('payment_date', '>=', $from)->whereDate('payment_date', '<=', $to)
                ->whereIn('currency', ['SAR', 'USD'])
                ->select('currency', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))->groupBy('currency')->get();

        } elseif ($tab === 'expenses') {
            [$year, $monthNum] = explode('-', $month);
            $monthFrom = "{$year}-{$monthNum}-01";
            $monthTo   = \Carbon\Carbon::parse($monthFrom)->endOfMonth()->toDateString();
            $expItems  = \App\Models\Expense::whereDate('expense_date', '>=', $monthFrom)->whereDate('expense_date', '<=', $monthTo)->get();
            $totalExpenses = $expItems->sum('amount');
            $expenseCount  = $expItems->count();
            $expensesByCategory = $expItems->groupBy('category')
                ->map(fn($g) => (object)['category' => $g->first()->category, 'count' => $g->count(), 'total' => $g->sum('amount')])
                ->sortByDesc('total')->values();

        } elseif ($tab === 'methods') {
            $allPayments = Payment::whereDate('payment_date', '>=', $from)->whereDate('payment_date', '<=', $to)->where('currency', 'YER')->get();
            $byMethod = $allPayments->groupBy('method')->map(fn($g) => [
                'count' => $g->count(), 'total' => $g->sum('amount'), 'average' => $g->count() > 0 ? $g->sum('amount') / $g->count() : 0,
            ]);
            $totalMethodAmount = $allPayments->sum('amount');
            $totalMethodCount  = $allPayments->count();
            $dailyByMethod     = $allPayments->groupBy(fn($p) => $p->payment_date->format('Y-m-d'))
                ->map(fn($g) => $g->groupBy('method')->map(fn($gm) => $gm->sum('amount')));

            // تفاصيل كل عملية دفع فردياً — النزيل والغرفة وسند التحويل، لا الإجمالي المجمّع فقط.
            // withTrashed() على reservation ضروري: حجز مُلغى محذوف حذفاً ناعماً، ودون هذا
            // الاستثناء تختفي بيانات النزيل/الغرفة لأي دفعة سابقة تخص حجزاً أُلغي لاحقاً.
            $paymentDetails = Payment::with(['reservation' => fn($q) => $q->withTrashed(), 'reservation.guest', 'reservation.room', 'receivedBy'])
                ->whereDate('payment_date', '>=', $from)->whereDate('payment_date', '<=', $to)
                ->where('currency', 'YER')
                ->orderByDesc('payment_date')
                ->get();

            // الاسترجاعات المالية (استرجاع مباشر أو ناتج عن إلغاء حجز) خلال نفس الفترة
            $refundDetails = Refund::with(['reservation' => fn($q) => $q->withTrashed(), 'reservation.guest', 'reservation.room', 'processedBy'])
                ->whereDate('refunded_at', '>=', $from)->whereDate('refunded_at', '<=', $to)
                ->where('currency', 'YER')
                ->orderByDesc('refunded_at')
                ->get();
            $totalRefundAmount = $refundDetails->sum('amount');

        } elseif ($tab === 'ratios') {
            $ratioFrom = match ($period) {
                'today'   => now()->toDateString(),
                'week'    => now()->startOfWeek()->toDateString(),
                'quarter' => now()->startOfQuarter()->toDateString(),
                'year'    => now()->startOfYear()->toDateString(),
                'all'     => Reservation::orderBy('check_in_date')->first()?->check_in_date->toDateString() ?? now()->subYear()->toDateString(),
                default   => now()->startOfMonth()->toDateString(),
            };
            $ratioTo  = now()->toDateString();
            $days     = \Carbon\Carbon::parse($ratioFrom)->diffInDays($ratioTo) + 1;
            $revGross = Payment::whereDate('payment_date', '>=', $ratioFrom)->whereDate('payment_date', '<=', $ratioTo)->where('currency', 'YER')->sum('amount');
            // الاسترجاعات تُخصم من الإيراد — نفس منطق تقرير الأرباح والخسائر، فلا
            // يظهر هامش ربح مضخَّم بسبب مبالغ خرجت فعلياً من الصندوق كاسترجاع.
            $ratioRefunds = Refund::whereDate('refunded_at', '>=', $ratioFrom)->whereDate('refunded_at', '<=', $ratioTo)->where('currency', 'YER')->sum('amount');
            $rev      = $revGross - $ratioRefunds;
            $exp      = \App\Models\Expense::whereDate('expense_date', '>=', $ratioFrom)->whereDate('expense_date', '<=', $ratioTo)->where('currency', 'YER')->sum('amount');
            $profit   = $rev - $exp;
            $profitMargin = $rev > 0 ? round(($profit / $rev) * 100, 1) : 0;
            $costRatio    = $rev > 0 ? round(($exp / $rev) * 100, 1) : 0;
            $totalRooms   = Room::count();
            $ratioRes     = Reservation::whereDate('check_in_date', '>=', $ratioFrom)->whereDate('check_in_date', '<=', $ratioTo)->get();
            $totalNights  = $ratioRes->sum('nights');
            $occupancyRate   = ($totalRooms * $days) > 0 ? round(($totalNights / ($totalRooms * $days)) * 100) : 0;
            $revenuePerRoom  = $totalRooms > 0 ? round($rev / $totalRooms) : 0;
            $guestCount      = $ratioRes->count();
            $revenuePerGuest = $guestCount > 0 ? round($rev / $guestCount) : 0;
            $avgStay         = $guestCount > 0 ? round($totalNights / $guestCount, 2) : 0;
            $dailyRevenueAvg  = round($rev / $days);
            $dailyExpenseAvg  = round($exp / $days);
            $dailyProfitAvg   = $dailyRevenueAvg - $dailyExpenseAvg;
            $adr              = $totalNights > 0 ? round($rev / $totalNights) : 0;
            $avgOccupiedRooms = round($totalNights / $days, 1);
            $profitabilityScore = min(10, max(0, (int)($profitMargin / 3)));
            $efficiencyScore    = min(10, max(0, round((100 - $costRatio) / 10)));
            $overallHealth      = round(($profitabilityScore + $efficiencyScore) / 2);
        }

        return view('reports.finance-hub', compact(
            'tab', 'from', 'to', 'month', 'period',
            'totalRevenue', 'paymentCount', 'reservationCount', 'avgPayment',
            'revenueByType', 'revenueByMethod', 'dailyRevenue', 'topRooms', 'foreignPayments',
            'totalExpenses', 'expenseCount', 'expensesByCategory', 'monthFrom', 'monthTo',
            'byMethod', 'totalMethodAmount', 'totalMethodCount', 'dailyByMethod', 'methodLabels', 'paymentDetails',
            'refundDetails', 'totalRefundAmount',
            'profitMargin', 'costRatio', 'occupancyRate', 'revenuePerRoom', 'adr',
            'revenuePerGuest', 'avgStay', 'guestCount',
            'dailyRevenueAvg', 'dailyExpenseAvg', 'dailyProfitAvg', 'avgOccupiedRooms',
            'profitabilityScore', 'efficiencyScore', 'overallHealth'
        ));
    }

    private function pdfOptions(\Barryvdh\DomPDF\PDF $pdf): \Barryvdh\DomPDF\PDF
    {
        $dompdf = $pdf->getDomPDF();
        $opts = $dompdf->getOptions();
        $opts->setFontDir(storage_path('fonts'));
        $opts->setFontCache(storage_path('fonts'));
        $dompdf->setOptions($opts);
        return $pdf;
    }

    public function reservationsPdf(Request $request)
    {
        $preset = $request->input('preset', 'custom');
        $from = match($preset) {
            'today' => now()->toDateString(),
            'week'  => now()->subDays(6)->toDateString(),
            default => $request->input('from', now()->subDays(30)->toDateString()),
        };
        $to = match($preset) {
            'today' => now()->toDateString(),
            'week'  => now()->toDateString(),
            default => $request->input('to', now()->toDateString()),
        };

        $days = \Carbon\Carbon::parse($from)->diffInDays(\Carbon\Carbon::parse($to)) + 1;
        if ($days > 30) {
            return redirect()->back()->with('pdf_warning',
                "تقرير PDF يدعم حتى 30 يوماً — الفترة المحددة {$days} يوم. استخدم Excel لتصدير فترات أطول."
            );
        }

        ini_set('memory_limit', '256M');

        // فلتر الحالة: الكل (افتراضي) / لم يغادر (مقيم) / غادر
        $status = $request->input('status', 'all');
        if (!in_array($status, ['checked_in', 'checked_out'], true)) {
            $status = 'all';
        }

        $reservationsQuery = Reservation::with(['guest', 'room', 'payments', 'createdBy', 'checkedOutBy'])
            ->whereDate('check_in_date', '>=', $from)
            ->whereDate('check_in_date', '<=', $to)
            ->whereNotIn('status', ['cancelled']);
        if ($status !== 'all') {
            $reservationsQuery->where('status', $status);
        }
        $reservations = $reservationsQuery->orderBy('check_in_date', 'desc')->get();
        // عدد الصفوف المطبوعة فعلياً — يعكس فلتر الحالة، بخلاف بطاقات الملخص أدناه
        $printedCount = $reservations->count();

        // إجماليات الفترة كاملة (بغضّ النظر عن فلتر الحالة) — لبطاقات الملخص أعلى التقرير فقط
        $total      = Reservation::whereDate('check_in_date', '>=', $from)
            ->whereDate('check_in_date', '<=', $to)->whereNotIn('status', ['cancelled'])->count();
        $checkedIn  = Reservation::whereDate('check_in_date', '>=', $from)
            ->whereDate('check_in_date', '<=', $to)->where('status', 'checked_in')->count();
        $checkedOut = Reservation::whereDate('check_in_date', '>=', $from)
            ->whereDate('check_in_date', '<=', $to)->where('status', 'checked_out')->count();

        // الأعمدة التي اختارها الأدمن — كل الأعمدة افتراضياً إن لم يُحدَّد شيء
        $selectedColumns = array_values(array_intersect(
            $request->input('columns', array_keys(self::RESERVATIONS_PDF_COLUMNS)),
            array_keys(self::RESERVATIONS_PDF_COLUMNS)
        ));
        if (empty($selectedColumns)) {
            $selectedColumns = array_keys(self::RESERVATIONS_PDF_COLUMNS);
        }

        // عند عرض "الكل" (مقيمون ومغادرون معاً)، عمود حالة الإقامة إجباري حتى
        // يمكن تمييز من غادر ممن لم يغادر — لا يُستبعد حتى لو لم يختره الأدمن
        if ($status === 'all' && !in_array('stay_status', $selectedColumns, true)) {
            $selectedColumns[] = 'stay_status';
        }

        // عمود "مغادرة بواسطة" يظهر فقط في تقرير المغادرين (status = checked_out):
        // نُلزم إظهاره هناك، ونُخفيه في أي فلتر آخر ولو اختاره الأدمن — فلا معنى له
        // لنزيل لم يغادر بعد.
        if ($status === 'checked_out') {
            if (!in_array('checked_out_by', $selectedColumns, true)) {
                $selectedColumns[] = 'checked_out_by';
            }
        } else {
            $selectedColumns = array_values(array_filter($selectedColumns, fn($c) => $c !== 'checked_out_by'));
        }

        $pdf = $this->pdfOptions(pdf_load_view('reports.reservations_pdf', compact('reservations', 'from', 'to', 'total', 'checkedIn', 'checkedOut', 'printedCount', 'selectedColumns', 'status')));
        $pdf->setPaper('a3', 'landscape');
        $filenameSuffix = $status !== 'all' ? '-' . $status : '';
        return $pdf->download('reservations-' . $from . '-' . $to . $filenameSuffix . '.pdf');
    }

    public function dailyHub(Request $request)
    {
        $tab    = $request->input('tab', 'daily');
        $date   = $request->input('date', today()->toDateString());
        $preset = $request->input('preset', 'custom');
        $from   = $request->input('from', now()->subDays(30)->toDateString());
        $to     = $request->input('to', now()->toDateString());

        $dailyReservations = collect();
        $totalRooms = 0;
        $dailyOccupancy = collect();
        $reservations = collect();
        $total = $checkedIn = $checkedOut = 0;
        $status = 'all';
        $printedCount = 0;

        if ($tab === 'daily') {
            $dailyReservations = Reservation::with(['guest', 'room.roomType', 'companions', 'payments'])
                ->whereDate('check_in_date', '<=', $date)
                ->whereDate('check_out_date', '>=', $date)
                ->where('status', 'checked_in')
                ->orderBy('room_id')
                ->get();

        } elseif ($tab === 'occupancy') {
            $totalRooms = Room::count();
            $dailyOccupancy = Reservation::select(
                DB::raw('DATE(check_in_date) as date'),
                DB::raw('COUNT(*) as count')
            )
            ->whereDate('check_in_date', '>=', $from)
            ->whereDate('check_in_date', '<=', $to)
            ->whereIn('status', ['checked_in', 'checked_out'])
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(fn($r) => [
                'date'    => $r->date,
                'percent' => $totalRooms > 0 ? round(($r->count / $totalRooms) * 100) : 0,
            ]);

        } elseif ($tab === 'reservations') {
            $from = match($preset) {
                'today' => now()->toDateString(),
                'week'  => now()->subDays(6)->toDateString(),
                default => $from,
            };
            $to = match($preset) {
                'today' => now()->toDateString(),
                'week'  => now()->toDateString(),
                default => $to,
            };
            // فلتر الحالة: الكل (افتراضي) / لم يغادر (مقيم) / غادر
            $status = $request->input('status', 'all');
            if (!in_array($status, ['checked_in', 'checked_out'], true)) {
                $status = 'all';
            }

            $query = Reservation::with(['guest', 'room.roomType', 'payments'])
                ->whereDate('check_in_date', '>=', $from)
                ->whereDate('check_in_date', '<=', $to)
                ->whereNotIn('status', ['cancelled']);
            if ($status !== 'all') {
                $query->where('status', $status);
            }
            $reservations = $query->orderBy('check_in_date', 'desc')
                ->orderBy('id', 'desc')
                ->paginate(50)
                ->withQueryString();
            // عدد الحجوزات المطابقة لفلتر الحالة الحالي (يظهر فوق الجدول)، بخلاف بطاقات الملخص أدناه
            $printedCount = $reservations->total();

            // إجماليات الفترة كاملة (بغضّ النظر عن فلتر الحالة) — للبطاقات التعريفية فقط
            $total      = Reservation::whereDate('check_in_date', '>=', $from)
                ->whereDate('check_in_date', '<=', $to)->whereNotIn('status', ['cancelled'])->count();
            $checkedIn  = Reservation::whereDate('check_in_date', '>=', $from)
                ->whereDate('check_in_date', '<=', $to)->where('status', 'checked_in')->count();
            $checkedOut = Reservation::whereDate('check_in_date', '>=', $from)
                ->whereDate('check_in_date', '<=', $to)->where('status', 'checked_out')->count();
        }

        return view('reports.daily-hub', compact(
            'tab', 'date', 'from', 'to', 'preset',
            'dailyReservations', 'totalRooms', 'dailyOccupancy',
            'reservations', 'total', 'checkedIn', 'checkedOut', 'status', 'printedCount'
        ));
    }

    public function reservationsExcel(Request $request)
    {
        $from   = $request->input('from', now()->subDays(30)->toDateString());
        $to     = $request->input('to', now()->toDateString());
        $status = $request->input('status', 'all');
        if (!in_array($status, ['checked_in', 'checked_out'], true)) {
            $status = 'all';
        }
        $suffix = $status !== 'all' ? '-' . $status : '';
        return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\ReservationsReportExport($from, $to, $status), 'reservations-' . $from . '-' . $to . $suffix . '.xlsx');
    }

    public function revenuePdf(Request $request)
    {
        $from = $request->input('from', now()->subDays(30)->toDateString());
        $to   = $request->input('to', now()->toDateString());

        $baseQuery = fn() => Payment::whereDate('payment_date', '>=', $from)
            ->whereDate('payment_date', '<=', $to)->where('currency', 'YER');

        $totalRevenue     = $baseQuery()->sum('amount');
        $paymentCount     = $baseQuery()->count();
        $reservationCount = $baseQuery()->distinct('reservation_id')->count('reservation_id');
        $avgPayment       = $paymentCount > 0 ? $totalRevenue / $paymentCount : 0;

        $revenueByType = Payment::join('reservations', 'payments.reservation_id', '=', 'reservations.id')
            ->join('rooms', 'reservations.room_id', '=', 'rooms.id')
            ->join('room_types', 'rooms.room_type_id', '=', 'room_types.id')
            ->whereDate('payments.payment_date', '>=', $from)->whereDate('payments.payment_date', '<=', $to)
            ->where('payments.currency', 'YER')
            ->select('room_types.name', DB::raw('SUM(payments.amount) as total'), DB::raw('COUNT(payments.id) as payment_count'), DB::raw('COUNT(DISTINCT payments.reservation_id) as reservation_count'))
            ->groupBy('room_types.name')->orderByDesc('total')->get();

        $revenueByMethod = $baseQuery()
            ->select('method', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy('method')->get();

        $topRooms = Payment::join('reservations', 'payments.reservation_id', '=', 'reservations.id')
            ->join('rooms', 'reservations.room_id', '=', 'rooms.id')
            ->join('room_types', 'rooms.room_type_id', '=', 'room_types.id')
            ->whereDate('payments.payment_date', '>=', $from)->whereDate('payments.payment_date', '<=', $to)
            ->where('payments.currency', 'YER')
            ->select('rooms.room_number', 'room_types.name as type_name', DB::raw('SUM(payments.amount) as total'), DB::raw('COUNT(DISTINCT payments.reservation_id) as reservation_count'))
            ->groupBy('rooms.room_number', 'room_types.name')->orderByDesc('total')->limit(10)->get();

        $foreignPayments = Payment::whereDate('payment_date', '>=', $from)->whereDate('payment_date', '<=', $to)
            ->whereIn('currency', ['SAR', 'USD'])->select('currency', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))->groupBy('currency')->get();

        $pdf = $this->pdfOptions(pdf_load_view('reports.revenue_pdf', compact(
            'revenueByType', 'revenueByMethod', 'totalRevenue',
            'paymentCount', 'reservationCount', 'avgPayment',
            'topRooms', 'from', 'to', 'foreignPayments'
        )));
        $pdf->setPaper('a4', 'portrait');
        return $pdf->download('revenue-' . $from . '-' . $to . '.pdf');
    }

    public function revenueExcel(Request $request)
    {
        $from = $request->input('from', now()->subDays(30)->toDateString());
        $to   = $request->input('to', now()->toDateString());
        return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\RevenueReportExport($from, $to), 'revenue-' . $from . '-' . $to . '.xlsx');
    }

    public function roomsPdf(Request $request)
    {
        // تقرير حالة الغرف فقط: رقم الغرفة وحالتها الحالية — دون إيرادات أو عدد
        // حجوزات، فلا حاجة لفلتر فترة أو withCount/withSum.
        $rooms = Room::orderByRaw('CAST(room_number AS UNSIGNED) ASC')
            ->orderBy('room_number')
            ->get();
        $pdf = $this->pdfOptions(pdf_load_view('reports.rooms_pdf', compact('rooms')));
        $pdf->setPaper('a4', 'portrait');
        return $pdf->download('rooms-status-' . now()->format('Y-m-d') . '.pdf');
    }

    public function roomsExcel(Request $request)
    {
        $from = $request->input('from', now()->subDays(30)->toDateString());
        $to   = $request->input('to', now()->toDateString());
        return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\RoomsReportExport($from, $to), 'rooms-' . $from . '-' . $to . '.xlsx');
    }

    public function guestsPdf(Request $request)
    {
        $from = $request->input('from', now()->subDays(30)->toDateString());
        $to   = $request->input('to', now()->toDateString());
        $totalGuests     = \App\Models\Guest::count();
        $newGuests       = \App\Models\Guest::whereHas('reservations', fn($q) => $q->whereDate('check_in_date', '>=', $from)->whereDate('check_in_date', '<=', $to))->whereDoesntHave('reservations', fn($q) => $q->whereDate('check_in_date', '<', $from))->count();
        $returningGuests = \App\Models\Guest::whereHas('reservations', fn($q) => $q->whereDate('check_in_date', '>=', $from)->whereDate('check_in_date', '<=', $to))->whereHas('reservations', fn($q) => $q->whereDate('check_in_date', '<', $from))->count();
        $byNationality   = \App\Models\Guest::select('nationality', DB::raw('count(*) as count'))->groupBy('nationality')->orderByDesc('count')->limit(10)->get();
        $topGuests       = \App\Models\Guest::withCount(['reservations as period_reservations' => fn($q) => $q->whereDate('check_in_date', '>=', $from)->whereDate('check_in_date', '<=', $to)->whereNotIn('status', ['cancelled'])])->having('period_reservations', '>', 0)->orderByDesc('period_reservations')->limit(10)->get();
        $pdf = $this->pdfOptions(pdf_load_view('reports.guests_pdf', compact('totalGuests', 'newGuests', 'returningGuests', 'byNationality', 'topGuests', 'from', 'to')));
        $pdf->setPaper('a3', 'landscape');
        return $pdf->download('guests-' . $from . '-' . $to . '.pdf');
    }

    public function guestsExcel(Request $request)
    {
        $from = $request->input('from', now()->subDays(30)->toDateString());
        $to   = $request->input('to', now()->toDateString());
        return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\GuestsReportExport($from, $to), 'guests-' . $from . '-' . $to . '.xlsx');
    }

    public function debtsPdf(Request $request)
    {
        $section = $request->input('section', 'list');

        $reservations = Reservation::with(['guest', 'room'])->whereIn('status', ['checked_in', 'checked_out'])->whereRaw('paid_amount < total_amount')->orderByRaw('(total_amount - paid_amount) DESC')->get();
        $totalDebt    = $reservations->sum(fn($r) => $r->total_amount - $r->paid_amount);

        if ($section === 'aged') {
            $buckets = ['current' => ['count' => 0, 'total' => 0], '30_60' => ['count' => 0, 'total' => 0], '60_90' => ['count' => 0, 'total' => 0], 'over_90' => ['count' => 0, 'total' => 0]];
            foreach ($reservations as $res) {
                $refDate = $res->actual_check_out ?? $res->check_out_date;
                $days    = $refDate ? now()->startOfDay()->diffInDays($refDate->startOfDay()) : 0;
                $balance = $res->total_amount - $res->paid_amount;
                if ($days <= 30)     { $buckets['current']['count']++; $buckets['current']['total'] += $balance; }
                elseif ($days <= 60) { $buckets['30_60']['count']++;   $buckets['30_60']['total']   += $balance; }
                elseif ($days <= 90) { $buckets['60_90']['count']++;   $buckets['60_90']['total']   += $balance; }
                else                 { $buckets['over_90']['count']++;  $buckets['over_90']['total']  += $balance; }
            }
            $pdf = $this->pdfOptions(pdf_load_view('reports.debts_pdf_aged', compact('reservations', 'totalDebt', 'buckets')));
        } else {
            $pdf = $this->pdfOptions(pdf_load_view('reports.debts_pdf', compact('reservations', 'totalDebt')));
        }

        $pdf->setPaper('a3', 'landscape');
        return $pdf->download('debts-' . $section . '-' . now()->format('Y-m-d') . '.pdf');
    }

    public function debtsExcel(Request $request)
    {
        return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\DebtsReportExport(), 'debts-' . now()->format('Y-m-d') . '.xlsx');
    }

    /**
     * تقرير أسباب إلغاء الحجوزات — الحجوزات المحذوفة حذفاً ناعماً وتحمل سبب
     * إلغاء، مع المتأخرات وقت الإلغاء إن وُجدت، لتمكين الأدمن من مراجعة
     * الأسباب ومعالجتها بدل أن تختفي بيانات النزيل نهائياً عند الإلغاء.
     */
    public function cancelledReservations(Request $request)
    {
        $from   = $request->input('from', now()->subDays(30)->toDateString());
        $to     = $request->input('to', now()->toDateString());
        $search = $request->input('search', '');

        $query = Reservation::cancelledOnly()
            ->with(['guest', 'room', 'cancelledBy'])
            ->whereDate('cancelled_at', '>=', $from)
            ->whereDate('cancelled_at', '<=', $to);

        if ($search) {
            $query->whereHas('guest', fn($g) => $g->where('full_name', 'like', "%{$search}%"));
        }

        $reservations = $query->orderByDesc('cancelled_at')->paginate(30)->withQueryString();

        $totalCancelled     = (clone $query)->count();
        $totalUnpaidBalance = (clone $query)->get()->sum(fn($r) => max(0, (float)$r->total_amount - (float)$r->paid_amount));

        return view('reports.cancelled-reservations', compact('reservations', 'from', 'to', 'search', 'totalCancelled', 'totalUnpaidBalance'));
    }

    public function cancelledReservationsPdf(Request $request)
    {
        $from   = $request->input('from', now()->subDays(30)->toDateString());
        $to     = $request->input('to', now()->toDateString());
        $search = $request->input('search', '');

        $query = Reservation::cancelledOnly()
            ->with(['guest', 'room', 'cancelledBy'])
            ->whereDate('cancelled_at', '>=', $from)
            ->whereDate('cancelled_at', '<=', $to);

        if ($search) {
            $query->whereHas('guest', fn($g) => $g->where('full_name', 'like', "%{$search}%"));
        }

        $reservations       = $query->orderByDesc('cancelled_at')->get();
        $totalCancelled     = $reservations->count();
        $totalUnpaidBalance = $reservations->sum(fn($r) => max(0, (float)$r->total_amount - (float)$r->paid_amount));

        $pdf = $this->pdfOptions(pdf_load_view('reports.cancelled_reservations_pdf', compact('reservations', 'from', 'to', 'totalCancelled', 'totalUnpaidBalance')));
        $pdf->setPaper('a3', 'landscape');
        return $pdf->download('cancelled-reservations-' . $from . '-' . $to . '.pdf');
    }

    /**
     * تقرير الجهات الحكومية — سجل نزلاء بكل البيانات المطلوبة لإبلاغ الجهات
     * الحكومية (الأمن/الجوازات): النزيل الرئيسي وبيانات هويته كاملة، بالإضافة
     * إلى بيانات المرافقين وحالة الإقامة (غادروا/لم يغادروا).
     */
    private function governmentQuery(Request $request)
    {
        $from   = $request->input('from', now()->subDays(30)->toDateString());
        $to     = $request->input('to', now()->toDateString());
        $search = $request->input('search', '');

        $query = Reservation::with(['guest', 'room', 'companions'])
            ->whereDate('check_in_date', '>=', $from)
            ->whereDate('check_in_date', '<=', $to);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('guest', fn($g) => $g->where('full_name', 'like', "%{$search}%"))
                  ->orWhereHas('room', fn($r) => $r->where('room_number', 'like', "%{$search}%"));
            });
        }

        return [$query, $from, $to, $search];
    }

    /**
     * النزلاء المتواجدون اليوم (لم يغادروا بعد) والنزلاء الذين غادروا اليوم فعلياً
     * (بحسب actual_check_out، بغضّ النظر عن تاريخ دخولهم) — لتقرير الجهات الحكومية.
     */
    private function governmentTodayData(): array
    {
        $presentToday = Reservation::with(['guest', 'room', 'companions'])
            ->where('status', 'checked_in')
            ->orderBy('room_id')
            ->get();

        $departedToday = Reservation::with(['guest', 'room', 'companions'])
            ->where('status', 'checked_out')
            ->whereDate('actual_check_out', today())
            ->orderBy('actual_check_out')
            ->get();

        return [$presentToday, $departedToday];
    }

    public function government(Request $request)
    {
        [$query, $from, $to, $search] = $this->governmentQuery($request);

        $reservations = $query->orderByDesc('check_in_date')->paginate(30)->withQueryString();
        [$presentToday, $departedToday] = $this->governmentTodayData();

        return view('reports.government', compact('reservations', 'from', 'to', 'search', 'presentToday', 'departedToday'));
    }

    public function governmentPdf(Request $request)
    {
        $mode = $request->input('mode', 'range');

        if ($mode === 'today') {
            [$presentToday, $departedToday] = $this->governmentTodayData();

            $pdf = $this->pdfOptions(pdf_load_view('reports.government_pdf', [
                'mode'          => 'today',
                'presentToday'  => $presentToday,
                'departedToday' => $departedToday,
            ]));
            $pdf->setPaper('a4', 'portrait');
            return $pdf->download('government-report-today-' . now()->toDateString() . '.pdf');
        }

        [$query, $from, $to, $search] = $this->governmentQuery($request);

        $reservations = $query->orderByDesc('check_in_date')->get();

        $pdf = $this->pdfOptions(pdf_load_view('reports.government_pdf', compact('reservations', 'from', 'to') + ['mode' => 'range']));
        $pdf->setPaper('a4', 'portrait');
        return $pdf->download('government-report-' . $from . '-' . $to . '.pdf');
    }

    public function partialPayments(Request $request)
    {
        $from   = $request->input('from', now()->startOfYear()->toDateString());
        $to     = $request->input('to', now()->toDateString());
        $search = $request->input('search', '');
        $status = $request->input('status', 'partial');

        $query = Reservation::with(['guest', 'room.roomType', 'payments' => fn($q) => $q->with('receivedBy')->orderBy('payment_date')])
            ->whereIn('status', ['checked_in', 'checked_out', 'reserved'])
            ->whereDate('check_in_date', '>=', $from)
            ->whereDate('check_in_date', '<=', $to);

        if ($status === 'partial') {
            $query->where('payment_status', 'partial');
        } elseif ($status === 'all') {
            $query->whereIn('payment_status', ['partial', 'paid', 'unpaid'])
                  ->whereHas('payments');
        }

        if ($search) {
            $query->whereHas('guest', fn($q) => $q->where('full_name', 'like', "%{$search}%"));
        }

        $reservations = $query->orderByRaw('(total_amount - paid_amount) DESC')->get();

        $totalReservations = $reservations->count();
        $totalCollected    = $reservations->sum('paid_amount');
        $totalRemaining    = $reservations->sum(fn($r) => $r->total_amount - $r->paid_amount);
        $totalTransactions = $reservations->sum(fn($r) => $r->payments->count());

        return view('reports.partial-payments', compact(
            'reservations', 'from', 'to', 'search', 'status',
            'totalReservations', 'totalCollected', 'totalRemaining', 'totalTransactions'
        ));
    }

    /**
     * تقرير عم علي — جدول واحد بـ8 أعمدة لكل غرفة: رقم الغرفة، حالة الغرفة،
     * نزيل اليوم (مع وقت الدخول)، كم دفع نزيل اليوم، من استلم دفعته، مديونيته،
     * ثم نفس الشيء لنزيل الأمس (نزيل الأمس، سدد عند من، مديونيته) — مقارنة
     * سريعة بين يومين لكل غرفة على ورقة واحدة عرضية.
     */
    public function amAli(Request $request)
    {
        $date = $request->input('date', now()->toDateString());
        return view('reports.am-ali', $this->amAliData($date));
    }

    /**
     * تصدير تقرير عم علي إلى PDF لليوم المحدَّد (عرضي/landscape).
     */
    public function amAliPdf(Request $request)
    {
        $date = $request->input('date', now()->toDateString());
        $pdf  = $this->pdfOptions(pdf_load_view('reports.am_ali_pdf', $this->amAliData($date)));
        $pdf->setPaper('a4', 'landscape');
        return $pdf->download('am-ali-' . $date . '.pdf');
    }

    /**
     * بناء بيانات تقرير عم علي (مشترك بين العرض والتصدير): صف واحد لكل غرفة
     * يقارن نزيل اليوم بنزيل الأمس (إن وُجدا).
     */
    private function amAliData(string $date): array
    {
        $yesterday = \Carbon\Carbon::parse($date)->subDay()->toDateString();

        $rooms = Room::orderBy('room_number')->get()->sortBy(fn($r) => $r->room_number, SORT_NATURAL)->values();

        $rows = $rooms->map(function ($room) use ($date, $yesterday) {
            $today = $this->amAliRoomOccupant($room->id, $date);
            $yday  = $this->amAliRoomOccupant($room->id, $yesterday);

            return [
                'room'         => $room,
                // حالة الغرفة الفعلية الحالية (مشغولة/متاحة/تحت الفحص/صيانة) من
                // Room::status نفسه، وليس مجرد استنتاج من وجود نزيل اليوم —
                // فغرفة فيها نزيل اليوم قد تكون مثلاً تحت الفحص بعد مغادرته.
                'status'       => $room->status_label,
                'status_color' => $room->status_color,
                'today'        => $today,
                'yday'         => $yday,
            ];
        });

        return compact('rows', 'date', 'yesterday');
    }

    /**
     * بيانات نزيل غرفة معيّنة في تاريخ محدَّد (إن وُجد): اسمه، وقت دخوله،
     * المبلغ المدفوع لغرفته، آخر موظف استلم دفعة منه، ومديونيته المتبقية.
     */
    private function amAliRoomOccupant(int $roomId, string $date): ?array
    {
        $res = Reservation::with(['guest', 'payments.receivedBy'])
            ->where('room_id', $roomId)
            ->where(function ($q) use ($date) {
                $q->where(function ($q2) use ($date) {
                    $q2->where('status', 'checked_in')
                       ->whereDate('check_in_date', '<=', $date)
                       ->whereDate('check_out_date', '>=', $date);
                })->orWhere(function ($q2) use ($date) {
                    $q2->where('status', 'checked_out')
                       ->whereDate('check_in_date', '<=', $date)
                       ->whereDate('actual_check_out', '>=', $date);
                });
            })
            ->first();

        if (!$res) {
            return null;
        }

        $remaining = (float) $res->total_amount - (float) $res->paid_amount;

        // إذا كان نفس الحجز مستمراً من الأمس إلى اليوم، فـ"آخر دفعة كما كانت في
        // ذلك التاريخ" ليست بالضرورة آخر دفعة على الحجز إجمالاً (قد تكون دفعة
        // لاحقة اليوم) — نقتصر على الدفعات التي تمّت في ذلك التاريخ أو قبله.
        $cutoff      = \Carbon\Carbon::parse($date)->endOfDay();
        $lastPayment = $res->payments
            ->filter(fn ($p) => $p->payment_date && $p->payment_date->lte($cutoff))
            ->sortByDesc('payment_date')
            ->first();

        return [
            'guest_name'    => $res->guest?->full_name ?? '—',
            'check_in_date' => $res->check_in_date,
            'check_in_time' => $res->check_in_time ?: $res->check_in_date?->format('H:i'),
            // "كم دفع" و"من استلم" يعرضان آخر دفعة فعلية (لا إجمالي المدفوع)،
            // حتى يتطابق المبلغ المعروض مع اسم من استلمه فعلاً.
            'paid'          => $lastPayment ? (float) $lastPayment->amount : 0.0,
            'paid_date'     => $lastPayment?->payment_date,
            'received_by'   => $lastPayment?->receivedBy?->name,
            'remaining'     => $remaining,
            'currency'      => $res->currency_symbol,
        ];
    }

    public function salariesPdf(Request $request)
    {
        $year = (int) $request->input('year', now()->year);
        $salaries = \App\Models\Salary::with('employee')->where('year', $year)->orderBy('month', 'desc')->get();
        $byMonth  = $salaries->groupBy('month')->map(fn($g) => ['count' => $g->count(), 'total_net' => $g->sum('net_salary'), 'total_base' => $g->sum('base_salary'), 'total_bonus' => $g->sum('bonuses'), 'total_ded' => $g->sum('deductions'), 'paid' => $g->where('status', 'paid')->count(), 'pending' => $g->where('status', 'pending')->count()]);
        $totalNet = $salaries->sum('net_salary');
        $pdf = $this->pdfOptions(pdf_load_view('reports.salaries_pdf', compact('salaries', 'byMonth', 'year', 'totalNet')));
        $pdf->setPaper('a4', 'portrait');
        return $pdf->download('salaries-' . $year . '.pdf');
    }

    public function salariesExcel(Request $request)
    {
        $year = (int) $request->input('year', now()->year);
        return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\SalariesReportExport($year), 'salaries-' . $year . '.xlsx');
    }

    public function hrHub(Request $request)
    {
        $tab  = $request->input('tab', 'salaries');
        $from = $request->input('from', now()->subDays(30)->toDateString());
        $to   = $request->input('to', now()->toDateString());
        $year = (int) $request->input('year', now()->year);

        $salaries  = collect();
        $byMonth   = collect();
        $years     = collect();
        $totalNet  = 0;
        $staffData = collect();

        if ($tab === 'salaries') {
            $salaries = Salary::with('employee')->where('year', $year)->orderBy('month', 'desc')->get();
            $byMonth  = $salaries->groupBy('month')->map(fn($g) => [
                'count'       => $g->count(),
                'total_net'   => $g->sum('net_salary'),
                'total_base'  => $g->sum('base_salary'),
                'total_bonus' => $g->sum('bonuses'),
                'total_ded'   => $g->sum('deductions'),
                'paid'        => $g->where('status', 'paid')->count(),
                'pending'     => $g->where('status', 'pending')->count(),
            ]);
            $years    = Salary::selectRaw('DISTINCT year')->orderByDesc('year')->pluck('year');
            $totalNet = $salaries->sum('net_salary');
        } elseif ($tab === 'staff') {
            $staffData = User::with(['reservations' => fn($q) => $q
                ->whereDate('check_in_date', '>=', $from)
                ->whereDate('check_in_date', '<=', $to)
                ->with(['room.roomType', 'guest', 'payments'])
            ])->where('is_active', true)->get()
            ->map(fn($u) => [
                'user'         => $u,
                'checkins'     => $u->reservations->count(),
                'checked_out'  => $u->reservations->where('status', 'checked_out')->count(),
                'revenue'      => $u->reservations->sum(fn($r) => $r->payments->sum('amount')),
                'reservations' => $u->reservations,
            ]);
        }

        return view('reports.hr-hub', compact('tab', 'from', 'to', 'year', 'salaries', 'byMonth', 'years', 'totalNet', 'staffData'));
    }

    public function guestsRoomsHub(Request $request)
    {
        $tab  = $request->input('tab', 'guests');
        $from = $request->input('from', now()->subDays(30)->toDateString());
        $to   = $request->input('to', now()->toDateString());

        $totalGuests     = 0;
        $newGuests       = 0;
        $returningGuests = 0;
        $byNationality   = collect();
        $topGuests       = collect();
        $rooms           = collect();

        if ($tab === 'guests') {
            $totalGuests     = \App\Models\Guest::count();
            $newGuests       = \App\Models\Guest::whereHas('reservations', fn($q) => $q->whereDate('check_in_date', '>=', $from)->whereDate('check_in_date', '<=', $to))
                ->whereDoesntHave('reservations', fn($q) => $q->whereDate('check_in_date', '<', $from))->count();
            $returningGuests = \App\Models\Guest::whereHas('reservations', fn($q) => $q->whereDate('check_in_date', '>=', $from)->whereDate('check_in_date', '<=', $to))
                ->whereHas('reservations', fn($q) => $q->whereDate('check_in_date', '<', $from))->count();
            $byNationality   = \App\Models\Guest::select('nationality', DB::raw('count(*) as count'))
                ->groupBy('nationality')->orderByDesc('count')->limit(10)->get();
            $topGuests       = \App\Models\Guest::withCount(['reservations as period_reservations' => fn($q) => $q
                ->whereDate('check_in_date', '>=', $from)->whereDate('check_in_date', '<=', $to)
                ->whereNotIn('status', ['cancelled'])])
                ->having('period_reservations', '>', 0)->orderByDesc('period_reservations')->limit(10)->get();
        } elseif ($tab === 'rooms') {
            // نعرض الغرف المتاحة/المشغولة/تحت الفحص فقط (نُخفي الصيانة)، مرتّبةً
            // تصاعدياً حسب رقم الغرفة (من الأصغر للأكبر) بترتيب رقمي لا نصّي.
            $rooms = Room::with('roomType')
                ->whereIn('status', ['available', 'occupied', 'under_inspection'])
                ->withCount(['reservations as total_reservations' => fn($q) => $q
                    ->whereDate('check_in_date', '>=', $from)->whereDate('check_in_date', '<=', $to)
                    ->whereNotIn('status', ['cancelled'])])
                ->withSum(['reservations as total_revenue' => fn($q) => $q
                    ->whereDate('check_in_date', '>=', $from)->whereDate('check_in_date', '<=', $to)
                    ->whereNotIn('status', ['cancelled'])], 'total_amount')
                ->orderByRaw('CAST(room_number AS UNSIGNED) ASC')
                ->orderBy('room_number')
                ->get();
        }

        return view('reports.guests-rooms-hub', compact(
            'tab', 'from', 'to',
            'totalGuests', 'newGuests', 'returningGuests',
            'byNationality', 'topGuests', 'rooms'
        ));
    }
}
