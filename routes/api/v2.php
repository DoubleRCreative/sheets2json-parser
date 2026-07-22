<?php

use Illuminate\Support\Facades\Route;

/**
 * API v2
 */

// Parsing Api
Route::group([
        'prefix' => 'v2',
        'namespace' => 'App\Http\Controllers\Api'
    ], function () {
    // Document routes
    // /api/v2/doc/*
    Route::middleware([])->group(function () {
        Route::get('/doc', 'DocumentController@indexV2')
            ->name('api.document.data');
        Route::get('/doc/stream', 'DocumentStreamController@stream')
            ->name('api.document.stream');
    });
});
