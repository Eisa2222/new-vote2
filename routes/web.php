<?php

use App\Http\Controllers\Admin\AdminCampaignController;
use App\Http\Controllers\Admin\AdminClubController;
use App\Http\Controllers\Admin\AdminPlayerController;
use App\Http\Controllers\Admin\AdminResultController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect('/login'));

Route::middleware('guest')->group(function () {
    Route::get('login',  [LoginController::class, 'show'])->name('login');
    Route::post('login', [LoginController::class, 'store']);
});

Route::post('logout', [LoginController::class, 'destroy'])
    ->middleware('auth')->name('logout');

Route::middleware(['web', 'auth'])->prefix('admin')->group(function () {
    Route::view('/', 'admin.dashboard');

    Route::get('clubs',                [AdminClubController::class, 'index']);
    Route::get('clubs/create',         [AdminClubController::class, 'create']);
    Route::post('clubs',               [AdminClubController::class, 'store']);
    Route::get('clubs/{club}/edit',    [AdminClubController::class, 'edit']);
    Route::put('clubs/{club}',         [AdminClubController::class, 'update']);

    Route::get('players',                    [AdminPlayerController::class, 'index']);
    Route::get('players/create',             [AdminPlayerController::class, 'create']);
    Route::post('players',                   [AdminPlayerController::class, 'store']);
    Route::get('players/{player}/edit',      [AdminPlayerController::class, 'edit']);
    Route::put('players/{player}',           [AdminPlayerController::class, 'update']);
    Route::delete('players/{player}',        [AdminPlayerController::class, 'destroy']);

    Route::get('campaigns',                       [AdminCampaignController::class, 'index']);
    Route::get('campaigns/create',                [AdminCampaignController::class, 'create']);
    Route::post('campaigns',                      [AdminCampaignController::class, 'store']);
    Route::get('campaigns/{campaign}',            [AdminCampaignController::class, 'show']);
    Route::post('campaigns/{campaign}/publish',   [AdminCampaignController::class, 'publish']);
    Route::post('campaigns/{campaign}/close',     [AdminCampaignController::class, 'close']);

    Route::get('results',                        [AdminResultController::class, 'index'])->name('results.index');
    Route::get('results/{campaign}',             [AdminResultController::class, 'show'])->name('results.show');
    Route::post('results/{campaign}/calculate',  [AdminResultController::class, 'calculate'])->name('results.calculate');
    Route::post('results/approve/{result}',      [AdminResultController::class, 'approve'])->name('results.approve');
    Route::post('results/hide/{result}',         [AdminResultController::class, 'hide'])->name('results.hide');
    Route::post('results/announce/{result}',     [AdminResultController::class, 'announce'])->name('results.announce');

    Route::get('users',                  [AdminUserController::class, 'index']);
    Route::get('users/create',           [AdminUserController::class, 'create']);
    Route::post('users',                 [AdminUserController::class, 'store']);
    Route::get('users/{user}/edit',      [AdminUserController::class, 'edit']);
    Route::put('users/{user}',           [AdminUserController::class, 'update']);
});

// Set locale via ?locale=ar|en
Route::middleware('web')->get('/set-locale/{locale}', function (string $locale) {
    if (in_array($locale, ['ar', 'en'], true)) {
        session(['locale' => $locale]);
        app()->setLocale($locale);
    }
    return back();
});
