<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\{
    ProfileController, ClienteController, EmpleadoController,
    CitaController, ServicioController, RegistroCobroController,
    userController, HorarioTrabajoController,
    Auth\AuthenticatedSessionController, Auth\RegisterClienteController, 
    Auth\PerfilController, Auth\PasswordResetLinkController,
    Auth\NewPasswordController
};

Route::get('/', fn () => view('welcome'));

Route::get('/dashboard', fn () => view('dashboard'))
    ->middleware(['auth'])
    ->name('dashboard');

Route::get('/login', [AuthenticatedSessionController::class, 'create'])
    ->middleware('guest')
    ->name('login');

Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])
    ->middleware('guest')
    ->name('password.request');

Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])
    ->middleware('guest')
    ->name('password.email');

Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])
    ->middleware('guest')
    ->name('password.reset');


Route::middleware(['auth'])->group(function () {
    // Perfil común
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Rutas solo para ADMIN
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::resource('users', userController::class)->names('users');

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

// Rutas de citas accesibles por ADMIN, EMPLEADO y CLIENTE
Route::middleware(['auth', 'role:admin,empleado,cliente'])->group(function () {
    Route::get('/citas', [CitaController::class, 'index'])->name('Citas.index');
    Route::get('/citas/create', [CitaController::class, 'create'])->name('Citas.create');
    Route::post('/citas', [CitaController::class, 'store'])->name('Citas.store');
    Route::get('/citas/{cita}', [CitaController::class, 'show'])->name('Citas.show');
    Route::get('/citas/{cita}/edit', [CitaController::class, 'edit'])->name('Citas.edit');
    Route::put('/citas/{cita}', [CitaController::class, 'update'])->name('Citas.update');
    Route::patch('/citas/{cita}', [CitaController::class, 'update'])->name('Citas.update');
});

// Rutas para que un user se pueda registrar
Route::middleware('guest')->group(function () {
    Route::get('/register/cliente', [RegisterClienteController::class, 'create'])->name('register.cliente');
    Route::post('/register/cliente', [RegisterClienteController::class, 'store'])->name('register.cliente.store');
});

//Rutas para la edicion del perfil
Route::middleware('auth')->group(function(){
    Route::get('/perfil/edit', [ProfileController::class, 'edit'])->name('perfil.edit');
    Route::put('/perfil/update', [ProfileController::class, 'update'])->name('perfil.update');
});

Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');



require __DIR__.'/auth.php';
