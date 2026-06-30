<?php

use App\Http\Controllers\Admin\AdminGameController;
use App\Models\Game;
use App\Models\Workspace;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin');
});

Route::get('/dashboard', function () {
    return redirect('/admin');
})->middleware(['auth'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/games', [AdminGameController::class, 'index'])->name('games.index');
    Route::get('/games/{game}/edit', [AdminGameController::class, 'edit'])->name('games.edit');
    Route::patch('/games/{game}', [AdminGameController::class, 'update'])->name('games.update');
    Route::get('/games/{game}/reward-codes', [AdminGameController::class, 'rewardCodes'])->name('games.reward-codes');
    Route::post('/games/{game}/reward-codes', [AdminGameController::class, 'storeRewardCodes'])->name('games.reward-codes.store');
    Route::get('/games/{game}/submissions', [AdminGameController::class, 'submissions'])->name('games.submissions');
    Route::get('/games/{game}/activity', [AdminGameController::class, 'activity'])->name('games.activity');
    Route::get('/games/{game}/claims', [AdminGameController::class, 'claims'])->name('games.claims');
    Route::patch('/games/{game}/claims/{claim}/fulfill', [AdminGameController::class, 'fulfillClaim'])->name('games.claims.fulfill');

    Route::get('/workspaces/{workspace}', function (Workspace $workspace) {
        return response()->json([
            'id' => $workspace->id,
            'slug' => $workspace->slug,
            'name' => $workspace->name,
            'status' => $workspace->status,
        ]);
    })->can('view', 'workspace')->name('workspaces.show');

    Route::get('/games/{game}', function (Game $game) {
        return response()->json([
            'id' => $game->id,
            'workspace_id' => $game->workspace_id,
            'slug' => $game->slug,
            'name' => $game->name,
            'status' => $game->status?->value ?? $game->status,
        ]);
    })->can('view', 'game')->name('games.show');
});

require __DIR__.'/auth.php';
