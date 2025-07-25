<?php

namespace App\Http\Controllers;

use App\Models\Meal;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    public function addOrder(Request $request)
    {
        $user=Auth::user();
        $existingOrdersCount = Order::where('user_id', $user->id)->count();

        if ($existingOrdersCount >= 2) {
            return response()->json([
                'code' => 422,
                'message' => 'You have reached the maximum limit of two orders per user.',
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'meal_id' => 'required|integer|exists:meals,id',

        ],[
            'meal_id.exists'=>'Meal is not exist in the system'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'code' => 422,
                'message' => $validator->errors()->first(),
            ],422);}


        $order = Order::create([
            'user_id' => $user->id,
            'meal_id' => $request->meal_id,
            'order_date'=>Carbon::now()
        ]);

        return response()->json([
                'code' => 201,
                'message' => 'order added successfully ',
                'data' => [
                    'id'=>$order->id,
                    'meal' => $order->meal->name,
                    'order_date' => $order->order_date,                ]
            ]
            , 201);
    }

}
