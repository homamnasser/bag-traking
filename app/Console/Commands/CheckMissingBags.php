<?php

namespace App\Console\Commands;

use App\Http\Controllers\PushNotificationController;
use App\Models\Bag;
use App\Models\Customer;
use App\Models\Message;
use App\Models\Scan_Log;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CheckMissingBags extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'Bags:check-missing-bags';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check the status of the bags and discover the missing bag.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $pushController = new PushNotificationController();
        $admins = User::role('admin')->get();


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
                    $bagIds = $bagsAtCustomer->pluck('id')->implode(', ');
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
                    }
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
            //////////////////////////////////////number bag
            $bagCount = $customer->bags->count();
            if ($bagCount <= 1) {
                $eventKey = "one_or_zero_bag_customer_{$customer->id}";
                if (!Message::where('event_key', $eventKey)->exists()) {
                    Message::create([
                        'sender_id' => null,
                        'receiver_id' => 1,
                        'type' => 'system_notification',
                        'data' => [
                            'message' => " ⚠️ Customer {$customer->user->first_name} {$customer->user->last_name} owns only one bag, but every customer should have exactly two bags.",
                            'date' => Carbon::now()->toDateTimeString()],
                        'status' => 'approved',
                        'event_key' => $eventKey,
                    ]);
                    foreach ($admins as $admin) {
                        if ($admin->fcm_token) {
                            $pushController->send(
                                [
                                    'id' => $admin->id,
                                    'fcm_token' => $admin->fcm_token,
                                    'first_name' => $admin->first_name,
                                    'last_name' => $admin->last_name,
                                ],
                                '⚠️ Possible Bag Issue',
                                "Customer {$customer->user->first_name} {$customer->user->last_name} owns only one bag "
                            );
                        }
                    }
                }
            }

            if ($bagCount > 2) {
                $eventKey = "more_than_two_bags_customer_{$customer->id}";
                if (!Message::where('event_key', $eventKey)->exists()) {
                    Message::create([
                        'sender_id' => null,
                        'receiver_id' => 1,
                        'type' => 'system_notification',
                        'data' => [
                            'message' => "Customer {$customer->user->first_name} {$customer->user->last_name} owns {$bagCount} bags, which is more than expected (each customer must have exactly 2 bags)."
                                . " Immediate follow-up is recommended.",
                            'date' => Carbon::now()->toDateTimeString(),
                        ],
                        'status' => 'approved',
                        'event_key' => $eventKey,
                    ]);
                    foreach ($admins as $admin) {
                        if ($admin->fcm_token) {
                            $pushController->send(
                                [
                                    'id' => $admin->id,
                                    'fcm_token' => $admin->fcm_token,
                                    'first_name' => $admin->first_name,
                                    'last_name' => $admin->last_name,
                                ],
                                '⚠️ Possible Bag Issue',
                                "Customer {$customer->user->first_name} {$customer->user->last_name} owns {$bagCount} bags "
                                . " Immediate follow-up is recommended."
                            );
                        }
                    }
                }
            }
        }
    }
}
