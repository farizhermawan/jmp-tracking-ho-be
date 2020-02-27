<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::get('/vehicle', 'API\VehicleController@getAll');
Route::get('/driver', 'API\DriverController@getAll');
Route::get('/kenek', 'API\KenekController@getAll');
Route::get('/user', 'API\UserController@getAll');
Route::get('/route', 'API\RouteController@getAll');
Route::get('/customer', 'API\CustomerController@getAll');
Route::get('/entity', 'API\EntityController@getAll');

Route::post('/auth/callback', 'API\AuthController@callback');
Route::post('/auth/saved', 'API\AuthController@savedAuth');

Route::middleware('jwt')->group(function () {
    Route::get('/auth/profile', 'API\AuthController@getProfile');
    Route::get ('/dashboard/info', 'API\DashboardController@getInfo');
    Route::get ('/dashboard/ritasi', 'API\DashboardController@getRitasi');
    Route::post('/monitor', 'API\MonitoringController@getMonitor');

    Route::post('/route/add', 'API\RouteController@addRoute');
    Route::post('/route/toggle', 'API\RouteController@toggleRoute');
    Route::post('/route/update', 'API\RouteController@updateRoute');
    Route::post('/route/remove', 'API\RouteController@removeRoute');

    Route::post('/customer/add', 'API\CustomerController@addCustomer');
    Route::post('/customer/toggle', 'API\CustomerController@toggleCustomer');
    Route::post('/customer/update', 'API\CustomerController@updateCustomer');
    Route::post('/customer/remove', 'API\CustomerController@removeCustomer');

    Route::post('/vehicle/add', 'API\VehicleController@addVehicle');
    Route::post('/vehicle/toggle', 'API\VehicleController@toggleVehicle');
    Route::post('/vehicle/update', 'API\VehicleController@updateVehicle');
    Route::post('/vehicle/remove', 'API\VehicleController@removeVehicle');

    Route::post('/driver/add', 'API\DriverController@addDriver');
    Route::post('/driver/toggle', 'API\DriverController@toggleDriver');
    Route::post('/driver/update', 'API\DriverController@updateDriver');
    Route::post('/driver/remove', 'API\DriverController@removeDriver');

    Route::post('/kenek/add', 'API\KenekController@addKenek');
    Route::post('/kenek/toggle', 'API\KenekController@toggleKenek');
    Route::post('/kenek/update', 'API\KenekController@updateKenek');
    Route::post('/kenek/remove', 'API\KenekController@removeKenek');

    Route::post('/user/add', 'API\UserController@addUser');
    Route::post('/user/toggle', 'API\UserController@toggleUser');
    Route::post('/user/update', 'API\UserController@updateUser');
    Route::post('/user/remove', 'API\UserController@removeUser');

    Route::get ('/ballance/{id}', 'API\BallanceController@getBallance');
    Route::post('/ballance/add', 'API\BallanceController@addBallance');

    Route::post('/jot/view', 'API\TransactionController@viewJot');
    Route::post('/jot/submit', 'API\TransactionController@saveJot');
    Route::post('/jot/revise', 'API\TransactionController@reviseJot');
    Route::post('/jot/update', 'API\TransactionController@updateJot');
    Route::post('/jot/adjust', 'API\TransactionController@adjustJot');
    Route::post('/jot/remove', 'API\TransactionController@removeJot');

    Route::post('/plan/submit', 'API\TransactionController@savePlan');


    Route::post('/vehicle-cost/submit', 'API\VehicleCostController@saveTransaction');
    Route::post('/vehicle-cost/remove', 'API\VehicleCostController@remove');

    Route::post('/transaksi', 'API\TransactionController@getTransaksi');
    Route::post('/finance', 'API\FinanceController@getFinance');
    Route::post('/vehicle-cost', 'API\VehicleCostController@getTransaksi');

    Route::post('/export/route', 'API\RouteController@export');
    Route::post('/export/finance', 'API\FinanceController@export');
    Route::post('/export/transaksi', 'API\TransactionController@export');
    Route::post('/export/transaksi-detail', 'API\TransactionController@exportDetail');
    Route::post('/export/vehicle-cost', 'API\VehicleCostController@export');
    Route::post('/export/vehicle-cost-report', 'API\VehicleCostController@exportReport');
});

