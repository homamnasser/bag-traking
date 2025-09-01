<?php

namespace App\Http\Controllers;

use App\Models\Bag;
use App\Models\Customer;
use App\Models\DriverAreaService;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
            'phone' => ['required', 'phone:AUTO', 'unique:users,phone'],
            'password' => 'required|string|min:6|confirmed',
            'email' => 'required|email|unique:users,email',
            'image' => ['image', 'mimes:jpeg,png,jpg,gif', 'max:512'],
            'area_id' => 'required|exists:driver_area_services,id',
            'address' => 'required|string',
            'subscription_status' => 'required|in:0,1',

        ], ['phone.unique' => 'the phone already exist',
            'phone.phone' => 'please enter a valid  phone number',
            'area_id.exists' => 'Area not found',
            'email.email' => 'Please enter a valid email address in the format name@gmail.com'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'code' => 422,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        try {
            $result=DB::transaction(function () use ($request, &$qrUrls) {
                $images = null;
                if ($request->hasFile('image')) {
                    $file = $request->file('image');
                    $fileName = 'images/' . 'images_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                    Storage::disk('public')->put($fileName, file_get_contents($file));
                    $image = 'storage/' . $fileName;
                    $images = asset($image);
                }
                $bags = Bag::where('status', 'available')->take(2)->get();
                if ($bags->count() < 2) {
                    throw new \Exception('Not enough available bags to assign to this customer.');
                }
                $user = User::create([
                    'first_name' => $request->first_name,
                    'last_name' => $request->last_name,
                    'phone' => $request->phone,
                    'password' => Hash::make($request->password),
                    'email' => $request->email,
                    'image' => $images,
                    'is_active' => true
                ]);

                $user->assignRole('customer');

                $subscriptionStartDate = Carbon::now();
               // $subscriptionExpiryDate = $subscriptionStartDate->copy()->addMonth();
                $subscriptionExpiryDate = Carbon::today();  //test

                $customer = Customer::create([
                    'user_id' => $user->id,
                    'area_id' => $request->area_id,
                    'address' => $request->address,
                    'subscription_start_date' => $subscriptionStartDate,
                    'subscription_expiry_date' => $subscriptionExpiryDate,
                    'subscription_status' => $request->subscription_status
                ]);

                $qrUrls = [];
                foreach ($bags as $bag) {
                    $qrUrls[] = $this->generateBagQr($bag, $user, $customer);
                }
                return [
                    'user' => $user,
                    'customer' => $customer,
                    'bags' => $bags,
                    'qrUrls' => $qrUrls
                ];
            });
                return response()->json([
                    'code' => 201,
                    'message' => 'Customer  added successfully ',
                    'data' => [
                        'id' => $result['customer']->id,
                        'name' => $result['user']->first_name . ' ' . $result['user']->last_name,
                        'phone' => $result['user']->phone,
                        'email' => $result['user']->email,
                        'role' => 'customer',
                        'area' => $result['customer']->area->name,
                        'address' => $result['customer']->address,
                        'subscription_start_date' => $result['customer']->subscription_start_date->toDateString(),
                        'subscription_expiry_date' => $result['customer']->subscription_expiry_date->toDateString(),
                        'subscription_status' => $result['customer']->subscription_status,
                        'bags_assigned' => $result['bags']->pluck('bag_id'),
                        'qr_urls' => $result['qrUrls'],
                    ]
                ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'code' => 422,
                'message' => $e->getMessage()
            ], 422);
        }
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
            'phone' => ['string','unique:users,phone','phone:AUTO'],
            'email'=> 'email|unique:users,email',
            'is_active'  => 'boolean',
            'password'   => 'string|min:6|confirmed',
            'image' => ['image','mimes:jpeg,png,jpg,gif','max:512'],
            'area_id' => 'exists:driver_area_services,id',
            'address' => 'string',
            'old_bag_id' => 'exists:bags,bag_id'
            ],[
                'phone.unique' => 'The phone already exist',
                'phone.phone' =>'Please enter a valid  phone number',
                'old_bag_id.exists'=>'There is no bag in this ID in the system',
                'email.email' => 'Please enter a valid email address in the format name@gmail.com'
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
                'last_update_at'=>'atStore'
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
            ],422);
        }

        $newStatus = (int) $request->subscription_status;

        $updateData = [
            'subscription_status' => $newStatus,
        ];

        if ($newStatus == 1) {
            if (!($customer->subscription_status == 0
                && $customer->subscription_expiry_date
                && $customer->subscription_expiry_date->gt(Carbon::today())
            )) {
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
        }

        $customer->update($updateData);

        $customer->user->update([
            'is_active' => $newStatus,
        ]);

        return response()->json([
            'code' => 200,
            'message' => 'Customer updated successfully ',
            'data' => [
                'id'=> $customer->id,
                'name'=> $customer->user->first_name.' '.$customer->user->last_name,
                'subscription_start_date' => optional($customer->subscription_start_date)->toDateString(),
                'subscription_expiry_date' => optional($customer->subscription_expiry_date)->toDateString(),
                'is_active' => $customer->user->is_active,
            ]
        ],200);
    }



    public function getCustomerByStatus($request)
    {

            $validator = Validator::make(['status' => $request], [
                'status' => 'required|in:0,1,all'
            ], [
                'status.in' => 'The status value must be 0, 1, or all.'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'code' => 422,
                    'message' => $validator->errors()->first(),
                ], 422);
            }

            $query = Customer::with(['user', 'area.driver', 'bags']);

            if ($request != "all") {
                $query->whereHas('user', function ($q) use ($request) {
                    $q->where('is_active', $request);
                });
            }

            $customers = $query->get();

            if ($customers->isEmpty()) {
                return response()->json([
                    'code' => 404,
                    'message' => 'Customers not found.',
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
                    'area' => $customer->area->name,
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
            $customer = Customer::with('user', 'area.driver', 'bags')->find($id);

            if (!$customer) {
                return response()->json([
                    'code' => 404,
                    'message' => 'Customer not found',
                    'data' => []
                ], 404);
            }

            $bags = $customer->bags;

            $qrUrls = $bags->map(function ($bag) {
                return $bag->qr_code_path ? asset('storage/' . $bag->qr_code_path) : null;
            });


            $customerMap = [
                'id' => $customer->id,
                'full_name' => $customer->user->first_name . ' ' . $customer->user->last_name,
                'phone' => $customer->user->phone,
                'email' => $customer->user->email,
                'is_active' => $customer->user->is_active,
                'image' => $customer->user->image,
                'area' => $customer->area->name,
                'driverName' => $customer->area->driver->first_name . '' . $customer->area->driver->last_name,                              ///////////////
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
            ], 200);
        }

        public function getCustomerInfo()
        {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'code' => 401,
                    'message' => 'Unauthenticated. Please log in.'
                ], 401);
            }

            if (!$user->hasRole('customer')) {
                return response()->json([
                    'code' => 403,
                    'message' => 'Access denied. Only customers can access this resource'
                ], 403);
            }

            $customer = Customer::with(['area.driver', 'bags'])
                ->where('user_id', $user->id)
                ->first();

            if (!$customer) {
                return response()->json([
                    'code' => 404,
                    'message' => 'Customer profile not found'
                ], 404);
            }

            return response()->json([
                'code' => 200,
                'message' => 'My info',
                'data' => [
                    'id' => $customer->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'phone' => $user->phone,
                    'email' => $user->email,
                    'role' => $user->getRoleNames()->first(),
                    'is_active' => $user->is_active,
                    'address' => $customer->address,
                    'area' => optional($customer->area)->name,
                    'driverName' => optional($customer->area->driver)->first_name . ' ' . optional($customer->area->driver)->last_name,
                    'subscription_start_date' => optional($customer->subscription_start_date)->toDateString(),
                    'subscription_expiry_date' => optional($customer->subscription_expiry_date)->toDateString(),
                    'subscription_status' => $customer->subscription_status,
                    'bags_assigned' => $customer->bags->pluck('bag_id'),
                ]
            ], 200);
        }



    public function updateInfoByCustomer(Request $request)
    {
        $user = User::findOrFail(Auth::id());

        if (!$user) {
            return response()->json([
                'code' => 401,
                'message' => 'Unauthorized',
                'data' => []
            ], 401);
        }


        $customer = Customer::where('user_id', $user->id)->first();
        if (!$customer) {
            return response()->json([
                'code' => 404,
                'message' => 'Customer not found',
                'data' => []
            ], 404);
        }


        $validator = Validator::make($request->all(), [
            'phone' => ['string', 'unique:users,phone,' . $user->id, 'phone:AUTO'],
            'email' => 'email|unique:users,email,' . $user->id
        ], [
            'phone.unique' => 'The phone already exists',
            'phone.phone' => 'Please enter a valid phone number',
            'email.unique' => 'The email already exists',
            'email.email' => 'Please enter a valid email address in the format name@gmail.com'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'code' => 422,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $dataToUpdate = $request->only(['phone','email']);
        $user->update($dataToUpdate);

        $bags = $customer->bags;


        return response()->json([
            'code' => 200,
            'message' => 'Your information has been updated successfully',
            'data' => [
                'id' => $customer->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'phone' => $user->phone,
                'email' => $user->email,
                'role' => $user->getRoleNames()->first(),
                'is_active' => $user->is_active,
                'address' => $customer->address,
                'area' => optional($customer->area)->name,
                'driverName' => optional($customer->area->driver)->first_name . ' ' . optional($customer->area->driver)->last_name,
                'subscription_start_date' => optional($customer->subscription_start_date)->toDateString(),
                'subscription_expiry_date' => optional($customer->subscription_expiry_date)->toDateString(),
                'subscription_status' => $customer->subscription_status,
                'bags_assigned' => $customer->bags->pluck('bag_id'),

            ]
        ], 200);


    }
}
