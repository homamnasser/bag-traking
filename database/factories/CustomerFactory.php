<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\DriverAreaService;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;   // ← أضف هذا السطر

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Customer>
 */
class CustomerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    protected $model = Customer::class;

    public function definition()
    {
        return [
            'user_id' => \App\Models\User::factory(), // كل عميل مربوط بمستخدم
            'area_id' => DriverAreaService::inRandomOrder()->first()->id, // مؤقتًا، أو تعمل Factory لـ driver_area_services
            'address' => $this->faker->address(),
            'subscription_status' => true,
            'subscription_start_date' => now(),
            'subscription_expiry_date' => now()->addMonth(),
        ];

    }
}
