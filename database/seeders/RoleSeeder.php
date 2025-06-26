<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $super_admin=Role::create(['name' => 'super_admin']);
        $super_admin->givePermissionTo([
            'createUser',
            'addArea',
            'updateArea',
            'deleteArea',
            'getAllArea',
            'addUserDetails',
            'updateUserDetails',
            'updateSubscription',
            'getAllUsers'


        ]);
        $admin=Role::create(['name' => 'admin']);
        $admin->givePermissionTo([
            'createUser',
            'addArea',
            'updateArea',
            'deleteArea',
            'getAllArea',
            'addUserDetails',
            'updateUserDetails',
            'updateSubscription',
            'getAllUsers'


        ]);

       $driver= Role::create(['name' => 'driver']);
       $driver->givePermissionTo([

       ]);
       $admin_cook= Role::create(['name' => 'admin_cook']);
       $admin_cook->givePermissionTo([

       ]);

       $store_employee= Role::create(['name' => 'store_employee']);
       $store_employee->givePermissionTo([

       ]);

       $customer= Role::create(['name' => 'customer']);
        $customer->givePermissionTo([

        ]);



    }
}
