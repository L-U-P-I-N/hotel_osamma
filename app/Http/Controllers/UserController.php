<?php
namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\CashWithdrawal;
use App\Models\Shift;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\PermissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with('roles');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('username', 'like', "%{$s}%")
                  ->orWhere('employee_id', 'like', "%{$s}%");
            });
        }

        if ($request->filled('role')) {
            $query->whereHas('roles', fn($q) => $q->where('name', $request->role));
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $users      = $query->paginate(20)->withQueryString();
        $roles      = Role::all();
        $totalCount  = User::count();
        $activeCount = User::where('is_active', true)->count();

        return view('users.index', compact('users', 'roles', 'totalCount', 'activeCount'));
    }

    public function permissions(User $user)
    {
        $permissionMap = PermissionService::getMap($user);
        return view('users.permissions', compact('user', 'permissionMap'));
    }

    public function togglePermission(Request $request, User $user)
    {
        $request->validate([
            'permission' => 'required|string',
            'grant'      => 'required|boolean',
        ]);

        if ($user->isAdmin()) {
            return back()->withErrors(['error' => 'لا يمكن تعديل صلاحيات المدير']);
        }

        PermissionService::toggle($user, $request->permission, (bool)$request->grant, auth()->user());
        AuditLogService::log('update', $user, [], [
            'permission' => $request->permission,
            'granted'    => $request->grant,
        ], auth()->user());

        $label = PermissionService::ALL_PERMISSIONS[$request->permission]['label'] ?? $request->permission;
        $action = $request->grant ? 'منح' : 'سحب';

        return back()->with('success', "تم {$action} صلاحية \"{$label}\" بنجاح");
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'username' => 'required|string|unique:users|alpha_dash',
            'password' => 'required|string|min:8',
            'phone'    => 'nullable|string',
            'role'     => 'nullable|exists:roles,name',
        ]);

        $lastNum    = User::where('employee_id', 'like', 'EMP%')->get()
            ->map(fn($u) => (int) preg_replace('/\D/', '', $u->employee_id))
            ->max() ?? 0;
        $employeeId = 'EMP' . str_pad($lastNum + 1, 3, '0', STR_PAD_LEFT);

        $plainCode = $this->generateBackupCode();

        $user = User::create([
            'name'        => $request->name,
            'employee_id' => $employeeId,
            'username' => $request->username,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'is_active' => true,
            'backup_code' => Hash::make($plainCode),
        ]);

        $user->assignRole($request->role ?? 'receptionist');
        AuditLogService::log('create', $user, null, $user->toArray());

        return back()
            ->with('success', 'تم إنشاء المستخدم بنجاح')
            ->with('new_backup_code', $plainCode)
            ->with('new_backup_code_user', $user->name);
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string',
            'role' => 'nullable|exists:roles,name',
            'password' => 'nullable|string|min:8',
        ]);

        $old = $user->toArray();
        $updateData = [
            'name' => $request->name,
            'phone' => $request->phone,
        ];

        if ($request->filled('password')) {
            $updateData['password'] = Hash::make($request->password);
        }

        $user->update($updateData);
        $user->syncRoles([$request->role ?? $user->roles->first()?->name ?? 'receptionist']);

        AuditLogService::log('update', $user, $old, $user->fresh()->toArray());

        return back()->with('success', 'تم تحديث بيانات المستخدم بنجاح');
    }

    public function toggleActive(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->withErrors(['error' => 'لا يمكنك تعطيل حسابك الخاص']);
        }
        $user->update(['is_active' => !$user->is_active]);
        return back()->with('success', 'تم تحديث حالة الحساب');
    }

    public function regenerateBackupCode(User $user)
    {
        $plainCode = $this->generateBackupCode();
        $user->update(['backup_code' => Hash::make($plainCode)]);

        AuditLogService::log('update', $user, [], ['backup_code_regenerated' => true], auth()->user());

        return back()
            ->with('success', 'تم تجديد رمز الاسترداد بنجاح')
            ->with('new_backup_code', $plainCode)
            ->with('new_backup_code_user', $user->name);
    }

    /**
     * كشف حساب الموظف/المستخدم: كل ورديّاته ومستلماته وسحبياته خلال فترة
     * محدَّدة (افتراضياً آخر 90 يوماً)، مع إجمالي ما استلم وما سحب وأي عجز.
     */
    public function statement(User $user, Request $request)
    {
        $from = $request->input('from', now()->subDays(90)->toDateString());
        $to   = $request->input('to', now()->toDateString());

        [$shifts, $payments, $withdrawals, $totals] = $this->buildStatementData($user, $from, $to);

        return view('users.statement', compact('user', 'shifts', 'payments', 'withdrawals', 'totals', 'from', 'to'));
    }

    public function statementPdf(User $user, Request $request)
    {
        $from = $request->input('from', now()->subDays(90)->toDateString());
        $to   = $request->input('to', now()->toDateString());

        [$shifts, $payments, $withdrawals, $totals] = $this->buildStatementData($user, $from, $to);

        $pdf = pdf_load_view('users.statement_pdf', compact('user', 'shifts', 'payments', 'withdrawals', 'totals', 'from', 'to'));
        $pdf->setPaper('a4', 'portrait');

        $dompdf = $pdf->getDomPDF();
        $opts   = $dompdf->getOptions();
        $opts->setFontDir(storage_path('fonts'));
        $opts->setFontCache(storage_path('fonts'));
        $dompdf->setOptions($opts);

        return $pdf->stream('statement-user-' . $user->id . '.pdf');
    }

    private function buildStatementData(User $user, string $from, string $to): array
    {
        $shifts = Shift::where('user_id', $user->id)
            ->whereDate('shift_date', '>=', $from)
            ->whereDate('shift_date', '<=', $to)
            ->orderByDesc('shift_date')
            ->get();

        $payments = $user->payments()
            ->with(['reservation.guest', 'reservation.room'])
            ->whereDate('payment_date', '>=', $from)
            ->whereDate('payment_date', '<=', $to)
            ->orderByDesc('payment_date')
            ->get();

        $withdrawals = CashWithdrawal::whereHas('shift', fn($q) => $q->where('user_id', $user->id))
            ->whereDate('withdrawal_date', '>=', $from)
            ->whereDate('withdrawal_date', '<=', $to)
            ->orderByDesc('withdrawal_date')
            ->get();

        $totals = [
            'shifts_count'    => $shifts->count(),
            'received'        => $payments->sum('amount'),
            'withdrawals'     => $withdrawals->sum('amount'),
            'total_shortfall' => $shifts->sum(fn($s) => $s->shortfall !== null && $s->shortfall < 0 ? abs((float) $s->shortfall) : 0),
        ];

        return [$shifts, $payments, $withdrawals, $totals];
    }

    public function auditLog(Request $request)
    {
        $query = AuditLog::with('user')->latest();
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }
        $logs = $query->paginate(25)->withQueryString();
        $users = User::select('id', 'name')->get();
        return view('users.audit-log', compact('logs', 'users'));
    }

    private function generateBackupCode(): string
    {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $segments = [];
        for ($i = 0; $i < 3; $i++) {
            $segment = '';
            for ($j = 0; $j < 4; $j++) {
                $segment .= $chars[random_int(0, strlen($chars) - 1)];
            }
            $segments[] = $segment;
        }
        return implode('-', $segments);
    }
}
