<?php

use App\Http\Controllers\DocsController;
use Illuminate\Support\Facades\Route;

Route::get('/docs', [DocsController::class, 'index'])->name('docs');
Route::get('/docs/openapi.yml', [DocsController::class, 'openapi'])->name('docs.openapi');