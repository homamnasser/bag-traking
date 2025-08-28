<?php

namespace Database\Factories;

use App\Models\Bag;
use App\Models\Scan_Log;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class Scan_LogFactory extends Factory
{
    public function definition()
    {
        $user = User::factory()->withRole(
            $this->faker->randomElement(['driver', 'store_employee'])
        )->create();
        $bag = Bag::inRandomOrder()->first(); // أو Bag::factory()->create();

        return [
            'user_id' => $user->id,
            'bag_id' => $bag->id,
            'date' => now()->toDateString(),
            'time' => now()->toTimeString(),
            'status' => $this->faker->randomElement(['atCustomer', 'atWay', 'atStore']),
        ];
    }
}
