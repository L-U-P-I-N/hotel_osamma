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
            'dashboard.view',
            'rooms.view',
            'rooms.create', 'rooms.edit', 'rooms.delete',
            'rooms.maintenance',
            'checkin.create',
            'checkin.view',
            'checkout.process',
            'guests.view',
            'guests.sensitive',
            'companions.manage',
            'payments.create',
            'payments.view',
            'payments.bank_receipt',
            'extra_charges.manage',
            'settlement.view',
            'settlement.manage',
            'settlement.lock',
            'reports.view',
            'users.manage',
            'audit_log.view',
            'government.export',
            'deferred.approve',
            // HR Module
            'hr.view',
            'hr.create', 'hr.edit', 'hr.delete',
            // Expense Module
            'expenses.view',
            'expenses.create', 'expenses.edit', 'expenses.delete',
            // Withdrawal
            'withdrawal.create', 'withdrawal.view', 'withdrawal.edit', 'withdrawal.delete',
            // Shifts
            'shifts.reopen',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        $roles = [
            'admin' => $permissions,
            'accountant' => [
                'dashboard.view', 'rooms.view', 'payments.create', 'payments.view',
                'payments.bank_receipt', 'settlement.view', 'settlement.manage',
                'settlement.lock', 'reports.view',
                'hr.view', 'hr.create', 'hr.edit', 'hr.delete', 'expenses.view', 'expenses.create', 'expenses.edit', 'expenses.delete',
                'withdrawal.create', 'withdrawal.view', 'withdrawal.edit', 'withdrawal.delete',
            ],
            'receptionist' => [
                'dashboard.view', 'rooms.view', 'checkin.create',
                'checkin.view', 'checkout.process', 'guests.view', 'guests.sensitive',
                'companions.manage', 'payments.create', 'payments.bank_receipt',
                'extra_charges.manage', 'government.export',
                'withdrawal.create', 'withdrawal.view',
            ],
            'maintenance' => [
                'dashboard.view', 'rooms.view', 'rooms.maintenance',
            ],
            'auditor' => [
                'dashboard.view', 'rooms.view', 'checkin.view', 'guests.view',
                'payments.view', 'settlement.view', 'reports.view', 'audit_log.view',
            ],
        ];

        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::findOrCreate($roleName);
            $role->syncPermissions($rolePermissions);
        }
    }
}
