<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web routes
|--------------------------------------------------------------------------
| Entry-level routes only. Everything behind /admin is in routes/admin.php.
| Module-specific public routes (voting, results) are loaded by the
| ModulesServiceProvider from each module's routes directory.
*/

// Root: send guests to /login, signed-in admins to the admin landing.
// Voters arrive at the bare domain rarely (they normally come via a
// /vote/club/{token} deep link), so the home URL is reserved for
// admin/staff entry.
Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('admin.landing')
        : redirect()->route('login');
})->name('home');

Route::middleware('guest')->group(function () {
    Route::get('login',  [LoginController::class, 'show'])->name('login');
    Route::post('login', [LoginController::class, 'store'])->name('login.attempt');

    // Forgot / reset password — public (can't be behind auth).
    Route::get('password/forgot',         [ForgotPasswordController::class, 'show'])->name('password.request');
    Route::post('password/forgot',        [ForgotPasswordController::class, 'send'])->name('password.email');
    Route::get('password/reset/{token}',  [ForgotPasswordController::class, 'showReset'])->name('password.reset');
    Route::post('password/reset',         [ForgotPasswordController::class, 'reset'])->name('password.update');
});

Route::post('logout', [LoginController::class, 'destroy'])
    ->middleware('auth')->name('logout');

// Profile — any authenticated user can see/edit their own profile.
Route::middleware('auth')->group(function () {
    Route::get('profile',           [ProfileController::class, 'show'])->name('profile.show');
    Route::post('profile',          [ProfileController::class, 'update'])->name('profile.update');
    Route::post('profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
});

Route::middleware('web')->get('/set-locale/{locale}', [LocaleController::class, 'switch'])
    ->name('locale.switch');

require __DIR__.'/admin.php';
