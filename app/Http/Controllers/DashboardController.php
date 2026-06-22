<?php
namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\Expense;
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

        $alerts = [];

        $totalDebt = Reservation::whereRaw('paid_amount < total_amount')->sum(DB::raw('total_amount - paid_amount'));
        if ($totalDebt > 50000) {
            $alerts[] = ['type' => 'danger', 'title' => 'ديون عالية جداً', 'message' => 'إجمالي الديون المعلقة: ' . number_format($totalDebt, 0) . ' ر.ي', 'icon' => '⚠️'];
        }

        $deferredExpenses = Expense::where('payment_method', 'later')->whereNull('settled_at')->sum('amount');
        if ($deferredExpenses > 30000) {
            $alerts[] = ['type' => 'warning', 'title' => 'مصروفات مؤجلة عالية', 'message' => 'إجمالي المصروفات المؤجلة: ' . number_format($deferredExpenses, 0) . ' ر.ي', 'icon' => '💰'];
        }

        $todayExpenses = Expense::whereDate('expense_date', today())->sum('amount');
        if ($todayExpenses > $todayRevenue && $todayRevenue > 0) {
            $alerts[] = ['type' => 'danger', 'title' => 'المصروفات تتجاوز الإيرادات', 'message' => 'المصروفات: ' . number_format($todayExpenses, 0) . ' ر.ي والإيرادات: ' . number_format($todayRevenue, 0) . ' ر.ي', 'icon' => '📉'];
        }

        $overdueDays30 = Expense::where('payment_method', 'later')->whereNull('settled_at')->where('expense_date', '<', now()->subDays(30))->count();
        if ($overdueDays30 > 0) {
            $alerts[] = ['type' => 'danger', 'title' => 'مصروفات متأخرة 30+ يوم', 'message' => 'عدد المصروفات: ' . $overdueDays30 . ' مصروف', 'icon' => '🚨'];
        }

        return view('dashboard.index', compact(
            'totalRooms', 'occupiedRooms', 'availableRooms', 'maintenanceRooms',
            'todayArrivals', 'todayDepartures',
            'todayRevenue', 'monthlyRevenue',
            'expiringGuests', 'roomStatusCounts', 'alerts'
        ));
    }
}
