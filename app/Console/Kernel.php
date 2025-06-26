<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * تحديد أوامر Artisan التي يوفرها تطبيقك.
     *
     * @var array
     */
    protected $commands = [
        // قد يكون لديك أوامر أخرى هنا، لا تقم بحذفها
        \App\Console\Commands\CheckSubscriptionExpiry::class, // أضف هذا السطر هنا
    ];

    /**
     * تحديد جدول الأوامر.
     */
    protected function schedule(Schedule $schedule): void
    {
        // جدولة الأمر ليتم تشغيله يومياً في منتصف الليل (أو أي وقت تختاره)
        $schedule->command('subscriptions:check-expiry')->everyMinute();

        // يمكنك استخدام ->dailyAt('02:00') لتشغيله في الساعة 2 صباحاً مثلاً
        // أو ->everyMinute() للاختبار (لا تستخدمه في بيئة الإنتاج!)
    }

    /**
     * تسجيل المسارات لأوامر Artisan.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
