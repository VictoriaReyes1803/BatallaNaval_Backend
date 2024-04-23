<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ActivationController;
use App\Http\Controllers\PartidaController;


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
        Route::prefix('game')->group(function(){
            Route::post('create', [PartidaController::class, 'createGame']);
            Route::get('find', [PartidaController::class, 'findMatch']);
            Route::post('{id}/finish', [PartidaController::class, 'finishGame']);
            Route::get('games', [PartidaController::class, 'getGames']);
            Route::post('/queue', [PartidaController::class, 'queueGame']);
        Route::put('/join/random', [PartidaController::class, 'joinRandomGame']);
        Route::put('/end', [PartidaController::class, 'endGame']);
        Route::post('/dequeue', [PartidaController::class, 'dequeueGame']);
        Route::post('/cancel/random', [PartidaController::class, 'cancelRandomQueue']);
        Route::post('/send/board', [PartidaController::class, 'sendBoard']);
        Route::get('/history', [PartidaController::class, 'myGameHistory']);
        Route::post('/notify', [PartidaController::class, 'sendNotify']);
        Route::post('/attack', [PartidaController::class, 'attack']);
        Route::post('/attack/success', [PartidaController::class, 'attackSuccess']);
        Route::post('/attack/failed', [PartidaController::class, 'attackFailed']);
        });
    });
});

Route::post('/sendevent', function(){
    event(new TestEvent(['msg' => 'Hello World']));
    return response()->json(['success' => true]);
});
