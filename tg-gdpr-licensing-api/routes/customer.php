<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Customer\DashboardController as CustomerDashboardController;
use App\Http\Controllers\Customer\LicenseController as CustomerLicenseController;
use App\Http\Controllers\Customer\ApiKeyController;
use App\Http\Controllers\Customer\SubscriptionController;
use App\Http\Controllers\Customer\InvoiceController;

// Customer routes - Protected by auth and role:customer middleware
Route::middleware(['auth', 'role:customer'])->prefix('customer')->name('customer.')->group(function () {
    Route::get('/', [CustomerDashboardController::class, 'index'])->name('dashboard');
    
    // Licenses
    Route::get('/licenses', [CustomerLicenseController::class, 'index'])->name('licenses.index');
    Route::get('/licenses/{license}', [CustomerLicenseController::class, 'show'])->name('licenses.show');
    Route::get('/licenses/{license}/download', [CustomerLicenseController::class, 'download'])->name('licenses.download');
    
    // API Keys
    Route::get('/api-keys', [ApiKeyController::class, 'index'])->name('api-keys.index');
    Route::post('/api-keys/generate', [ApiKeyController::class, 'generate'])->name('api-keys.generate');
    Route::delete('/api-keys/revoke', [ApiKeyController::class, 'revoke'])->name('api-keys.revoke');
    
    // Subscriptions
    Route::get('/subscriptions', [SubscriptionController::class, 'index'])->name('subscriptions.index');
    Route::post('/subscriptions/cancel', [SubscriptionController::class, 'cancel'])->name('subscriptions.cancel');
    Route::post('/subscriptions/resume', [SubscriptionController::class, 'resume'])->name('subscriptions.resume');

    // Checkout — drives the selected payment provider's hosted checkout.
    Route::get('/checkout/{plan}', [SubscriptionController::class, 'checkout'])->name('checkout');
    
    // Invoices
    Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices.index');
    Route::get('/invoices/{invoice}/download', [InvoiceController::class, 'download'])->name('invoices.download');
});
