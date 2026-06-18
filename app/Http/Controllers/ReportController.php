<?php
namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomType;
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
        $from     = $request->input('from', now()->subDays(30)->toDateString());
        $to       = $request->input('to', now()->toDateString());
        $currency = in_array($request->input('currency'), ['YER', 'SAR', 'USD'])
                    ? $request->input('currency')
                    : 'YER';

        $currencySymbols = ['YER' => 'ر.ي', 'SAR' => 'ر.س', 'USD' => '$'];
        $currencySymbol  = $currencySymbols[$currency];

        $baseQuery = fn() => Payment::whereDate('payment_date', '>=', $from)
            ->whereDate('payment_date', '<=', $to)
            ->where('currency', $currency);

        $revenueByType = Payment::join('reservations', 'payments.reservation_id', '=', 'reservations.id')
            ->join('rooms', 'reservations.room_id', '=', 'rooms.id')
            ->join('room_types', 'rooms.room_type_id', '=', 'room_types.id')
            ->whereDate('payments.payment_date', '>=', $from)
            ->whereDate('payments.payment_date', '<=', $to)
            ->where('payments.currency', $currency)
            ->select('room_types.name', DB::raw('SUM(payments.amount) as total'))
            ->groupBy('room_types.name')
            ->get();

        $revenueByMethod = $baseQuery()
            ->select('method', DB::raw('SUM(amount) as total'))
            ->groupBy('method')
            ->get();

        $totalRevenue = $baseQuery()->sum('amount');

        // Summary totals per currency for the tab switcher
        $currencyTotals = Payment::whereDate('payment_date', '>=', $from)
            ->whereDate('payment_date', '<=', $to)
            ->select('currency', DB::raw('SUM(amount) as total'))
            ->groupBy('currency')
            ->pluck('total', 'currency')
            ->toArray();

        return view('reports.revenue', compact(
            'revenueByType', 'revenueByMethod', 'totalRevenue',
            'from', 'to', 'currency', 'currencySymbol', 'currencyTotals'
        ));
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
}
