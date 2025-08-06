<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Scan_Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReportController extends Controller
{
    public function report(Request $request)
    {
            $validator = Validator::make($request->all(), [
                'date' => 'date',
                'time_from' => 'time',
                'time_to' => 'time',
                'status' => 'string',
                'bag_id' => 'integer|exists:bags,id',
                'user_id' => 'integer|exists:users,id',
                ],
                [
                'bag_id.exists' => 'Bag is not exist in the system',
                    'user_id.exists' => 'User is not exist in the system'
                ]
            );



            if ($request->date!= null && $request->time_from !=null && $request->time_to !=null && $request->status !=null)
            {
                $report = Scan_Log::where('date', $request->date )->where('status', $request->status)
                ->whereBetween('time',[$request->time_from,$request->time_to])->get();
            }
           elseif ($request->date != null && $request->time_from !=null && $request->time_to !=null && $request->user_id !=null)
            {
                $report = Scan_Log::where('date', $request->date )->where('user_id', $request->user_id)
                    ->whereBetween('time',[$request->time_from,$request->time_to])->get();
            }
            else
            {
                $report = Scan_Log::all();

            }

        $reports = $report->map(function ($repo) {
            return [
                'id' => $repo->id,
                'user' => $repo->user->first_name . ' ' . $repo->user->last_name,
                'bag' => $repo->bag->id,
                'status' => $repo->status,
                'date' => \Carbon\Carbon::parse($repo->date)->toDateString(),
                'time' => \Carbon\Carbon::parse($repo->time)->toTimeString(),
            ];
        });

        if ($reports->isEmpty()) {
            return response()->json([
                'code'=>404,
                'message' => 'Not Found Scans',
                'data'=>[]
            ], 404);
        }
        return response()->json([
                'code' => 200,
                'message' => 'Report',
                'data' => [
                    'meal' => $reports,
                ]
            ]
            , 200);
    }
}
