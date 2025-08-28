<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Message;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Message>
 */
class MessageFactory extends Factory
{
    protected $model = Message::class;

    public function definition()
    {
        $messageType = $this->faker->randomElement(['account_creation', 'account_update', 'issue', 'system_notification']);

        $sender = User::factory()->create();
        $receiverId = 1; // المدير الأساسي

        $data = [
            'message' => $this->faker->sentence(),
            'first_name' => $sender->first_name,
            'last_name' => $sender->last_name,
            'phone' => $sender->phone,
        ];

        if ($messageType === 'account_creation' || $messageType === 'account_update') {
            $data['password'] = $this->faker->password(6);
            $data['role'] = $sender->role ?? null; // استخدم null لو مافيش role
        }

        return [
            'type' => $messageType,
            'sender_id' => $sender->id,
            'receiver_id' => $receiverId,
            'event_key' => $this->faker->word(),
            'status' => $this->faker->randomElement(['pending', 'approved', 'rejected']),
            'data' => json_encode($data),
        ];
    }
}
