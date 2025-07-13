<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\DriverAreaService;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CustomerController extends Controller
{
    public function addCustomer(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id|unique:customers,user_id',
            'area_id' => 'required|exists:driver_area_services,id',
            'address' => 'required|string',
            'subscription_status' => 'required|in:0,1',

        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors()->toJson(), 422);
        }
        $user = User::find($request->user_id);
        if (!$user) {
            return response()->json([
                'message' => 'User not found'
            ], 404);
        }

        if (!$user->hasRole('customer')) {
            return response()->json([
                'message' => 'The assigned user does not have the "customer" role.'
            ], 200);
        }
        $area = DriverAreaService::find($request->area_id);
        if (!$area) {
            return response()->json([
                'message' => 'Area not found'
            ], 404);
        }
        $subscriptionStartDate = Carbon::now();

        $subscriptionExpiryDate = $subscriptionStartDate->copy()->addMonth();

        $customer = Customer::create([
            'user_id' => $request->user_id,
            'area_id' => $request->area_id,
            'address' => $request->address,
            'subscription_start_date' => $subscriptionStartDate,
            'subscription_expiry_date' => $subscriptionExpiryDate,
            'subscription_status'=>$request->subscription_status
        ]);

        return response()->json([
            'message' => 'Customer  added successfully ',
            'result' => [

                    'id'=> $customer->id,
                    'name'=> $customer->user->first_name.' '.$customer->user->last_name,
                    'area'=> $customer->area->name,
                    'address'=> $customer->address,
                    'subscription_start_date' => $customer->subscription_start_date->toDateString(),
                    'subscription_expiry_date' => $customer->subscription_expiry_date->toDateString(),
                    'subscription_status'=> $customer->subscription_status
                ]
            ], 201);
    }
    public function updateCustomer(Request $request, $id)
    {
        $customer= Customer::find($id);

        if (!$customer) {
            return response()->json([
                'message' => 'Customer not found',
            ], 404);
        }
        $validator = Validator::make($request->all(), [
            'user_id' => 'exists:users,id|unique:customers,user_id',
            'area_id' => 'exists:driver_area_services,id',
            'address' => 'string'
        ]);

        if ($validator->fails()) {

            return response()->json($validator->errors()->toJson(), 422);
        }
        $user = User::find($request->user_id);

        if (!$user) {
            return response()->json([
                'message' => 'User not found'
            ], 404);
        }

        if (!$user->hasRole('customer')) {
            return response()->json([
                'message' => 'The assigned user does not have the "customer" role.'
            ], 200);
        }

        $area = DriverAreaService::find($request->area_id);
        if (!$area) {
            return response()->json([
                'message' => 'Area not found'
            ], 404  );
        }

        $customer->update($request->all());

        return response()->json([
                'message' => 'Customer updated successfully ',
                'result' => [
                    'id'=> $customer->id,
                    'name'=> $customer->user->first_name.' '.$customer->user->last_name,
                    'area'=> $customer->area->name,
                    'address'=> $customer->address,

                ]
            ]
            , 200);
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

            return response()->json($validator->errors()->toJson(), 422);
        }
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
                'message' => 'Customer updated successfully ',
                'result' => [
                    'id'=> $customer->id,
                    'name'=> $customer->user->first_name.' '.$customer->user->last_name,
                    'subscription_start_date' => $customer->subscription_start_date->toDateString(),
                    'subscription_expiry_date' => $customer->subscription_expiry_date->toDateString(),
                ]
            ]
            , 200);
    }
    public function getAllCustomers(Request $request)
    {
        $customers = Customer::all();

        if ($customers->isEmpty()) {
            return response()->json([
                'message' => 'No Customers found.',
            ], 404);
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
            'message' => 'All customers retrieved successfully.',
            'result' => $allCustomer,
        ], 200);
    }

        public function getCustomerByStatus($subscription_status){
        if ($subscription_status == "all") {
            $customers = Customer::all();
        }
        else{
            $customers = Customer::where('subscription_status', $subscription_status)->get();
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
                'message' => 'Customers by Status ',
                'result' => $allCustomer,
            ], 200);
        }
    public function getCustomer($id)
    {
        $customer = Customer::find($id);

        if (!$customer) {
            return response()->json([
                'message' => 'Customer not found',
            ], 404);
        }
        return response()->json([
                'message' => 'This is Customer ',
                'result' => [
                    'id' => $customer->id,
                    'name' => $customer->user->first_name . ' ' . $customer->user->last_name,
                    'area' => $customer->area->name,
                    'address' => $customer->address,
                    'subscription_start_date' => $customer->subscription_start_date->toDateString(),
                    'subscription_expiry_date' => $customer->subscription_expiry_date->toDateString(),
                    'subscription_status' => $customer->subscription_status,
                ]
            ]
            , 201);
    }
}
