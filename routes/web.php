<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\LocaleController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web routes
|--------------------------------------------------------------------------
| Entry-level routes only. Everything behind /admin is in routes/admin.php.
| Module-specific public routes (voting, results) are loaded by the
| ModulesServiceProvider from each module's routes directory.
*/

Route::get('/', fn () => redirect()->route('public.campaigns'))->name('home');

Route::middleware('guest')->group(function () {
    Route::get('login',  [LoginController::class, 'show'])->name('login');
    Route::post('login', [LoginController::class, 'store'])->name('login.attempt');
});

Route::post('logout', [LoginController::class, 'destroy'])
    ->middleware('auth')->name('logout');

Route::middleware('web')->get('/set-locale/{locale}', [LocaleController::class, 'switch'])
    ->name('locale.switch');

require __DIR__.'/admin.php';
