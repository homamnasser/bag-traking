<?php

namespace App\Http\Controllers;

use App\Models\Bag;
use App\Models\Customer;
use App\Models\DriverAreaService;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class CustomerController extends Controller
{

    private function generateBagQr(Bag $bag, User $user, Customer $customer)
    {
        $qrContent = url('/api/bag') . '?bag_id=' . $bag->bag_id .
            '&first_name=' . urlencode($user->first_name) .
            '&last_name=' . urlencode($user->last_name);

        $fileName = 'qr_codes/bag_' . $bag->bag_id . '.svg';
        $qrImage = QrCode::format('svg')->size(300)->generate($qrContent);
        Storage::disk('public')->put($fileName, $qrImage);

        $bag->update([
            'qr_code_path' => $fileName,
            'customer_id' => $customer->id,
            'status' => 'unavailable',
        ]);

        return asset('storage/' . $fileName);
    }

    public function addCustomer(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:55',
            'last_name' => 'required|string|max:55',
            'phone' =>  ['required', 'string', 'unique:users,phone', 'regex:/^(\+9715[0-9]{7}|^\+[1-9]\d{7,14})$/'],
            'password' => 'required|string|min:6|confirmed',
            'email'=> 'required|email|unique:users,email',
            'image' => ['image','mimes:jpeg,png,jpg,gif','max:512'],
            'area_id' => 'required|exists:driver_area_services,id',
            'address' => 'required|string',
            'subscription_status' => 'required|in:0,1',

        ],['phone.unique' => 'the phone already exist',
            'phone.regex' =>'please enter a valid  phone number' ]);

        if ($validator->fails()) {
            return response()->json([
                'code' => 422,
                'message' => $validator->errors()->first(),
            ]);}

        $images = null;
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $fileName = 'images/' . 'images_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            Storage::disk('public')->put($fileName, file_get_contents($file));
            $image = 'storage/' . $fileName;
            $images=asset($image);
        }

        $user = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'email'=>$request->email,
            'image' =>$images,
            'is_active'=>true
        ]);

        $user->assignRole('customer');

        $area = DriverAreaService::find($request->area_id);
        if (!$area) {
            return response()->json([
                'message' => 'Area not found'
            ], 404);
        }

        $bags = Bag::where('status', 'available')->take(2)->get();
        if ($bags->count() < 2) {
            return response()->json([
                'code' => 422,
                'message' => 'Not enough available bags to assign to this customer.']);
        }

        $subscriptionStartDate = Carbon::now();

        $subscriptionExpiryDate = $subscriptionStartDate->copy()->addMonth();

        $customer = Customer::create([
            'user_id' => $user->id,
            'area_id' => $request->area_id,
            'address' => $request->address,
            'subscription_start_date' => $subscriptionStartDate,
            'subscription_expiry_date' => $subscriptionExpiryDate,
            'subscription_status'=>$request->subscription_status
        ]);

        $qrUrls = [];
        foreach ($bags as $bag) {
            $qrUrls[] = $this->generateBagQr($bag, $user, $customer);
        }

        return response()->json([
            'code' => 201,
            'message' => 'Customer  added successfully ',
            'data' => [

                    'id'=> $customer->id,
                    'name' => $user->first_name . ' ' . $user->last_name,
                    'phone' => $user->phone,
                    'email'=>$request->email,
                    'role' => 'customer',
                    'area'=> $customer->area->name,
                    'address'=> $customer->address,
                    'subscription_start_date' => $customer->subscription_start_date->toDateString(),
                    'subscription_expiry_date' => $customer->subscription_expiry_date->toDateString(),
                    'subscription_status'=> $customer->subscription_status,
                    'bags_assigned' => $bags->pluck('bag_id'),
                    'qr_urls' => $qrUrls
                ]
            ]);
    }
    public function updateCustomer(Request $request, $id)
    {
        $customer = Customer::find($id);

        if (!$customer) {
            return response()->json([
                'code'=>404,
                'message' => 'Customer not found',
                'data'=>[]
            ]);
        }

        $user = $customer->user;

        if (!$user) {
            return response()->json([
                'code'=>404,
                'message' => 'User not found',
                'data'=>[]
            ]);
        }

        $validator = Validator::make($request->all(), [
            'first_name' => 'string|max:55',
            'last_name'  => 'string|max:55',
            'phone' => [ 'string', 'unique:users,phone', 'regex:/^(\+9715[0-9]{7}|^\+[1-9]\d{7,14})$/'],
            'email'=> 'email|unique:users,email',
            'is_active'  => 'boolean',
            'password'   => 'string|min:6|confirmed',
            'image' => ['image','mimes:jpeg,png,jpg,gif','max:512'],
            'area_id' => 'exists:driver_area_services,id',
            'address' => 'string',
            'old_bag_id' => 'exists:bags,bag_id'
            ],[
                'phone.unique' => 'the phone already exist',
                'phone.regex' =>'please enter a valid  phone number' ]);

        if ($validator->fails()) {
            return response()->json([
                'code' => 422,
                'message' => $validator->errors()->first(),
            ]);}


        $dataToUpdate = $request->only(['first_name', 'last_name', 'phone','email','is_active']);

        if ($request->filled('password')) {
            $dataToUpdate['password'] = Hash::make($request->password);
        }


        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $fileName = 'images_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('images', $fileName, 'public');
            $images = asset('storage/' . $path);
            $dataToUpdate['image'] = $images;
        } else {
            $dataToUpdate['image'] = null;
        }

        $user->update($dataToUpdate);

        if ($request->has('area_id')) {
            $area = DriverAreaService::find($request->area_id);
            if (!$area) {
                return response()->json([
                    'code'=>404,
                    'message' => 'Area not found',
                    'data'=>[]
                ]);
            }
            $customer->area_id = $request->area_id;
        }

        if ($request->filled('address')) {
            $customer->address = $request->address;
        }
        $customer->save();


        if ($request->has('old_bag_id')) {
            $oldBag = Bag::where('bag_id', $request->old_bag_id)
                ->where('customer_id', $customer->id)
                ->first();

            if ($oldBag) {
                $oldBag->update([
                    'customer_id' => null,
                    'status' => 'available',
                    'qr_code_path' => null,
                ]);
            }}


        $newBag = Bag::whereNull('customer_id')
            ->where('status', 'available')
            ->inRandomOrder()
            ->first();

        if (!$newBag) {
            return response()->json(['message' => 'No available bags found'], 404);
        }
        $newBag->update([
            'customer_id' => $customer->id,
            'status' => 'unavailable',
        ]);
            $qrUrl = $this->generateBagQr($newBag, $user, $customer);
            $qrUrls[] = $qrUrl;


        $customer->update($request->all());

        return response()->json([
                'code' => 200,
                'message' => 'Customer updated successfully ',
                'result' => [
                    'id'=> $customer->id,
                    'name' => $user->first_name . ' ' . $user->last_name,
                    'phone' => $user->phone,

                    'area'=> $customer->area->name,
                    'address'=> $customer->address,
                    'bags_assigned' =>[$newBag->bag_id],
                    'qr_urls' => $qrUrls
                ]
            ]);
    }


    public function editStatus(Request $request, $id)
    {
        $customer= Customer::find($id);

        if (!$customer) {
            return response()->json([
                'message' => 'Customer not found',
            ], 200);
        }
        $validator = Validator::make($request->all(), [
            'subscription_status' => 'required|in:0,1',

        ]);

        if ($validator->fails()) {
            return response()->json([
                'code' => 422,
                'message' => $validator->errors()->first(),
            ]);}

        $newStatus = (int) $request->subscription_status;


        $updateData = [
            'subscription_status' => $newStatus,
        ];

        if($request->subscription_status == 1) {
            if ($customer->subscription_status == 1) {
                $newExpiryDate = $customer->subscription_expiry_date->copy()->addMonth();

                $updateData['subscription_expiry_date'] = $newExpiryDate;
            }
            if ($customer->subscription_status == 0) {

                $newStartDate = Carbon::now();
                $newExpiryDate = $newStartDate->copy()->addMonth();


                $updateData['subscription_start_date'] = $newStartDate;
                $updateData['subscription_expiry_date'] = $newExpiryDate;
            }
        }
            $customer->update($updateData);

        return response()->json([
                'code' => 200,
                'message' => 'Customer updated successfully ',
                'data' => [
                    'id'=> $customer->id,
                    'name'=> $customer->user->first_name.' '.$customer->user->last_name,
                    'subscription_start_date' => $customer->subscription_start_date->toDateString(),
                    'subscription_expiry_date' => $customer->subscription_expiry_date->toDateString(),
                ]
            ]
            , 200);
    }


        public function getCustomerByStatus($request){
        if ($request == "all") {
            $customers = Customer::all();
        }
        else{
            $customers = Customer::where('subscription_status', $request)->get();
        if ($customers->isEmpty()) {
            return response()->json([
                'message' => 'No Customers found.',
            ], 404);
        }
}
        $allCustomer = $customers->map(function ($customer) {
            return [
                'id' => $customer->id,
                'name' => $customer->user->first_name . ' ' . $customer->user->last_name,
                'area' => $customer->area->name,
                'address' => $customer->address,
                'subscription_start_date' => $customer->subscription_start_date->toDateString(),
                'subscription_expiry_date' => $customer->subscription_expiry_date->toDateString(),
                'subscription_status' => $customer->subscription_status,

            ];
        });

            return response()->json([
                'code'=>200,
                'message' => 'Customers by Status ',
                'data' => $allCustomer,
            ]);
        }
    public function getCustomer($id)
    {
        $customer = Customer::find($id);

        if (!$customer) {
            return response()->json([
                'code'=>404,
                'message' => 'Customer not found',
            ]);
        }

        $customerMap = [
            'id' => $customer->id,
            'name' => $customer->user->first_name . ' ' . $customer->user->last_name,
            'driverName' => $customer->area->driver_id->name,                              ///////////////
            'address' => $customer->address,
            'subscription_start_date' => optional($customer->subscription_start_date)->toDateString(),
            'subscription_expiry_date' => optional($customer->subscription_expiry_date)->toDateString(),
            'subscription_status' => $customer->subscription_status,

        ];


        return response()->json([
            'code' => 200,
            'message' => 'This is Customer',
            'data' => [
                'customer' => $customerMap,
            ]
        ], 200);
    }
}
