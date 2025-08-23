<?php

namespace App\Console\Commands;

use App\Http\Controllers\PushNotificationController;
use App\Models\Message;
use Illuminate\Console\Command;
use App\Models\Customer; // تأكد من استيراد موديل Customer الخاص بك
use Carbon\Carbon;        // تأكد من استيراد Carbon للتعامل مع التواريخ

class CheckSubscriptionExpiry extends Command
{
    /**
     * اسم الأمر وتوقيعه (Signature).
     * هذا هو الاسم الذي ستستخدمه لجدولة الأمر.
     *
     * @var string
     */
    protected $signature = 'subscriptions:check-expiry';

    /**
     * وصف الأمر.
     *
     * @var string
     */
    protected $description = 'Checks for expired customer subscriptions and updates their status to 0.';

    /**
     * تنفيذ الأمر.
     */
    public function handle()
    {
        $this->info('Checking for expired subscriptions...');

        $expiredCustomers = Customer::where('subscription_status', 1)
            ->where('subscription_expiry_date', '<=', Carbon::today())
            ->get();

        if ($expiredCustomers->isEmpty()) {
            $this->info('No expired subscriptions found today.');
            return Command::SUCCESS;
        }
        $pushController = new PushNotificationController();
        foreach ($expiredCustomers as $customer) {

            $customer->update(['subscription_status' => 0]);

            $user = $customer->user;
            $user->update(['is_active' => 0]);

            $eventKey = "Subscription Expired_{$customer->id}_{$customer->subscription_expiry_date}";

            $alreadySent = Message::where('event_key', $eventKey)->exists();

            if ($alreadySent) {
                continue;
            }
                Message::create([
                    'sender_id' => null,
                    'receiver_id' => $user->id,
                    'type' => 'system_notification',
                    'data' => [
                        'message' => 'We apologize, your service has been suspended due to subscription expiration. Please contact the restaurant to renew your account and continue enjoying our service',
                        'expiry_date' => $customer->subscription_expiry_date,
                    ],
                    'status' => 'rejected',
                    'event_key' => $eventKey,
                ]);
            if ($customer->user->fcm_token) {
                $pushController->send(
                    [
                        'id'         => $customer->id,
                        'fcm_token'  => $customer->user->fcm_token,
                        'first_name' => $customer->user->first_name,
                        'last_name'  => $customer->user->last_name,
                    ],
                    'Your Subscription is Expiring',
                    'We apologize, your service has been suspended due to subscription expiration. Please contact the restaurant to renew your account and continue enjoying our service'
                );
            }

        }
        $this->sendSubscriptionReminders();
        $this->info("Updated " . count($expiredCustomers) . " expired subscriptions and sent notifications.");
        return Command::SUCCESS;
    }



       public function sendSubscriptionReminders()
       {
        $today = now()->startOfDay();

        $customers = Customer::whereIn('subscription_expiry_date', [
            $today->copy()->addDays(3)->toDateString(),
            $today->copy()->addDays(2)->toDateString(),
            $today->copy()->addDay()->toDateString(),
        ])->get();

        $pushController = new PushNotificationController();

        foreach ($customers as $customer) {
            $daysLeft = $today->diffInDays(Carbon::parse($customer->subscription_expiry_date), false);

            $eventKey = "subscription_reminder_{$customer->id}_{$daysLeft}";

            $alreadySent = Message::where('event_key', $eventKey)->exists();

            if ($alreadySent) {
                continue;
            }

            Message::create([
                'sender_id' => null,
                'receiver_id' => $customer->user_id,
                'type' => 'system_notification',
                'data' => [
                    'message' => "Your subscription will expire in {$daysLeft} day" .($daysLeft > 1 ? 's' : '') .".Please visit the restaurant to renew and continue enjoying our services ❤",
                    'expiry_date' => $customer->subscription_expiry_date,
                ],
                'status' => 'approved',
                'event_key' => $eventKey,
            ]);
            if ($customer->user->fcm_token) {
                $pushController->send(
                    [
                        'id'         => $customer->id,
                        'fcm_token'  => $customer->user->fcm_token,
                        'first_name' => $customer->user->first_name,
                        'last_name'  => $customer->user->last_name,
                    ],
                    'Reminder: Your Subscription is Expiring',
                    "Your subscription will expire in {$daysLeft} day" .($daysLeft > 1 ? 's' : '') .".Please visit the restaurant to renew and continue enjoying our services ❤"
                );
            }
          }
        }



}
