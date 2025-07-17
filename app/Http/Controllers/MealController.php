<?php

namespace App\Http\Controllers;

use App\Models\Meal;
use App\Traits\PhotoTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MealController extends Controller
{
    use PhotoTrait;

    public function addMeal(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'description' => 'required|string',
            'ingredients' => 'required|string',
            'meal_type' => 'required|string',
            "is_active" => 'required|boolean',
            'imgs'=> 'required',
            'imgs.*' => [ 'image', 'mimes:jpeg,png,jpg,gif', 'max:512'],
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors()->toJson(), 400);
        }
        $images = $this->upload($request->imgs);

        $meal = Meal::create([
                'name' => $request->name,
                'description' => $request->description,
                'ingredients' => $request->ingredients,
                'meal_type' => $request->meal_type,
                "is_active" => $request->is_active,
                'imgs' => $images,
            ]
        );


        return response()->json([
            'code' => 201,
            'message' => 'Meal added successfully ',
            'result' => [
                'id' => $meal->id,
                'name' => $meal->name,
                'description' => $meal->description,
                'ingredients' => $meal->ingredients,
                'meal_type' => $meal->meal_type,
                "is_active" => $meal->is_active,
                'imgs'=>json_decode($images),

            ]
        ], 201);
    }

    public function updateMeal(Request $request, $id)
    {
        $meal= Meal::find($id);

        if (!$meal) {
            return response()->json([
                'message' => 'Meal not found',
            ], 200);
        }
        $validator = Validator::make($request->all(), [
            'name' => 'string',
            'description' => 'string',
            'ingredients' => 'string',
            'meal_type' => 'string',
            "is_active" => 'boolean',
            ]);

        if ($validator->fails()) {

            return response()->json($validator->errors()->toJson(), 400);
        }

        $meal->update($request->all());

        return response()->json([
                'code' => 200,
                'message' => 'Meal updated successfully ',
                'result' => [
                    'id' => $meal->id,
                    'name' => $meal->name,
                    'description' => $meal->description,
                    'ingredients' => $meal->ingredients,
                    'meal_type' => $meal->meal_type,
                    "is_active" => $meal->is_active,

                ]
            ]
            , 200);
    }

    public function deleteMeal($id)
    {
        $meal = Meal::find($id);

        if (!$meal) {
            return response()->json([
                'message' => 'Meal not found',
            ], 200);
        }


        $meal->delete();

        return response()->json([
            'code' => 200,
            'message' => 'Meal deleted successfully ',
        ], 200);

    }

    public function getMeal($id)
    {
        $meal =Meal::find($id);

        if (!$meal) {
            return response()->json([
                'message' => 'Meal not found',
            ], 404);
        }
        return response()->json([
                'code' => 200,
                'message' => 'This is Meal ',
                'result' => [
                    'id' => $meal->id,
                    'name' => $meal->name,
                    'description' => $meal->description,
                    'ingredients' => $meal->ingredients,
                    'meal_type' => $meal->meal_type,
                    "is_active" => $meal->is_active,
                    'imgs'=>json_decode($meal->imgs),

                ]
            ]
            , 200);
    }
//name ingre is_active Type

    public function getAllMeal($request)
    {

        if ( $request == "all") {
            $meals = Meal::all();
        }
        else
        {
            $meals = Meal::where('name', 'like', '%' . $request . '%')
                        ->orWhere('ingredients', 'like', '%' . $request . '%')
                ->orWhere('meal_type', 'like', '%' . $request . '%')
                ->orWhere('is_active', 'like', '%' . $request . '%')


                ->get();

        }
        $allMeals = $meals->map(function ($meal) {
            return [
                'id' => $meal->id,
                'name' => $meal->name,
                'description' => $meal->description,
                'ingredients' => $meal->ingredients,
                'meal_type' => $meal->meal_type,
                "is_active" => $meal->is_active,
                'imgs'=>json_decode($meal->imgs),

            ];
        });

        if ($allMeals->isEmpty()) {
            return response()->json([
                'message' => 'Not Found Meals',
            ], 200);
        }
        return response()->json([
                'code' => 200,
                'message' => 'Meals',
                'result' => [
                    'meal' => $allMeals,
                ]
            ]
            , 200);
    }
    public function updatePhoto(Request $request, $id)
    {
        $meal = Meal::find($id);

        if (!$meal) {
            return response()->json([
                'message' => 'Meal not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'imgs'=> 'required',
            'imgs.*' => [ 'image', 'mimes:jpeg,png,jpg,gif', 'max:512'],
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors()->toJson(), 400);
        }
        $images = $this->upload($request->imgs);

        $meal->update([
            'imgs'=>$images
        ]);
        return response()->json([
                'code' => 200,
                'message' => 'Updated photo',
                'result' => [
                    'imgs' =>json_decode($images) ,
                ]
            ]
            , 200);
    }
}
