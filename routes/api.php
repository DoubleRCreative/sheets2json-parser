<?php

use App\Http\Controllers\ApiController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ApiController::class, 'index'])->name('api.root');
Route::get('/openapi.json', [ApiController::class, 'openapi'])->name('api.openapi');

// Route::get('/login', [ApiController::class, 'authRouteInfo'])->defaults('path', 'login');
// Route::get('/signup', [ApiController::class, 'authRouteInfo'])->defaults('path', 'register');
// Route::get('/register', [ApiController::class, 'authRouteInfo'])->defaults('path', 'register');

require base_path('routes/api/v1.php');
require base_path('routes/api/v2.php');