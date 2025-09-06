<?php

namespace App\Http\Controllers;

use App\Models\Bag;
use App\Models\Customer;
use App\Models\Message;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class ScheduleController extends Controller
{
    public function executionSchedule($customer_id ){

        $customersubscription= Customer::find($customer_id);

        if (!$customersubscription) {
            return response()->json([
                'code'=>404,
                'message' => 'Customer not found',
                'data'=>[]
            ],404);
        }
        $startSubscription = $customersubscription->subscription_start_date->copy();
        $subscriptionExpiry=$startSubscription->addDays(2)->toDateString();
        $customersubscription->update([
            'subscription_expiry_date' => $subscriptionExpiry
        ]);


        $missingBagsCount = $this->checkMissingBags();
        $expiredCount     = $this->checkSubscriptionExpire();
        $remindersCount   = $this->sendSubscriptionReminders();
        $dailyCount       = $this->sendDailyOrderReminder();

        return response()->json([
            'code' => 200,
            'message' => 'Schedule executed successfully',
            'data' => [
                'missing_bags_notifications' => $missingBagsCount,
                'expired_subscriptions'      => $expiredCount,
                'reminders_sent'             => $remindersCount,
                'daily_order_reminders'      => $dailyCount,
            ]
        ], 200);


    }
      static public function checkMissingBags(){

       $pushController = new PushNotificationController();
       $admins = User::role('admin')->get();
          $count = 0;

       $lostBags = Bag::where('last_update_at', 'atWay')->get();

       foreach ($lostBags as $bag) {
           $eventKey = "lost_bag_atWay_{$bag->id}";

           if (Message::where('event_key', $eventKey)->exists()) {
               continue;
           }
           $messageText = "⚠️ The system has detected an unusual condition."
               . "Bag #{$bag->bag_id} seems to have been stuck in transit since yesterday. "
               . "This could indicate a potential loss.investigate promptly.";

           Message::create([
               'sender_id' => null,
               'receiver_id' => 1,
               'type' => 'system_notification',
               'data' => ['message' => $messageText
                   ,'date'=>Carbon::now()->toDateTimeString()],
               'status' => 'approved',
               'event_key' => $eventKey,
           ]);
           $count++;
           foreach ($admins as $admin) {
               if ($admin->fcm_token) {
                   $pushController->send(
                       [
                           'id' => $admin->id,
                           'fcm_token' => $admin->fcm_token,
                           'first_name' => $admin->first_name,
                           'last_name' => $admin->last_name,
                       ],
                       '⚠️ Possible Bag Loss',
                       "Bag #{$bag->bag_id} appears stuck in transit. Please check."
                   );
               }
           }
       }
///////////////////////lost in customer

       $customers = Customer::with('bags','user')->get();

       foreach ($customers as $customer) {
           $bags = $customer->bags;
           $isActive = $customer->user->is_active;
           $atCustomerBags = $bags->where('last_update_at', 'atCustomer');
           $atStoreBags = $bags->where('last_update_at', 'atStore')
               ->where('status', 'unavailable');
           if ($atCustomerBags->count() === 2) {

               $eventKey = "lost_bag_customer_dual_atCustomer_{$customer->id}";

               if (!Message::where('event_key', $eventKey)->exists()) {
                   $bagIds = $atCustomerBags->pluck('bag_id')->implode(', ');
                   $messageText = "⚠️ The system detected an unusual condition."
                       . "Customer {$customer->user->first_name} {$customer->user->last_name} currently has both bags "
                       . "(IDs: {$bagIds}) marked as 'atCustomer'."
                       . "This likely means the old bag was not picked up by the driver and could be missing. "
                       . "Immediate follow-up is recommended.";

                   Message::create([
                       'sender_id' => null,
                       'receiver_id' => 1,
                       'type' => 'system_notification',
                       'data' => ['message' => $messageText,
                           'date' => Carbon::now()->toDateTimeString()],
                       'status' => 'approved',
                       'event_key' => $eventKey,
                   ]);
                   $count++;
                   foreach ($admins as $admin) {
                       if ($admin->fcm_token) {
                           $pushController->send(
                               [
                                   'id' => $admin->id,
                                   'fcm_token' => $admin->fcm_token,
                                   'first_name' => $admin->first_name,
                                   'last_name' => $admin->last_name,
                               ],
                               '⚠️ Possible Bag Loss',
                               "Customer {$customer->user->first_name} {$customer->user->last_name} seems to have both bags. The old bag This likely means was not picked up by the driver ."
                           );
                       }
                   }
               }
           }

/////////////////////////////lost at store
           if ($atStoreBags->count() === 2 && $isActive) {
               $eventKey = "lost_bag_customer_dual_atStore_{$customer->id}";
               if (!Message::where('event_key', $eventKey)->exists()) {
                   $bagIds = $atStoreBags->pluck('bag_id')->implode(', ');
                   $messageText = "⚠️ The system detected an unusual condition."
                       . "Both bags for active customer {$customer->user->first_name} {$customer->user->last_name} "
                       . "(IDs: {$bagIds}) are marked as 'atStore'."
                       . "This may indicate the customer did not receive a bag yesterday.Immediate follow-up is recommended.";

                   Message::create([
                       'sender_id' => null,
                       'receiver_id' => 1,
                       'type' => 'system_notification',
                       'data' => [
                           'message' => $messageText,
                           'date' => Carbon::now()->toDateTimeString(),
                       ],
                       'status' => 'approved',
                       'event_key' => $eventKey,
                   ]);
                   $count++;
                   foreach ($admins as $admin) {
                       if ($admin->fcm_token) {
                           $pushController->send(
                               [
                                   'id' => $admin->id,
                                   'fcm_token' => $admin->fcm_token,
                                   'first_name' => $admin->first_name,
                                   'last_name' => $admin->last_name,
                               ],
                               '⚠️ Possible Bag Loss',
                               "Customer {$customer->user->first_name} {$customer->user->last_name} (bags: {$bagIds}) "
                               . "still has both bags at the store. This may mean they didn’t receive a bag yesterday."
                           );
                       }
                   }
               }
           }
           ///////////////////////////////////customer is not active
           if (!$isActive) {
               $bagsAtCustomer = $customer->bags->where('last_update_at', 'atCustomer');

               if ($bagsAtCustomer->isNotEmpty()) {
                   $bagIds = $bagsAtCustomer->pluck('bag_id')->implode(', ');
                   $eventKey = "lost_bag_inactive_customer_{$customer->id}";

                   if (!Message::where('event_key', $eventKey)->exists()) {
                       $messageText = "⚠️ The system detected an unusual condition."
                           . "Customer {$customer->user->first_name} {$customer->user->last_name} has an inactive subscription "
                           . "but still retains bag with ID: {$bagIds} marked as 'atCustomer'."
                           . "This could indicate that the bag was not returned after deactivation. Immediate follow-up is recommended.";


                       Message::create([
                           'sender_id' => null,
                           'receiver_id' => 1,
                           'type' => 'system_notification',
                           'data' => [
                               'message' => $messageText,
                               'date' => Carbon::now()->toDateTimeString(),
                           ],
                           'status' => 'approved',
                           'event_key' => $eventKey,
                       ]);
                       $count++;

                   foreach ($admins as $admin) {
                       if ($admin->fcm_token) {
                           $pushController->send(
                               [
                                   'id' => $admin->id,
                                   'fcm_token' => $admin->fcm_token,
                                   'first_name' => $admin->first_name,
                                   'last_name' => $admin->last_name,
                               ],
                               '⚠️ Possible Bag Loss',
                               "Customer {$customer->user->first_name} {$customer->usre->last_name} (bag IDs: {$bagIds}) "
                               . "still has bag(s) despite inactive subscription."
                           );
                       }
                   }
                 }
               }
           }
       }
          return $count;
    }

    //////////////////////////////////////////////////////////////
    public function checkSubscriptionExpire(){
        $expiredCustomers = Customer::where('subscription_status', 1)
            ->where('subscription_expiry_date', '<=', Carbon::today())
            ->get();

        $count = 0;

        if ($expiredCustomers->isNotEmpty()) {

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
                        'message' => "We apologize, your service has been suspended due to subscription expiration"
                            . "Please contact the restaurant to renew your account and continue enjoying our service "
                            . "expiry_date:$customer->subscription_expiry_date",
                        'date' => Carbon::now()->toDateTimeString(),
                    ],
                    'status' => 'approved',
                    'event_key' => $eventKey,
                ]);
                $count++;
                if ($customer->user->fcm_token) {
                    $pushController->send(
                        [
                            'id' => $customer->id,
                            'fcm_token' => $customer->user->fcm_token,
                            'first_name' => $customer->user->first_name,
                            'last_name' => $customer->user->last_name,
                        ],
                        'Your Subscription is Expiring',
                        'We apologize, your service has been suspended due to subscription expiration. Please contact the restaurant to renew your account and continue enjoying our service'
                    );
                }

            }
        }
            return $count;
    }
