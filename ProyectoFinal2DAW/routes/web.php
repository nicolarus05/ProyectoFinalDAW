<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TenantRegistrationController;

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

// FASE 5: Registro de nuevos salones (tenants)
Route::get('/registrar-salon', [TenantRegistrationController::class, 'create'])
    ->name('tenant.register.create');

Route::post('/registrar-salon', [TenantRegistrationController::class, 'store'])
    ->name('tenant.register.store');

// Verificación AJAX de disponibilidad de slug
Route::get('/verificar-slug', [TenantRegistrationController::class, 'checkSlug'])
    ->name('tenant.register.check-slug');
