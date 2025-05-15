<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\EmpleadoController;
use App\Http\Controllers\CitaController;
use App\Http\Controllers\ServicioController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::get('/clientes', [ClienteController::class, 'index'])->name('Clientes.index');
Route::get('/clientes/create', [ClienteController::class, 'create'])->name('Clientes.create');
Route::post('/clientes', [ClienteController::class, 'store'])->name('Clientes.store');
Route::get('/clientes/{cliente}', [ClienteController::class, 'show'])->name('Clientes.show');
Route::get('/clientes/{cliente}/edit', [ClienteController::class, 'edit'])->name('Clientes.edit');
Route::put('/clientes/{cliente}', [ClienteController::class, 'update'])->name('Clientes.update');
Route::delete('/clientes/{cliente}', [ClienteController::class, 'destroy'])->name('Clientes.destroy');

Route::get('/empleados', [EmpleadoController::class, 'index'])->name('Empleados.index');
Route::get('/empleados/create', [EmpleadoController::class, 'create'])->name('Empleados.create');
Route::post('/empleados', [EmpleadoController::class, 'store'])->name('Empleados.store');
Route::get('/empleados/{empleado}', [EmpleadoController::class, 'show'])->name('Empleados.show');
Route::get('/empleados/{empleado}/edit', [EmpleadoController::class, 'edit'])->name('Empleados.edit');
Route::put('/empleados/{empleado}', [EmpleadoController::class, 'update'])->name('Empleados.update');
Route::delete('/empleados/{empleado}', [EmpleadoController::class, 'destroy'])->name('Empleados.destroy');

Route::get('/citas', [CitaController::class, 'index'])->name('Citas.index');
Route::get('/citas/create', [CitaController::class, 'create'])->name('Citas.create');
Route::post('/citas', [CitaController::class, 'store'])->name('Citas.store');
Route::get('/citas/{cita}', [CitaController::class, 'show'])->name('Citas.show');
Route::get('/citas/{cita}/edit', [CitaController::class, 'edit'])->name('Citas.edit');
Route::put('/citas/{cita}', [CitaController::class, 'update'])->name('Citas.update');
Route::delete('/citas/{cita}', [CitaController::class, 'destroy'])->name('Citas.destroy');

Route::get('/servicios', [ServicioController::class, 'index'])->name('Servicios.index');
Route::get('/servicios/create', [ServicioController::class, 'create'])->name('Servicios.create');
Route::post('/servicios', [ServicioController::class, 'store'])->name('Servicios.store');
Route::get('/servicios/{servicio}', [ServicioController::class, 'show'])->name('Servicios.show');
Route::get('/servicios/{servicio}/edit', [ServicioController::class, 'edit'])->name('Servicios.edit');
Route::put('/servicios/{servicio}', [ServicioController::class, 'update'])->name('Servicios.update');
Route::delete('/servicios/{servicio}', [ServicioController::class, 'destroy'])->name('Servicios.destroy');
Route::get('/servicios/{servicio}/empleados', [ServicioController::class, 'empleados'])->name('Servicios.empleados');
Route::post('/servicios/{servicio}/empleados', [ServicioController::class, 'addEmpleado'])->name('Servicios.addEmpleado');
Route::delete('/servicios/{servicio}/empleados/{empleado}', [ServicioController::class, 'removeEmpleado'])->name('Servicios.removeEmpleado');
Route::get('/servicios/{servicio}/citas', [ServicioController::class, 'citas'])->name('Servicios.citas');
Route::post('/servicios/{servicio}/citas', [ServicioController::class, 'addCita'])->name('Servicios.addCita');
Route::delete('/servicios/{servicio}/citas/{cita}', [ServicioController::class, 'removeCita'])->name('Servicios.removeCita');
Route::get('/servicios/{servicio}/empleados/create', [ServicioController::class, 'createEmpleado'])->name('Servicios.createEmpleado');
Route::post('/servicios/{servicio}/empleados/store', [ServicioController::class, 'storeEmpleado'])->name('Servicios.storeEmpleado');
Route::get('/servicios/{servicio}/citas/create', [ServicioController::class, 'createCita'])->name('Servicios.createCita');
Route::post('/servicios/{servicio}/citas/store', [ServicioController::class, 'storeCita'])->name('Servicios.storeCita');
Route::get('/servicios/{servicio}/empleados/{empleado}/edit', [ServicioController::class, 'editEmpleado'])->name('Servicios.editEmpleado');
Route::put('/servicios/{servicio}/empleados/{empleado}', [ServicioController::class, 'updateEmpleado'])->name('Servicios.updateEmpleado');
Route::get('/servicios/{servicio}/citas/{cita}/edit', [ServicioController::class, 'editCita'])->name('Servicios.editCita');


require __DIR__.'/auth.php';
