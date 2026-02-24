<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'index_users',
            'view_users',
            'create_users',
            'update_users',
            'delete_users'
        ];

        foreach($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        $superadminRole = Role::create(['name' => 'superadmin']);
        $superadminRole->givePermissionTo(Permission::all());

        
        
    }
}