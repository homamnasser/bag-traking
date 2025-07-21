<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

// استيراد موديل الدور

class CreateSuperAdmin extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. ابحث عن دور 'super_admin' أو أنشئه إذا لم يكن موجودًا
        // من الأفضل استدعاء RoleSeeder أولاً في DatabaseSeeder لضمان وجوده
        $superAdminRole = Role::firstOrCreate(['name' => 'super_admin']);

        // 2. تحقق مما إذا كان المستخدم السوبر أدمن موجودًا بالفعل
        $user = User::firstOrCreate(
            [
                'phone' => '+963938316303' // استخدم رقم هاتف فريد لهذا المستخدم
            ],
            [
                'first_name' => 'Super',
                'last_name' => 'Admin',
                'phone' => '+963938316303', // استخدم رقم هاتف فريد لهذا المستخدم
                'password' => Hash::make('123456789'), // كلمة مرور قوية لأغراض الإنتاج!
                'phone_verified_at' => now(), // يمكنك تعيينه كـ verified مباشرةً
                'is_active'=>true
            ]
        );
        $user->assignRole('super_admin');

        // يمكنك طباعة رسالة تأكيد (اختياري)
        if ($user->wasRecentlyCreated) {
            $this->command->info('Super Admin user created successfully!');
        } else {
            $this->command->info('Super Admin user already exists!');
        }
    }
}
