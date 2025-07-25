<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Customer_Food_Preferences;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class CustomerFoodPreferencesController extends Controller
{
    public function __construct()
    {

        $this->middleware('auth');
    }
    public function addFoodPrefer(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'code'=>401,
                'message' => 'Unauthenticated. Please log in.'
            ], 401);
        }

        $customer = Customer::where('user_id', $user->id)->first();


        if (!$customer) {
            return response()->json([
                'code'=>404,
                'message' => 'No customer profile found for the authenticated user. Please create a customer profile first.',
                'data'=>[]
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'preferred_food_type' => 'required|string',
            'allergies'           => 'required|string',
            'health_conditions'   => 'required|string',
            'dietary_system'      => 'required|string',
            'daily_calorie_needs' => 'required|integer',
        ]);


        if ($validator->fails()) {
            return response()->json([
                'code' => 422,
                'message' => $validator->errors()->first(),
            ],422);}

        $foodPrefer = Customer_Food_Preferences::create([
            'customer_id'         => $customer->id,
            'preferred_food_type' => $request->preferred_food_type,
            'allergies'           => $request->allergies,
            'health_conditions'   => $request->health_conditions,
            'dietary_system'      => $request->dietary_system,
            'daily_calorie_needs' => $request->daily_calorie_needs,
        ]);

        return response()->json([
            'code' => 201,
            'message' => 'Customer food preference added successfully.',
            'data' => [
                'id'                  => $foodPrefer->id,
                'customer_id'         => $foodPrefer->customer_id,
                'name'                => $user->first_name . ' ' . $user->last_name,
                'preferred_food_type' => $foodPrefer->preferred_food_type,
                'allergies'           => $foodPrefer->allergies,
                'health_conditions'   => $foodPrefer->health_conditions,
                'dietary_system'      => $foodPrefer->dietary_system,
                'daily_calorie_needs' => $foodPrefer->daily_calorie_needs,
            ]
        ], 201);
    }
    public function updateFoodPrefer(Request $request )
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'code'=>401,
                'message' => 'Unauthenticated. Please log in.'
            ], 401);
        }

        $customer = Customer::where('user_id', $user->id)->first();
        if (!$customer) {
            return response()->json([
                'code'=>404,
                'message' => 'No customer profile found for the authenticated user. Cannot update food preferences.',
                'data'=>[]
            ], 404);
        }

        $foodPrefer = Customer_Food_Preferences::where('customer_id', $customer->id)->first();

        if (!$foodPrefer) {
            return response()->json([
                'code'=>404,
                'message' => 'Food preferences not found for this customer. Please add them first.',
                'data'=>[]
            ], 404); // 404 Not Found
        }

        $validator = Validator::make($request->all(), [
            'preferred_food_type' => 'string',
            'allergies'           => 'string',
            'health_conditions'   => 'string',
            'dietary_system'      => 'string',
            'daily_calorie_needs' => 'integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'code' => 422,
                'message' => $validator->errors()->first(),
            ],422);}

        $foodPrefer->update($request->only([
            'preferred_food_type',
            'allergies',
            'health_conditions',
            'dietary_system',
            'daily_calorie_needs',
        ]));


        return response()->json([
            'code' => 200,
            'message' => 'Customer food preference updated successfully.',
            'data' => [
                'id'                  => $foodPrefer->id,
                'customer_id'         => $foodPrefer->customer_id,
                'name'      => $user->first_name . ' ' . $user->last_name,
                'preferred_food_type' => $foodPrefer->preferred_food_type,
                'allergies'           => $foodPrefer->allergies,
                'health_conditions'   => $foodPrefer->health_conditions,
                'dietary_system'      => $foodPrefer->dietary_system,
                'daily_calorie_needs' => $foodPrefer->daily_calorie_needs,
            ]
        ], 200);
    }
    public function deleteFoodPrefer()
    {

        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'code'=>401,
                'message' => 'Unauthenticated. Please log in.'
            ], 401);
        }

        $customer = Customer::where('user_id', $user->id)->first();


        if (!$customer) {
            return response()->json([
                'code'=>404,
                'message' => 'No customer profile found for the authenticated user. Please create a customer profile first.',
                'data'=>[]
            ], 404);
        }

        $foodPrefer = Customer_Food_Preferences::where('customer_id', $customer->id)->first();

        if (!$foodPrefer) {
            return response()->json([
                'code'=>404,
                'message' => 'Food preferences not found for this customer.',
                'data'=>[]
            ], 404); // 404 Not Found
        }
        $foodPrefer->delete();

        return response()->json([
            'code' => 200,
            'message' => 'Prefer deleted successfully ',
        ], 200);
    }

    public function getAllFoodPreferences()
    {
        $preferFoods = Customer_Food_Preferences::all();

        if ($preferFoods->isEmpty()) {
            return response()->json([
                'code'=>404,
                'message' => 'No Food found.',
                'data'=>[]
            ], 404);
        }

        $allPreferFoods = $preferFoods->map(function ($foodPrefer) {
            return [
                'id'                  => $foodPrefer->id,
                'customer_id'         => $foodPrefer->customer_id,
                'name'      => $foodPrefer->customer->user->first_name . ' ' . $foodPrefer->customer->user->last_name,
                'preferred_food_type' => $foodPrefer->preferred_food_type,
                'allergies'           => $foodPrefer->allergies,
                'health_conditions'   => $foodPrefer->health_conditions,
                'dietary_system'      => $foodPrefer->dietary_system,
                'daily_calorie_needs' => $foodPrefer->daily_calorie_needs,

            ];
        });

        return response()->json([
            'code' => 200,
            'message' => 'All Food Prefer retrieved successfully.',
            'data' =>$allPreferFoods ,
        ], 200);
    }

    public function getCustomerFoodPreferences()
    {

        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'code'=>401,
                'message' => 'Unauthenticated. Please log in.'
            ], 401);
        }

        $customer = Customer::where('user_id', $user->id)->first();


        if (!$customer) {
            return response()->json([
                'code'=>404,
                'message' => 'No customer profile found for the authenticated user. Please create a customer profile first.',
                'data'=>[]
            ], 404);
        }

        $foodPrefer = Customer_Food_Preferences::where('customer_id', $customer->id)->first();

        if (!$foodPrefer) {
            return response()->json([
                'code'=>404,
                'message' => 'Food preferences not found for this customer.',
                'data'=>[]
            ], 404);
        }



        return response()->json([
            'code' => 200,
            'message' => 'Food Prefer retrieved successfully.',
            'data' =>
            [
                'id'                  => $foodPrefer->id,
                'customer_id'         => $foodPrefer->customer_id,
                'name'                => $foodPrefer->customer->user->first_name . ' ' . $foodPrefer->customer->user->last_name,
                'preferred_food_type' => $foodPrefer->preferred_food_type,
                'allergies'           => $foodPrefer->allergies,
                'health_conditions'   => $foodPrefer->health_conditions,
                'dietary_system'      => $foodPrefer->dietary_system,
                'daily_calorie_needs' => $foodPrefer->daily_calorie_needs,
            ],
        ], 200);
    }

}
