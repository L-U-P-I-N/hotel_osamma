<?php
namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\Salary;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function occupancy(Request $request)
    {
        $from = $request->input('from', now()->subDays(30)->toDateString());
        $to = $request->input('to', now()->toDateString());

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
            'date' => $r->date,
            'percent' => $totalRooms > 0 ? round(($r->count / $totalRooms) * 100) : 0,
        ]);

        return view('reports.occupancy', compact('dailyOccupancy', 'from', 'to', 'totalRooms'));
    }

    public function revenue(Request $request)
    {
        $from = $request->input('from', now()->subDays(30)->toDateString());
        $to   = $request->input('to', now()->toDateString());

        $baseQuery = fn() => Payment::whereDate('payment_date', '>=', $from)
            ->whereDate('payment_date', '<=', $to)
            ->where('currency', 'YER');

        $totalRevenue     = $baseQuery()->sum('amount');
        $paymentCount     = $baseQuery()->count();
        $reservationCount = $baseQuery()->distinct('reservation_id')->count('reservation_id');
        $avgPayment       = $paymentCount > 0 ? $totalRevenue / $paymentCount : 0;

        $revenueByType = Payment::join('reservations', 'payments.reservation_id', '=', 'reservations.id')
            ->join('rooms', 'reservations.room_id', '=', 'rooms.id')
            ->join('room_types', 'rooms.room_type_id', '=', 'room_types.id')
            ->whereDate('payments.payment_date', '>=', $from)
            ->whereDate('payments.payment_date', '<=', $to)
            ->where('payments.currency', 'YER')
            ->select(
                'room_types.name',
                DB::raw('SUM(payments.amount) as total'),
                DB::raw('COUNT(payments.id) as payment_count'),
                DB::raw('COUNT(DISTINCT payments.reservation_id) as reservation_count')
            )
            ->groupBy('room_types.name')
            ->orderByDesc('total')
            ->get();

        $revenueByMethod = $baseQuery()
            ->select('method', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy('method')
            ->get();

        $dailyRevenue = $baseQuery()
            ->select(
                DB::raw('DATE(payment_date) as date'),
                DB::raw('SUM(amount) as total'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $topRooms = Payment::join('reservations', 'payments.reservation_id', '=', 'reservations.id')
            ->join('rooms', 'reservations.room_id', '=', 'rooms.id')
            ->join('room_types', 'rooms.room_type_id', '=', 'room_types.id')
            ->whereDate('payments.payment_date', '>=', $from)
            ->whereDate('payments.payment_date', '<=', $to)
            ->where('payments.currency', 'YER')
            ->select(
                'rooms.room_number',
                'room_types.name as type_name',
                DB::raw('SUM(payments.amount) as total'),
                DB::raw('COUNT(DISTINCT payments.reservation_id) as reservation_count')
            )
            ->groupBy('rooms.room_number', 'room_types.name')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        $foreignPayments = Payment::whereDate('payment_date', '>=', $from)
            ->whereDate('payment_date', '<=', $to)
            ->whereIn('currency', ['SAR', 'USD'])
            ->select('currency', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy('currency')
            ->get();

        return view('reports.revenue', compact(
            'revenueByType', 'revenueByMethod', 'totalRevenue',
            'paymentCount', 'reservationCount', 'avgPayment',
            'dailyRevenue', 'topRooms',
            'from', 'to', 'foreignPayments'
        ));
    }

    public function staffPerformance(Request $request)
    {
        $from = $request->input('from', now()->subDays(30)->toDateString());
        $to = $request->input('to', now()->toDateString());

        $staffData = User::with(['reservations' => function ($q) use ($from, $to) {
            $q->whereDate('check_in_date', '>=', $from)
              ->whereDate('check_in_date', '<=', $to)
              ->with(['room.roomType', 'guest', 'payments']);
        }])->where('is_active', true)->get()
        ->map(fn($u) => [
            'user'         => $u,
            'checkins'     => $u->reservations->count(),
            'checked_out'  => $u->reservations->where('status', 'checked_out')->count(),
            'revenue'      => $u->reservations->sum(fn($r) => $r->payments->sum('amount')),
            'reservations' => $u->reservations,
        ]);

        return view('reports.staff', compact('staffData', 'from', 'to'));
    }

    public function reservations(Request $request)
    {
        $preset = $request->input('preset', 'custom');
        $from   = match($preset) {
            'today'  => now()->toDateString(),
            'week'   => now()->subDays(6)->toDateString(),
            default  => $request->input('from', now()->subDays(30)->toDateString()),
        };
        $to = match($preset) {
            'today'  => now()->toDateString(),
            'week'   => now()->toDateString(),
            default  => $request->input('to', now()->toDateString()),
        };

        $reservations = Reservation::with(['guest', 'room.roomType'])
            ->whereDate('check_in_date', '>=', $from)
            ->whereDate('check_in_date', '<=', $to)
            ->whereNotIn('status', ['cancelled'])
            ->orderBy('check_in_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(50)
            ->withQueryString();

        $total   = $reservations->total();
        $checkedIn  = Reservation::whereDate('check_in_date', '>=', $from)->whereDate('check_in_date', '<=', $to)->where('status', 'checked_in')->count();
        $checkedOut = Reservation::whereDate('check_in_date', '>=', $from)->whereDate('check_in_date', '<=', $to)->where('status', 'checked_out')->count();

        return view('reports.reservations', compact('reservations', 'from', 'to', 'preset', 'total', 'checkedIn', 'checkedOut'));
    }

    public function shifts(Request $request)
    {
        $from = $request->input('from', now()->subDays(30)->toDateString());
        $to   = $request->input('to', now()->toDateString());

        $shifts = Shift::with(['user', 'payments.reservation.room', 'withdrawals'])
            ->whereDate('shift_date', '>=', $from)
            ->whereDate('shift_date', '<=', $to)
            ->orderBy('shift_date', 'desc')
            ->orderBy('started_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        return view('reports.shifts', compact('shifts', 'from', 'to'));
    }

    public function rooms(Request $request)
    {
        $from = $request->input('from', now()->subDays(30)->toDateString());
        $to   = $request->input('to', now()->toDateString());

        $rooms = Room::with('roomType')
            ->withCount(['reservations as total_reservations' => function ($q) use ($from, $to) {
                $q->whereDate('check_in_date', '>=', $from)
                  ->whereDate('check_in_date', '<=', $to)
                  ->whereNotIn('status', ['cancelled']);
            }])
            ->withSum(['reservations as total_revenue' => function ($q) use ($from, $to) {
                $q->whereDate('check_in_date', '>=', $from)
                  ->whereDate('check_in_date', '<=', $to)
                  ->whereNotIn('status', ['cancelled']);
            }], 'total_amount')
            ->orderByDesc('total_revenue')
            ->get();

        return view('reports.rooms', compact('rooms', 'from', 'to'));
    }

    public function guests(Request $request)
    {
        $from = $request->input('from', now()->subDays(30)->toDateString());
        $to   = $request->input('to', now()->toDateString());

        $totalGuests = \App\Models\Guest::count();

        $newGuests = \App\Models\Guest::whereHas('reservations', function ($q) use ($from, $to) {
            $q->whereDate('check_in_date', '>=', $from)
              ->whereDate('check_in_date', '<=', $to);
        })->whereDoesntHave('reservations', function ($q) use ($from) {
            $q->whereDate('check_in_date', '<', $from);
        })->count();

        $returningGuests = \App\Models\Guest::whereHas('reservations', function ($q) use ($from, $to) {
            $q->whereDate('check_in_date', '>=', $from)
              ->whereDate('check_in_date', '<=', $to);
        })->whereHas('reservations', function ($q) use ($from) {
            $q->whereDate('check_in_date', '<', $from);
        })->count();

        $byNationality = \App\Models\Guest::select('nationality', DB::raw('count(*) as count'))
            ->groupBy('nationality')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        $topGuests = \App\Models\Guest::withCount(['reservations as period_reservations' => function ($q) use ($from, $to) {
            $q->whereDate('check_in_date', '>=', $from)
              ->whereDate('check_in_date', '<=', $to)
              ->whereNotIn('status', ['cancelled']);
        }])
        ->having('period_reservations', '>', 0)
        ->orderByDesc('period_reservations')
        ->limit(10)
        ->get();

        return view('reports.guests', compact(
            'totalGuests', 'newGuests', 'returningGuests',
            'byNationality', 'topGuests', 'from', 'to'
        ));
    }

    public function debts(Request $request)
    {
        $reservations = Reservation::with(['guest', 'room.roomType'])
            ->whereIn('status', ['checked_in', 'checked_out'])
            ->whereRaw('paid_amount < total_amount')
            ->orderByRaw('(total_amount - paid_amount) DESC')
            ->get();

        $totalDebt = $reservations->sum(fn($r) => $r->total_amount - $r->paid_amount);

        return view('reports.debts', compact('reservations', 'totalDebt'));
    }

    public function dailyClose(Request $request)
    {
        $date = $request->input('date', today()->toDateString());

        $payments = Payment::whereDate('payment_date', $date)->where('currency', 'YER')->get();
        $totalRevenue     = $payments->sum('amount');
        $paymentCount     = $payments->count();
        $reservationCount = $payments->unique('reservation_id')->count();

        $revenueByMethod = Payment::whereDate('payment_date', $date)
            ->where('currency', 'YER')
            ->select('method', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy('method')
            ->get();

        $expenses = \App\Models\Expense::whereDate('expense_date', $date)->get();
        $totalExpenses = $expenses->sum('amount');

        $expensesByCategory = \App\Models\Expense::whereDate('expense_date', $date)
            ->select('category', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy('category')
            ->get();

        $shifts = \App\Models\Shift::with('user')
            ->whereDate('shift_date', $date)
            ->get();

        $cashRevenue     = $payments->where('method', 'cash')->sum('amount');
        $cashExpenses    = $expenses->where('payment_method', 'cash')->sum('amount');
        $totalWithdrawals = $shifts->sum('total_withdrawals_yer');
        $expectedCash    = $cashRevenue - $cashExpenses;
        $netDay          = $totalRevenue - $totalExpenses;

        return view('reports.daily-close', compact(
            'date', 'totalRevenue', 'paymentCount', 'reservationCount',
            'revenueByMethod', 'totalExpenses', 'expensesByCategory',
            'shifts', 'cashRevenue', 'cashExpenses', 'totalWithdrawals',
            'expectedCash', 'netDay'
        ));
    }

    public function agedDebts(Request $request)
    {
        $reservations = Reservation::with(['guest', 'room'])
            ->whereIn('status', ['checked_in', 'checked_out'])
            ->whereRaw('paid_amount < total_amount')
            ->orderByRaw('(total_amount - paid_amount) DESC')
            ->get();

        $totalDebt = $reservations->sum(fn($r) => $r->total_amount - $r->paid_amount);

        $buckets = [
            'current' => ['count' => 0, 'total' => 0],
            '30_60'   => ['count' => 0, 'total' => 0],
            '60_90'   => ['count' => 0, 'total' => 0],
            'over_90' => ['count' => 0, 'total' => 0],
        ];

        foreach ($reservations as $res) {
            $refDate = $res->actual_check_out ?? $res->check_out_date;
            $days    = $refDate ? now()->startOfDay()->diffInDays($refDate->startOfDay()) : 0;
            $balance = $res->total_amount - $res->paid_amount;

            if ($days <= 30)      { $buckets['current']['count']++; $buckets['current']['total'] += $balance; }
            elseif ($days <= 60)  { $buckets['30_60']['count']++;   $buckets['30_60']['total']   += $balance; }
            elseif ($days <= 90)  { $buckets['60_90']['count']++;   $buckets['60_90']['total']   += $balance; }
            else                  { $buckets['over_90']['count']++;  $buckets['over_90']['total']  += $balance; }
        }

        return view('reports.aged-debts', compact('reservations', 'totalDebt', 'buckets'));
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

        $rawTrend = DB::select("
            SELECT
                DATE_FORMAT(m.month_start, '%Y-%m') as month_key,
                COALESCE(rev.revenue, 0) as revenue,
                COALESCE(exp.expenses, 0) as expenses
            FROM (
                SELECT DATE_FORMAT(payment_date, '%Y-%m-01') as month_start
                FROM payments
                WHERE payment_date BETWEEN ? AND ? AND currency = 'YER' AND deleted_at IS NULL
                GROUP BY DATE_FORMAT(payment_date, '%Y-%m-01')
                UNION
                SELECT DATE_FORMAT(expense_date, '%Y-%m-01') as month_start
                FROM expenses
                WHERE expense_date BETWEEN ? AND ? AND deleted_at IS NULL
                GROUP BY DATE_FORMAT(expense_date, '%Y-%m-01')
            ) m
            LEFT JOIN (
                SELECT DATE_FORMAT(payment_date, '%Y-%m-01') as mo, SUM(amount) as revenue
                FROM payments WHERE payment_date BETWEEN ? AND ? AND currency = 'YER' AND deleted_at IS NULL
                GROUP BY mo
            ) rev ON rev.mo = m.month_start
            LEFT JOIN (
                SELECT DATE_FORMAT(expense_date, '%Y-%m-01') as mo, SUM(amount) as expenses
                FROM expenses WHERE expense_date BETWEEN ? AND ? AND deleted_at IS NULL
                GROUP BY mo
            ) exp ON exp.mo = m.month_start
            GROUP BY m.month_start
            ORDER BY m.month_start
        ", [$from, $to, $from, $to, $from, $to, $from, $to]);

        $monthlyTrend = collect($rawTrend)->map(function ($row) {
            [$year, $month] = explode('-', $row->month_key);
            $row->month_label = \App\Models\Salary::monthName((int)$month) . ' ' . $year;
            return $row;
        });

        return view('reports.profit-loss', compact(
            'from', 'to', 'totalRevenue', 'totalExpenses', 'netProfit',
            'revenueByMethod', 'expensesByCategory', 'monthlyTrend'
        ));
    }

    public function roomRevenue(Request $request)
    {
        $from = $request->input('from', now()->startOfMonth()->toDateString());
        $to   = $request->input('to', now()->toDateString());

        $roomTypes = RoomType::with('rooms')->get();

        $revenueByType = [];
        foreach ($roomTypes as $type) {
            $roomIds = $type->rooms->pluck('id');

            $actualRevenue = Payment::whereHas('reservation', fn($q) => $q->whereIn('room_id', $roomIds))
                ->whereDate('payment_date', '>=', $from)
                ->whereDate('payment_date', '<=', $to)
                ->where('currency', 'YER')
                ->sum('amount');

            $reservationCount = Reservation::whereIn('room_id', $roomIds)
                ->whereDate('check_in_date', '>=', $from)
                ->whereDate('check_in_date', '<=', $to)
                ->whereIn('status', ['checked_in', 'checked_out'])
                ->count();

            $totalNights = Reservation::whereIn('room_id', $roomIds)
                ->whereDate('check_in_date', '>=', $from)
                ->whereDate('check_in_date', '<=', $to)
                ->whereIn('status', ['checked_in', 'checked_out'])
                ->selectRaw('SUM(DATEDIFF(check_out_date, check_in_date)) as nights')
                ->value('nights') ?? 0;

            $expectedRevenue = $totalNights * (float)$type->base_price;

            $revenueByType[] = [
                'type'             => $type,
                'rooms_count'      => $type->rooms->count(),
                'actual_revenue'   => (float)$actualRevenue,
                'expected_revenue' => $expectedRevenue,
                'reservation_count'=> $reservationCount,
                'total_nights'     => (int)$totalNights,
                'adr'              => $totalNights > 0 ? round($actualRevenue / $totalNights) : 0,
                'achievement'      => $expectedRevenue > 0 ? round(($actualRevenue / $expectedRevenue) * 100) : 0,
            ];
        }

        $totalActual   = array_sum(array_column($revenueByType, 'actual_revenue'));
        $totalExpected = array_sum(array_column($revenueByType, 'expected_revenue'));

        return view('reports.room-revenue', compact(
            'from', 'to', 'revenueByType', 'totalActual', 'totalExpected'
        ));
    }

    public function cashFlow(Request $request)
    {
        $from = $request->input('from', now()->startOfMonth()->toDateString());
        $to   = $request->input('to', now()->toDateString());

        // Build daily cash flow
        $days = collect();
        $period = \Carbon\CarbonPeriod::create($from, $to);

        $allPayments = Payment::whereDate('payment_date', '>=', $from)
            ->whereDate('payment_date', '<=', $to)
            ->where('currency', 'YER')
            ->selectRaw('DATE(payment_date) as day, SUM(amount) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $allExpenses = Expense::whereDate('expense_date', '>=', $from)
            ->whereDate('expense_date', '<=', $to)
            ->where('currency', 'YER')
            ->selectRaw('DATE(expense_date) as day, SUM(amount) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $runningBalance = 0;
        foreach ($period as $date) {
            $d   = $date->format('Y-m-d');
            $rev = (float)($allPayments[$d] ?? 0);
            $exp = (float)($allExpenses[$d] ?? 0);
            $runningBalance += $rev - $exp;
            $days->push([
                'date'    => $d,
                'label'   => $date->format('d/m'),
                'revenue' => $rev,
                'expense' => $exp,
                'net'     => $rev - $exp,
                'balance' => $runningBalance,
            ]);
        }

        $totalIn  = $days->sum('revenue');
        $totalOut = $days->sum('expense');
        $netFlow  = $totalIn - $totalOut;

        return view('reports.cash-flow', compact(
            'from', 'to', 'days', 'totalIn', 'totalOut', 'netFlow'
        ));
    }

    public function salaries(Request $request)
    {
        $year = $request->input('year', now()->year);

        $salaries = \App\Models\Salary::with('employee')
            ->where('year', $year)
            ->orderBy('month', 'desc')
            ->get();

        $byMonth = $salaries->groupBy('month')->map(fn($g) => [
            'count'       => $g->count(),
            'total_net'   => $g->sum('net_salary'),
            'total_base'  => $g->sum('base_salary'),
            'total_bonus' => $g->sum('bonuses'),
            'total_ded'   => $g->sum('deductions'),
            'paid'        => $g->where('status', 'paid')->count(),
            'pending'     => $g->where('status', 'pending')->count(),
        ]);

        $years = \App\Models\Salary::selectRaw('DISTINCT year')->orderByDesc('year')->pluck('year');
        $totalNet = $salaries->sum('net_salary');

        return view('reports.salaries', compact('salaries', 'byMonth', 'year', 'years', 'totalNet'));
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
        $pdf = $this->pdfOptions(\Barryvdh\DomPDF\Facade\Pdf::loadView('reports.occupancy_pdf', compact('dailyOccupancy', 'from', 'to', 'totalRooms')));
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
        $pdf = $this->pdfOptions(\Barryvdh\DomPDF\Facade\Pdf::loadView('reports.staff_pdf', compact('staffData', 'from', 'to')));
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
        $pdf = $this->pdfOptions(\Barryvdh\DomPDF\Facade\Pdf::loadView('reports.shifts_pdf', compact('shifts', 'from', 'to')));
        $pdf->setPaper('a4', 'landscape');
        return $pdf->download('shifts-' . $from . '-' . $to . '.pdf');
    }

    public function shiftsExcel(Request $request)
    {
        $from = $request->input('from', now()->subDays(30)->toDateString());
        $to   = $request->input('to', now()->toDateString());
        return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\ShiftsReportExport($from, $to), 'shifts-' . $from . '-' . $to . '.xlsx');
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
        $reservations = Reservation::with(['guest', 'room', 'payments'])
            ->whereDate('check_in_date', '>=', $from)
            ->whereDate('check_in_date', '<=', $to)
            ->whereNotIn('status', ['cancelled'])
            ->orderBy('check_in_date', 'desc')
            ->get();
        $total      = $reservations->count();
        $checkedIn  = $reservations->where('status', 'checked_in')->count();
        $checkedOut = $reservations->where('status', 'checked_out')->count();
        $pdf = $this->pdfOptions(\Barryvdh\DomPDF\Facade\Pdf::loadView('reports.reservations_pdf', compact('reservations', 'from', 'to', 'total', 'checkedIn', 'checkedOut')));
        $pdf->setPaper('a4', 'landscape');
        return $pdf->download('reservations-' . $from . '-' . $to . '.pdf');
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

        $pdf = $this->pdfOptions(\Barryvdh\DomPDF\Facade\Pdf::loadView('reports.revenue_pdf', compact(
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
        $pdf = $this->pdfOptions(\Barryvdh\DomPDF\Facade\Pdf::loadView('reports.rooms_pdf', compact('rooms', 'from', 'to')));
        $pdf->setPaper('a4', 'portrait');
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
        $pdf = $this->pdfOptions(\Barryvdh\DomPDF\Facade\Pdf::loadView('reports.guests_pdf', compact('totalGuests', 'newGuests', 'returningGuests', 'byNationality', 'topGuests', 'from', 'to')));
        $pdf->setPaper('a4', 'portrait');
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
        $reservations = Reservation::with(['guest', 'room'])->whereIn('status', ['checked_in', 'checked_out'])->whereRaw('paid_amount < total_amount')->orderByRaw('(total_amount - paid_amount) DESC')->get();
        $totalDebt    = $reservations->sum(fn($r) => $r->total_amount - $r->paid_amount);
        $pdf = $this->pdfOptions(\Barryvdh\DomPDF\Facade\Pdf::loadView('reports.debts_pdf', compact('reservations', 'totalDebt')));
        $pdf->setPaper('a4', 'portrait');
        return $pdf->download('debts-' . now()->format('Y-m-d') . '.pdf');
    }

    public function debtsExcel(Request $request)
    {
        return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\DebtsReportExport(), 'debts-' . now()->format('Y-m-d') . '.xlsx');
    }

    public function salariesPdf(Request $request)
    {
        $year = (int) $request->input('year', now()->year);
        $salaries = \App\Models\Salary::with('employee')->where('year', $year)->orderBy('month', 'desc')->get();
        $byMonth  = $salaries->groupBy('month')->map(fn($g) => ['count' => $g->count(), 'total_net' => $g->sum('net_salary'), 'total_base' => $g->sum('base_salary'), 'total_bonus' => $g->sum('bonuses'), 'total_ded' => $g->sum('deductions'), 'paid' => $g->where('status', 'paid')->count(), 'pending' => $g->where('status', 'pending')->count()]);
        $totalNet = $salaries->sum('net_salary');
        $pdf = $this->pdfOptions(\Barryvdh\DomPDF\Facade\Pdf::loadView('reports.salaries_pdf', compact('salaries', 'byMonth', 'year', 'totalNet')));
        $pdf->setPaper('a4', 'portrait');
        return $pdf->download('salaries-' . $year . '.pdf');
    }

    public function salariesExcel(Request $request)
    {
        $year = (int) $request->input('year', now()->year);
        return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\SalariesReportExport($year), 'salaries-' . $year . '.xlsx');
    }

    public function shiftDeficits(Request $request)
    {
        $from = $request->input('from', now()->startOfMonth()->toDateString());
        $to   = $request->input('to', now()->toDateString());

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
            $shifts       = $user->shifts;
            $deficitShifts = $shifts->filter(fn($s) => $s->shortfall !== null && $s->shortfall < 0);
            $surplusShifts = $shifts->filter(fn($s) => $s->shortfall !== null && $s->shortfall > 0);

            return [
                'user'            => $user,
                'shift_count'     => $shifts->count(),
                'total_received'  => $shifts->sum('total_received_yer'),
                'total_withdrawn' => $shifts->sum('total_withdrawals_yer'),
                'total_deficit'   => $deficitShifts->sum(fn($s) => abs($s->shortfall)),
                'total_surplus'   => $surplusShifts->sum('shortfall'),
                'deficit_count'   => $deficitShifts->count(),
                'deducted_count'  => $deficitShifts->whereNotNull('salary_deducted_at')->count(),
                'shifts'          => $shifts,
            ];
        })->sortByDesc('total_deficit')->values();

        return view('reports.shift-deficits', compact('summary', 'from', 'to'));
    }
}
