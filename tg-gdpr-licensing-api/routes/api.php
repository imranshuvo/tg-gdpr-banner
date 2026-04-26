<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\LicenseController;
use App\Http\Controllers\Api\ConsentController;
use App\Http\Controllers\Api\CookieController;
use App\Http\Controllers\Api\DsarController;

Route::prefix('v1')->group(function () {
    // License management — heartbeat traffic from WP plugins.
    // Mutations (activate/deactivate) and verify are throttled by license_key
    // so sites behind shared NAT aren't bucketed together. See AppServiceProvider.
    Route::prefix('licenses')->group(function () {
        Route::post('activate',   [LicenseController::class, 'activate'])->middleware('throttle:license-mutations');
        Route::post('deactivate', [LicenseController::class, 'deactivate'])->middleware('throttle:license-mutations');
        Route::post('verify',     [LicenseController::class, 'verify'])->middleware('throttle:license-verify');
    });

    // Site settings & consent (requires site token) — high-volume visitor traffic.
    // Throttled per-IP only; per-site usage is unbounded so busy sites aren't penalised.
    Route::prefix('site')->middleware('throttle:site-public')->group(function () {
        Route::get('settings', [ConsentController::class, 'getSettings']);
        Route::get('usage',    [ConsentController::class, 'getUsage']);
    });

    Route::prefix('consents')->middleware('throttle:site-public')->group(function () {
        Route::post('record',   [ConsentController::class, 'recordConsent']);
        Route::post('sync',     [ConsentController::class, 'syncConsents']);
        Route::post('withdraw', [ConsentController::class, 'withdrawConsent']);
    });

    Route::prefix('sessions')->middleware('throttle:site-public')->group(function () {
        Route::post('sync', [ConsentController::class, 'syncSessions']);
    });

    Route::prefix('cookies')->middleware('throttle:site-public')->group(function () {
        Route::get('site',         [CookieController::class, 'getSiteCookies']);
        Route::get('lookup',       [CookieController::class, 'lookupCookie']);
        Route::post('bulk-lookup', [CookieController::class, 'bulkLookup']);
        Route::post('scan',        [CookieController::class, 'submitScan']);
        Route::post('update',      [CookieController::class, 'updateSiteCookie']);
    });

    // DSAR — strict per-IP to deter spam submissions from one visitor.
    Route::prefix('dsar')->middleware('throttle:dsar-public')->group(function () {
        Route::post('submit',           [DsarController::class, 'submit']);
        Route::get('verify/{token}',    [DsarController::class, 'verify'])->name('api.dsar.verify');
        Route::get('status/{token}',    [DsarController::class, 'status'])->name('api.dsar.status');
        Route::get('download/{token}',  [DsarController::class, 'download'])->name('api.dsar.download');
    });
});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
