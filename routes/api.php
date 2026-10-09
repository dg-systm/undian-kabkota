<?php

use App\Http\Controllers\ApiController;
use App\Http\Controllers\DrawController;
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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::group(['prefix' => 'get-data'], function () {
    Route::get('prize', [ApiController::class, 'prizeAll']);
    Route::get('locations', [ApiController::class, 'locationsAll']);
    Route::get('coordinator', [ApiController::class, 'coordinatorMapping']);
    Route::get('wilayah', [ApiController::class, 'wilayah']);
});

Route::group(['prefix' => 'draw'], function () {
    Route::post('pick', [DrawController::class, 'pick']);
});
