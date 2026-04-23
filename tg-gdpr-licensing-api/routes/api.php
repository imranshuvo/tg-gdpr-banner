<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\LicenseController;
use App\Http\Controllers\Api\ConsentController;
use App\Http\Controllers\Api\CookieController;
use App\Http\Controllers\Api\DsarController;

Route::prefix('v1')->group(function () {
    // License management
    Route::prefix('licenses')->group(function () {
        Route::post('activate', [LicenseController::class, 'activate']);
        Route::post('deactivate', [LicenseController::class, 'deactivate']);
        Route::post('verify', [LicenseController::class, 'verify']);
    });
    
    // Site settings & consent (requires site token)
    Route::prefix('site')->group(function () {
        Route::get('settings', [ConsentController::class, 'getSettings']);
        Route::get('usage', [ConsentController::class, 'getUsage']);
    });
    
    // Consent management
    Route::prefix('consents')->group(function () {
        Route::post('record', [ConsentController::class, 'recordConsent']);
        Route::post('sync', [ConsentController::class, 'syncConsents']);
        Route::post('withdraw', [ConsentController::class, 'withdrawConsent']);
    });
    
    // Session tracking
    Route::prefix('sessions')->group(function () {
        Route::post('sync', [ConsentController::class, 'syncSessions']);
    });
    
    // Cookie database
    Route::prefix('cookies')->group(function () {
        Route::get('site', [CookieController::class, 'getSiteCookies']);
        Route::get('lookup', [CookieController::class, 'lookupCookie']);
        Route::post('bulk-lookup', [CookieController::class, 'bulkLookup']);
        Route::post('scan', [CookieController::class, 'submitScan']);
        Route::post('update', [CookieController::class, 'updateSiteCookie']);
    });
    
    // DSAR (Data Subject Access Requests)
    Route::prefix('dsar')->group(function () {
        Route::post('submit', [DsarController::class, 'submit']);
        Route::get('verify/{token}', [DsarController::class, 'verify'])->name('api.dsar.verify');
        Route::get('status/{token}', [DsarController::class, 'status'])->name('api.dsar.status');
        Route::get('download/{token}', [DsarController::class, 'download'])->name('api.dsar.download');
    });
});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
