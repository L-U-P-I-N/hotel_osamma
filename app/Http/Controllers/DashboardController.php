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
        $ytdRevenue     = Payment::whereYear('payment_date', now()->year)->where('currency','YER')->sum('amount');
        $lastMonthRevenue = Payment::whereMonth('payment_date', now()->subMonth()->month)
            ->whereYear('payment_date', now()->subMonth()->year)->where('currency','YER')->sum('amount');

        // Expenses today
        $todayExpenses  = Expense::whereDate('expense_date', today())->where('currency','YER')->sum('amount');
        $monthlyExpenses = Expense::whereMonth('expense_date', now()->month)
            ->whereYear('expense_date', now()->year)->where('currency','YER')->sum('amount');
        $ytdExpenses    = Expense::whereYear('expense_date', now()->year)->where('currency','YER')->sum('amount');
        $lastMonthExpenses = Expense::whereMonth('expense_date', now()->subMonth()->month)
            ->whereYear('expense_date', now()->subMonth()->year)->where('currency','YER')->sum('amount');

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

        // 🆕 Weekly Revenue Chart (last 7 days)
        $weeklyRevenue = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $revenue = Payment::whereDate('payment_date', $date)->sum('amount');
            $weeklyRevenue[] = [
                'date' => now()->subDays($i)->format('d/m'),
                'revenue' => (int)$revenue
            ];
        }

        // 🆕 Occupancy Rate Today
        $occupancyRateToday = $totalRooms > 0 ? round(($occupiedRooms / $totalRooms) * 100) : 0;

        // 🆕 Today's Expenses
        $todayExpenses = Expense::whereDate('expense_date', today())->sum('amount');

        $expiringGuests = Reservation::with(['guest', 'room'])
            ->where('status', 'checked_in')
            ->orderBy('check_out_date', 'asc')
            ->get();

        $overdueGuests = $expiringGuests->filter(
            fn($r) => $r->check_out_date->copy()->startOfDay()->lte(now()->startOfDay())
        )->values();

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

        if ($todayExpenses > $todayRevenue && $todayRevenue > 0) {
            $alerts[] = ['type' => 'danger', 'title' => 'المصروفات تتجاوز الإيرادات', 'message' => 'المصروفات: ' . number_format($todayExpenses, 0) . ' ر.ي والإيرادات: ' . number_format($todayRevenue, 0) . ' ر.ي', 'icon' => '📉'];
        }

        $overdueDays30 = Expense::where('payment_method', 'later')->whereNull('settled_at')->where('expense_date', '<', now()->subDays(30))->count();
        if ($overdueDays30 > 0) {
            $alerts[] = ['type' => 'danger', 'title' => 'مصروفات متأخرة 30+ يوم', 'message' => 'عدد المصروفات: ' . $overdueDays30 . ' مصروف', 'icon' => '🚨'];
        }

        // حجوزات جُدِّدت تلقائياً (تجاوز موعد المغادرة دون تسجيل خروج) لم يطّلع عليها بعد
        // الموظف الذي أنشأ الحجز تحديداً
        $autoRenewedPending = Reservation::with(['guest', 'room'])
            ->where('created_by', auth()->id())
            ->where('auto_renew_acknowledged', false)
            ->whereNotNull('auto_renewed_at')
            ->orderByDesc('auto_renewed_at')
            ->get();

        return view('dashboard.index', compact(
            'totalRooms', 'occupiedRooms', 'availableRooms', 'maintenanceRooms',
            'todayArrivals', 'todayDepartures',
            'todayRevenue', 'monthlyRevenue', 'ytdRevenue', 'lastMonthRevenue',
            'todayExpenses', 'monthlyExpenses', 'ytdExpenses', 'lastMonthExpenses', 'todayNetProfit',
            'occupancyRate', 'adr',
            'debtReservations', 'totalOutstandingDebt',
            'upcomingArrivals', 'trendDays',
            'expiringGuests', 'overdueGuests', 'roomStatusCounts', 'alerts',
            'weeklyRevenue', 'occupancyRateToday', 'autoRenewedPending'
        ));
    }
}
