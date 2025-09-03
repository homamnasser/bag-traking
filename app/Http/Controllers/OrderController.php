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
        $user = Auth::user();

        $customer = $user->customer;

        if (!$customer || $customer->subscription_status == 0) {
            return response()->json([
                'code' => 403,
                'message' => 'Your subscription is inactive. You cannot place an order.',
            ], 403);
        }
        $today = Carbon::today()->toDateString();

        $existingOrdersCount = Order::where('user_id', $user->id)
            ->whereDate('order_date', $today)
            ->count();

        if ($existingOrdersCount >= 1) {
            return response()->json([
                'code' => 422,
                'message' => 'You already have an order today please try tomorrow.',
            ], 422);
        }
        $validator = Validator::make($request->all(), [
            'meal1_id' => 'required|integer|exists:meals,id',
            'meal2_id' => 'nullable|integer|exists:meals,id',

        ], [

            'meal1_id.*.exists' => 'The specified meal does not exist in the system.',
            'meal2_id.*.exists' => 'The specified meal does not exist in the system.'

        ]);
        if ($validator->fails()) {
            return response()->json([
                'code' => 422,
                'message' => $validator->errors()->first(),
            ], 422);
        }
        $order = Order::create([
                'user_id'=>$user->id,
                'meal1_id' => $request->meal1_id,
                'meal2_id' => $request->meal2_id,
                'order_date' => $today,

            ]
        );


        return response()->json([
            'code' => 200,
            'message' => 'Order added successfully ',
            'data' => [
                'id' => $order->id,
                'meal1' => $order->meal1?->name,
                'meal2' => $order->meal2?->name,

            ]
        ], 201);

    }
        public function updateOrder(Request $request, $id)
    {
        $order = Order::find($id);

        $currentTime = Carbon::now();
        $limitHour = 14 ;
        $today = Carbon::today()->toDateString();

        if (!$order) {
            return response()->json([
                'code' => 404,
                'message' => 'Order not found',
                'data' => []
            ], 404);
        }

        if ($order->order_date != $today) {
            return response()->json([
                'code' => 403,
                'message' => 'This order cannot be updated as its date is not today.',
                'data' => []
            ], 403);
        }

        if ($currentTime->hour >= $limitHour) {
            return response()->json([
                'code' => 403,
                'message' => 'Order cannot be updated after 5 PM.',
                'data' => []
            ], 403);
        }



        $validator = Validator::make($request->all(), [
            'meal1_id' => 'integer|exists:meals,id',
            'meal2_id' => 'integer|exists:meals,id',


        ], [
            'meal1_id.exists' => 'Meal is not exist in the system',
            'meal2_id.exists' => 'Meal is not exist in the system'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'code' => 422,
                'message' => $validator->errors()->first(),
            ], 422);
        }


        $order->update($request->all());

        return response()->json([
            'code' => 200,
            'message' => 'Order updated successfully ',
            'data' => [
                'id' => $order->id,
                'meal1' => $order->meal1?->name,
                'meal2' => $order->meal2?->name,

            ]
        ], 200);
    }

    public function deleteOrder($id)
    {
        $currentTime = Carbon::now();
        $limitHour = 14;
        $order = Order::find($id);
        $today = Carbon::today()->toDateString();

        if (!$order) {
            return response()->json([
                'code' => 404,
                'message' => 'Order not found',
                'data' => []
            ], 404);
        }
        if ($order->order_date != $today) {
            return response()->json([
                'code' => 422,
                'message' => 'This order cannot be deleted as its date is not today.',
                'data' => []
            ], 422);
        }
        if ($currentTime->hour >= $limitHour) {
            return response()->json([
                'code' => 422,
                'message' => 'Order cannot be deleted after 5 PM.',
                'data' => []
            ], 422);
        }



        $order->delete();

        return response()->json([
            'code' => 200,
            'message' => 'Order deleted successfully ',
        ], 200);

    }

    public function getOrder($id)
    {
        $order = Order::find($id);

        if (!$order) {
            return response()->json([
                'code' => 404,
                'message' => 'Order not found',
                'data' => []
            ], 404);
        }

        return response()->json([
            'code' => 200,
            'message' => 'This is Order',
            'result' => [
                'id' => $order->id,
                'meal1' => $order->meal1?->name,
                'meal2' => $order->meal2?->name,
                'customer_name' => $order->user->first_name . ' ' . $order->user->last_name,
                'order_date' => \Carbon\Carbon::parse($order->order_date)->toDateString(),
            ]
        ], 200);
    }

    public function getMyOrders()
    {
        $today = Carbon::today()->toDateString();

        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'code' => 401,
                'message' => 'Unauthenticated. Please log in.'
            ], 401);
        }
        $isBeforeTwoPm = Carbon::now()->lessThan(Carbon::parse('14:00'));
        $orders = Order::where('user_id', $user->id)->get();


        if ($orders->isEmpty()) {
            return response()->json([
                'code' => 200,
                'message' => 'There is no orders  yet.',
                'data' => []
            ], 200);
        }

        $myOrders = $orders->map(function ($order) use ($isBeforeTwoPm, $today) {
            $status = ($order->order_date == $today) ? 'In preparation' : 'Delivered';
            $canBeEdited = ($order->order_date == $today) && $isBeforeTwoPm;
            return [
                'id' => $order->id,
                'meal1' => $order->meal1?->name,
                'meal2' => $order->meal2?->name,
                'order_date' => \Carbon\Carbon::parse($order->order_date)->toDateString(),
                'can_be_edited' => $canBeEdited,
                'status' => $status,
            ];
        });

        return response()->json(
            [
                'code' => 200,
                'message' => 'Orders ',
                'data' => $myOrders

            ]
            , 200);

    }

    public function getTodayOrders()
    {
        $today = Carbon::today()->toDateString();

        $orders = Order::whereDate('order_date', $today)->get();

        if ($orders->isEmpty()) {
            return response()->json([
                'code' => 404,
                'message' => 'No orders found for today.',
                'data' => []
            ], 404);
        }

        $allOrders = $orders->map(function ($order) {
            return [
                'id' => $order->id,
                'meal1' => $order->meal1->name ,
                'meal2' => $order->meal2?->name,
                'customer_name' => $order->user->first_name . ' ' . $order->user->last_name,
                'order_date' => \Carbon\Carbon::parse($order->order_date)->toDateString(),
            ];
        });

        return response()->json([
            'code' => 200,
            'message' => 'Todays orders retrieved successfully.',
            'data' => $allOrders,
        ], 200);
    }

}
