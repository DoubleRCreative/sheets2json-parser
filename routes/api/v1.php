<?php

use Illuminate\Support\Facades\Route;

/**
 * Parsing API v1
 */
Route::group([
        'prefix' => 'v1',
        'namespace' => 'App\Http\Controllers\Api'
    ], function () {
    // Document routes
    // /api/v1/doc/*
    Route::middleware([])->group(function () {
        Route::get('/doc/stream', 'DocumentStreamController@stream')
            ->name('api.document.parse');
    });
});