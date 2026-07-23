<?php

use Illuminate\Support\Facades\Route;

/**
 * API v1
 * Legacy document endpoint
 */

// Legacy document routes
// /api/v1/doc/*
Route::middleware(['throttle:12'])->group(function () {
    Route::get('/v1/doc', 'App\Http\Controllers\Api\DocumentController@index')
        ->name('api.document.data.legacy');
});
