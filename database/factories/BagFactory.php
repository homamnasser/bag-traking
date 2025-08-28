<?php

namespace Database\Factories;

use App\Models\Bag;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

class BagFactory extends Factory
{
    protected $model = Bag::class;

    public function definition(): array
    {
        $status = $this->faker->randomElement(['available', 'unavailable']);
        return [
            'bag_id' => $this->faker->unique()->bothify('BAG-####'),
            'status' => $this->faker->randomElement(['available', 'unavailable']),
            'customer_id' => $status === 'available' ? null : Customer::factory(),
            'qr_code_path' => 'storage/qr_codes/' . $this->faker->unique()->bothify('BAG-####.png'),
            'last_update_at' => 'atStore',
        ];
    }
}
