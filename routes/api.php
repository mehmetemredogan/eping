<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\HistoryController;
use App\Http\Controllers\Api\ResultController;
use App\Http\Controllers\Api\TargetController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:10,1');

    // Public read of active targets (desktop can browse before login; report requires auth).
    Route::get('/targets', [TargetController::class, 'index'])->middleware('throttle:60,1');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::post('/targets/{target}/results', [ResultController::class, 'store'])->middleware('throttle:120,1');

        Route::get('/results/history', [HistoryController::class, 'index'])->middleware('throttle:60,1');
        Route::get('/results/trend', [HistoryController::class, 'trend'])->middleware('throttle:60,1');
    });
});
