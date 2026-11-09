<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PatientController;
use App\Http\Controllers\Api\VisitController;
use App\Http\Middleware\DecryptPayloadMiddleware;
use Illuminate\Support\Facades\Route;

// Endpoint login publik dengan dekripsi otomatis payload AES-256-CBC
Route::post('/login', [AuthController::class, 'login'])
    ->middleware(DecryptPayloadMiddleware::class);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'profile']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/patients', [PatientController::class, 'index']);
    Route::post('/patients', [PatientController::class, 'store']);
    Route::get('/patients/{idOrMrn}', [PatientController::class, 'show']);

    Route::post('/visits', [VisitController::class, 'store']);
});
