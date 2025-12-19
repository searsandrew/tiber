<?php

use App\Http\Controllers\GameActionController;
use App\Http\Controllers\GameController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/games', [GameController::class, 'index']);
    Route::post('/games', [GameController::class, 'store']);
    Route::get('/games/{game}', [GameController::class, 'show']);
    Route::post('/games/{game}/join', [GameController::class, 'join']);
    Route::post('/games/{game}/leave', [GameController::class, 'leave']);
    Route::post('/games/{game}/start', [GameController::class, 'start']);
    Route::post('/games/{game}/actions', [GameActionController::class, 'store']);
});