//////////////////////////////////////////////////////////////////
    public function sendSubscriptionReminders()
    {
        $today = now()->startOfDay();

        $customers = Customer::whereIn('subscription_expiry_date', [
            $today->copy()->addDays(3)->toDateString(),
            $today->copy()->addDays(2)->toDateString(),
            $today->copy()->addDay()->toDateString(),
        ])->get();
        $pushController = new PushNotificationController();
        $count = 0;
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
                    'message' => "Your subscription will expire in {$daysLeft} day" .($daysLeft > 1 ? 's' : '')
                        .".Please visit the restaurant to renew and continue enjoying our services ❤ "
                        ."expiry_date :$customer->subscription_expiry_date",
                    'date'=>Carbon::now()->toDateTimeString(),
                ],
                'status' => 'approved',
                'event_key' => $eventKey,
            ]);
            $count++;
            if ($customer->user->fcm_token) {
                $pushController->send(
                    [
                        'id'         => $customer->id,
                        'fcm_token'  => $customer->user->fcm_token,
                        'first_name' => $customer->user->first_name,
                        'last_name'  => $customer->user->last_name,
                    ],
                    'Reminder: Your Subscription is Expiring',
                    "Your subscription will expire in {$daysLeft} day" .($daysLeft > 1 ? 's' : '') .".Please visit the restaurant to renew and continue enjoying our services ❤ "
                );
            }

        }
        return $count;
    }
//////////////////////////////////////////////////////////////////////////////////
         public function sendDailyOrderReminder(){
             $today = Carbon::today();

             $customers = Customer::where('subscription_status', 1)
                 ->whereHas('user', function ($query) {
                     $query->where('is_active', 1);
                 })
                 ->get();
             $sentCount = 0;

             if ($customers->isEmpty()) {
                 return  $sentCount ;
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
                     $sentCount++;
                 }
                 Cache::put($eventKey, true, now()->endOfDay());
             }
             return $sentCount;
         }

}
