<?php

use App\Modules\Voting\Http\Controllers\PublicVoteController;
use Illuminate\Support\Facades\Route;

Route::prefix('vote')->group(function () {
    Route::get('{token}', [PublicVoteController::class, 'show'])
        ->middleware('throttle:30,1')
        ->name('voting.show');

    Route::post('{token}/verify', [PublicVoteController::class, 'verify'])
        ->middleware('throttle:5,1')               // anti brute-force on identity
        ->name('voting.verify');

    Route::get('{token}/form', [PublicVoteController::class, 'form'])
        ->middleware('throttle:30,1')
        ->name('voting.form');

    Route::post('{token}/submit', [PublicVoteController::class, 'submit'])
        ->middleware('throttle:5,1')
        ->name('voting.submit');

    Route::get('{token}/thanks', [PublicVoteController::class, 'thanks'])
        ->name('voting.thanks');
});
