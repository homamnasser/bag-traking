<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $superAdminExists = Role::where('name','super_admin')->first()
            ? User::role('super_admin')->exists()
             : false;

        if ($superAdminExists) {
            return response()->json([
                'message' => 'Super Admin already exists.',
            ], 403);
        }

        $request->validate([
            'first_name' => ['required', 'max:55'],
            'last_name' => ['required', 'max:55'],
            'password' => ['required','min:6|confirmed'],
            'phone' => ['unique:users,phone','phone:AUTO', 'required'],

        ]);
        $user =User::query()->create([
            'first_name' => $request['first_name'],
            'last_name' => $request['last_name'],
            'phone' => $request['phone'],
            'password' => Hash::make($request['password']),
        ]);
        $user->assignRole('super_admin');
        $userData = $user->toArray();
        $role=$user->getRoleNames()->first();
        $token = $user->createToken("API TOKEN")->plainTextToken;

        return response()->json([
            'code' => 201,
            'message' => ' super admin register successfully',
            'result' => array_merge($userData, ['role' => $role],['token'=>$token])
        ]);
    }


    public function loginUser(Request $request)
    {
        $request->validate([
            'phone' => ['required','phone:AUTO','exists:users,phone'],
            'password' => ['required','min:6'],
        ]);
        $user = User::query()->where('phone', $request->phone)
            ->first();


        if (!Auth::attempt($request->only(['phone', 'password']))) {
            return response()->json([
                'data' => [],
                'status' =>0 ,
                'message' => 'The password is incorrect'
            ],401);
        }

        $token = $user->createToken('API TOKEN')->plainTextToken;
        $userData = $user->toArray();
        $role=$user->getRoleNames()->first();

        return response()->json([
            'code' => 200,
            'message' => 'user login successfully',
            'result' =>array_merge($userData, ['role' => $role],['token'=>$token])
        ],200);
    }

}
