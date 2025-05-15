<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\EmpleadoController;

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

require __DIR__.'/auth.php';
