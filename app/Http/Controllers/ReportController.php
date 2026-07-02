<?php
namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\Salary;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{


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
        $from = $request->input('from', now()->startOfMonth()->toDateString());
        $to   = $request->input('to', now()->toDateString());

        $totalRevenue = Payment::whereDate('payment_date', '>=', $from)
            ->whereDate('payment_date', '<=', $to)
            ->where('currency', 'YER')
            ->sum('amount');

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
            'from', 'to', 'totalRevenue', 'totalExpenses', 'netProfit',
            'revenueByMethod', 'expensesByCategory', 'monthlyTrend'
        ));
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
        $pdf->setPaper('a4', 'landscape');
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

        } elseif ($tab === 'ratios') {
            $ratioFrom = match ($period) {
                'quarter' => now()->startOfQuarter()->toDateString(),
                'year'    => now()->startOfYear()->toDateString(),
                'all'     => Reservation::orderBy('check_in_date')->first()?->check_in_date->toDateString() ?? now()->subYear()->toDateString(),
                default   => now()->startOfMonth()->toDateString(),
            };
            $ratioTo  = now()->toDateString();
            $days     = \Carbon\Carbon::parse($ratioFrom)->diffInDays($ratioTo) + 1;
            $rev      = Payment::whereDate('payment_date', '>=', $ratioFrom)->whereDate('payment_date', '<=', $ratioTo)->where('currency', 'YER')->sum('amount');
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
            'byMethod', 'totalMethodAmount', 'totalMethodCount', 'dailyByMethod', 'methodLabels',
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

        $reservations = Reservation::with(['guest', 'room', 'payments'])
            ->whereDate('check_in_date', '>=', $from)
            ->whereDate('check_in_date', '<=', $to)
            ->whereNotIn('status', ['cancelled'])
            ->orderBy('check_in_date', 'desc')
            ->get();
        $total      = $reservations->count();
        $checkedIn  = $reservations->where('status', 'checked_in')->count();
        $checkedOut = $reservations->where('status', 'checked_out')->count();
        $pdf = $this->pdfOptions(pdf_load_view('reports.reservations_pdf', compact('reservations', 'from', 'to', 'total', 'checkedIn', 'checkedOut')));
        $pdf->setPaper('a4', 'landscape');
        return $pdf->download('reservations-' . $from . '-' . $to . '.pdf');
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
            $reservations = Reservation::with(['guest', 'room.roomType', 'payments'])
                ->whereDate('check_in_date', '>=', $from)
                ->whereDate('check_in_date', '<=', $to)
                ->whereNotIn('status', ['cancelled'])
                ->orderBy('check_in_date', 'desc')
                ->orderBy('id', 'desc')
                ->paginate(50)
                ->withQueryString();
            $total      = $reservations->total();
            $checkedIn  = Reservation::whereDate('check_in_date', '>=', $from)
                ->whereDate('check_in_date', '<=', $to)->where('status', 'checked_in')->count();
            $checkedOut = Reservation::whereDate('check_in_date', '>=', $from)
                ->whereDate('check_in_date', '<=', $to)->where('status', 'checked_out')->count();
        }

        return view('reports.daily-hub', compact(
            'tab', 'date', 'from', 'to', 'preset',
            'dailyReservations', 'totalRooms', 'dailyOccupancy',
            'reservations', 'total', 'checkedIn', 'checkedOut'
        ));
    }

    public function reservationsExcel(Request $request)
    {
        $from = $request->input('from', now()->subDays(30)->toDateString());
        $to   = $request->input('to', now()->toDateString());
        return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\ReservationsReportExport($from, $to), 'reservations-' . $from . '-' . $to . '.xlsx');
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
        $from = $request->input('from', now()->subDays(30)->toDateString());
        $to   = $request->input('to', now()->toDateString());
        $rooms = Room::with('roomType')
            ->withCount(['reservations as total_reservations' => fn($q) => $q->whereDate('check_in_date', '>=', $from)->whereDate('check_in_date', '<=', $to)->whereNotIn('status', ['cancelled'])])
            ->withSum(['reservations as total_revenue' => fn($q) => $q->whereDate('check_in_date', '>=', $from)->whereDate('check_in_date', '<=', $to)->whereNotIn('status', ['cancelled'])], 'total_amount')
            ->orderByDesc('total_revenue')->get();
        $pdf = $this->pdfOptions(pdf_load_view('reports.rooms_pdf', compact('rooms', 'from', 'to')));
        $pdf->setPaper('a4', 'landscape');
        return $pdf->download('rooms-' . $from . '-' . $to . '.pdf');
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
        $pdf->setPaper('a4', 'landscape');
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

        $pdf->setPaper('a4', 'landscape');
        return $pdf->download('debts-' . $section . '-' . now()->format('Y-m-d') . '.pdf');
    }

    public function debtsExcel(Request $request)
    {
        return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\DebtsReportExport(), 'debts-' . now()->format('Y-m-d') . '.xlsx');
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
            $rooms = Room::with('roomType')
                ->withCount(['reservations as total_reservations' => fn($q) => $q
                    ->whereDate('check_in_date', '>=', $from)->whereDate('check_in_date', '<=', $to)
                    ->whereNotIn('status', ['cancelled'])])
                ->withSum(['reservations as total_revenue' => fn($q) => $q
                    ->whereDate('check_in_date', '>=', $from)->whereDate('check_in_date', '<=', $to)
                    ->whereNotIn('status', ['cancelled'])], 'total_amount')
                ->orderByDesc('total_revenue')->get();
        }

        return view('reports.guests-rooms-hub', compact(
            'tab', 'from', 'to',
            'totalGuests', 'newGuests', 'returningGuests',
            'byNationality', 'topGuests', 'rooms'
        ));
    }
}
