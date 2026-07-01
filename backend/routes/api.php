<?php

use App\Http\Controllers\Api\RuntimeGameController;
use Illuminate\Support\Facades\Route;

require __DIR__.'/spin.php';
require __DIR__.'/webhook.php';

Route::prefix('games/{publicIdentifier}')->group(function () {
    Route::get('/bootstrap', [RuntimeGameController::class, 'bootstrap']);
    Route::post('/submissions', [RuntimeGameController::class, 'storeSubmission']);
    Route::post('/eligibility-check', [RuntimeGameController::class, 'checkEligibility']);
    Route::post('/spin', [RuntimeGameController::class, 'spin']);
    Route::post('/claim', [RuntimeGameController::class, 'claim']);
});
