<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CasController;

Route::middleware('api.token')->group(function () {
    Route::post('/cas/command', [CasController::class, 'executeCommand']);
    Route::get('/logs/export', [CasController::class, 'exportLogs']);
});