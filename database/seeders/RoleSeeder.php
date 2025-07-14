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
            'updateUser',
            'deleteUser',
            'getUser',
            'getAllUsers',
            'addArea',
            'updateArea',
            'deleteArea',
            'getAllArea',
            'getArea',
            'addCustomer',
            'updateCustomer',
            'editStatus',
           // 'getAllCustomer',
            'getCustomer',
            'getCustomerByStatus',
            'getAllFoodPreferences',




        ]);
        $admin=Role::create(['name' => 'admin']);
        $admin->givePermissionTo([
            'createUser',
            'updateUser',
            'deleteUser',
            'getUser',
            'getAllUsers',
            'addArea',
            'updateArea',
            'deleteArea',
            'getAllArea',
            'getArea',
            'addCustomer',
            'updateCustomer',
            'editStatus',
            //'getAllCustomer',
            'getCustomer',
            'getCustomerByStatus',
            'getAllFoodPreferences',
        ]);

       $driver= Role::create(['name' => 'driver']);
       $driver->givePermissionTo([

       ]);
       $admin_cook= Role::create(['name' => 'admin_cook']);
       $admin_cook->givePermissionTo([
           'addMeal',
           'updateMeal',
           'deleteMeal',
           'getMeal',
           'getAllMeal',//search
           'updatePhoto'
       ]);

       $store_employee= Role::create(['name' => 'store_employee']);
       $store_employee->givePermissionTo([

       ]);

       $customer= Role::create(['name' => 'customer']);
        $customer->givePermissionTo([
            'addFoodPreferences',
            'updateFoodPreferences',
            'deleteFoodPreferences',
            'getCustomerFoodPreferences'

        ]);



    }
}
