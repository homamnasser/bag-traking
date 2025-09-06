<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\DriverAreaService;

use App\Models\User;
use App\Traits\PhotoTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
class AdminController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }
    use PhotoTrait;

    public function createUser(Request $request)
    {
     $validator = Validator::make($request->all(), [
         'first_name' => 'required|string|max:55',
         'last_name'  => 'required|string|max:55',
         'phone'      => ['required', 'unique:users,phone','regex:/^\+9715[0,2-8]\d{7}$/'],
         'password'   => 'required|string|min:6|confirmed',
         'role'       => 'required|string|in:admin,admin_cook,driver,store_employee',
         'image.*' => ['image','mimes:jpeg,png,jpg,gif','max:2048'],
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
            $file->move(public_path('images'), $fileName);
            $image= asset( $fileName);

        }


            $user = User::create([
                'first_name' => $request->first_name,
                'last_name'  => $request->last_name,
                'phone'      => $request->phone,
                'password'   => Hash::make($request->password),
                'is_active'  => true,
                'image' =>$image,

            ]);
           $user->assignRole($role);

            return response()->json([
                'code' => 201,
                'message' => "{$role} user created successfully.",
                'data' => [
                    'id' => $user->id,
                    'name' => $user->first_name . ' ' . $user->last_name,
                    'phone' => $user->phone,
                    'role' => $role,
                   'image'=> $user->image ,
                ]
            ],201);
        }


    public function updateUser(Request $request ,$id)
    {
        $user= User::find($id);

        if (!$user) {
            return response()->json([
                'code'=>404,
                'message' => 'User not found',
                'data'=>[]
            ],404);
        }

        if ($user->id === 1) {
            return response()->json([
                'code'=>403,
                'message' => 'Cannot update the primary system user (ID: 1).'
            ],403);}

        $validator = Validator::make($request->all(), [
            'first_name' => 'string|max:55',
            'last_name'  => 'string|max:55',
            'phone'      => ['unique:users,phone','regex:/^\+9715[0,2-8]\d{7}$/'],
            'password'   => 'string|min:6|confirmed',
            'role'       => 'string|in:admin,admin_cook,driver,store_employee',
            'is_active'  => 'boolean',
            'image' => ['image','mimes:jpeg,png,jpg,gif','max:2048'],
        ],[
            'phone.regex' =>'please enter a valid United Arab Emirates phone number',
            'phone.unique' => 'the phone already exist',
            'is_active'  => 'The is active field must be 0 or 1'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'code' => 422,
                'message' => $validator->errors()->first(),
            ],422);}

        $dataToUpdate = $request->only([
            'first_name',
            'last_name',
            'phone',
            'password',
            'is_active'
        ]);
        if ($request->filled('password')) {
            $dataToUpdate['password'] = Hash::make($request->password);
        }

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $fileName = 'images_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('images'), $fileName);
            $dataToUpdate['image'] = asset( $fileName);
        }

        if($request->is_active==0){
            $areas=$user->areas;
            if($user->hasRole('driver')&&  $areas->where('driver_id', $user->id)->isNotEmpty()){
                $area = $areas->where('driver_id', $user->id)->first();
              return response()->json([
                  'code'=>403,
                  'message'=>"You cannot deactivate this driver because they are currently assigned to the area: {$area->name}."
              ],403);
            }
        }

        $user->update($dataToUpdate);

        if ($request->has('role') && !empty($request->role)) {
            $user->syncRoles($request->role);
        }

        $currentRole = $user->getRoleNames()->first();

        return response()->json([
            'code'=> 200,
            'message' => " user updated successfully.",
            'data' => [
                'id'=> $user->id,
                'name'=> $user->first_name.' '.$user->last_name,
                'phone'=> $user->phone,
                'role'=> $currentRole,
                'is_active'  => $user->is_active,
                'image'=>$user->image ,
            ]
        ],200);
    }

       public function deleteImage($user_id){

        $user=User::find($user_id);

           if (!$user) {
               return response()->json([
                   'code'=>404,
                   'message' => 'User not found',
                   'data'=>[]
               ],404);
           }
           if ($user->image) {
               $imagePath = public_path($user->image);
               if (file_exists($imagePath)) {
                   unlink($imagePath);
               }

               $user->update(['image' => null]);
           }

           return response()->json([
               'code'=>200,
               'message'=>'image delete successfully',

           ],200);
       }

    public function deleteUser($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'code'=>404,
                'message' => 'User not found',
                'data'=>[]
            ],404);
        }
        if ($user->id === 1) {
            return response()->json([
                'code'=>403,
                'message' => 'Cannot delete the primary system user (ID: 1).'
            ],403);
        }
        if ($user->hasRole('driver')) {
            $area = DriverAreaService::where('driver_id', $user->id)->first();
            if ($area) {
                return response()->json([
                    'code' => 403,
                    'message' => "Cannot delete the driver assigned to area : {$area->name}, Please assign the area to another driver and then try again"
                ],403);
            }
        }

        $user->delete();

        return response()->json([
            'code'=> 200 ,
            'message' => 'Uesr deleted successfully ',
        ],200);

    }
    public function getUser($id)
    {
        $currentUser = auth()->user();
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'code'=>404,
                'message' => 'User not found',
                'data'=>[]
            ], 404);
        }

        if(!$currentUser->hasRole('super_admin')&&$id==1){
            return response()->json([
                'code'=>422,
                'message' => 'only super admin can view super admin account ',
            ], 422);

        }
        return response()->json([
                'code'=> 200,
                'message' => 'This is User ',
                'data' => [
                    'id'=> $user->id,
                    'name'=> $user->first_name.' '.$user->last_name,
                    'phone'=> $user->phone,
                    'is_active'=>$user->is_active,
                    'role'=> $user->getRoleNames()->first(),
                    'image'=> $user->image ,
                ]
            ],200);
    }

