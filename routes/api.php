<?php

use App\Http\Controllers\NbaGameController;
use App\Http\Controllers\NbaMarketController;
use App\Http\Controllers\NbaPlayerController;
use App\Http\Controllers\TestController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware(['api'])->group(function () {
    // Aquí puedes añadir más rutas para tu API
    Route::prefix('/test')->group(function () {
        Route::prefix('/nba')->group(function () {
            Route::prefix('/markets')->group(function () {
                Route::get('/points', [TestController::class, 'getFakePointsMarkets'])->name('test.nba.markets.points');
                Route::get('/game-points', [TestController::class, 'getFakePointsMarkets'])->name('test.nba.markets.game.points');
            });
        });
    });

    Route::prefix('/wnba')->group(function () {
        Route::prefix('/games')->group(function () {
            Route::get('/', [NbaGameController::class, 'index']);
            Route::post('/import', [NbaGameController::class, 'importByDateRange']);
        });

        Route::prefix('/markets')->group(function () {
            Route::get('/', [NbaMarketController::class, 'index']);
            Route::get('/matchups', [NbaMarketController::class, 'matchups']);
            Route::put('/sync', [NbaMarketController::class, 'sync']);
            Route::put('/sync-players', [NbaMarketController::class, 'syncWnbaPlayers']);
        });
        Route::prefix('/players')->group(function () {
            Route::get('/{player}/scores', [NbaPlayerController::class, 'getScores']);
        });
    });

    Route::prefix('/nba')->group(function () {
        Route::prefix('/games')->group(function () {
            Route::get('/', [NbaGameController::class, 'index']);
            Route::post('/import', [NbaGameController::class, 'importByDateRange']);
        });

        Route::prefix('/markets')->group(function () {
            Route::get('/matchups', [NbaMarketController::class, 'matchups']);
            Route::put('/sync', [NbaMarketController::class, 'sync']);
            Route::put('/sync-players', [NbaMarketController::class, 'syncPlayers']);
        });
    });
});


