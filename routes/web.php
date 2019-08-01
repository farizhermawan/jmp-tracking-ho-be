<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get( '/auth0/callback', '\Auth0\Login\Auth0Controller@callback' )->name( 'auth0-callback' );

Route::get( '/download/{hash}', function($hash) {
    $pathToFile = storage_path("app/public/{$hash}.xlsx");
    return response()->download($pathToFile)->deleteFileAfterSend(true);
});

Route::get('/', function () {
    return view('welcome');
});
