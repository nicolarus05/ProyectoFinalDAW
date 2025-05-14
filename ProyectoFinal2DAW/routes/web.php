<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ClienteController;

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


require __DIR__.'/auth.php';
