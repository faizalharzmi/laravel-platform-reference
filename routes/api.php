<?php

use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\TokenController;
use Illuminate\Support\Facades\Route;

Route::post('/v1/tokens', [TokenController::class, 'store']);

Route::middleware('auth:sanctum')->prefix('v1')->group(function (): void {
    Route::post('/workspaces/{workspace}/projects', [ProjectController::class, 'store']);
});
