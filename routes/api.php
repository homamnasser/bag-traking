<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DriverAreaServiceController;
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
        Route::get('/getAllAreas',[DriverAreaServiceController::class,'getAllAreas']);
});

Route::group([
    'middleware' => ['api', 'auth:sanctum', 'role:super_admin|admin'],
    'prefix' => 'customer'
], function ($router) {
    Route::post('/addCustomer', [CustomerController::class,'addCustomer']);
    Route::post('/updateCustomer/{id}', [CustomerController::class,'updateCustomer']);
    Route::post('/editStatus/{id}', [CustomerController::class,'editStatus']);



});

Route::post('/register', [AuthController::class, 'register']);
Route::post('/loginUser', [AuthController::class, 'loginUser']);


Route::middleware(['auth:sanctum','role:super_admin|admin'])
    ->post('/createUser', [AdminController::class,'createUser']);

Route::middleware(['auth:sanctum','role:super_admin|admin'])
    ->get('/getAllUsers', [AdminController::class,'getAllUsers']);

//Route::middleware(['auth:sanctum','role:super_admin|admin'])
//    ->post('/addArea', [DriverAreaServiceController::class,'addArea']);
