<?php

namespace Database\Factories;

use App\Models\Meal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Meal>
 */
class MealFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    protected $model = Meal::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        // توليد قائمة من 1 إلى 3 صور وهمية لتلبية شرط 'imgs.*'
        $images = $this->faker->numberBetween(1, 3);
        $imageUrls = [];
        for ($i = 0; $i < $images; $i++) {
            $imageUrls[] = $this->faker->imageUrl(640, 480, 'food', true);
        }

        return [
            'name' => $this->faker->unique()->word(),
            'description' => $this->faker->sentence(),
            'ingredients' => json_encode($this->faker->words(3)), // تخزن كمصفوفة JSON
            'meal_type' => $this->faker->randomElement(['breakfast', 'lunch', 'dinner']),
            'is_active' => true,
            'imgs' => json_encode($imageUrls), // تخزن كمصفوفة JSON
        ];
    }
}
