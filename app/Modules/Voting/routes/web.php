<?php

use App\Modules\Voting\Http\Controllers\PublicVoteController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public voting routes
|--------------------------------------------------------------------------
| Rate limits are per-IP. They are tuned so a real voter (one
| verification + one submission, maybe one retry after a validation
| error) never hits the wall, while spam/brute-force attempts do.
|
| If a legitimate voter does hit 429, clearing Laravel's cache store
| (`php artisan cache:clear`) resets every throttle counter instantly.
*/

// Public directory of open campaigns — landing page for voters.
Route::get('/campaigns', [PublicVoteController::class, 'index'])
    ->middleware('throttle:120,1')
    ->name('public.campaigns');

Route::prefix('vote')->group(function () {
    Route::get('{token}', [PublicVoteController::class, 'show'])
        ->middleware('throttle:60,1')
        ->name('voting.show');

    // Identity verification is the only endpoint where we want a
    // strict limit — it's the only one that accepts a guess. 10/min
    // still gives ~3 tries per minute of typos before blocking.
    Route::post('{token}/verify', [PublicVoteController::class, 'verify'])
        ->middleware('throttle:10,1')
        ->name('voting.verify');

    Route::get('{token}/form', [PublicVoteController::class, 'form'])
        ->middleware('throttle:60,1')
        ->name('voting.form');

    // Submit — duplicate-vote prevention + auto-close-at-max-voters
    // already cap misuse at the domain layer, so the rate limit here
    // only exists to throttle accidental rapid resubmits.
    Route::post('{token}/submit', [PublicVoteController::class, 'submit'])
        ->middleware('throttle:20,1')
        ->name('voting.submit');

    Route::get('{token}/thanks', [PublicVoteController::class, 'thanks'])
        ->name('voting.thanks');
});
