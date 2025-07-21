<?php

namespace App\Http\Controllers;

use App\Models\DriverAreaService;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DriverAreaServiceController extends Controller
{
    public function __construct()
    {

        $this->middleware('auth');
    }

    public function addArea(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'driver_id' => 'required|integer|exists:users,id',

        ],[
            'driver_id.exists'=>'driver is not exist in the system'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'code' => 422,
                'message' => $validator->errors()->first(),
            ]);}

        $driverUser = User::find($request->driver_id);

        if (!$driverUser) {
            return response()->json([
                'code'=>404,
                'message' => 'Driver user not found.'
            ]);
        }

        if (!$driverUser->hasRole('driver')) {
            return response()->json([
                'message' => 'The assigned user does not have the driver role.'
            ], 403);
        }

        $area = DriverAreaService::create([
            'name' => $request->name,
            'driver_id' => $request->driver_id,
        ]);

        return response()->json([
                'code' => 201,
                'message' => 'area added successfully ',
                'data' => [
                    'id'=>$area->id,
                    'area_name' => $area->name,
                    'diver_name' => $area->driver->first_name . ' ' . $area->driver->last_name,                ]
            ]
            , 201);
    }


    public function updateArea(Request $request, $id)
    {
        $area= DriverAreaService::find($id);

        if (!$area) {
            return response()->json([
                'code'=>200,
                'message' => 'Area not found',
            ]);
        }
        $validator = Validator::make($request->all(), [
            'name' => 'string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'code' => 422,
                'message' => $validator->errors()->first(),
            ]);}



        $area->update($request->all());

        return response()->json([
                'code' => 200,
                'message' => 'Area updated successfully ',
                'data' => [
                    'id'=>$area->id,
                    'area_name' => $area->name,
                    'diver_name' => $area->driver->first_name . ' ' . $area->driver->last_name,


                ]
            ]
            , 200);
    }
    public function deleteArea($id)
    {
        $area = DriverAreaService::find($id);

        if (!$area) {
            return response()->json([
                'message' => 'Area not found',
            ], 200);
        }


        $area->delete();

        return response()->json([
            'code' => 200,
            'message' => 'Area deleted successfully ',
        ]);

    }
//By All Or Name Or Driver
    public function getAllAreas($request)
    {
        $areas = [];

        if ( $request == "all") {
            $data = DriverAreaService::all();
        }
        else
        {
            $data = DriverAreaService::where('name', $request)
                ->orWhereHas('driver', function ($query) use ($request) {
                    $query->where('first_name',  $request )
                        ->orWhere('last_name',  $request);
                })
                ->get();

        }
        foreach ($data as $data1) {

            array_push($areas, [
                'id'=>$data1->id,
                'name' => $data1->name,
                'diver_name' => $data1->driver->first_name . ' ' . $data1->driver->last_name,
            ]);
        }

        if ($data->isEmpty()) {
            return response()->json([
                'message' => 'Not Found Areas',
            ], 200);
        }
        return response()->json([
                'code' => 200,
                'message' => 'All Areas',
                'result' => [
                    'area' => $areas,
                ]
            ]
            , 200);
    }
    public function getArea($id)
    {
        $area = DriverAreaService::find($id);

        if (!$area) {
            return response()->json([
                'message' => 'Area not found',
            ], 404);
        }
        return response()->json([
                'code' => 200,
                'message' => 'This is Area ',
                'result' => [
                    'id'=>$area->id,
                    'area_name' => $area->name,
                    'diver_name' => $area->driver->first_name . ' ' . $area->driver->last_name,
                ]
            ]
            , 200);
    }

}
