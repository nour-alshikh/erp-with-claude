<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    public function run(): void
    {
        Company::firstOrCreate(
            ['name' => 'Demo Company'],
            [
                'currency' => 'USD',
                'tax_rate' => 15.00,
                'address'  => '123 Business Ave, Commerce City',
                'is_active' => true,
            ]
        );
    }
}
