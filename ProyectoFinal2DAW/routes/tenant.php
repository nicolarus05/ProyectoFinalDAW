<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    ProfileController, ClienteController, EmpleadoController,
    CitaController, ServicioController, RegistroCobroController,
    userController, HorarioTrabajoController, RegistroEntradaSalidaController,
    Auth\AuthenticatedSessionController, Auth\RegisterClienteController, 
    Auth\PerfilController, Auth\PasswordResetLinkController,
    Auth\NewPasswordController,
    CajaDiariaController, ProductosController, DeudaController, BonoController,
    FacturacionController
};

// GRUPO PRINCIPAL: Middleware de tenancy para TODAS las rutas
Route::middleware([
    \Stancl\Tenancy\Middleware\InitializeTenancyByDomain::class,
    \Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains::class,
    'web',
])->group(function () {

    // Dashboard
    Route::get('/', fn () => view('dashboard'))
        ->middleware(['auth'])
        ->name('dashboard');
    Route::get('/dashboard', fn () => view('dashboard'))
        ->middleware(['auth'])
        ->name('dashboard.legacy');

    // Autenticación
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])
        ->middleware('guest')
        ->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware(['guest', 'throttle:5,1'])
        ->name('login.post');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
        ->middleware('auth')
        ->name('logout');

    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])
        ->middleware('guest')
        ->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])
        ->middleware(['guest', 'throttle:5,1'])
        ->name('password.email');
    Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])
        ->middleware('guest')
        ->name('password.reset');

    // Perfil de usuario
    Route::middleware(['auth'])->group(function () {
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
        Route::put('/perfil/update', [ProfileController::class, 'update'])->name('perfil.update');
    });

    // ============================================================================
    // PRODUCTOS DISPONIBLES - Debe estar ANTES del Route::resource('productos')
    // Accesible por admin y empleado
    // ============================================================================
    Route::middleware(['auth', 'role:admin,empleado'])->group(function () {
        Route::get('productos/available', [ProductosController::class, 'available'])->name('productos.available');
    });

    // ============================================================================
    // SOLO ADMIN - Acceso completo a todas las funcionalidades
    // ============================================================================
    Route::middleware(['auth', 'role:admin'])->group(function () {
        Route::resource('users', userController::class)->names('users');
        Route::resource('empleados', EmpleadoController::class)->names('empleados');
        
        // Rutas adicionales para horarios de empleados

        Route::resource('servicios', ServicioController::class)->names('servicios');
        
        // Productos - solo admin puede gestionar (CRUD)
        // NOTA: productos/available está definida en el grupo admin,empleado más abajo
        Route::resource('productos', ProductosController::class)->names('productos');

        // Horarios (solo admin)
        Route::get('horarios/calendario', [HorarioTrabajoController::class, 'calendario'])->name('horarios.calendario');
        Route::get('horarios/configurar', [HorarioTrabajoController::class, 'mostrarFormularioGeneracion'])->name('horarios.configurar');
        Route::post('horarios/generar-semana', [HorarioTrabajoController::class, 'generarSemana'])->name('horarios.generarSemana');
        Route::post('horarios/generar-mes', [HorarioTrabajoController::class, 'generarMes'])->name('horarios.generarMes');
        Route::post('horarios/generar-anual', [HorarioTrabajoController::class, 'generarAnual'])->name('horarios.generarAnual');
        Route::post('horarios/toggle-disponibilidad', [HorarioTrabajoController::class, 'toggleDisponibilidad'])->name('horarios.toggleDisponibilidad');
        Route::post('horarios/toggle-disponibilidad-rango', [HorarioTrabajoController::class, 'toggleDisponibilidadRango'])->name('horarios.toggleDisponibilidadRango');
        Route::post('horarios/deshabilitar-bloque', [HorarioTrabajoController::class, 'deshabilitarBloque'])->name('horarios.deshabilitarBloque');
        Route::get('horarios/bloques-dia', [HorarioTrabajoController::class, 'bloquesDia'])->name('horarios.bloquesDia');
        Route::resource('horarios', HorarioTrabajoController::class)->names('horarios');

        // Servicios anidados (solo admin)
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

        // Bonos (solo admin)
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

        // Asistencia completa (solo admin)
        Route::get('asistencia', [RegistroEntradaSalidaController::class, 'index'])->name('asistencia.index');
        Route::get('asistencia/empleado/{empleado}', [RegistroEntradaSalidaController::class, 'porEmpleado'])->name('asistencia.empleado');
        Route::post('asistencia/desconectar/{registro}', [RegistroEntradaSalidaController::class, 'desconectarEmpleado'])->name('asistencia.desconectar');
    });

    // ============================================================================
    // ADMIN Y EMPLEADO - Acceso limitado solo a: Citas, Clientes, Cobros y Caja
    // ============================================================================
    Route::middleware(['auth', 'role:admin,empleado'])->group(function () {
        // Clientes
        Route::resource('clientes', ClienteController::class)->names('clientes');
        Route::get('clientes/{cliente}/historial', [ClienteController::class, 'historial'])->name('clientes.historial');

        // Citas - Rutas específicas antes del resource
        Route::post('citas/mover', [CitaController::class, 'moverCita'])->name('citas.mover')->middleware('throttle:60,1');
        Route::post('citas/marcar-completada', [CitaController::class, 'marcarCompletada'])->name('citas.marcarCompletada')->middleware('throttle:60,1');
        Route::post('citas/actualizar-duracion', [CitaController::class, 'actualizarDuracion'])->name('citas.actualizarDuracion')->middleware('throttle:60,1');
        Route::post('citas/{cita}/actualizar-notas', [CitaController::class, 'actualizarNotas'])->name('citas.actualizarNotas')->middleware('throttle:60,1');
        Route::post('citas/{cita}/completar-y-cobrar', [CitaController::class, 'completarYCobrar'])->name('citas.completarYCobrar')->middleware('throttle:60,1');
        Route::post('citas/{cita}/cancelar', [CitaController::class, 'cancelar'])->name('citas.cancelar')->middleware('throttle:60,1');
        Route::resource('citas', CitaController::class)->names('citas')->middleware('throttle:60,1');

        // Cobros
        Route::get('cobros/direct/create', [RegistroCobroController::class, 'createDirect'])->name('cobros.create.direct');
        Route::resource('cobros', RegistroCobroController::class)->names('cobros')->middleware('throttle:30,1');
        
        // Deudas (admin y empleado)
        Route::prefix('deudas')->name('deudas.')->group(function () {
            Route::get('/', [DeudaController::class, 'index'])->name('index');
            Route::get('/cliente/{cliente}', [DeudaController::class, 'show'])->name('show');
            Route::get('/cliente/{cliente}/pago', [DeudaController::class, 'crearPago'])->name('pago.create');
            Route::post('/cliente/{cliente}/pago', [DeudaController::class, 'registrarPago'])->name('pago.store');
            Route::get('/cliente/{cliente}/historial', [DeudaController::class, 'historial'])->name('historial');
        });
        
        // Caja diaria
        Route::get('/caja', [CajaDiariaController::class, 'index'])->name('caja.index');
        
        // Facturación mensual
        Route::get('/facturacion', [FacturacionController::class, 'index'])->name('facturacion.index');
    });

    // ============================================================================
    // Registro asistencia (todos los autenticados)
    // ============================================================================
    Route::middleware(['auth', 'desktop.only'])->group(function () {
        Route::post('asistencia/entrada', [RegistroEntradaSalidaController::class, 'registrarEntrada'])->name('asistencia.entrada');
        Route::post('asistencia/salida', [RegistroEntradaSalidaController::class, 'registrarSalida'])->name('asistencia.salida');
        Route::get('asistencia/mi-historial', [RegistroEntradaSalidaController::class, 'miHistorial'])->name('asistencia.mi-historial');
        Route::get('asistencia/estado', [RegistroEntradaSalidaController::class, 'estadoActual'])->name('asistencia.estado');
    });

    // ============================================================================
    // Citas para CLIENTES (solo lectura)
    // ============================================================================
    Route::middleware(['auth', 'role:cliente'])->group(function () {
        Route::get('/mis-citas', [CitaController::class, 'index'])->name('cliente.citas.index');
        Route::get('/mis-citas/create', [CitaController::class, 'create'])->name('cliente.citas.create');
        Route::post('/mis-citas', [CitaController::class, 'store'])->name('cliente.citas.store');
        Route::get('/mis-citas/{cita}', [CitaController::class, 'show'])->name('cliente.citas.show');
    });

    // Registro de clientes (público)
    Route::middleware('guest')->group(function () {
        Route::get('/register/cliente', [RegisterClienteController::class, 'create'])->name('register.cliente');
        Route::post('/register/cliente', [RegisterClienteController::class, 'store'])->name('register.cliente.store');
    });

    // Perfil adicional
    Route::middleware('auth')->group(function(){
        Route::get('/perfil/edit', [ProfileController::class, 'edit'])->name('perfil.edit');
        Route::put('/perfil/update', [ProfileController::class, 'update'])->name('perfil.update');
    });

});
