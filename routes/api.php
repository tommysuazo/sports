<?php

use App\Http\Controllers\NbaGameController;
use App\Http\Controllers\NbaMarketController;
use App\Http\Controllers\NbaPlayerController;
use App\Http\Controllers\NbaTeamController;
use App\Http\Controllers\NflGameController;
use App\Http\Controllers\NflMarketController;
use App\Http\Controllers\NflPlayerController;
use App\Http\Controllers\NflTeamController;
use App\Http\Controllers\NhlGameController;
use App\Http\Controllers\NhlMarketController;
use App\Http\Controllers\NhlPlayerController;
use App\Http\Controllers\NhlTeamController;
use App\Http\Controllers\TestController;
use App\Http\Controllers\WnbaGameController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware(['api'])->group(function () {
    // Aquí puedes añadir más rutas para tu API
    Route::get('/test', TestController::class);

    Route::prefix('/nba')->group(function () {
        Route::prefix('/injuries')->group(function () {
            Route::get('/', [NbaGameController::class, 'importByDateRange']);
        });

        Route::prefix('/games')->group(function () {
            Route::get('/', [NbaGameController::class, 'index']);
            Route::get('/lineups', [NbaGameController::class, 'getLineups']);
            Route::post('/import', [NbaGameController::class, 'importByDateRange']);
        });

        Route::prefix('/markets')->group(function () {
            Route::get('/', [NbaMarketController::class, 'index']);
            Route::get('/matchups', [NbaMarketController::class, 'matchups']);
            Route::put('/sync', [NbaMarketController::class, 'sync']);
            Route::put('/sync-players', [NbaMarketController::class, 'syncPlayers']);
        });

        Route::prefix('/players')->group(function () {
            Route::get('/{player}/stats', [NbaPlayerController::class, 'getStats']);
            Route::get('/{player}/scores', [NbaPlayerController::class, 'getScores']);
        });

        Route::prefix('/teams')->group(function () {
            Route::get('/stats/averages', [NbaTeamController::class, 'getAverageStatsAll']);
            Route::get('/{team}/stats/averages', [NbaTeamController::class, 'getAverageStats']);
            Route::get('/{team}/stats', [NbaTeamController::class, 'getStats']);
            Route::get('/stats/recent-performance', [NbaTeamController::class, 'getRecentPerformance']);
        });
    });

    Route::prefix('/wnba')->group(function () {
        Route::prefix('/games')->group(function () {
            Route::get('/', [WnbaGameController::class, 'index']);
            Route::post('/import', [WnbaGameController::class, 'importByDateRange']);
        });

        Route::prefix('/markets')->group(function () {
            Route::get('/', [WnbaGameController::class, 'index']);
            Route::get('/matchups', [WnbaGameController::class, 'matchups']);
            Route::put('/sync', [WnbaGameController::class, 'sync']);
            Route::put('/sync-players', [WnbaGameController::class, 'syncWnbaPlayers']);
        });
        Route::prefix('/players')->group(function () {
            Route::get('/{player}/scores', [WnbaGameController::class, 'getScores']);
        });
    });

    Route::prefix('/nfl')->group(function () {
        Route::prefix('/games')->group(function () {
            Route::get('/', [NflGameController::class, 'index']);
        });

        Route::prefix('/players')->group(function () {
            Route::get('/{player}/stats', [NflPlayerController::class, 'getStats']);
        });

        Route::prefix('/teams')->group(function () {
            Route::get('/stats/averages', [NflTeamController::class, 'getAverageStatsAll']);
            Route::get('/{team}/stats/averages', [NflTeamController::class, 'getAverageStats']);
            Route::get('/{team}/stats', [NflTeamController::class, 'getStats']);
        });

        Route::prefix('/markets')->group(function () {
            Route::get('/', [NflMarketController::class, 'index']);
            Route::get('/matchups', [NflMarketController::class, 'matchups']);
        });

    });

    Route::prefix('/nhl')->group(function () {
        Route::prefix('/games')->group(function () {
            Route::get('/', [NhlGameController::class, 'index']);
        });

        Route::prefix('/players')->group(function () {
            Route::get('/{player}/stats', [NhlPlayerController::class, 'getStats']);
        });

        Route::prefix('/teams')->group(function () {
            Route::get('/stats/averages', [NhlTeamController::class, 'getAverageStatsAll']);
            Route::get('/{team}/stats/averages', [NhlTeamController::class, 'getAverageStats']);
            Route::get('/{team}/stats', [NhlTeamController::class, 'getStats']);
        });

        Route::prefix('/markets')->group(function () {
            Route::get('/', [NhlMarketController::class, 'index']);
            Route::get('/matchups', [NhlMarketController::class, 'matchups']);
        });
    });
});
