<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::first();

        $admin = User::firstOrCreate(
            ['email' => 'admin@erp.test'],
            [
                'name'       => 'Super Admin',
                'password'   => Hash::make('password'),
                'company_id' => $company->id,
                'is_active'  => true,
            ]
        );
        $admin->syncRoles(['Super Admin']);

        $accountant = User::firstOrCreate(
            ['email' => 'accountant@erp.test'],
            [
                'name'       => 'Jane Accountant',
                'password'   => Hash::make('password'),
                'company_id' => $company->id,
                'is_active'  => true,
            ]
        );
        $accountant->syncRoles(['Accountant']);

        $hrManager = User::firstOrCreate(
            ['email' => 'hr@erp.test'],
            [
                'name'       => 'Bob HR Manager',
                'password'   => Hash::make('password'),
                'company_id' => $company->id,
                'is_active'  => true,
            ]
        );
        $hrManager->syncRoles(['HR Manager']);

        $this->command->info('Demo users seeded:');
        $this->command->line('  admin@erp.test     / password  (Super Admin)');
        $this->command->line('  accountant@erp.test / password  (Accountant)');
        $this->command->line('  hr@erp.test         / password  (HR Manager)');
    }
}
