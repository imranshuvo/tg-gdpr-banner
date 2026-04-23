<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\LicenseController;
use App\Http\Controllers\Admin\SiteController;
use App\Http\Controllers\Admin\CookieDefinitionController;
use App\Http\Controllers\Admin\DsarController;
use App\Http\Controllers\Admin\SettingsController;

// Admin routes - Protected by auth and role:admin middleware
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        // Mail settings
        Route::get('settings/mail', [SettingsController::class, 'mail'])->name('settings.mail');
        Route::post('settings/mail/test', [SettingsController::class, 'testMail'])->name('settings.mail.test');
    
    // Customers
    Route::resource('customers', CustomerController::class);
    
    // Licenses  
    Route::resource('licenses', LicenseController::class);
    Route::post('licenses/{license}/revoke', [LicenseController::class, 'revoke'])->name('licenses.revoke');
    Route::post('licenses/{license}/extend', [LicenseController::class, 'extend'])->name('licenses.extend');
    Route::delete('licenses/{license}/activations/{activation}', [LicenseController::class, 'deactivateSite'])->name('licenses.deactivate-site');
    
    // Sites (CMP Tenants)
    Route::resource('sites', SiteController::class);
    Route::get('sites/{site}/settings', [SiteController::class, 'settings'])->name('sites.settings');
    Route::put('sites/{site}/settings', [SiteController::class, 'updateSettings'])->name('sites.settings.update');
    Route::post('sites/{site}/regenerate-token', [SiteController::class, 'regenerateToken'])->name('sites.regenerate-token');
    Route::post('sites/{site}/increment-policy', [SiteController::class, 'incrementPolicy'])->name('sites.increment-policy');
    Route::get('sites/{site}/cookies', [SiteController::class, 'cookies'])->name('sites.cookies');
    Route::get('sites/{site}/consents', [SiteController::class, 'consents'])->name('sites.consents');
    Route::get('sites/{site}/analytics', [SiteController::class, 'analytics'])->name('sites.analytics');
    
    // Global Cookie Definitions
    Route::resource('cookie-definitions', CookieDefinitionController::class);
    Route::post('cookie-definitions/{cookieDefinition}/verify', [CookieDefinitionController::class, 'verify'])->name('cookie-definitions.verify');
    Route::post('cookie-definitions/import', [CookieDefinitionController::class, 'import'])->name('cookie-definitions.import');
    
    // DSAR Requests
    Route::get('dsar', [DsarController::class, 'index'])->name('dsar.index');
    Route::get('dsar/{dsarRequest}', [DsarController::class, 'show'])->name('dsar.show');
    Route::post('dsar/{dsarRequest}/start', [DsarController::class, 'startProcessing'])->name('dsar.start');
    Route::post('dsar/{dsarRequest}/process', [DsarController::class, 'process'])->name('dsar.process');
    Route::get('dsar/{dsarRequest}/download', [DsarController::class, 'download'])->name('dsar.download');
});
