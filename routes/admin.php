<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Admin routes (prefix /admin, middleware ['web','auth'])
|--------------------------------------------------------------------------
| Everything behind the admin dashboard lives here. Names are namespaced
| as `admin.{module}.{action}` so views can call route() without
| hard-coding strings.
|
| Loaded by routes/web.php.
*/

use App\Http\Controllers\Admin\AdminCampaignController;
use App\Http\Controllers\Admin\AdminCategoryController;
use App\Http\Controllers\Admin\AdminClubController;
use App\Http\Controllers\Admin\AdminLandingController;
use App\Http\Controllers\Admin\AdminPlayerController;
use App\Http\Controllers\Admin\AdminResultController;
use App\Http\Controllers\Admin\AdminRoleController;
use App\Http\Controllers\Admin\AdminSettingsController;
use App\Http\Controllers\Admin\AdminTeamOfSeasonController;
use App\Http\Controllers\Admin\AdminEmailTemplateController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\ArchiveHubController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', AdminLandingController::class)->name('landing');

    // Archive hub — one screen with per-module counts + links.
    Route::get('archive', ArchiveHubController::class)->name('archive');

    // Clubs
    Route::prefix('clubs')->name('clubs.')->group(function () {
        Route::get('/',                       [AdminClubController::class, 'index'])->name('index');
        Route::get('create',                  [AdminClubController::class, 'create'])->name('create');
        Route::post('/',                      [AdminClubController::class, 'store'])->name('store');
        Route::get('export',                  [AdminClubController::class, 'export'])->name('export');
        Route::get('export/template',         [AdminClubController::class, 'exportTemplate'])->name('export.template');
        Route::post('import',                 [AdminClubController::class, 'import'])->name('import');
        // Archive routes BEFORE {club} wildcard (same ordering rule
        // we used for users — "archive" must not be parsed as an id).
        Route::get('archive',                 [AdminClubController::class, 'archive'])->name('archive');
        Route::post('archive/{id}/restore',   [AdminClubController::class, 'restore'])->name('restore');
        Route::delete('archive/{id}/force',   [AdminClubController::class, 'forceDelete'])->name('forceDelete');
        Route::get('{club}/edit',             [AdminClubController::class, 'edit'])->name('edit');
        Route::put('{club}',                  [AdminClubController::class, 'update'])->name('update');
        Route::post('{club}/toggle',          [AdminClubController::class, 'toggle'])->name('toggle');
        Route::delete('{club}',               [AdminClubController::class, 'destroy'])->name('destroy');
    });

    // Players
    Route::prefix('players')->name('players.')->group(function () {
        Route::get('/',                       [AdminPlayerController::class, 'index'])->name('index');
        Route::get('create',                  [AdminPlayerController::class, 'create'])->name('create');
        Route::post('/',                      [AdminPlayerController::class, 'store'])->name('store');
        Route::get('export',                  [AdminPlayerController::class, 'export'])->name('export');
        Route::get('export/template',         [AdminPlayerController::class, 'exportTemplate'])->name('export.template');
        Route::post('import',                 [AdminPlayerController::class, 'import'])->name('import');
        Route::get('archive',                 [AdminPlayerController::class, 'archive'])->name('archive');
        Route::post('archive/{id}/restore',   [AdminPlayerController::class, 'restore'])->name('restore');
        Route::delete('archive/{id}/force',   [AdminPlayerController::class, 'forceDelete'])->name('forceDelete');
        Route::get('{player}/edit',           [AdminPlayerController::class, 'edit'])->name('edit');
        Route::put('{player}',                [AdminPlayerController::class, 'update'])->name('update');
        Route::delete('{player}',             [AdminPlayerController::class, 'destroy'])->name('destroy');
    });

    // Campaigns
    Route::prefix('campaigns')->name('campaigns.')->group(function () {
        Route::get('/',                              [AdminCampaignController::class, 'index'])->name('index');
        Route::get('create',                         [AdminCampaignController::class, 'create'])->name('create');
        Route::post('/',                             [AdminCampaignController::class, 'store'])->name('store');
        // Soft-delete archive index. Name it `archiveList` because
        // `admin.campaigns.archive` is already taken by the existing
        // lifecycle POST (Active → Archived status transition).
        Route::get('archive',                        [AdminCampaignController::class, 'archiveIndex'])->name('archiveList');
        Route::post('archive/{id}/restore',          [AdminCampaignController::class, 'restore'])->name('restore');
        Route::delete('archive/{id}/force',          [AdminCampaignController::class, 'forceDelete'])->name('forceDelete');
        Route::get('{campaign}',                     [AdminCampaignController::class, 'show'])->name('show');
        Route::delete('{campaign}',                  [AdminCampaignController::class, 'destroy'])->name('destroy');
        Route::get('{campaign}/edit',                [AdminCampaignController::class, 'edit'])->name('edit');
        Route::put('{campaign}',                     [AdminCampaignController::class, 'update'])->name('update');
        Route::get('{campaign}/stats',               [AdminCampaignController::class, 'stats'])->name('stats');
        Route::post('{campaign}/submit-approval',    [AdminCampaignController::class, 'submitForApproval'])->name('submit-approval');
        Route::post('{campaign}/approve',            [AdminCampaignController::class, 'approve'])->name('approve');
        Route::post('{campaign}/reject',             [AdminCampaignController::class, 'reject'])->name('reject');
        Route::post('{campaign}/publish',            [AdminCampaignController::class, 'publish'])->name('publish');
        Route::post('{campaign}/activate',           [AdminCampaignController::class, 'activate'])->name('activate');
        Route::post('{campaign}/close',              [AdminCampaignController::class, 'close'])->name('close');
        Route::post('{campaign}/archive',            [AdminCampaignController::class, 'archive'])->name('archive');
    });

    // Campaign categories & candidates (legacy non-TOS paths)
    Route::get('campaigns/{campaign}/categories',    [AdminCategoryController::class, 'index'])->name('categories.index');
    Route::post('campaigns/{campaign}/categories',   [AdminCategoryController::class, 'store'])->name('categories.store');
    Route::put('categories/{category}',              [AdminCategoryController::class, 'update'])->name('categories.update');
    Route::delete('categories/{category}',           [AdminCategoryController::class, 'destroy'])->name('categories.destroy');
    Route::post('categories/{category}/candidates',  [AdminCategoryController::class, 'storeCandidate'])->name('categories.candidates.store');
    Route::delete('candidates/{candidate}',          [AdminCategoryController::class, 'destroyCandidate'])->name('candidates.destroy');

    // Team of the Season (dedicated flow)
    Route::prefix('tos')->name('tos.')->group(function () {
        Route::get('create',                         [AdminTeamOfSeasonController::class, 'create'])->name('create');
        Route::post('/',                             [AdminTeamOfSeasonController::class, 'store'])->name('store');
        Route::get('{campaign}/candidates',          [AdminTeamOfSeasonController::class, 'candidates'])->name('candidates');
        Route::post('{campaign}/candidates',         [AdminTeamOfSeasonController::class, 'attachCandidates'])->name('candidates.attach');
    });

    // Results
    Route::prefix('results')->name('results.')->group(function () {
        Route::get('/',                              [AdminResultController::class, 'index'])->name('index');
        Route::get('{campaign}',                     [AdminResultController::class, 'show'])->name('show');
        Route::post('{campaign}/calculate',          [AdminResultController::class, 'calculate'])->name('calculate');
        Route::post('approve/{result}',              [AdminResultController::class, 'approve'])->name('approve');
        Route::post('hide/{result}',                 [AdminResultController::class, 'hide'])->name('hide');
        Route::post('announce/{result}',             [AdminResultController::class, 'announce'])->name('announce');
        Route::post('{result}/resolve-tie',          [AdminResultController::class, 'resolveTie'])->name('resolveTie');
    });

    // Email templates — admin-editable bodies for every system email.
    Route::prefix('email-templates')->name('email-templates.')->group(function () {
        Route::get('/',        [AdminEmailTemplateController::class, 'index'])->name('index');
        Route::get('edit',     [AdminEmailTemplateController::class, 'edit'])->name('edit');
        Route::post('/',       [AdminEmailTemplateController::class, 'update'])->name('update');
        Route::post('preview', [AdminEmailTemplateController::class, 'preview'])->name('preview');
    });

    // Settings
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/',                              [AdminSettingsController::class, 'index'])->name('index');
        Route::post('general',                       [AdminSettingsController::class, 'updateGeneral'])->name('general.update');
        Route::post('mail',                          [AdminSettingsController::class, 'updateMail'])->name('mail.update');
        Route::post('sms',                           [AdminSettingsController::class, 'updateSms'])->name('sms.update');
        Route::post('sports',                        [AdminSettingsController::class, 'storeSport'])->name('sports.store');
        Route::put('sports/{sport}',                 [AdminSettingsController::class, 'updateSport'])->name('sports.update');
        Route::delete('sports/{sport}',              [AdminSettingsController::class, 'destroySport'])->name('sports.destroy');
        Route::post('leagues',                       [AdminSettingsController::class, 'storeLeague'])->name('leagues.store');
        Route::delete('leagues/{league}',            [AdminSettingsController::class, 'destroyLeague'])->name('leagues.destroy');
        Route::get('leagues/{league}/clubs',         [AdminSettingsController::class, 'leagueClubs'])->name('leagues.clubs');
    });

    // Users
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/',                              [AdminUserController::class, 'index'])->name('index');
        Route::get('create',                         [AdminUserController::class, 'create'])->name('create');
        Route::post('/',                             [AdminUserController::class, 'store'])->name('store');
        Route::post('bulk-delete',                   [AdminUserController::class, 'bulkDelete'])->name('bulkDelete');

        // Archive (soft-deleted). MUST come before the {user} wildcard
        // routes so "archive" isn't captured as a user id.
        Route::get('archive',                        [AdminUserController::class, 'archive'])->name('archive');
        Route::post('archive/bulk-restore',          [AdminUserController::class, 'bulkRestore'])->name('bulkRestore');
        Route::post('archive/bulk-force',            [AdminUserController::class, 'bulkForceDelete'])->name('bulkForceDelete');
        Route::post('archive/{id}/restore',          [AdminUserController::class, 'restore'])->name('restore');
        Route::delete('archive/{id}/force',          [AdminUserController::class, 'forceDelete'])->name('forceDelete');

        Route::get('{user}/edit',                    [AdminUserController::class, 'edit'])->name('edit');
        Route::put('{user}',                         [AdminUserController::class, 'update'])->name('update');
        Route::post('{user}/toggle',                 [AdminUserController::class, 'toggle'])->name('toggle');
        Route::delete('{user}',                      [AdminUserController::class, 'destroy'])->name('destroy');
    });

    // Roles
    Route::prefix('roles')->name('roles.')->group(function () {
        Route::get('/',                              [AdminRoleController::class, 'index'])->name('index');
        Route::get('create',                         [AdminRoleController::class, 'create'])->name('create');
        Route::post('/',                             [AdminRoleController::class, 'store'])->name('store');
        Route::get('{role}/edit',                    [AdminRoleController::class, 'edit'])->name('edit');
        Route::put('{role}',                         [AdminRoleController::class, 'update'])->name('update');
        Route::delete('{role}',                      [AdminRoleController::class, 'destroy'])->name('destroy');
    });
});
