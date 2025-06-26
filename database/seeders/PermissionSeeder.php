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
            'getAllUsers',
            'addArea',
            'updateArea',
            'deleteArea',
            'getAllArea',
            'addUserDetails',
            'updateUserDetails',
            'updateSubscription'
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $this->command->info('Permissions seeded successfully!');
    }


}
