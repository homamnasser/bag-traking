<?php

namespace App\Console\Commands;

use App\Http\Controllers\PushNotificationController;
use App\Models\Customer;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SendDailyOrderReminder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscriptions:reminder-select-order';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send daily reminder to customers to select their daily order';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $today = Carbon::today();

        $customers = Customer::where('subscription_status', 1)
            ->whereHas('user', function ($query) {
                $query->where('is_active', 1);
            })
            ->get();

        if ($customers->isEmpty()) {
            $this->info('No active customers found today.');
            return Command::SUCCESS;
        }

        foreach ($customers as $customer) {

            $eventKey = "subscription_reminder_select_order_{$customer->id}_{$today->toDateString()}";

            if (Cache::has($eventKey)) {
                continue;
            }
            $pushController = new PushNotificationController();
            if ($customer->user->fcm_token) {
                $pushController->send(
                    [
                        'id'         => $customer->id,
                        'fcm_token'  => $customer->user->fcm_token,
                        'first_name' => $customer->user->first_name,
                        'last_name'  => $customer->user->last_name,
                    ],
                    'Select Order Reminder',
                    "Dear {$customer->user->first_name},Stay healthy and energized 😃! Pick your meal for today and make it a great day 🌮."
               ,'basic'
                    ,'add_order' );
            }
            Cache::put($eventKey, true, now()->endOfDay());
        }

        $this->info("Sent select order reminders to " . count($customers) . " customers.");
        return Command::SUCCESS;
    }
}
