<?php

use App\Http\Controllers\HealthController;
use Illuminate\Support\Facades\Route;

// Todas las rutas de este archivo quedan bajo el prefijo /api (ver bootstrap/app.php).

Route::get('/health', HealthController::class)->name('health');
