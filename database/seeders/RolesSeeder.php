<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            // Reservations
            'checkin.create', 'checkin.view', 'checkout.process',
            // حذف نهائي للحجز وبياناته (للمدير فقط)
            'reservations.delete',
            // Guests
            'guests.view', 'guests.sensitive', 'companions.manage',
            // Rooms
            'rooms.view', 'rooms.create', 'rooms.edit', 'rooms.delete',
            'rooms.maintenance', 'room.price.edit',
            // Payments & Withdrawals
            'payments.view', 'payments.create', 'payments.bank_receipt',
            'extra_charges.manage',
            'withdrawal.create', 'withdrawal.view', 'withdrawal.edit', 'withdrawal.delete',
            // Shifts & Settlement
            'shifts.view', 'shifts.reopen',
            'settlement.view', 'settlement.manage', 'settlement.lock',
            // Reports
            'reports.view', 'government.export',
            // HR
            'hr.view', 'hr.create', 'hr.edit', 'hr.delete',
            // Expenses
            'expenses.view', 'expenses.create', 'expenses.edit', 'expenses.delete',
            // Attendance
            'attendance.view', 'attendance.create',
            // Admin-only
            'users.manage', 'audit_log.view',
            // Misc
            'dashboard.view', 'deferred.approve',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        $roles = [
            'admin' => $permissions,
            'accountant' => [
                'dashboard.view', 'rooms.view',
                'payments.view', 'payments.create', 'payments.bank_receipt',
                'settlement.view', 'settlement.manage', 'settlement.lock',
                'reports.view',
                'hr.view', 'hr.create', 'hr.edit', 'hr.delete',
                'expenses.view', 'expenses.create', 'expenses.edit', 'expenses.delete',
                'withdrawal.create', 'withdrawal.view', 'withdrawal.edit', 'withdrawal.delete',
            ],
            'receptionist' => [
                'dashboard.view', 'rooms.view',
                'checkin.create', 'checkin.view', 'checkout.process',
                'guests.view', 'guests.sensitive', 'companions.manage',
                'payments.create', 'payments.bank_receipt', 'extra_charges.manage',
                'government.export',
                'withdrawal.create', 'withdrawal.view',
                'shifts.view',
            ],
            'maintenance' => [
                'dashboard.view', 'rooms.view', 'rooms.maintenance',
            ],
            'auditor' => [
                'dashboard.view', 'rooms.view', 'checkin.view',
                'guests.view', 'payments.view',
                'settlement.view', 'reports.view', 'audit_log.view',
            ],
        ];

        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::findOrCreate($roleName);
            $role->syncPermissions($rolePermissions);
        }
    }
}
