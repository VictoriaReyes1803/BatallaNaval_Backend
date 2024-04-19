<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ActivationController;


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
Route::group(['prefix' => ''], function () {
    Route::any('403', function() {
        return response()->json([
            'data' => 'No auntentificado.'
        ], 403);
    })->name('notauthenticated');
});

Route::post('/register', [UsersController::class, 'register']); 
Route::get('/activation/{user}', [ActivationController::class, 'activate'])->name('activation');

Route::group(['prefix' => 'auth'], function () {
    Route::post('login', [AuthController::class, 'login'])->name('login');
    Route::group(['middleware' => ['auth:api:jwt']], function () {
        Route::post('verify-code', [AuthController::class, 'verificar']);
        Route::get('logout', [AuthController::class, 'logout']);
        Route::get('refresh', [AuthController::class, 'refresh']);
        Route::get('me', [AuthController::class, 'me']);
    });
});