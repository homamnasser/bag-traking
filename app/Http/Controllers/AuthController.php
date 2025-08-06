<?php

namespace App\Http\Controllers;

use App\Mail\SendCodeResetPassword;
use App\Models\ResetCodePassword;
use App\Models\User;
use App\Traits\PhotoTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;

class  AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => ['required', 'max:55'],
            'last_name' => ['required', 'max:55'],
            'password' => ['required','min:6','confirmed'],
            'phone' => ['required', 'unique:users,phone', 'regex:/^\+9715[0,2-8]\d{7}$/'],
            'role' => ['required','in:driver,store_employee'],
            'image.*' => ['image','mimes:jpeg,png,jpg,gif','max:512'],

        ],[
            'phone.unique' => 'the phone already exist',
            'phone.regex' =>'please enter a valid United Arab Emirates phone number' ]);

        if ($validator->fails()) {
            return response()->json([
                'code' => 422,
                'message' => $validator->errors()->first(),
            ],422);}


        $role = $request->role;
        $images = null;
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $fileName = 'images/' . 'images_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            Storage::disk('public')->put($fileName, file_get_contents($file));
            $image = 'storage/' . $fileName;
            $images=asset($image);
        }

        $user =User::query()->create([
            'first_name' => $request['first_name'],
            'last_name' => $request['last_name'],
            'phone' => $request['phone'],
            'password' => Hash::make($request['password']),
            'image' =>$images,
            'is_active'=>false
        ]);
           $user->assignRole($role);

        MessageController::createAccountRequestMessage([
            'user_id'    =>$user->id,
            'role'       => $request->role,
            'first_name' => $request->first_name,
            'last_name'  => $request->last_name,
            'phone'      => $request->phone,
            'password'   => $request->password,
        ]);
        return response()->json([
            'code'=>200,
            'message' => 'Your account creation request has been submitted  to the restaurant management and is pending approval.',
        ],200);
    }


    public function loginUser(Request $request)
    {
        $request->validate([
            'phone' => ['required'],
            'password' => ['required','min:6'],
        ]);

        $user = User::query()->where('phone', $request->phone)
            ->first();
        if (!$user) {
            return response()->json([
                'code' => 404,
                'message' => 'The phone is incorrect'
            ],404);}

        if (!Auth::attempt($request->only(['phone', 'password']))) {
            return response()->json([
                'code' =>401 ,
                'message' => 'The password is incorrect'
            ],401);
        }
         if(!$user->is_active) {
             return response()->json([
                 'code' => 403,
                 'message' => 'The account is not activated yet. Please contact the restaurant management if there is an issue.'
             ],403);
         }

        $token = $user->createToken('API TOKEN')->plainTextToken;
        $userData = $user->toArray();
        $role=$user->getRoleNames()->first();

        return response()->json([
            'code' => 200,
            'message' => 'user login successfully',
            'data' =>array_merge($userData, ['role' => $role],['token'=>$token])
        ],200);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'code'=>200,
            'message' => 'User successfully signed out'],200);
    }

    public function customerForgetPassword(Request $request)
    {
        $validator =Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ],
            ['email.exists' => 'Email not found',
                'email.email' => 'Please enter a valid email address',]);

        if ($validator->fails()) {
            return response()->json([
                'code' => 422,
                'message' => $validator->errors()->first(),
            ],422);}

        $data = $validator->validated();

        $existingCode =ResetCodePassword::query()->where('email', $request['email'])->first();;

        if ($existingCode && $existingCode->created_at->gt(now()->subMinutes(5))) {

            $remaining = now()->diffInSeconds($existingCode->created_at->addMinutes(5));
            return response()->json([
                'code' => 429,
                'message' => 'A code was already sent. Please try again after ' . ceil($remaining / 60) . ' minute(s).'
            ], 429);
        }
        ResetCodePassword::where('email', $request->email)->delete();

        $data['code'] = mt_rand(100000, 999999);
        $codeData = ResetCodePassword::query()->create($data);

        Mail::to($request['email'])->send(new SendCodeResetPassword($codeData['code']));

        return response()->json([
            'code'=>200,
            'message' => trans('A verification code has been sent to your email. It will expire in 5 minutes.')
        ], 200);

    }

    public function customerCheckCode(Request $request){

        $validator = Validator::make($request->all(), [
            'code' => 'required|string|exists:reset_code_passwords',
        ],['code.exists' => 'Invalid verification code.']
        );

        if ($validator->fails()) {
            return response()->json([
                'code' => 422,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $passwordReset = ResetCodePassword::firstWhere('code', $request['code']);

        if ( $passwordReset->created_at->lt(now()->subMinutes(5))) {

            $passwordReset->delete();
            return response()->json([
                'code'=>422,
                'message' => trans('The verification code has expired. Please request a new one.'
                )], 422);
        }

        return response()->json([
            'code' => $passwordReset['code'],
            'message' => trans('Verification code is valid')
        ], 200);
    }

    public function customerResetPassword(Request $request){

        $validator = Validator::make($request->all(), [
            'code' => 'required|string|exists:reset_code_passwords',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'code' => 422,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $passwordReset = ResetCodePassword::firstWhere('code', $request['code']);


        if ( $passwordReset->created_at->lt(now()->subMinutes(5))) {
            $passwordReset->delete();
            return response([
                'code'=>422,
                'message' => trans('The verification code has expired Please request a new one.')
            ], 422);
        }

        $user = User::firstWhere('email', $passwordReset['email']);

        $input['password']=bcrypt($request['password']);
        $user->update([
            'password'=>$input['password'],
            ]);

        $passwordReset->delete();

        return response([
            'code'=>200,
            'message' =>'password has been successfully reset'], 200);
    }

}
