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
            'password'   => 'required|string|min:6|confirmed',
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
            'code'=> 201 ,
            'message' => "{$role} user created successfully.",
            'result' => [
                'id'=> $user->id,
                'name'=> $user->first_name.' '.$user->last_name,
                'phone'=> $user->phone,
                'role'=> $role,

            ]

        ], 201);
    }

    public function updateUser(Request $request ,$id)
    {
        $user= User::find($id);

        if (!$user) {
            return response()->json([
                'message' => 'User not found',
            ], 404);
        }


        if ($request->role === 'admin' && !Auth::user()->hasRole('super_admin')) {
            return response()->json([
                'message' => 'Only super admin can update an admin user.'
            ], 403);
        }

        $user= User::find($id);

        if (!$user) {
            return response()->json([
                'message' => 'User not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'first_name' => 'string|max:55',
            'last_name'  => 'string|max:55',
            'phone'      => 'string|unique:users,phone',
            'password'   => 'string|min:6|confirmed',
            'role'       => 'string|in:admin,admin_cook,driver,store_employee,customer',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        if ($request->has('role') && $request->role === 'admin' && !Auth::user()->hasRole('super_admin')) {
            return response()->json([
                'message' => 'Only super admin can assign or update to the "admin" role.'
            ], 403);
        }

        $user->update($request->all());

        if ($request->has('role') && !empty($request->role)) {
            $user->syncRoles($request->role);
        }

        $currentRole = $user->getRoleNames()->first();

        return response()->json([
            'code'=> 200,
            'message' => " user updated successfully.",
            'result' => [
                'id'=> $user->id,
                'name'=> $user->first_name.' '.$user->last_name,
                'phone'=> $user->phone,
                'role'=> $currentRole,

            ]
        ], 200);
    }
    public function deleteUser($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'message' => 'User not found',
            ], 404);
        }
        if ($user->id === 1) {
            return response()->json([
                'message' => 'Cannot delete the primary system user (ID: 1).'
            ], 403);
        }

        $user->delete();

        return response()->json([
            'code'=> 200 ,
            'message' => 'Uesr deleted successfully ',
        ], 200);

    }
    public function getUser($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'message' => 'User not found',
            ], 404);
        }
        return response()->json([
                'code'=> 200,
                'message' => 'This is User ',
                'result' => [
                    'id'=> $user->id,
                    'name'=> $user->first_name.' '.$user->last_name,
                    'phone'=> $user->phone,
                    'role'=> $user->getRoleNames()->first(),
                ]
            ]
            , 200);
    }

//first/last name phone role
    public function getAllUsers($request)
    {
        if ($request == "all") {
            $users = User::all();

        }
        else
        {
            $users = User::where('phone',  $request)
            ->orWhere('first_name', $request )
            ->orWhere('last_name',  $request )
            ->orWhereHas('roles', function ($query) use ($request) {
             $query->where('name',  $request );
        })
        ->get();
        }
        if ($users->isEmpty()) {
            return response()->json([
                'message' => 'No users found.',
            ], 404);
        }

        $allUsers = $users->map(function ($user) {
            return [
                'id' => $user->id,
                'full_name' => $user->first_name . ' ' . $user->last_name,
                'phone' => $user->phone,
                'role' => $user->getRoleNames()->first(),
            ];
        });

        return response()->json([
            'code'=> 200,
            'message' => 'Users retrieved successfully.',
            'result' => $allUsers,
        ], 200);
    }

}
