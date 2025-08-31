<?php


namespace Database\Factories;

use App\Models\User;  // ← هذا مهم جدًا
use App\Models\Meal;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;
/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $userId = User::factory()->withRole('customer')->create()->id;
        $meal1Id = Meal::factory()->create()->id;
        $meal2Id = $this->faker->boolean() ? Meal::factory()->create()->id : null;

        return [
            'user_id' => $userId,
            'meal1_id' => $meal1Id,
            'meal2_id' => $meal2Id,
            'order_date' => Carbon::today(),
        ];
    }
}
