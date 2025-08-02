<?php

namespace App\Http\Controllers;

use  App\Models\Bag;
use App\Models\Scan_Log;
use App\Models\User;
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
                'message' => 'Phone number is incorrect or not registered.'
            ],404);
        }
        $fullName = $userPhone->first_name . ' ' . $userPhone->last_name;

        if (trim(strtolower($fullName)) !== trim(strtolower($request->full_name))) {
            return response()->json([
                'code'=>422,
                'message' => 'Full name is incorrect.'
            ],422);
        }


        MessageController::passwordResetRequestMessage([
            'user_id'    =>$userPhone->id,
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
        $currentState = $bag->last_update_at;
        $scanType = $request->action;

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

                if (!in_array($currentState, ['atStore', 'atCustomer'])) {
                    return response()->json([
                        'code' => 400,
                        'message' => 'Bag is not ready for pickup by the driver.'
                    ],400);
                }
                $bag->last_update_at = 'atWay';
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

}
