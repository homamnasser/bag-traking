<?php

namespace App\Http\Controllers;

use  App\Models\Bag;
use App\Models\Customer;
use App\Models\DriverAreaService;
use App\Models\Message;
use App\Models\Scan_Log;
use App\Models\User;
use App\Services\WhatsAppService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class WorkerController extends Controller
{
    public function forgetPassword(Request $request){
        $request->validate([
            'full_name' => 'string|max:55',
            'phone'      => ['required','regex:/^\+9715[0,2-8]\d{7}$/'],
            'new_password'=> 'string|min:6|confirmed',
        ],
            [ 'phone.regex' =>'please enter a valid United Arab Emirates phone number']);

        $userPhone = User::where('phone', $request->phone)->first();

        if (!$userPhone) {
            return response()->json([
                'code'=>404,
                'message' => 'Phone number is incorrect ,please Check your for number.'
            ],404);
        }
        if (! $userPhone->hasAnyRole(['driver', 'store_employee', 'admin_cook','admin'])) {
            return response()->json([
                'code' => 403,
                'message' => 'You do not have permission to access this resource.',
            ], 403);
        }
        $fullName = $userPhone->first_name . ' ' . $userPhone->last_name;

        if (trim(strtolower($fullName)) !== trim(strtolower($request->full_name))) {
            return response()->json([
                'code'=>422,
                'message' => 'Full name is incorrect.'
            ],422);
        }


        MessageController::passwordResetRequestMessage([
            'sender_id'    =>$userPhone->id,
            'role'       => $userPhone->getRoleNames()->first(),
            'full_name'   => $request->full_name,
            'phone'       => $request->phone,
            'new_password'=> $request->new_password,

        ]);

        return response()->json([
            'code'=>200,
            'message' => 'Your request to change the password has been submitted to the restaurant management .Please wait until your request is approved'
        ],200);
    }


    public function scanQr(Request $request)
    {
        $user=Auth::user();
        $validator=validator::make($request->all(), [
            'action'=>'required|string|in:check_in_warehouse,check_out_warehouse,check_in_driver,check_out_driver,delivered',
            'bag_id' => 'required|exists:bags,bag_id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'code' => 422,
                'message' => $validator->errors()->first(),
            ],422);}

        $bag = Bag::where('bag_id', $request->bag_id)->first();

        if (!$bag) {
            return response()->json([
                'code' => 404,
                'message' => 'Bag not found',
                'data' => []
            ],404);
        }
        $driverAreaIds=DriverAreaService::where('driver_id',$user->id)->pluck('id')->toArray();
        $customerAreaId = optional($bag->customer)->area_id;

        if ($customerAreaId && !in_array($customerAreaId, $driverAreaIds)) {
            return response()->json([
                'code' => 422,
                'message' => "You are not allowed to scan this bag because it belongs to another area."
            ], 422);
        }


        if ($request->has('first_name') || $request->has('last_name')) {
            $ownerFirst = strtolower(isset($bag->customer->user->first_name) ? $bag->customer->user->first_name : '');
            $ownerLast  = strtolower(isset($bag->customer->user->last_name) ? $bag->customer->user->last_name : '');

            if (strtolower($request->first_name) !== $ownerFirst ||
                strtolower($request->last_name) !== $ownerLast) {
                return response()->json([
                    'code' => 403,
                    'message' => 'The bag owner information does not match.'
                ], 403);
            }
        }

        $currentState = $bag->last_update_at;
        $scanType = $request->action;
        $customer=$bag->customer->user;
        switch ($scanType) {

            case 'check_out_warehouse':
                if ($currentState !== 'atStore') {
                    return response()->json([
                        'code' => 400,
                        'message' => 'Bag is not in the store. Please scan check-in warehouse first.'
                    ],400);
                }
                $bag->last_update_at = 'atStore';
                break;

            case 'check_in_driver':
                if ($currentState =='atWay') {
                    return response()->json([
                        'code' => 400,
                        'message' =>'Bag status is On the Way. It has already been scanned by the driver.'
                    ],400);
                }
                $previousLog = Scan_Log::where('bag_id', $bag->id)
                    ->latest()
                    ->first();

                $bag->last_update_at = 'atWay';
                $bag->save();
                if (! $previousLog || $previousLog->status === 'atStore') {
                Message::create([
                    'sender_id' => null,
                    'receiver_id' => $customer->id,
                    'type' => 'system_notification',
                    'data' => [
                        'message' =>"Dear {$customer->first_name}, Great news🤩 your bag has left the restaurant and is on its way to your location 🚛📍 "
                        ,'date'=>Carbon::now()->toDateTimeString(),
                    ],
                    'status' => 'approved',
                ]);
                $pushController = new PushNotificationController();
                if ($customer &&$customer->fcm_token) {
                    $pushController->send(
                        [
                            'id' => $customer->id,
                            'fcm_token' => $customer->fcm_token,
                            'first_name' => $customer->first_name,
                            'last_name' => $customer->last_name,
                        ],
                        'Your Bag is on the Way',
                        "Dear {$customer->first_name}, Great news🤩\n your bag has left the restaurant and is on its way to your location 🚛📍 "
                    );
                }
                }
                break;

            case 'check_out_driver':

                if ($currentState !== 'atWay') {
                    return response()->json([
                        'code' => 400,
                        'message' => 'Bag is not on the way. Please scan check-in driver first.'
                    ],400);
                }
                $bag->last_update_at = 'atStore';
                break;

            case 'delivered':

                if ($currentState !== 'atWay') {
                    return response()->json([
                        'code' => 400,
                        'message' => 'Bag is not on the way. Please scan check-in driver first.'
                    ],400);
                }
                 $bag->last_update_at = 'atCustomer';
                 $customerPhone = $customer->phone;

                $whatsAppService = app(WhatsAppService::class);

                $whatsAppService->sendMessage(
                    $customerPhone,
                    "Dear {$customer->first_name}, thank you for choosing our restaurant to take care of your food 🙏🏻
Great news🤩
Your bag has just been delivered to your location.🚛📍"

                );

                break;


            case 'check_in_warehouse':

                if ($currentState !== 'atStore') {
                    return response()->json([
                        'code' => 400,
                        'message' => ' Please wait until the driver scan check out '
                    ],400);
                }
                $bag->last_update_at = 'atStore';
                break;

            default:
                return response()->json([
                    'code' => 400,
                    'message' => 'Invalid scan type.'
                ],400);
        }

        $bag->save();
        Scan_Log::create([
            'user_id' => $user->id,
            'bag_id' => $bag->id,

            'date' => Carbon::now()->toDateString(),
            'time' => Carbon::now()->toTimeString(),
            'status' => $bag->last_update_at,
        ]);
        $customerFirstName = optional(optional($bag->customer)->user)->first_name;
        $customerLastName = optional(optional($bag->customer)->user)->last_name;
        $customerFullName = trim($customerFirstName . ' ' . $customerLastName);

        return response()->json([
            'code'=>200,
            'message'=>'Scan processed successfully',
            'data'=>[
            'bag_id' => $bag->bag_id,
            'customer_name' => $bag->customer->user->first_name . ' ' . $bag->customer->user->last_name,
            'newState'  => $bag->last_update_at,
                ]
        ],200);
    }



   public function  getCustomerForDriver($id){

       $driver =User::with('areas')->find($id);

       if (!$driver ||$driver->areas->isEmpty()) {
           return response()->json([
               'code' => 404,
               'message' => 'Driver or Area not found'
           ], 404);
       }

       if (!$driver->hasRole('driver')) {
           return response()->json([
               'code' => 403,
               'message' => 'User does not have role driver'
           ], 403);
       }
       $areaIds = $driver->areas->pluck('id');
       $customers = Customer::with('user', 'bags')
           ->whereIn('area_id', $areaIds)
           ->whereHas('user', function ($query) {
               $query->where('is_active', 1);
           })
           ->get();


       $allCustomer = $customers->map(function ($customer) use ($driver){
        $reservedBags = $customer->bags->where('last_update_at', 'atCustomer')->pluck('bag_id');
           return [
               'id_customer' => $customer->id,
               'name' => $customer->user->first_name . ' ' . $customer->user->last_name,
               'address' => $customer->address,
               'phone' => $customer->user->phone,
               'driverName' => $driver->first_name . ' ' . $driver->last_name,
               'reservedBags' => $reservedBags,
               'bags' => $customer->bags->pluck('bag_id'),
           ];
       });

       return response()->json([
           'code' => 200,
           'data' => $allCustomer
       ],200);
   }
}
