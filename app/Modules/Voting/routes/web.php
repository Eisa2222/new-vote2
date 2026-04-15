<?php

use App\Modules\Voting\Http\Controllers\PublicVoteController;
use Illuminate\Support\Facades\Route;

Route::prefix('vote')->group(function () {
    Route::get('{token}',
        [PublicVoteController::class, 'show'])
        ->middleware('throttle:30,1')            // 30 GETs per minute per IP
        ->name('voting.show');

    Route::post('{token}',
        [PublicVoteController::class, 'submit'])
        ->middleware('throttle:5,1')             // 5 POSTs per minute per IP — anti-spam
        ->name('voting.submit');

    Route::get('{token}/thanks',
        [PublicVoteController::class, 'thanks'])
        ->name('voting.thanks');
});
