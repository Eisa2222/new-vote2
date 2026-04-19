<?php

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
});
