<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\LicenseController;

Route::prefix('v1')->group(function () {
    Route::prefix('licenses')->group(function () {
        Route::post('activate', [LicenseController::class, 'activate']);
        Route::post('deactivate', [LicenseController::class, 'deactivate']);
        Route::post('verify', [LicenseController::class, 'verify']);
    });
});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
