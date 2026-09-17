<?php

use App\Http\Controllers\Api\AppConfigController;
use App\Http\Controllers\Api\NewsController;
use App\Http\Controllers\Api\SportController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\MemberController;
use App\Http\Controllers\Api\RegistrationController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('throttle:60,1')->group(function () {
    Route::get('/news', [NewsController::class, 'index']);
    Route::get('/news/{newsPost}', [NewsController::class, 'show']);
    Route::get('/sports', [SportController::class, 'index']);
    Route::get('/events', [EventController::class, 'index']);
    Route::get('/events/{event}', [EventController::class, 'show']);
    Route::get('/registrations/{registrationNo}', [RegistrationController::class, 'show']);
    Route::post('/auth/register', [AuthController::class, 'register'])->middleware('throttle:10,1');
    Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::middleware('api.token')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/me', [MemberController::class, 'show']);
        Route::put('/me', [MemberController::class, 'update']);
        Route::get('/me/registrations', [RegistrationController::class, 'index']);
        Route::post('/events/{event}/registrations', [RegistrationController::class, 'store']);
        Route::delete('/registrations/{registration}', [RegistrationController::class, 'cancel']);
    });
    Route::get('/app-config', [AppConfigController::class, 'show']);
    Route::get('/health', fn () => response()->json([
        'success' => true,
        'data' => ['status' => 'ok', 'time' => now()->toIso8601String()],
        'meta' => null,
        'error' => null,
    ]));
});
