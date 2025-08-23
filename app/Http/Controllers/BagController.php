<?php

namespace App\Http\Controllers;

use App\Models\Bag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class BagController extends Controller
{
    public function addBag(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'quantity' => 'required|integer|min:1'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'code' => 422,
                'message' => $validator->errors()->first(),
            ],422);}

        $quantity = $request->input('quantity');

        $createdBags = [];

        for ($i = 0; $i < $quantity; $i++) {
            $bag = Bag::create([
                'status' => 'available'
            ]);
            $createdBags[] = [
                'bag_id' => $bag->bag_id,
                'status' => $bag->status
            ];
        }
        return response()->json([
            'code'=>201,
            'message' => "$quantity bag(s) created successfully.",
        ],201);
    }

    public function deleteBag($id){

        $bag = Bag::find($id);

        if (!$bag) {
            return response()->json([
                'code'=>404,
                'message' => 'Bag not found.',
                'data'=>[]
            ],404);
        }
        if ($bag->customer_id !== null) {
            return response()->json([
                'code'=>403,
                'message' => 'Cannot delete bag. It is assigned to a customer.',
            ],403);
        }

        $bag->delete();

        return response()->json([
            'code'=>200,
            'message' => 'Bag deleted successfully.',
        ],200);
    }

    public function getBagsByStatus($request)
    {
        if ($request == "all") {
            $bags = Bag::all();
        } else {
            $bags = Bag::where('status', $request)
                ->orWhere('last_update_at', $request)
                ->get();

        }
        $allBags = $bags->map(function ($bag) {
            return [
                'id' => $bag->id,
                'status' => $bag->status,
                'customer' => $bag->customer->user->first_name. ' ' . $bag->customer->user->last_name,
                'qr_code_path' => $bag->qr_code_path,
                'last_update_at' => $bag->last_update_at,

            ];
        });
        if ($allBags->isEmpty()) {
            return response()->json([
                'code'=>404,
                'message' => 'Not Found Bags',
                'data'=>[]
            ], 404);
        }
        return response()->json([
                'code' => 200,
                'message' => 'Bags retrieved successfully.',
                'data' => [
                    'meal' => $allBags,
                ]
            ]
            , 200);

    }

    public function searchBagById($bagId)
    {
        $bag = Bag::where('bag_id', $bagId)
            ->first();

        if (!$bag) {
            return response()->json([
                'code'=>404,
                'message' => 'The bag is not exist',
                'data'=>[]
            ],404);
        }
        return response()->json([
            'code'=>200,
            'data' => $bag
        ],200);
    }

}
