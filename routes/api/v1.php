<?php

use Illuminate\Support\Facades\Route;

/**
 * Parsing API v1
 */
Route::group([
        'prefix' => 'v1',
        'namespace' => 'App\Http\Controllers\Api'
    ], function () {
    // Streaming route
    Route::middleware([])->group(function () {
        Route::get('/doc/stream', 'DocumentStreamController@stream')
            ->name('api.document.stream');
    });
    // JSON response route
    Route::middleware([])->group(function () {
        Route::get('/doc', 'DocumentController@index')
            ->name('api.document');
    });
});