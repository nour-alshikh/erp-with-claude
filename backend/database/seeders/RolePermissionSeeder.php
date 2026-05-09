<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'view-hr', 'manage-employees', 'manage-payroll',
            'view-accounting', 'manage-journals', 'view-reports',
            'view-inventory', 'manage-products', 'manage-stock',
            'view-sales', 'manage-invoices', 'manage-customers',
            'view-purchasing', 'manage-vendors',
            'use-pos', 'manage-pos-sessions',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name'       => $permission,
                'guard_name' => 'sanctum',
            ]);
        }

        $roles = [
            'Super Admin' => $permissions,
            'HR Manager'  => ['view-hr', 'manage-employees', 'manage-payroll'],
            'Accountant'  => ['view-accounting', 'manage-journals', 'view-reports'],
            'Warehouse'   => ['view-inventory', 'manage-stock'],
            'Sales Rep'   => ['view-sales', 'manage-invoices', 'manage-customers', 'use-pos'],
            'Purchasing'  => ['view-purchasing', 'manage-vendors', 'view-inventory'],
            'Viewer'      => ['view-reports'],
        ];

        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::firstOrCreate([
                'name'       => $roleName,
                'guard_name' => 'sanctum',
            ]);
            $role->syncPermissions($rolePermissions);
        }
    }
}
