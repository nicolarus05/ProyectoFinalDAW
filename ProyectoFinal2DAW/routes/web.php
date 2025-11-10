<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\TenantRegistrationController;
use App\Http\Controllers\HealthCheckController;

/*
|--------------------------------------------------------------------------
| Central Routes
|--------------------------------------------------------------------------
|
| Estas son las rutas del dominio CENTRAL (sin tenant).
| Se acceden desde el dominio principal: misalon.com o salonlolahernandez.ddns.net
|
| Aquí se incluirán:
| - Landing page pública
| - Página de registro de nuevos salones (FASE 5)
| - Página "Sobre nosotros", "Precios", etc.
|
| Las rutas de la aplicación (dashboard, clientes, citas, etc.) están en routes/tenant.php
|
*/

// Landing page - Página pública del dominio central
Route::get('/', function () {
    return view('welcome');
})->name('home');

// Health Check endpoint para Render (sin middleware de autenticación)
Route::get('/health', HealthCheckController::class)->name('health.check');

// FASE 5: Registro de nuevos salones (tenants)
Route::get('/registrar-salon', [TenantRegistrationController::class, 'create'])
    ->name('tenant.register.create');

Route::post('/registrar-salon', [TenantRegistrationController::class, 'store'])
    ->name('tenant.register.store');

// Verificación AJAX de disponibilidad de slug
Route::get('/verificar-slug', [TenantRegistrationController::class, 'checkSlug'])
    ->name('tenant.register.check-slug');
