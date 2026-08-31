<?php

use App\Http\Controllers\Api\SyncInboundController;
use App\Http\Controllers\Api\SyncBootstrapController;
use Illuminate\Support\Facades\Route;

Route::middleware(['sync.ip', 'throttle:sync-api'])->group(function () {
    Route::post('/sync/push', [SyncInboundController::class, 'push'])->name('api.sync.push');
    Route::get('/sync/bootstrap/master', [SyncBootstrapController::class, 'master'])->name('api.sync.bootstrap.master');
    Route::post('/sync/pull/master', [SyncBootstrapController::class, 'masterIncremental'])->name('api.sync.pull.master');
});
