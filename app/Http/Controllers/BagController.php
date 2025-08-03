<?php

namespace App\Http\Controllers;

use App\Models\Bag;
use Illuminate\Http\Request;
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

    public function getAllBags(){
        $bags = Bag::get();

        return response()->json([
            'code'=>200,
            'data' => $bags
        ],200);
    }
    public function getBagsByStatus($status)
    {
        if (!in_array($status, ['available', 'unavailable'])) {
            return response()->json([
                'code'=>400,
                'message' => 'Invalid status. Use available or unavailable.'
            ],400);
        }
        $bags = Bag::where('status',$status)
            ->get();

        return response()->json([
            'code'=>200,
            'data' => $bags
        ],200);
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
