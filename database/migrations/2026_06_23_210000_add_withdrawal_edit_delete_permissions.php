<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = ['withdrawal.view', 'withdrawal.edit', 'withdrawal.delete', 'shifts.reopen'];
        foreach ($permissions as $name) {
            Permission::findOrCreate($name);
        }

        $admin = Role::where('name', 'admin')->where('guard_name', 'web')->first();
        if ($admin) {
            $admin->givePermissionTo($permissions);
        }

        // accountant can view and edit withdrawals too
        $accountant = Role::where('name', 'accountant')->where('guard_name', 'web')->first();
        if ($accountant) {
            $accountant->givePermissionTo(['withdrawal.view', 'withdrawal.edit', 'withdrawal.delete']);
        }
    }

    public function down(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (['withdrawal.view', 'withdrawal.edit', 'withdrawal.delete', 'shifts.reopen'] as $name) {
            Permission::where('name', $name)->delete();
        }
    }
};
