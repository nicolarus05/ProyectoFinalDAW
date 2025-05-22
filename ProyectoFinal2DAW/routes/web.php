<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    ProfileController, ClienteController, EmpleadoController,
    CitaController, ServicioController, RegistroCobroController,
    UsuarioController, HorarioTrabajoController,
    Auth\AuthenticatedSessionController
};

Route::get('/', fn () => view('welcome'));

Route::get('/dashboard', fn () => view('dashboard'))
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::get('/login', [AuthenticatedSessionController::class, 'create'])
    ->middleware('guest')
    ->name('login');

Route::middleware(['auth'])->group(function () {
    // Perfil común
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});


// Rutas solo para ADMIN
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::resource('usuarios', UsuarioController::class)->names('Usuarios');

    Route::resource('clientes', ClienteController::class)->names('Clientes');
    Route::resource('empleados', EmpleadoController::class)->names('Empleados');
    Route::resource('servicios', ServicioController::class)->names('Servicios');

    // Rutas anidadas de servicios
    Route::get('/servicios/{servicio}/empleados/create', [ServicioController::class, 'createEmpleado'])->name('Servicios.createEmpleado');
    Route::post('/servicios/{servicio}/empleados/store', [ServicioController::class, 'storeEmpleado'])->name('Servicios.storeEmpleado');
    Route::get('/servicios/{servicio}/empleados/{empleado}/edit', [ServicioController::class, 'editEmpleado'])->name('Servicios.editEmpleado');
    Route::put('/servicios/{servicio}/empleados/{empleado}', [ServicioController::class, 'updateEmpleado'])->name('Servicios.updateEmpleado');
    Route::delete('/servicios/{servicio}/empleados/{empleado}', [ServicioController::class, 'removeEmpleado'])->name('Servicios.removeEmpleado');

    Route::get('/servicios/{servicio}/citas/create', [ServicioController::class, 'createCita'])->name('Servicios.createCita');
    Route::post('/servicios/{servicio}/citas/store', [ServicioController::class, 'storeCita'])->name('Servicios.storeCita');
    Route::get('/servicios/{servicio}/citas/{cita}/edit', [ServicioController::class, 'editCita'])->name('Servicios.editCita');
    Route::delete('/servicios/{servicio}/citas/{cita}', [ServicioController::class, 'removeCita'])->name('Servicios.removeCita');

    Route::get('/servicios/{servicio}/empleados', [ServicioController::class, 'empleados'])->name('Servicios.empleados');
    Route::post('/servicios/{servicio}/empleados', [ServicioController::class, 'addEmpleado'])->name('Servicios.addEmpleado');
    Route::get('/servicios/{servicio}/citas', [ServicioController::class, 'citas'])->name('Servicios.citas');
    Route::post('/servicios/{servicio}/citas', [ServicioController::class, 'addCita'])->name('Servicios.addCita');

    Route::resource('horarios', HorarioTrabajoController::class)->names('Horarios');
    Route::resource('cobros', RegistroCobroController::class)->names('Cobros');
});


// Rutas accesibles por ADMIN y EMPLEADO
Route::middleware(['auth', 'role:admin,empleado'])->group(function () {
    Route::resource('citas', CitaController::class)->names('Citas');
});


// Rutas exclusivas para CLIENTES
Route::middleware(['auth', 'role:cliente'])->group(function () {
    // Aquí podrías poner, por ejemplo, crear cita o ver citas propias
    Route::get('/mis-citas', [CitaController::class, 'index'])->name('Cliente.Citas.index');
    Route::get('/mis-citas/create', [CitaController::class, 'create'])->name('Cliente.Citas.create');
    Route::post('/mis-citas', [CitaController::class, 'store'])->name('Cliente.Citas.store');
});

require __DIR__.'/auth.php';
