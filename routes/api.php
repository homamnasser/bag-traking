<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BagController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\CustomerFoodPreferencesController;
use App\Http\Controllers\DriverAreaServiceController;
use App\Http\Controllers\DriverController;
use App\Http\Controllers\MealController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\WorkerController;
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
        Route::delete('/deleteArea/{id}',[DriverAreaServiceController::class,'deleteArea']);
        Route::get('/getAllAreas/{request}',[DriverAreaServiceController::class,'getAllAreas']);
        Route::get('/getArea/{id}', [DriverAreaServiceController::class,'getArea']);


});


Route::group([
    'middleware' => ['api', 'auth:sanctum'],
], function ($router) {
    Route::get('/getMyInfo', [AdminController::class,'getMyInfo'])
        ->middleware('role:driver|store_employee|admin_cook|admin|super_admin');
    Route::post('/logout', [AuthController::class,'logout']);




});



Route::group([
    'middleware' => ['api', 'auth:sanctum', 'role:super_admin|admin'],
    'prefix' => 'customer'
], function ($router) {
    Route::post('/addCustomer', [CustomerController::class,'addCustomer']);
    Route::post('/updateCustomer/{id}', [CustomerController::class,'updateCustomer']);
    Route::get('/getCustomerByStatus/{is_active}', [CustomerController::class,'getCustomerByStatus']);
    Route::get('/getAllFoodPreferences', [CustomerFoodPreferencesController::class,'getAllFoodPreferences']);
    Route::get('/getCustomer/{id}', [CustomerController::class,'getCustomer']);




});
Route::group([
    'middleware' => ['api', 'auth:sanctum', 'role:super_admin|admin|customer'],
    'prefix' => 'customer'
], function ($router) {
    Route::post('/editStatus/{id}', [CustomerController::class,'editStatus']);





});


Route::group([
    'middleware' => ['api', 'auth:sanctum', 'role:customer'],
    'prefix' => 'customer'
], function ($router) {
    Route::post('/addFoodPrefer', [CustomerFoodPreferencesController::class,'addFoodPrefer']);
    Route::post('/updateFoodPrefer', [CustomerFoodPreferencesController::class,'updateFoodPrefer']);
    Route::delete('/deleteFoodPrefer', [CustomerFoodPreferencesController::class,'deleteFoodPrefer']);
    Route::get('/getCustomerFoodPreferences', [CustomerFoodPreferencesController::class,'getCustomerFoodPreferences']);
});

Route::group([
    'middleware' => ['api', 'auth:sanctum', 'role:customer'],
    'prefix' => 'order'
], function ($router) {
    Route::post('/addOrder', [OrderController::class,'addOrder']);
    Route::post('/updateOrder/{id}', [OrderController::class,'updateOrder']);
    Route::delete('/deleteOrder/{id}', [OrderController::class,'deleteOrder']);
    Route::get('/getMyOrders', [OrderController::class,'getMyOrders']);



});

Route::group([
    'middleware' => ['api', 'auth:sanctum', 'role:customer|admin_cook'],
    'prefix' => 'order'
], function ($router) {

    Route::get('/getOrder/{id}', [OrderController::class,'getOrder']);


});

Route::post('/register', [AuthController::class, 'register']);
Route::post('/loginUser', [AuthController::class, 'loginUser']);
Route::middleware('auth:sanctum')->post('/createFcmToken', [AuthController::class, 'createFcmToken']);

Route::group([
    'middleware' => ['api', 'auth:sanctum', 'role:customer'],
], function ($router) {

    Route::get('/getCustomerInfo', [CustomerController::class,'getCustomerInfo']);
    Route::post('/updateInfoByCustomer', [CustomerController::class,'updateInfoByCustomer']);
    Route::get('/getCustomerNotification', [MessageController::class,'getCustomerNotification']);
});
Route::post('/customerForgetPassword', [AuthController::class,'customerForgetPassword']);
Route::post('/customerCheckCode', [AuthController::class, 'customerCheckCode']);
Route::post('/customerResetPassword', [AuthController::class, 'customerResetPassword']);


