<?php
namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Reservation;
use App\Models\Room;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $totalRooms = Room::count();
        $occupiedRooms = Room::where('status', 'occupied')->count();
        $availableRooms = Room::where('status', 'available')->count();
        $occupancyPercent = $totalRooms > 0 ? round(($occupiedRooms / $totalRooms) * 100) : 0;

        $todayArrivals = Reservation::whereDate('check_in_date', today())->count();
        $todayDepartures = Reservation::whereDate('check_out_date', today())
            ->whereIn('status', ['checked_in', 'checked_out'])
            ->count();

        // YER revenue (default currency)
        $todayRevenue   = Payment::whereDate('payment_date', today())->where('currency', 'YER')->sum('amount');
        $weeklyRevenue  = Payment::whereBetween('payment_date', [now()->startOfWeek(), now()->endOfWeek()])->where('currency', 'YER')->sum('amount');
        $monthlyRevenue = Payment::whereMonth('payment_date', now()->month)->whereYear('payment_date', now()->year)->where('currency', 'YER')->sum('amount');
        $yearlyRevenue  = Payment::whereYear('payment_date', now()->year)->where('currency', 'YER')->sum('amount');

        // SAR revenue
        $monthlyRevenueSar = Payment::whereMonth('payment_date', now()->month)->whereYear('payment_date', now()->year)->where('currency', 'SAR')->sum('amount');
        $yearlyRevenueSar  = Payment::whereYear('payment_date', now()->year)->where('currency', 'SAR')->sum('amount');

        // USD revenue
        $monthlyRevenueUsd = Payment::whereMonth('payment_date', now()->month)->whereYear('payment_date', now()->year)->where('currency', 'USD')->sum('amount');
        $yearlyRevenueUsd  = Payment::whereYear('payment_date', now()->year)->where('currency', 'USD')->sum('amount');

        // Overdue guests: checked_in but checkout date has passed
        $overdueGuests = Reservation::with(['guest', 'room'])
            ->where('status', 'checked_in')
            ->whereDate('check_out_date', '<', today())
            ->orderBy('check_out_date')
            ->get();

        $recentReservations = Reservation::with(['guest', 'room.roomType', 'createdBy'])
            ->latest()
            ->take(10)
            ->get();

        $roomStatusCounts = Room::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        return view('dashboard.index', compact(
            'totalRooms', 'occupiedRooms', 'availableRooms', 'occupancyPercent',
            'todayArrivals', 'todayDepartures', 'todayRevenue',
            'weeklyRevenue', 'monthlyRevenue', 'yearlyRevenue',
            'monthlyRevenueSar', 'yearlyRevenueSar',
            'monthlyRevenueUsd', 'yearlyRevenueUsd',
            'overdueGuests', 'recentReservations', 'roomStatusCounts'
        ));
    }
}
