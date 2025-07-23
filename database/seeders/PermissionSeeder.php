<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
//    public function run(): void
//    {
//        Permission::create(
//            [
//                'name'=>'createUser',
//                'name'=>'addArea'
//            ]);
//
//    }
    public function run(): void
    {

        $permissions = [
            'createUser',
            'updateUser',
            'deleteUser',
            'getUser',
            'getAllUsers',
            'addArea',
            'updateArea',
            'deleteArea',
            'getAllArea',
            'getArea',//
            'addCustomer',
            'updateCustomer',
            'editStatus',
            //'getAllCustomer',
            'getCustomer',//
            'getCustomerByStatus',
            'addFoodPreferences',
            'updateFoodPreferences',
            'deleteFoodPreferences',
            'getAllFoodPreferences',//اذا بدهم ياه بحث حسب اسم المستخدم
            'getCustomerFoodPreferences',
            'addMeal',
            'updateMeal',
            'deleteMeal',
            'getMeal',
            'getAllMeal',//search
            'updatePhoto',
            'forgetPassword',
            'addBag',
            'deleteBag',
            'getAllBags',
            'getBagsByStatus',
            'searchBagById',
            'sendMessage',
            'getMessage',
            'getMessageByType',
            'getAllMessages',
            'respondRequest'
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $this->command->info('Permissions seeded successfully!');
    }


}
