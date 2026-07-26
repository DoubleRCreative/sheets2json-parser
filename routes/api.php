<?php

use App\Http\Controllers\ApiController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ApiController::class, 'index'])->name('api.root');
Route::get('/openapi.json', [ApiController::class, 'openapi'])->name('api.openapi');

require base_path('routes/api/v1.php');