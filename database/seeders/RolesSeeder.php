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
            'rooms.manage',
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
            'hr.manage',
            // Expense Module
            'expenses.view',
            'expenses.create', 'expenses.edit', 'expenses.delete',
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
                'hr.view', 'hr.manage', 'expenses.view', 'expenses.create', 'expenses.edit', 'expenses.delete',
            ],
            'receptionist' => [
                'dashboard.view', 'rooms.view', 'rooms.manage', 'checkin.create',
                'checkin.view', 'checkout.process', 'guests.view', 'guests.sensitive',
                'companions.manage', 'payments.create', 'payments.bank_receipt',
                'extra_charges.manage', 'government.export',
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
