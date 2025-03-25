<?php

use Illuminate\Http\Request;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\PagesController;
use  App\Http\Controllers\Api\GraphController;
use App\Http\Controllers\Api\LeadController;
use App\Http\Controllers\Api\PaymentController;

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

Route::group(['namespace' => 'App\Api\v1\Controllers'], function () {
    Route::group(['middleware' => 'auth:api'], function () {
        Route::get('users', ['uses' => 'UserController@index']);
    });
});

Route::post('/login', [LoginController::class, 'authenticate']);
Route::get('/dashboardjson',  [PagesController::class, 'dashboardJson']);
Route::get('/graph-data', [GraphController::class, 'getGraphData']);


Route::get('/leads/{month}/{year}', [LeadController::class, 'getLeadsByMonthAndYear']);
// Route::get('/leads/{month}', [LeadController::class, 'getLeadsByMonth']);
Route::delete('/payments/{id}', [PaymentController::class, 'deletePayment']);
Route::put('/payments/{id}', [PaymentController::class, 'updatePayment']);




