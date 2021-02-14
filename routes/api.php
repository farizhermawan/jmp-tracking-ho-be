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

Route::group(['prefix' => 'api', 'namespace' => 'API'], function () {
  Route::get('/vehicle', 'VehicleController@getAll');
  Route::get('/driver', 'DriverController@getAll');
  Route::get('/kenek', 'KenekController@getAll');
  Route::get('/user', 'UserController@getAll');
  Route::get('/route', 'RouteController@getAll');
  Route::get('/customer', 'CustomerController@getAll');
  Route::get('/entity', 'EntityController@getAll');

  Route::post('/auth/callback', 'AuthController@callback');
  Route::post('/auth/saved', 'AuthController@savedAuth');

  Route::group(['middleware' => 'jwt'], function () {
    Route::get('/auth/profile', 'AuthController@getProfile');
    Route::get('/dashboard/info', 'DashboardController@getInfo');
    Route::get('/dashboard/ritasi', 'DashboardController@getRitasi');
    Route::post('/monitor', 'MonitoringController@getMonitor');

    Route::post('/route/add', 'RouteController@addRoute');
    Route::post('/route/toggle', 'RouteController@toggleRoute');
    Route::post('/route/update', 'RouteController@updateRoute');
    Route::post('/route/remove', 'RouteController@removeRoute');

    Route::post('/customer/add', 'CustomerController@addCustomer');
    Route::post('/customer/toggle', 'CustomerController@toggleCustomer');
    Route::post('/customer/update', 'CustomerController@updateCustomer');
    Route::post('/customer/remove', 'CustomerController@removeCustomer');

    Route::post('/vehicle/add', 'VehicleController@addVehicle');
    Route::post('/vehicle/toggle', 'VehicleController@toggleVehicle');
    Route::post('/vehicle/update', 'VehicleController@updateVehicle');
    Route::post('/vehicle/remove', 'VehicleController@removeVehicle');

    Route::post('/driver/add', 'DriverController@addDriver');
    Route::post('/driver/toggle', 'DriverController@toggleDriver');
    Route::post('/driver/update', 'DriverController@updateDriver');
    Route::post('/driver/remove', 'DriverController@removeDriver');

    Route::post('/kenek/add', 'KenekController@addKenek');
    Route::post('/kenek/toggle', 'KenekController@toggleKenek');
    Route::post('/kenek/update', 'KenekController@updateKenek');
    Route::post('/kenek/remove', 'KenekController@removeKenek');

    Route::post('/user/add', 'UserController@addUser');
    Route::post('/user/toggle', 'UserController@toggleUser');
    Route::post('/user/update', 'UserController@updateUser');
    Route::post('/user/remove', 'UserController@removeUser');

    Route::get('/ballance/{id}', 'BallanceController@getBallance');
    Route::post('/ballance/add', 'BallanceController@addBallance');

    Route::post('/jot/view', 'TransactionController@viewJot');
    Route::post('/jot/submit', 'TransactionController@saveJot');
    Route::post('/jot/revise', 'TransactionController@reviseJot');
    Route::post('/jot/update', 'TransactionController@updateJot');
    Route::post('/jot/adjust', 'TransactionController@adjustJot');
    Route::post('/jot/remove', 'TransactionController@removeJot');

    Route::post('/plan/submit', 'TransactionController@savePlan');

    Route::post('/vehicle-cost/submit', 'VehicleCostController@saveTransaction');
    Route::post('/vehicle-cost/remove', 'VehicleCostController@remove');

    Route::post('/undirect-cost/submit', 'UndirectCostController@saveTransaction');
    Route::post('/undirect-cost/remove', 'UndirectCostController@remove');

    Route::post('/transaksi', 'TransactionController@getTransaksi');
    Route::post('/finance', 'FinanceController@getFinance');
    Route::post('/vehicle-cost', 'VehicleCostController@getTransaksi');
    Route::post('/undirect-cost', 'UndirectCostController@getTransaksi');

    Route::post('/export/route', 'RouteController@export');
    Route::post('/export/finance', 'FinanceController@export');
    Route::post('/export/transaksi', 'TransactionController@export');
    Route::post('/export/transaksi-detail', 'TransactionController@exportDetail');
    Route::post('/export/vehicle-cost', 'VehicleCostController@export');
    Route::post('/export/vehicle-cost-report', 'VehicleCostController@exportReport');
    Route::post('/export/undirect-cost', 'UndirectCostController@exportUndirectCost');
  });
});

Route::group(['prefix' => 'v1', 'namespace' => 'v1'], function () {
  Route::group(['middleware' => 'jwt'], function () {
    // Master Data
    Route::resource('sub-customers','SubCustomerController')->except(['create', 'edit']);
    Route::resource('depo-mt','DepoMTController')->except(['create', 'edit']);
  });
});

