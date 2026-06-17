<?php
namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\PermissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with('roles')->paginate(25);
        $roles = Role::all();
        return view('users.index', compact('users', 'roles'));
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

        if (in_array($request->permission, PermissionService::ADMIN_ONLY)) {
            return back()->withErrors(['error' => 'هذه الصلاحية للمدير فقط']);
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
            'name' => 'required|string|max:255',
            'employee_id' => 'required|string|unique:users',
            'username' => 'required|string|unique:users|alpha_dash',
            'password' => 'required|string|min:8',
            'phone' => 'nullable|string',
            'role' => 'required|exists:roles,name',
        ]);

        $plainCode = $this->generateBackupCode();

        $user = User::create([
            'name' => $request->name,
            'employee_id' => $request->employee_id,
            'username' => $request->username,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'is_active' => true,
            'backup_code' => Hash::make($plainCode),
        ]);

        $user->assignRole($request->role);
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
            'role' => 'required|exists:roles,name',
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
        $user->syncRoles([$request->role]);

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
