<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// /dashboard kept as a thin role-redirect shim so old bookmarks, the
// Breeze password-reset success flow, and email-verification links still
// land somewhere useful. The real per-role landing pages are
// /admin (super admins) and /customer (customers).
Route::get('/dashboard', function () {
    $user = auth()->user();
    return redirect($user?->isAdmin() ? route('admin.dashboard') : route('customer.dashboard'));
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

// Simple test route
Route::get('/test-simple', [\App\Http\Controllers\TestController::class, 'index'])->name('test.simple');
