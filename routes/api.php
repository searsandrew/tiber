<?php

use App\Http\Controllers\GameActionController;
use App\Http\Controllers\GameController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;

Route::post('/login', function (Request $request) {
    $credentials = $request->validate([
        'email' => ['required','email'],
        'password' => ['required'],
    ]);

    if (! Auth::attempt($credentials)) {
        throw ValidationException::withMessages([
            'email' => ['The provided credentials are incorrect.'],
        ]);
    }

    $user = $request->user();

    return response()->json([
        'token' => $user->createToken('api-token', [
            'games:read',
            'games:write',
        ])->plainTextToken,
    ]);
})->middleware('throttle:login');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', function (Request $request) {
        $request->user()->currentAccessToken()?->delete();
        return response()->json(['ok' => true]);
    });
    Route::get('/user', fn (Request $request) => $request->user());

    Route::middleware('abilities:games:read')->group(function () {
        Route::get('/games', [GameController::class, 'index']);
        Route::get('/games/{game}', [GameController::class, 'show']);
    });

    Route::middleware('abilities:games:write')->group(function () {
        Route::post('/games', [GameController::class, 'store']);
        Route::post('/games/{game}/join', [GameController::class, 'join']);
        Route::post('/games/{game}/leave', [GameController::class, 'leave']);
        Route::post('/games/{game}/actions', [GameActionController::class, 'store']);
        Route::post('/games/{game}/start', [GameController::class, 'start'])->middleware('can:start,game');; // also write
    });
});
