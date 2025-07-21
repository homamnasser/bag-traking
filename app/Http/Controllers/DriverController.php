<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class DriverController extends Controller
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
            ]);
        }
        $fullName = $userPhone->first_name . ' ' . $userPhone->last_name;

        if (trim(strtolower($fullName)) !== trim(strtolower($request->full_name))) {
            return response()->json([
                'code'=>422,
                'message' => 'Full name is incorrect.'
            ]);
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
        ]);


    }
}
