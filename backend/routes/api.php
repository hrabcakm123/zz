<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CasController;
use App\Http\Controllers\AnimationController;
use App\Http\Controllers\StatsController;
use App\Http\Controllers\DocumentationController;

Route::middleware('api.token')->group(function () {
    Route::post('/cas/command', [CasController::class, 'executeCommand']);
    Route::get('/logs/export', [CasController::class, 'exportLogs']);
    Route::post('/animation/pendulum', [AnimationController::class, 'pendulum']);
    Route::post('/animation/ballbeam', [AnimationController::class, 'ballbeam']);
    Route::get('/stats/animations', [StatsController::class, 'animationStats']);
    Route::get('/documentation/pdf', [DocumentationController::class, 'exportPdf']);
});