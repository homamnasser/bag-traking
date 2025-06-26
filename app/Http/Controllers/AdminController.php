<?php

namespace App\Http\Controllers;

use App\Models\User; // تأكد من استيراد موديل المستخدم
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // لاستخدام Auth::user() و Auth::check()
use Illuminate\Support\Facades\Hash; // لتشفير كلمة المرور
use Illuminate\Support\Facades\Validator; // إذا كنت تفضل استخدام Validator::make() بدلاً من $request->validate()
// لا تحتاج لاستيراد Spatie\Permission\Traits\HasRoles هنا، بل في موديل User

class AdminController extends Controller
{
    public function __construct()
    {

        $this->middleware('auth');
    }

    public function createUser(Request $request)
    {

        if ($request->role === 'admin' && !Auth::user()->hasRole('super_admin')) {
            return response()->json([
                'message' => 'Only super admin can create an admin user.'
            ], 403);
        }


        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:55',
            'last_name'  => 'required|string|max:55',
            'phone'      => 'required|string|unique:users,phone',
            'password'   => 'required|string|min:6',
            'role'       => 'required|string|in:admin,admin_cook,driver,store_employee,customer',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = User::create([
            'first_name' => $request->first_name,
            'last_name'  => $request->last_name,
            'phone'      => $request->phone,
            'password'   => Hash::make($request->password),
        ]);

        $user->assignRole($request->role);


        $role = $user->getRoleNames()->first();

        return response()->json([
            'message' => "{$role} user created successfully.",
        ], 201);
    }
    public function getAllUsers()
    {
        $users = User::all();

        if ($users->isEmpty()) {
            return response()->json([
                'message' => 'No users found.',
            ], 404);
        }

        $allUsers = $users->map(function ($user) {
            return [
                'id' => $user->id,
                'full_name' => $user->first_name . ' ' . $user->last_name,
                'role'=>$user->getRoleNames()->first(),
            ];
        });

        return response()->json([
            'message' => 'All users retrieved successfully.',
            'data' => $allUsers,
        ], 200);
    }
}
