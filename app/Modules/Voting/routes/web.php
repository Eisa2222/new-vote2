<?php

use App\Modules\Voting\Http\Controllers\Club\ClubVotingController;
use App\Modules\Voting\Http\Controllers\PublicVoteController;
use Illuminate\Support\Facades\Route;


// Public directory of open campaigns — landing page for voters.
Route::get('/campaigns', [PublicVoteController::class, 'index'])->middleware('throttle:120,1')->name('public.campaigns');

Route::prefix('vote')->group(function () {
    //
    Route::get('{token}', [PublicVoteController::class, 'show'])->middleware('throttle:60,1')->name('voting.show');

    Route::post('{token}/verify', [PublicVoteController::class, 'verify'])->middleware('throttle:10,1')->name('voting.verify');

    Route::get('{token}/form', [PublicVoteController::class, 'form'])->middleware('throttle:60,1')->name('voting.form');

    Route::post('{token}/submit', [PublicVoteController::class, 'submit'])->middleware('throttle:20,1')->name('voting.submit');

    Route::get('{token}/thanks', [PublicVoteController::class, 'thanks'])->name('voting.thanks');

    // Voter exit — clears the per-campaign voter session and sends
    // the user back to the campaigns list. POST so it cannot be
    // triggered by a prefetch or a GET link.
    Route::post('{token}/exit', [PublicVoteController::class, 'exit'])->name('voting.exit');
});

/*
|--------------------------------------------------------------------------
| Club-scoped voting (the NEW flow)
|--------------------------------------------------------------------------
| Each club receives a unique voting_link_token — players enter via
| /vote/club/{token}, pick their own name from the roster dropdown,
| and vote on Best Saudi, Best Foreign, and Team of the Season.
| Rate-limited per IP; stricter on the POSTs than the GETs.
*/
Route::prefix('vote/club')->name('voting.club.')->group(function () {
    Route::get('{token}',           [ClubVotingController::class, 'show'])
        ->middleware('throttle:60,1')->name('show');
    Route::post('{token}/start',    [ClubVotingController::class, 'start'])
        ->middleware('throttle:20,1')->name('start');
    Route::get('{token}/ballot',    [ClubVotingController::class, 'ballot'])
        ->middleware('throttle:60,1')->name('ballot');
    Route::get('{token}/already-voted', [ClubVotingController::class, 'alreadyVoted'])
        ->middleware('throttle:60,1')->name('alreadyVoted');
    Route::post('{token}/submit',   [ClubVotingController::class, 'submit'])
        ->middleware('throttle:10,1')->name('submit');
    Route::get('{token}/success',   [ClubVotingController::class, 'success'])
        ->name('success');
    Route::get('{token}/profile',   [ClubVotingController::class, 'profileForm'])
        ->name('profile');
    Route::post('{token}/profile',  [ClubVotingController::class, 'saveProfile'])
        ->middleware('throttle:20,1')->name('profile.save');
});
