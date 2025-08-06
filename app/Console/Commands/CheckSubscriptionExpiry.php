<?php

namespace App\Console\Commands;

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

        $expiredCustomerIds = Customer::where('subscription_status', 1)
            ->where('subscription_expiry_date', '<=', Carbon::today())
            ->pluck('id');

        if ($expiredCustomerIds->isEmpty()) {
            $this->info('No expired subscriptions found today.');
            return Command::SUCCESS;
        }

        Customer::whereIn('id', $expiredCustomerIds)
            ->update(['subscription_status' => 0]);

        $userIds = Customer::whereIn('id', $expiredCustomerIds)->pluck('user_id');
        \App\Models\User::whereIn('id', $userIds)
            ->update(['is_active' => 0]);

        $this->info("Updated " . count($expiredCustomerIds) . " expired subscriptions.");
        return Command::SUCCESS;
    }
}
