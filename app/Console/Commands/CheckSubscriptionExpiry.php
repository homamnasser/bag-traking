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
        $this->info('Checking for expired subscriptions...'); // رسالة معلوماتية عند بدء التشغيل

        // جلب جميع العملاء الذين حالتهم (subscription_status) نشطة (1)
        // وتاريخ انتهاء اشتراكهم (subscription_expiry_date) هو اليوم أو قبله.
        $expiredCustomers = Customer::where('subscription_status', 1)
            ->where('subscription_expiry_date', '<=', Carbon::today())
            ->get();

        if ($expiredCustomers->isEmpty()) {
            $this->info('No expired subscriptions found today.');
            return Command::SUCCESS; // إنهاء الأمر بنجاح
        }

        $count = 0;
        foreach ($expiredCustomers as $customer) {
            $customer->subscription_status = 0; // تحديث الحالة إلى 0 (منتهية الصلاحية)
            $customer->save(); // حفظ التغييرات في قاعدة البيانات
            $count++;
        }

        $this->info("Updated {$count} expired subscriptions."); // رسالة معلوماتية بعد التحديث
        return Command::SUCCESS; // إنهاء الأمر بنجاح
    }
}
