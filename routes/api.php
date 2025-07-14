<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\CustomerFoodPreferencesController;
use App\Http\Controllers\DriverAreaServiceController;
use App\Http\Controllers\MealController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/


Route::group([
    'middleware' => ['api', 'auth:sanctum', 'role:super_admin|admin'],
    'prefix' => 'area'
], function ($router) {
        Route::post('/addArea', [DriverAreaServiceController::class,'addArea']);
        Route::post('/updateArea/{id}', [DriverAreaServiceController::class,'updateArea']);
        Route::post('/deleteArea/{id}',[DriverAreaServiceController::class,'deleteArea']);
        Route::post('/getAllAreas/{request}',[DriverAreaServiceController::class,'getAllAreas']);
        Route::post('/getArea/{id}', [DriverAreaServiceController::class,'getArea']);


});

Route::group([
    'middleware' => ['api', 'auth:sanctum', 'role:super_admin|admin'],
    'prefix' => 'customer'
], function ($router) {
    Route::post('/addCustomer', [CustomerController::class,'addCustomer']);
    Route::post('/updateCustomer/{id}', [CustomerController::class,'updateCustomer']);
    Route::post('/editStatus/{id}', [CustomerController::class,'editStatus']);
    Route::get('/getAllCustomers', [CustomerController::class,'getAllCustomers']);
    Route::post('/getCustomerByStatus/{subscription_status}', [CustomerController::class,'getCustomerByStatus']);
    Route::get('/getAllFoodPreferences', [CustomerFoodPreferencesController::class,'getAllFoodPreferences']);
    Route::post('/getCustomer/{id}', [CustomerController::class,'getCustomer']);



});

Route::group([
    'middleware' => ['api', 'auth:sanctum', 'role:customer'],
    'prefix' => 'customer'
], function ($router) {
    Route::post('/addFoodPrefer', [CustomerFoodPreferencesController::class,'addFoodPrefer']);
    Route::post('/updateFoodPrefer', [CustomerFoodPreferencesController::class,'updateFoodPrefer']);
    Route::post('/deleteFoodPrefer', [CustomerFoodPreferencesController::class,'deleteFoodPrefer']);
    Route::post('/getCustomerFoodPreferences/{id}', [CustomerFoodPreferencesController::class,'getCustomerFoodPreferences']);







});

Route::post('/register', [AuthController::class, 'register']);
Route::post('/loginUser', [AuthController::class, 'loginUser']);

Route::group([
    'middleware' => ['api', 'auth:sanctum', 'role:super_admin|admin'],
    'prefix' => 'admin'
], function ($router) {
    Route::post('/createUser', [AdminController::class,'createUser']);
    Route::post('/updateUser/{id}', [AdminController::class,'updateUser']);
    Route::post('/deleteUser/{id}', [AdminController::class,'deleteUser']);
    Route::post('/getUser/{id}', [AdminController::class,'getUser']);
});


Route::group([
    'middleware' => ['api', 'auth:sanctum', 'role:admin_cook'],
    'prefix' => 'meal'
], function ($router) {
    Route::post('/addMeal', [MealController::class,'addMeal']);
    Route::post('/updateMeal/{id}', [MealController::class,'updateMeal']);
    Route::post('/deleteMeal/{id}', [MealController::class,'deleteMeal']);
    Route::post('/getMeal/{id}', [MealController::class,'getMeal']);
    Route::post('/updatePhoto/{id}', [MealController::class,'updatePhoto']);
    Route::post('/getAllMeal/{id}', [MealController::class,'getAllMeal']);





});

Route::middleware(['auth:sanctum','role:super_admin|admin'])
    ->get('/getAllUsers', [AdminController::class,'getAllUsers']);

//Route::middleware(['auth:sanctum','role:super_admin|admin'])
//    ->post('/addArea', [DriverAreaServiceController::class,'addArea']);
