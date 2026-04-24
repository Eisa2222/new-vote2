<?php

use App\Modules\Results\Http\Controllers\PublicResultsController;
use Illuminate\Support\Facades\Route;

Route::get('/results', [PublicResultsController::class, 'index'])->middleware('throttle:120,1')->name('public.results.index');
Route::get('/results/{token}', [PublicResultsController::class, 'show'])->middleware('throttle:60,1')->name('public.results');
