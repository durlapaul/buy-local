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
            'view reservations',
            'create reservations',
            'update reservations',
            'delete reservations',
            'accept reservations',
            'reject reservations',
            
            'view courts',
            'create courts',
            'update courts',
            'delete courts',
            
            'view schedules',
            'update schedules',
        
            'manage space',
            'view space analytics',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $consumer = Role::firstOrCreate(['name' => 'consumer']);
        $consumer->syncPermissions([
            'view reservations',
            'create reservations',
        ]);

        $spaceWorker = Role::firstOrCreate(['name' => 'space_worker']);
        $spaceWorker->syncPermissions([
            'view reservations',
            'create reservations',
            'update reservations',
            'accept reservations',
            'reject reservations',
            'view courts',
            'view schedules',
        ]);

        $spaceAdmin = Role::firstOrCreate(['name' => 'space_admin']);
        $spaceAdmin->syncPermissions(Permission::all());
    }
}