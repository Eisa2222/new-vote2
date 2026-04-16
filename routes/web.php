<?php

use App\Http\Controllers\Admin\AdminCampaignController;
use App\Http\Controllers\Admin\AdminCategoryController;
use App\Http\Controllers\Admin\AdminClubController;
use App\Http\Controllers\Admin\AdminPlayerController;
use App\Http\Controllers\Admin\AdminResultController;
use App\Http\Controllers\Admin\AdminSettingsController;
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
    Route::post('clubs/{club}/toggle', [AdminClubController::class, 'toggle']);
    Route::delete('clubs/{club}',      [AdminClubController::class, 'destroy']);

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
    Route::get('campaigns/{campaign}/edit',       [AdminCampaignController::class, 'edit']);
    Route::put('campaigns/{campaign}',            [AdminCampaignController::class, 'update']);
    Route::get('campaigns/{campaign}/stats',      [AdminCampaignController::class, 'stats']);
    Route::post('campaigns/{campaign}/publish',   [AdminCampaignController::class, 'publish']);
    Route::post('campaigns/{campaign}/activate',  [AdminCampaignController::class, 'activate']);
    Route::post('campaigns/{campaign}/close',     [AdminCampaignController::class, 'close']);
    Route::post('campaigns/{campaign}/archive',   [AdminCampaignController::class, 'archive']);

    Route::get('campaigns/{campaign}/categories',   [AdminCategoryController::class, 'index']);
    Route::post('campaigns/{campaign}/categories',  [AdminCategoryController::class, 'store']);
    Route::put('categories/{category}',             [AdminCategoryController::class, 'update']);
    Route::delete('categories/{category}',          [AdminCategoryController::class, 'destroy']);
    Route::post('categories/{category}/candidates', [AdminCategoryController::class, 'storeCandidate']);
    Route::delete('candidates/{candidate}',         [AdminCategoryController::class, 'destroyCandidate']);

    // Team of the Season dedicated flow
    Route::get('tos/create',                  [\App\Http\Controllers\Admin\AdminTeamOfSeasonController::class, 'create']);
    Route::post('tos',                        [\App\Http\Controllers\Admin\AdminTeamOfSeasonController::class, 'store']);
    Route::get('tos/{campaign}/candidates',   [\App\Http\Controllers\Admin\AdminTeamOfSeasonController::class, 'candidates']);
    Route::post('tos/{campaign}/candidates',  [\App\Http\Controllers\Admin\AdminTeamOfSeasonController::class, 'attachCandidates']);

    Route::get('results',                        [AdminResultController::class, 'index'])->name('results.index');
    Route::get('results/{campaign}',             [AdminResultController::class, 'show'])->name('results.show');
    Route::post('results/{campaign}/calculate',  [AdminResultController::class, 'calculate'])->name('results.calculate');
    Route::post('results/approve/{result}',      [AdminResultController::class, 'approve'])->name('results.approve');
    Route::post('results/hide/{result}',         [AdminResultController::class, 'hide'])->name('results.hide');
    Route::post('results/announce/{result}',     [AdminResultController::class, 'announce'])->name('results.announce');

    Route::get('settings',                        [AdminSettingsController::class, 'index'])->name('admin.settings');
    Route::post('settings/general',               [AdminSettingsController::class, 'updateGeneral']);
    Route::post('settings/sports',                [AdminSettingsController::class, 'storeSport']);
    Route::put('settings/sports/{sport}',         [AdminSettingsController::class, 'updateSport']);
    Route::delete('settings/sports/{sport}',      [AdminSettingsController::class, 'destroySport']);

    Route::get('users',                  [AdminUserController::class, 'index']);
    Route::get('users/create',           [AdminUserController::class, 'create']);
    Route::post('users',                 [AdminUserController::class, 'store']);
    Route::get('users/{user}/edit',      [AdminUserController::class, 'edit']);
    Route::put('users/{user}',           [AdminUserController::class, 'update']);
    Route::post('users/{user}/toggle',   [AdminUserController::class, 'toggle']);
    Route::delete('users/{user}',        [AdminUserController::class, 'destroy']);
});

// Set locale via ?locale=ar|en
Route::middleware('web')->get('/set-locale/{locale}', function (string $locale) {
    if (in_array($locale, ['ar', 'en'], true)) {
        session(['locale' => $locale]);
        app()->setLocale($locale);
    }
    return back();
});
