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
        
            // Route::post('join', [PartidaController::class, 'join']);
            // Route::post('start', [PartidaController::class, 'start']);
            // Route::post('attack', [PartidaController::class, 'attack']);
            // Route::post('leave', [PartidaController::class, 'leave']);
            // Route::post('end', [PartidaController::class, 'end']);
            // Route::post('status', [PartidaController::class, 'status']);
            // Route::post('list', [PartidaController::class, 'list']);
            // Route::post('chat', [PartidaController::class, 'chat']);
            // Route::post('history', [PartidaController::class, 'history']);
            // Route::post('ranking', [PartidaController::class, 'ranking']);
            // Route::post('replay', [PartidaController::class, 'replay']);
            // Route::post('replay-list', [PartidaController::class, 'replayList']);
            // Route::post('replay-save', [PartidaController::class, 'replaySave']);
            // Route::post('replay-delete', [GameController::class, 'replayDelete']);
            // Route::post('replay-load', [GameController::class, 'replayLoad']);
            // Route::post('replay-attack', [GameController::class, 'replayAttack']);
            // Route::post('replay-chat', [GameController::class, 'replayChat']);
            // Route::post('replay-end', [GameController::class, 'replayEnd']);
            // Route::post('replay-status', [GameController::class, 'replayStatus']);
            // Route::post('replay-history', [GameController::class, 'replayHistory']);
            // Route::post('replay-ranking', [GameController::class, 'replayRanking']);
            // Route::post('replay-leave', [GameController::class, 'replayLeave']);
            // Route::post('replay-start', [GameController::class, 'replayStart']);
            // Route::post('replay-list', [GameController::class, 'replayList']);
            // Route::post('replay-save', [GameController::class, 'replaySave']);
            // Route::post('replay-delete', [GameController::class, 'replayDelete']);
            
        });
    });
});

Route::post('/sendevent', function(){
    event(new TestEvent(['msg' => 'Hello World']));
    return response()->json(['success' => true]);
});
