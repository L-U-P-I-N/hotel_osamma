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
        $totalRooms      = Room::count();
        $occupiedRooms   = Room::where('status', 'occupied')->count();
        $availableRooms  = Room::where('status', 'available')->count();
        $maintenanceRooms = Room::whereIn('status', ['maintenance', 'under_inspection'])->count();

        $todayArrivals   = Reservation::whereDate('check_in_date', today())->count();
        $todayDepartures = Reservation::whereDate('check_out_date', today())
            ->whereIn('status', ['checked_in', 'checked_out'])
            ->count();

        $todayRevenue   = Payment::whereDate('payment_date', today())->sum('amount');
        $monthlyRevenue = Payment::whereMonth('payment_date', now()->month)
            ->whereYear('payment_date', now()->year)->sum('amount');

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
            'expiringGuests', 'roomStatusCounts'
        ));
    }
}
