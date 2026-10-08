<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'dashboard.view',
            'vehicle.view',
            'vehicle.create',
            'vehicle.update',
            'vehicle.delete',
            'vehicle.publish',
            'image.manage',
            'enquiry.view',
            'enquiry.update',
            'submission.view',
            'submission.review',
            'user.view',
            'user.manage',
            'role.manage',
            'setting.manage',
            'page.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $grants = [
            'super_admin' => $permissions,
            'admin' => array_values(array_filter($permissions, fn (string $p) => $p !== 'role.manage')),
            'editor' => [
                'dashboard.view',
                'vehicle.view',
                'vehicle.create',
                'vehicle.update',
                'image.manage',
                'enquiry.view',
                'enquiry.update',
                'submission.view',
                'page.manage',
            ],
            'viewer' => [
                'dashboard.view',
                'vehicle.view',
                'enquiry.view',
                'submission.view',
                'user.view',
            ],
        ];

        foreach ($grants as $role => $granted) {
            $model = Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
            $model->syncPermissions($granted);
        }
    }
}
