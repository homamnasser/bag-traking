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

        ]);
        $driverUser = User::find($request->driver_id);

        if (!$driverUser) {
            return response()->json([
                'message' => 'Driver user not found.'
            ], 404);
        }

        if (!$driverUser->hasRole('driver')) {
            return response()->json([
                'message' => 'The assigned user does not have the "driver" role.'
            ], 403);
        }

        $area = DriverAreaService::create([
            'name' => $request->name,
            'driver_id' => $request->driver_id,
        ]);

        return response()->json([
                'message' => 'area added successfully ',
                'result' => [
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
                'message' => 'Area not found',
            ], 200);
        }
        $validator = Validator::make($request->all(), [
            'name' => 'string',
        ]);

        if ($validator->fails()) {

            return response()->json($validator->errors()->toJson(), 400);
        }




        $area->update($request->all());

        return response()->json([
                'message' => 'Area updated successfully ',
                'result' => [
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
            'message' => 'Area deleted successfully ',
        ], 200);

    }

    public function getAllAreas()
    {
        $areas = [];

        $data = DriverAreaService::all();
        foreach ($data as $data1) {

            array_push($areas, [
                'id'=>$data1->id,
                'name' => $data1->name,
                'driver_id' => $data1->driver_id,
                'diver_name' => $data1->driver->first_name . ' ' . $data1->driver->last_name,
            ]);
        }

        if ($data->isEmpty()) {
            return response()->json([
                'message' => 'Not Found Areas',
            ], 200);
        }
        return response()->json([
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
                'message' => 'This is Area ',
                'result' => [
                    'id'=>$area->id,
                    'area_name' => $area->name,
                    'diver_name' => $area->driver->first_name . ' ' . $area->driver->last_name,
                ]
            ]
            , 201);
    }

}
