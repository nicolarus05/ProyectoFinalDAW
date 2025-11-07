<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\{
    ProfileController, ClienteController, EmpleadoController,
    CitaController, ServicioController, RegistroCobroController,
    userController, HorarioTrabajoController, RegistroEntradaSalidaController,
    Auth\AuthenticatedSessionController, Auth\RegisterClienteController, 
    Auth\PerfilController, Auth\PasswordResetLinkController,
    Auth\NewPasswordController,
    CajaDiariaController, ProductosController, DeudaController, BonoController
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
    Route::resource('horarios', HorarioTrabajoController::class)->names('horarios');
});

// Rutas accesibles por ADMIN y EMPLEADO
Route::middleware(['auth', 'role:admin,empleado'])->group(function () {
    Route::resource('clientes', ClienteController::class)->names('clientes');
    Route::resource('empleados', EmpleadoController::class)->names('empleados');
    Route::resource('servicios', ServicioController::class)->names('servicios');

    // Rutas anidadas de servicios
    Route::get('/servicios/{servicio}/empleados/create', [ServicioController::class, 'createEmpleado'])->name('servicios.createempleado');
    Route::post('/servicios/{servicio}/empleados/store', [ServicioController::class, 'storeEmpleado'])->name('servicios.storeempleado');
    Route::get('/servicios/{servicio}/empleados/{empleado}/edit', [ServicioController::class, 'editEmpleado'])->name('servicios.editempleado');
    Route::put('/servicios/{servicio}/empleados/{empleado}', [ServicioController::class, 'updateEmpleado'])->name('servicios.updateempleado');
    Route::delete('/servicios/{servicio}/empleados/{empleado}', [ServicioController::class, 'removeEmpleado'])->name('servicios.removeempleado');

    Route::get('/servicios/{servicio}/citas/create', [ServicioController::class, 'createCita'])->name('servicios.createcita');
    Route::post('/servicios/{servicio}/citas/store', [ServicioController::class, 'storeCita'])->name('servicios.storecita');
    Route::get('/servicios/{servicio}/citas/{cita}/edit', [ServicioController::class, 'editCita'])->name('servicios.editcita');
    Route::delete('/servicios/{servicio}/citas/{cita}', [ServicioController::class, 'removeCita'])->name('servicios.removecita');

    Route::get('/servicios/{servicio}/empleados', [ServicioController::class, 'empleados'])->name('servicios.empleados');
    Route::post('/servicios/{servicio}/empleados', [ServicioController::class, 'addEmpleado'])->name('servicios.addempleado');
    Route::get('/servicios/{servicio}/citas', [ServicioController::class, 'citas'])->name('servicios.citas');
    Route::post('/servicios/{servicio}/citas', [ServicioController::class, 'addCita'])->name('servicios.addcita');

    Route::get('cobros/direct/create', [RegistroCobroController::class, 'createDirect'])->name('cobros.create.direct');
    Route::resource('cobros', RegistroCobroController::class)->names('cobros');
    Route::get('/caja', [CajaDiariaController::class, 'index'])->name('caja.index');
    Route::get('productos/available', [ProductosController::class, 'available'])->name('productos.available');
    Route::resource('productos', ProductosController::class)->names('productos');
    Route::resource('citas', CitaController::class)->names('citas');
    
    // Rutas de deudas
    Route::prefix('deudas')->name('deudas.')->group(function () {
        Route::get('/', [DeudaController::class, 'index'])->name('index');
        Route::get('/cliente/{cliente}', [DeudaController::class, 'show'])->name('show');
        Route::get('/cliente/{cliente}/pago', [DeudaController::class, 'crearPago'])->name('pago.create');
        Route::post('/cliente/{cliente}/pago', [DeudaController::class, 'registrarPago'])->name('pago.store');
        Route::get('/cliente/{cliente}/historial', [DeudaController::class, 'historial'])->name('historial');
    });

    // Rutas de bonos
    Route::prefix('bonos')->name('bonos.')->group(function () {
        Route::get('/', [BonoController::class, 'index'])->name('index');
        Route::get('/crear', [BonoController::class, 'create'])->name('create');
        Route::post('/', [BonoController::class, 'store'])->name('store');
        Route::get('/clientes-con-bonos', [BonoController::class, 'clientesConBonos'])->name('clientesConBonos');
        Route::get('/{plantilla}/comprar', [BonoController::class, 'comprar'])->name('comprar');
        Route::post('/{plantilla}/comprar', [BonoController::class, 'procesarCompra'])->name('procesarCompra');
        Route::get('/cliente/{cliente}', [BonoController::class, 'misClientes'])->name('misClientes');
        Route::get('/{bono}/editar', [BonoController::class, 'edit'])->name('edit');
        Route::put('/{bono}', [BonoController::class, 'update'])->name('update');
    });
});

// Rutas de asistencia (Registro de entrada/salida)
Route::middleware(['auth'])->group(function () {
    // Para empleados
    Route::post('asistencia/entrada', [RegistroEntradaSalidaController::class, 'registrarEntrada'])->name('asistencia.entrada');
    Route::post('asistencia/salida', [RegistroEntradaSalidaController::class, 'registrarSalida'])->name('asistencia.salida');
    Route::get('asistencia/mi-historial', [RegistroEntradaSalidaController::class, 'miHistorial'])->name('asistencia.mi-historial');
    Route::get('asistencia/estado', [RegistroEntradaSalidaController::class, 'estadoActual'])->name('asistencia.estado');
    
    // Para admin
    Route::get('asistencia', [RegistroEntradaSalidaController::class, 'index'])->name('asistencia.index');
    Route::get('asistencia/empleado/{empleado}', [RegistroEntradaSalidaController::class, 'porEmpleado'])->name('asistencia.empleado');
});

// Rutas de citas accesibles por ADMIN, EMPLEADO y CLIENTE
Route::middleware(['auth', 'role:admin,empleado,cliente'])->group(function () {
    Route::get('/citas', [CitaController::class, 'index'])->name('citas.index');
    Route::get('/citas/create', [CitaController::class, 'create'])->name('citas.create');
    Route::post('/citas', [CitaController::class, 'store'])->name('citas.store');
    Route::get('/citas/{cita}', [CitaController::class, 'show'])->name('citas.show');
    Route::get('/citas/{cita}/edit', [CitaController::class, 'edit'])->name('citas.edit');
    Route::put('/citas/{cita}', [CitaController::class, 'update'])->name('citas.update');
    Route::patch('/citas/{cita}', [CitaController::class, 'update'])->name('citas.update');
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
