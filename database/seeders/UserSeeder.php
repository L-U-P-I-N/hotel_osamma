<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'مدير النظام',
                'employee_id' => 'EMP001',
                'password' => Hash::make('Admin@1234'),
                'phone' => '+967 1 000001',
                'is_active' => true,
            ]
        );
        $admin->assignRole('admin');

        $receptionist = User::firstOrCreate(
            ['username' => 'receptionist'],
            [
                'name' => 'موظف الاستقبال',
                'employee_id' => 'EMP002',
                'password' => Hash::make('Admin@1234'),
                'phone' => '+967 1 000002',
                'is_active' => true,
            ]
        );
        $receptionist->assignRole('receptionist');

        $accountant = User::firstOrCreate(
            ['username' => 'accountant'],
            [
                'name' => 'المحاسب',
                'employee_id' => 'EMP003',
                'password' => Hash::make('Admin@1234'),
                'phone' => '+967 1 000003',
                'is_active' => true,
            ]
        );
        $accountant->assignRole('accountant');
    }
}
