<?php
namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\Reservation;
use App\Models\Room;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $totalRooms       = Room::count();
        $occupiedRooms    = Room::where('status', 'occupied')->count();
        $availableRooms   = Room::where('status', 'available')->count();
        $maintenanceRooms = Room::whereIn('status', ['maintenance', 'under_inspection'])->count();

        $todayArrivals   = Reservation::whereDate('check_in_date', today())->count();
        $todayDepartures = Reservation::whereDate('check_out_date', today())
            ->whereIn('status', ['checked_in', 'checked_out'])->count();

        // Revenue
        $todayRevenue   = Payment::whereDate('payment_date', today())->where('currency','YER')->sum('amount');
        $monthlyRevenue = Payment::whereMonth('payment_date', now()->month)
            ->whereYear('payment_date', now()->year)->where('currency','YER')->sum('amount');

        // Expenses today
        $todayExpenses = Expense::whereDate('expense_date', today())->where('currency','YER')->sum('amount');

        // Net profit today
        $todayNetProfit = $todayRevenue - $todayExpenses;

        // Occupancy rate
        $occupancyRate = $totalRooms > 0 ? round(($occupiedRooms / $totalRooms) * 100) : 0;

        // ADR (Average Daily Rate) = today's revenue / occupied rooms
        $adr = $occupiedRooms > 0 ? round($todayRevenue / $occupiedRooms) : 0;

        // Outstanding debts (reservations with balance > 0)
        $debtReservations = Reservation::whereColumn('paid_amount', '<', 'total_amount')
            ->where('total_amount', '>', 0)
            ->whereIn('status', ['checked_in', 'checked_out'])
            ->count();
        $totalOutstandingDebt = Reservation::whereColumn('paid_amount', '<', 'total_amount')
            ->where('total_amount', '>', 0)
            ->whereIn('status', ['checked_in', 'checked_out'])
            ->selectRaw('SUM(total_amount - paid_amount) as debt')
            ->value('debt') ?? 0;

        // Upcoming arrivals (next 7 days)
        $upcomingArrivals = Reservation::with(['guest', 'room'])
            ->whereBetween('check_in_date', [today()->addDay(), today()->addDays(7)])
            ->whereIn('status', ['reserved', 'checked_in'])
            ->orderBy('check_in_date')
            ->get();

        // Last 7 days trend for chart
        $trendDays = collect();
        for ($i = 6; $i >= 0; $i--) {
            $date = today()->subDays($i);
            $rev  = Payment::whereDate('payment_date', $date)->where('currency','YER')->sum('amount');
            $exp  = Expense::whereDate('expense_date', $date)->where('currency','YER')->sum('amount');
            $trendDays->push([
                'label'   => $date->format('d/m'),
                'revenue' => (float) $rev,
                'expense' => (float) $exp,
                'net'     => (float) $rev - (float) $exp,
            ]);
        }

        $expiringGuests = Reservation::with(['guest', 'room'])
            ->where('status', 'checked_in')
            ->orderBy('check_out_date', 'asc')
            ->get();

        $roomStatusCounts = Room::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        return view('dashboard.index', compact(
            'totalRooms', 'occupiedRooms', 'availableRooms', 'maintenanceRooms',
            'todayArrivals', 'todayDepartures',
            'todayRevenue', 'monthlyRevenue',
            'todayExpenses', 'todayNetProfit',
            'occupancyRate', 'adr',
            'debtReservations', 'totalOutstandingDebt',
            'upcomingArrivals', 'trendDays',
            'expiringGuests', 'roomStatusCounts'
        ));
    }
}
