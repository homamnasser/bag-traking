<?php

namespace App\Http\Controllers;

use App\Models\Bag;
use Illuminate\Http\Request;
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
            ]);}

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
        ]);
    }

    public function deleteBag($id){

        $bag = Bag::find($id);

        if (!$bag) {
            return response()->json([
                'code'=>404,
                'message' => 'Bag not found.',
                'result'=>[]
            ]);
        }
        if ($bag->customer_id !== null) {
            return response()->json([
                'code'=>400,
                'message' => 'Cannot delete bag. It is assigned to a customer.',
            ]);
        }
        $bag->delete();
        return response()->json([
            'code'=>200,
            'message' => 'Bag deleted successfully.',
        ]);
    }

    public function getAllBags(){
        $bags = Bag::get();

        return response()->json([
            'code'=>200,
            'data' => $bags
        ]);
    }
    public function getBagsByStatus($status)
    {
        if (!in_array($status, ['available', 'unavailable'])) {
            return response()->json([
                'code'=>400,
                'message' => 'Invalid status. Use available or unavailable.'
            ]);
        }
        $bags = Bag::where('status',$status)
            ->get();

        return response()->json([
            'code'=>200,
            'data' => $bags
        ]);
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
            ]);
        }
        return response()->json([
            'code'=>200,
            'data' => $bag
        ]);
    }

}
