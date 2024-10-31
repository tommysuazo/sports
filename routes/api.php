<?php

use App\Http\Controllers\NbaGameController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware(['api'])->group(function () {
    // Aquí puedes añadir más rutas para tu API
    Route::get('/test', function () {
        return response()->json(['message' => 'API funcionando correctamente']);
    });

    Route::prefix('/nba')->group(function () {
        Route::prefix('/games')->group(function () {
            Route::get('/', [NbaGameController::class, 'index']);
            Route::post('/', [NbaGameController::class, 'store']);
        });
    });
});


