<?php
namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomType;
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

        $revenueByType = Payment::join('reservations', 'payments.reservation_id', '=', 'reservations.id')
            ->join('rooms', 'reservations.room_id', '=', 'rooms.id')
            ->join('room_types', 'rooms.room_type_id', '=', 'room_types.id')
            ->whereDate('payments.payment_date', '>=', $from)
            ->whereDate('payments.payment_date', '<=', $to)
            ->where('payments.currency', 'YER')
            ->select('room_types.name', DB::raw('SUM(payments.amount) as total'))
            ->groupBy('room_types.name')
            ->get();

        $revenueByMethod = Payment::whereDate('payment_date', '>=', $from)
            ->whereDate('payment_date', '<=', $to)
            ->where('currency', 'YER')
            ->select('method', DB::raw('SUM(amount) as total'))
            ->groupBy('method')
            ->get();

        $totalRevenue = Payment::whereDate('payment_date', '>=', $from)
            ->whereDate('payment_date', '<=', $to)
            ->where('currency', 'YER')
            ->sum('amount');

        // Foreign currency payments (note only)
        $foreignPayments = Payment::whereDate('payment_date', '>=', $from)
            ->whereDate('payment_date', '<=', $to)
            ->whereIn('currency', ['SAR', 'USD'])
            ->select('currency', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy('currency')
            ->get();

        return view('reports.revenue', compact(
            'revenueByType', 'revenueByMethod', 'totalRevenue',
            'from', 'to', 'foreignPayments'
        ));
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
            $q->whereDate('check_in_date', '>=', $from)->whereDate('check_in_date', '<=', $to);
        })->whereDoesntHave('reservations', function ($q) use ($from) {
            $q->whereDate('check_in_date', '<', $from);
        })->count();

        $returningGuests = \App\Models\Guest::whereHas('reservations', function ($q) use ($from, $to) {
            $q->whereDate('check_in_date', '>=', $from)->whereDate('check_in_date', '<=', $to);
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

        $years    = \App\Models\Salary::selectRaw('DISTINCT year')->orderByDesc('year')->pluck('year');
        $totalNet = $salaries->sum('net_salary');

        return view('reports.salaries', compact('salaries', 'byMonth', 'year', 'years', 'totalNet'));
    }

    public function staffPerformance(Request $request)
    {
        $from = $request->input('from', now()->subDays(30)->toDateString());
        $to = $request->input('to', now()->toDateString());

        $staffData = User::with(['reservations' => function ($q) use ($from, $to) {
            $q->whereDate('check_in_date', '>=', $from)
              ->whereDate('check_in_date', '<=', $to);
        }])->where('is_active', true)->get()
        ->map(fn($u) => [
            'user' => $u,
            'checkins' => $u->reservations->count(),
            'revenue' => $u->payments()
                ->whereDate('payment_date', '>=', $from)
                ->whereDate('payment_date', '<=', $to)
                ->sum('amount'),
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
}