//first/last name phone role/active
    public function getAllUsers($request)
    {
        $currentUser = auth()->user();

        if ($request == "all") {
            $users = User::query();

        }
        else
        {
            $users = User::where(function ($q) use ($request) {
                $q->where('phone',  $request)
                ->orWhere('first_name', $request )
                ->orWhere('last_name',  $request )
                ->orWhere('is_active', (in_array($request, ['0', '1']) ? (int) $request : -1))
                ->orWhereHas('roles', function ($query) use ($request) {
                    $query->where('name',  $request );
                });
            });
        }
        if ($currentUser-> id !== 1) {
            $users->where('id', '!=', 1);
        }

        $users->whereDoesntHave('roles', function ($q) {
            $q->where('name', 'customer');
        });

        $users = $users->get();

        if ($users->isEmpty()) {
            return response()->json([
                'code'=>404,
                'message' => 'No users found.',
                'data'=>[]
            ],404);
        }

        $allUsers = $users->map(function ($user) {
            return [
                'id' => $user->id,
                'full_name' => $user->first_name . ' ' . $user->last_name,
                'phone' => $user->phone,
                'is_active'=>$user->is_active,
                'image'=> $user->image ,
                'role' => $user->getRoleNames()->first(),
            ];
        });

        return response()->json([
            'code'=> 200,
            'message' => 'Users retrieved successfully.',
            'data' => $allUsers,
        ],200);
    }


    public function getMyInfo()
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'code'=>401,
                'message' => 'Unauthenticated. Please log in.'
            ], 401);
        }

        return response()->json([
            'code'=> 200,
            'message' => 'My info',
            'data' => [
                'id'=> $user->id,
                'first_name'=> $user->first_name,
                'last_name'=>$user->last_name,
                'phone'=> $user->phone,
                'email'=>$user->email,
                'role'=> $user->getRoleNames()->first(),
                'image'=> $user->image,
            ]
        ],200);
    }

}
