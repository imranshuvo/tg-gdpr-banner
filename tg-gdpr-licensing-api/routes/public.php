<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LandingController;

// Public landing page routes
Route::get('/', [LandingController::class, 'index'])->name('landing');
Route::get('/pricing', [LandingController::class, 'pricing'])->name('pricing');
Route::post('/contact', [LandingController::class, 'contact'])->name('contact');
Route::post('/download', [LandingController::class, 'download'])->name('download');
Route::get('/privacy-policy', [LandingController::class, 'privacyPolicy'])->name('privacy-policy');
Route::get('/terms-of-service', [LandingController::class, 'termsOfService'])->name('terms-of-service');
