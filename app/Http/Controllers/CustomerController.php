<?php

namespace App\Http\Controllers;

use App\Models\Bag;
use App\Models\Customer;
use App\Models\DriverAreaService;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        $QR = asset('storage/' . $fileName);

        $bag->update([
            'qr_code_path' => $fileName,
            'customer_id' => $customer->id,
            'status' => 'unavailable',
        ]);
        return $QR;
    }

    public function addCustomer(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:55',
            'last_name' => 'required|string|max:55',
            'phone' => ['required','phone:AUTO', 'unique:users,phone'],
            'password' => 'required|string|min:6|confirmed',
            'email'=> 'required|email|unique:users,email',
            'image' => ['image','mimes:jpeg,png,jpg,gif','max:512'],
            'area_id' => 'required|exists:driver_area_services,id',
            'address' => 'required|string',
            'subscription_status' => 'required|in:0,1',

        ],['phone.unique' => 'the phone already exist',
            'phone.regex' =>'please enter a valid  phone number' ,
            'area_id.exists'=>'Area not found'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'code' => 422,
                'message' => $validator->errors()->first(),
            ],422);}

        $images = null;
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $fileName = 'images/' . 'images_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            Storage::disk('public')->put($fileName, file_get_contents($file));
            $image = 'storage/' . $fileName;
            $images=asset($image);
        }
        $bags = Bag::where('status', 'available')->take(2)->get();
        if ($bags->count() < 2) {
            return response()->json([
                'code' => 422,
                'message' => 'Not enough available bags to assign to this customer.'],422);
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
                    'qr_urls' => $qrUrls,

                ]
            ],201);

    }

    public function updateCustomer(Request $request, $id)
    {
        $customer = Customer::find($id);

        if (!$customer) {
            return response()->json([
                'code'=>404,
                'message' => 'Customer not found',
                'data'=>[]
            ],404);
        }
        $user = $customer->user;

        if (!$user) {
            return response()->json([
                'code'=>404,
                'message' => 'User not found',
                'data'=>[]
            ],404);
        }

        $validator = Validator::make($request->all(), [
            'first_name' => 'string|max:55',
            'last_name'  => 'string|max:55',
            'phone' => ['string','unique:users,phone','regex:/^(\+9715[0-9]{7}|^\+[1-9]\d{7,14})$/'],
            'email'=> 'email|unique:users,email',
            'is_active'  => 'boolean',
            'password'   => 'string|min:6|confirmed',
            'image' => ['image','mimes:jpeg,png,jpg,gif','max:512'],
            'area_id' => 'exists:driver_area_services,id',
            'address' => 'string',
            'old_bag_id' => 'exists:bags,bag_id'
            ],[
                'phone.unique' => 'The phone already exist',
                'phone.regex' =>'Please enter a valid  phone number',
                'old_bag_id.exists'=>'There is no bag in this ID in the system'
            ]);

        if ($validator->fails()) {
            return response()->json([
                'code' => 422,
                'message' => $validator->errors()->first(),
            ],422);}


        $dataToUpdate = $request->only(['first_name', 'last_name', 'phone','email','is_active']);

        if ($request->filled('password')) {
            $dataToUpdate['password'] = Hash::make($request->password);
        }

            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $fileName = 'images_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('images', $fileName, 'public');
                $dataToUpdate['image'] = asset('storage/' . $path);
            }
        $user->update($dataToUpdate);

        if ($request->has('area_id')) {
            $area = DriverAreaService::find($request->area_id);
            if (!$area) {
                return response()->json([
                    'code'=>404,
                    'message' => 'Area not found',
                    'data'=>[]
                ],404);
            }
            $customer->area_id = $request->area_id;
        }
        if ($request->filled('address')) {
            $customer->address = $request->address;
        }

        $qrUrls = [];
        if ($request->has('old_bag_id')) {

            $oldBag = Bag::where('bag_id', $request->old_bag_id)
                ->where('customer_id', $customer->id)
                ->first();

            if (!$oldBag) {
                return response()->json([
                    'code'=>422,
                    'message' => 'The old bag does not belong to this customer',
                    'data'=>[]
                ],422);
            }
            $newBag = Bag::where('status', 'available')
                ->inRandomOrder()
                ->first();

            if (!$newBag) {
                return response()->json([
                    'code'=>422,
                    'message' => 'No available bags found in the system',
                    'data'=>[]
                ],422);
            }

            Storage::disk('public')->delete($oldBag->qr_code_path);
            $oldBag->update([
                'customer_id' => null,
                'status' => 'available',
                'qr_code_path' => null,
                'last_update_at'=>'at_store'
            ]);

            $qrUrl = $this->generateBagQr($newBag, $user, $customer);
            $qrUrls[$newBag->bag_id] = $qrUrl;
        }

        $customer->save();

        $bags = $customer->bags()->get();
        $qrUrls = [];
        foreach ($bags as $bag) {
            $qrUrls[$bag->bag_id] = asset('storage/' . $bag->qr_code_path);
        }

        return response()->json([
                'code' => 200,
                'message' => 'Customer updated successfully ',
                'data' => [
                    'id'=> $customer->id,
                    'name' => $user->first_name . ' ' . $user->last_name,
                    'phone' => $user->phone,
                    'image'=>$user->image,
                    'area'=> $customer->area->name,
                    'address'=> $customer->address,
                    'bags_assigned' => $bags->pluck('bag_id'),
                    'qr_urls' => $qrUrls
                ]
            ],200);
    }


    public function editStatus(Request $request, $id)
    {
        $customer= Customer::find($id);

        if (!$customer) {
            return response()->json([
                'code'=>404,
                'message' => 'Customer not found',
                'data'=>[]
            ],404);
        }
        $validator = Validator::make($request->all(), [
            'subscription_status' => 'required|in:0,1',

        ]);

        if ($validator->fails()) {
            return response()->json([
                'code' => 422,
                'message' => $validator->errors()->first(),
            ],422);}

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
            ],200);
    }


        public function getCustomerByStatus($request){
            $query = Customer::with(['user', 'area.driver', 'bags']);

            if ($request != "all") {
                $query->where('subscription_status', $request);
            }

            $customers = $query->get();

            if ($customers->isEmpty()) {
                return response()->json([
                    'code' => 404,
                    'message' => 'No Customers found.',
                    'data' => []
                ], 404);
            }

            $allCustomer = $customers->map(function ($customer) {
                $bags = $customer->bags;

                $qrUrls = $bags->map(function ($bag) {
                    return $bag->qr_code_path ? asset('storage/' . $bag->qr_code_path) : null;
                });

                return [
                    'id' => $customer->id,
                    'name' => $customer->user->first_name . ' ' . $customer->user->last_name,
                    'phone' => $customer->user->phone,
                    'email' => $customer->user->email,
                    'is_active' => $customer->user->is_active,
                    'image' => $customer->user->image,
                    'address' => $customer->address,
                    'area' => $customer->area->name ,
                    'driverName' => optional($customer->area->driver)->first_name . ' ' . optional($customer->area->driver)->last_name,
                    'subscription_start_date' => optional($customer->subscription_start_date)->toDateString(),
                    'subscription_expiry_date' => optional($customer->subscription_expiry_date)->toDateString(),
                    'subscription_status' => $customer->subscription_status,
                    'bags_assigned' => $bags->pluck('bag_id'),
                    'qr_urls' => $qrUrls,
                ];
            });

            return response()->json([
                'code' => 200,
                'message' => 'Customers by Status',
                'data' => $allCustomer
            ], 200);
        }

    public function getCustomer($id)
    {
        $customer = Customer::with('user', 'area.driver','bags')->find($id);

        $bags = $customer->bags;

        $qrUrls = $bags->map(function($bag) {
            return $bag->qr_code_path ? asset('storage/' . $bag->qr_code_path) : null;
        });

        if (!$customer) {
            return response()->json([
                'code'=>404,
                'message' => 'Customer not found',
                'data'=>[]
            ],404);
        }

        $customerMap = [
            'id' => $customer->id,
            'full_name' => $customer->user->first_name . ' ' . $customer->user->last_name,
            'phone'=>$customer->user->phone,
            'email'=>$customer->user->email,
            'is_active'=>$customer->user->is_active,
            'image'=>$customer->user->image,
            'area' => $customer->area->name,
            'driverName' => $customer->area->driver->first_name.''.$customer->area->driver->last_name,                              ///////////////
            'address' => $customer->address,
            'subscription_start_date' => optional($customer->subscription_start_date)->toDateString(),
            'subscription_expiry_date' => optional($customer->subscription_expiry_date)->toDateString(),
            'subscription_status' => $customer->subscription_status,
            'bags_assigned' => $bags->pluck('bag_id'),
            'qr_urls' => $qrUrls
        ];


        return response()->json([
            'code' => 200,
            'message' => 'This is Customer',
            'data' => [
                'customer' => $customerMap,
            ]
        ],200);
    }
}