Route::group([
    'middleware' => ['api', 'auth:sanctum', 'role:super_admin|admin'],
    'prefix' => 'admin'
], function ($router) {
    Route::post('/createUser', [AdminController::class,'createUser']);
    Route::post('/updateUser/{id}', [AdminController::class,'updateUser']);
    Route::delete('/deleteImage/{user_id}', [AdminController::class,'deleteImage']);
    Route::delete('/deleteUser/{id}', [AdminController::class,'deleteUser']);
    Route::get('/getUser/{id}', [AdminController::class,'getUser']);
    Route::get('/getAllUsers/{request}', [AdminController::class,'getAllUsers']);

    Route::get('/getMessage/{id}', [MessageController::class,'getMessage']);
    Route::get('/getAllMessages', [MessageController::class,'getAllMessages']);
    Route::get('/getMessageByType/{type}', [MessageController::class,'getMessageByType']);
    Route::post('/report', [ReportController::class,'report']);

});


Route::group([
    'middleware' => ['api', 'auth:sanctum', 'role:admin_cook'],
    'prefix' => 'meal'
], function ($router) {
    Route::post('/addMeal', [MealController::class,'addMeal']);
    Route::post('/updateMeal/{id}', [MealController::class,'updateMeal']);
    Route::delete('/deleteMeal/{id}', [MealController::class,'deleteMeal']);
    Route::post('/updatePhoto/{id}', [MealController::class,'updatePhoto']);

});

    Route::group([
        'middleware' => ['api', 'auth:sanctum', 'role:admin_cook|customer'],
    ], function ($router) {
        Route::get('/getMeal/{id}', [MealController::class,'getMeal']);
        Route::get('/getAllMeal/{id}', [MealController::class,'getAllMeal']);


    });


    Route::group([
        'middleware' => ['api', 'auth:sanctum', 'role:super_admin|admin'],
        'prefix' => 'bag'
    ], function ($router) {
        Route::post('/addBag', [BagController::class,'addBag']);
        Route::delete('/deleteBag/{id}',[BagController::class,'deleteBag']);
        Route::get('/getAllBags',[BagController::class,'getAllBags']);
        Route::get('/getBagByStatus/{request}', [BagController::class,'getBagsByStatus']);
        Route::get('/searchBagById/{id}', [BagController::class,'searchBagById']);
        Route::get('/editLastUpdateBagByAdmin/{bag_id}', [BagController::class,'editLastUpdateBagByAdmin']);
    });



    Route::group([
    'middleware' => ['api','auth:sanctum'],
    ], function ($router) {
    Route::get('/bag', [WorkerController::class,'scanQr'])->middleware('role:driver|store_employee');



    Route::get('/getCustomerForDriver/{id}', [WorkerController::class,'getCustomerForDriver'])
           ->middleware('role:driver|store_employee');
   });
    Route::post('/forgetPassword', [WorkerController::class,'forgetPassword']);



Route::group([
    'middleware' => ['api','auth:sanctum','role:admin_cook'],
], function ($router) {
    Route::get('/getTodayOrders', [OrderController::class,'getTodayOrders']);
});



Route::group([
    'middleware' => ['api', 'auth:sanctum'],
    'prefix' => 'message'
], function ($router) {
    Route::post('/sendMessage', [MessageController::class,'sendMessage'])
        ->middleware('role:driver|store_employee|customer');

    Route::post('/respondRequest', [MessageController::class,'respondRequest'])
        ->middleware('role:admin|super_admin');
});


Route::middleware(['auth:sanctum','role:super_admin|admin'])
    ->get('/getAllUsers', [AdminController::class,'getAllUsers']);

//Route::middleware(['auth:sanctum','role:super_admin|admin'])
//    ->post('/addArea', [DriverAreaServiceController::class,'addArea']);

