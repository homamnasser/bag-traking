<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Database\Seeders\BagSeeder;
use Database\Seeders\CustomerSeeder;
use Database\Seeders\DriverAreaServiceSeeder;
use Illuminate\Database\Seeder;
use Database\Seeders\MealSeeder;
use Database\Seeders\MessageSeeder;
use Database\Seeders\OrderSeeder;
use Database\Seeders\ScanLogSeeder;
use Database\Seeders\UserSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
        PermissionSeeder::class,
            RoleSeeder::class,
            CreateSuperAdmin::class,
            UserSeeder::class, // يجب إنشاء المستخدمين أولًا
            DriverAreaServiceSeeder::class, // ثم مناطق السائقين (تعتمد على المستخدمين)
            MealSeeder::class, // ثم الوجبات
            BagSeeder::class, // ثم الحقائب (تعتمد على العملاء)
            CustomerSeeder::class, // ثم العملاء (يعتمدون على المستخدمين ومناطق الخدمة)
            OrderSeeder::class, // ثم الطلبات (تعتمد على المستخدمين والوجبات)
            MessageSeeder::class, // ثم الرسائل (تعتمد على المستخدمين)
            ScanLogSeeder::class, // ثم سجلات الفحص (تعتمد على المستخدمين والحقائب)
        ]);
    }
}
