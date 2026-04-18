<?php

use App\Http\Controllers\Admin\AdminCampaignController;
use App\Http\Controllers\Admin\AdminCategoryController;
use App\Http\Controllers\Admin\AdminClubController;
use App\Http\Controllers\Admin\AdminPlayerController;
use App\Http\Controllers\Admin\AdminResultController;
use App\Http\Controllers\Admin\AdminRoleController;
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
    Route::get('/', function () {
        $u = auth()->user();
        // Committee members land on the campaigns board so they see the
        // pending-approval queue immediately at the top. They can move
        // to /admin/results whenever they want to approve a result.
        if ($u && $u->hasRole('committee') && ! $u->can('users.manage')) {
            return redirect('/admin/campaigns');
        }
        // Campaign managers land on their campaigns board too.
        if ($u && $u->hasRole('campaign_manager') && ! $u->can('users.manage')) {
            return redirect('/admin/campaigns');
        }
        return view('admin.dashboard');
    });

    Route::get('clubs',                       [AdminClubController::class, 'index']);
    Route::get('clubs/create',                [AdminClubController::class, 'create']);
    Route::post('clubs',                      [AdminClubController::class, 'store']);
    // Import / export — these must come BEFORE /{club} routes so the words
    // "export"/"import"/"template" aren't captured as a club slug parameter.
    Route::get('clubs/export',                [AdminClubController::class, 'export']);
    Route::get('clubs/export/template',       [AdminClubController::class, 'exportTemplate']);
    Route::post('clubs/import',               [AdminClubController::class, 'import']);
    Route::get('clubs/{club}/edit',           [AdminClubController::class, 'edit']);
    Route::put('clubs/{club}',                [AdminClubController::class, 'update']);
    Route::post('clubs/{club}/toggle',        [AdminClubController::class, 'toggle']);
    Route::delete('clubs/{club}',             [AdminClubController::class, 'destroy']);

    Route::get('players',                     [AdminPlayerController::class, 'index']);
    Route::get('players/create',              [AdminPlayerController::class, 'create']);
    Route::post('players',                    [AdminPlayerController::class, 'store']);
    Route::get('players/export',              [AdminPlayerController::class, 'export']);
    Route::get('players/export/template',     [AdminPlayerController::class, 'exportTemplate']);
    Route::post('players/import',             [AdminPlayerController::class, 'import']);
    Route::get('players/{player}/edit',       [AdminPlayerController::class, 'edit']);
    Route::put('players/{player}',            [AdminPlayerController::class, 'update']);
    Route::delete('players/{player}',         [AdminPlayerController::class, 'destroy']);

    Route::get('campaigns',                              [AdminCampaignController::class, 'index']);
    Route::get('campaigns/create',                       [AdminCampaignController::class, 'create']);
    Route::post('campaigns',                             [AdminCampaignController::class, 'store']);
    Route::get('campaigns/{campaign}',                   [AdminCampaignController::class, 'show']);
    Route::delete('campaigns/{campaign}',                [AdminCampaignController::class, 'destroy']);
    Route::get('campaigns/{campaign}/edit',              [AdminCampaignController::class, 'edit']);
    Route::put('campaigns/{campaign}',                   [AdminCampaignController::class, 'update']);
    Route::get('campaigns/{campaign}/stats',             [AdminCampaignController::class, 'stats']);
    Route::post('campaigns/{campaign}/submit-approval',  [AdminCampaignController::class, 'submitForApproval']);
    Route::post('campaigns/{campaign}/approve',          [AdminCampaignController::class, 'approve']);
    Route::post('campaigns/{campaign}/reject',           [AdminCampaignController::class, 'reject']);
    Route::post('campaigns/{campaign}/publish',          [AdminCampaignController::class, 'publish']);
    Route::post('campaigns/{campaign}/activate',         [AdminCampaignController::class, 'activate']);
    Route::post('campaigns/{campaign}/close',            [AdminCampaignController::class, 'close']);
    Route::post('campaigns/{campaign}/archive',          [AdminCampaignController::class, 'archive']);

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
    Route::post('results/{result}/resolve-tie', [AdminResultController::class, 'resolveTie'])->name('results.resolveTie');

    Route::get('settings',                        [AdminSettingsController::class, 'index'])->name('admin.settings');
    Route::post('settings/general',               [AdminSettingsController::class, 'updateGeneral']);
    Route::post('settings/sports',                [AdminSettingsController::class, 'storeSport']);
    Route::put('settings/sports/{sport}',         [AdminSettingsController::class, 'updateSport']);
    Route::delete('settings/sports/{sport}',      [AdminSettingsController::class, 'destroySport']);
    Route::post('settings/leagues',               [AdminSettingsController::class, 'storeLeague']);
    Route::delete('settings/leagues/{league}',    [AdminSettingsController::class, 'destroyLeague']);
    Route::get('settings/leagues/{league}/clubs', [AdminSettingsController::class, 'leagueClubs']);

    Route::get('users',                  [AdminUserController::class, 'index']);
    Route::get('users/create',           [AdminUserController::class, 'create']);
    Route::post('users',                 [AdminUserController::class, 'store']);
    Route::get('users/{user}/edit',      [AdminUserController::class, 'edit']);
    Route::put('users/{user}',           [AdminUserController::class, 'update']);
    Route::post('users/{user}/toggle',   [AdminUserController::class, 'toggle']);
    Route::delete('users/{user}',        [AdminUserController::class, 'destroy']);

    Route::get('roles',              [AdminRoleController::class, 'index'])->name('admin.roles.index');
    Route::get('roles/create',       [AdminRoleController::class, 'create'])->name('admin.roles.create');
    Route::post('roles',             [AdminRoleController::class, 'store'])->name('admin.roles.store');
    Route::get('roles/{role}/edit', [AdminRoleController::class, 'edit'])->name('admin.roles.edit');
    Route::put('roles/{role}',      [AdminRoleController::class, 'update'])->name('admin.roles.update');
    Route::delete('roles/{role}',   [AdminRoleController::class, 'destroy'])->name('admin.roles.destroy');
});

// Set locale via ?locale=ar|en
Route::middleware('web')->get('/set-locale/{locale}', function (string $locale) {
    if (in_array($locale, ['ar', 'en'], true)) {
        session(['locale' => $locale]);
        app()->setLocale($locale);
    }
    return back();
});
