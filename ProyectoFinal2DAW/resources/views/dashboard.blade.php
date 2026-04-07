<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Control - Salón de Belleza</title>
    {!! vite_asset(['resources/css/dashboard.css', 'resources/css/app.css', 'resources/js/app.js']) !!}
</head>
<body class="min-h-screen bg-white">
    @php
        $user = Auth::user();
        $rol = $user->rol ?? null;
        
        // Inicializamos el agente para detectar el dispositivo
        $agent = new \Jenssegers\Agent\Agent();
    @endphp

    <!-- Header Superior -->
    <header class="bg-black text-white py-4 px-6 shadow-lg">
        <div class="max-w-7xl mx-auto flex justify-between items-center">
            <div class="flex items-center gap-3">
                <h1 class="text-2xl titulo-elegante font-semibold">Salón de Belleza</h1>
            </div>
            
            <div class="flex items-center gap-4">
                <a href="{{ route('profile.edit') }}" class="flex items-center gap-3 hover:opacity-80 transition group">
                    @if ($user && $user->foto_perfil)
                        <img src="{{ route('tenant.file', $user->foto_perfil) }}" loading="lazy"
                            class="foto-perfil-redonda rounded-full border-2 border-white shadow-lg group-hover:border-gray-300 transition">
                    @else
                        <div class="w-12 h-12 flex items-center justify-center bg-white rounded-full text-black font-bold shadow-lg group-hover:bg-gray-200 transition">
                            {{ strtoupper(substr($user->nombre, 0, 1)) }}
                        </div>
                    @endif
                    <div class="hidden md:block text-right">
                        <p class="font-semibold text-sm group-hover:text-gray-300 transition">{{ $user->nombre }} {{ $user->apellidos }}</p>
                        <p class="text-xs text-gray-300 uppercase tracking-wide">{{ $user->rol }}</p>
                    </div>
                </a>
            </div>
        </div>
    </header>

    <!-- Contenido Principal -->
    <main class="max-w-7xl mx-auto px-6 py-10">
        
        @if ($rol === 'admin')
            <!-- Grid de Tarjetas para Admin -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                
                <!-- Gestionar Citas -->
                <a href="{{ route('citas.index') }}" class="card-hover bg-white border-2 border-black rounded-xl p-6 text-center group">
                    <div class="w-16 h-16 bg-black text-white rounded-full flex items-center justify-center mx-auto mb-4 group-hover:bg-gray-800 transition">
                        <span class="text-3xl">📅</span>
                    </div>
                    <h3 class="text-xl font-bold text-black mb-2">Gestionar Citas</h3>
                    <p class="text-gray-600 text-sm">Ver y administrar todas las citas programadas</p>
                </a>

                <!-- Gestionar Clientes -->
                <a href="{{ route('clientes.index') }}" class="card-hover bg-white border-2 border-black rounded-xl p-6 text-center group">
                    <div class="w-16 h-16 bg-black text-white rounded-full flex items-center justify-center mx-auto mb-4 group-hover:bg-gray-800 transition">
                        <span class="text-3xl">👥</span>
                    </div>
                    <h3 class="text-xl font-bold text-black mb-2">Clientes</h3>
                    <p class="text-gray-600 text-sm">Gestionar información de clientes</p>
                </a>

                <!-- Gestionar Empleados -->
                <a href="{{ route('empleados.index') }}" class="card-hover bg-white border-2 border-black rounded-xl p-6 text-center group">
                    <div class="w-16 h-16 bg-black text-white rounded-full flex items-center justify-center mx-auto mb-4 group-hover:bg-gray-800 transition">
                        <span class="text-3xl">💼</span>
                    </div>
                    <h3 class="text-xl font-bold text-black mb-2">Empleados</h3>
                    <p class="text-gray-600 text-sm">Administrar personal del salón</p>
                </a>

                <!-- Gestionar Servicios -->
                <a href="{{ route('servicios.index') }}" class="card-hover bg-white border-2 border-black rounded-xl p-6 text-center group">
                    <div class="w-16 h-16 bg-black text-white rounded-full flex items-center justify-center mx-auto mb-4 group-hover:bg-gray-800 transition">
                        <span class="text-3xl">💇</span>
                    </div>
                    <h3 class="text-xl font-bold text-black mb-2">Servicios</h3>
                    <p class="text-gray-600 text-sm">Gestionar servicios de belleza</p>
                </a>

                <!-- Gestionar Productos -->
                <a href="{{ route('productos.index') }}" class="card-hover bg-white border-2 border-black rounded-xl p-6 text-center group">
                    <div class="w-16 h-16 bg-black text-white rounded-full flex items-center justify-center mx-auto mb-4 group-hover:bg-gray-800 transition">
                        <span class="text-3xl">🛍️</span>
                    </div>
                    <h3 class="text-xl font-bold text-black mb-2">Productos</h3>
                    <p class="text-gray-600 text-sm">Control de inventario y productos</p>
                </a>

                <!-- Registro de Cobros -->
                <a href="{{ route('cobros.index') }}" class="card-hover bg-white border-2 border-black rounded-xl p-6 text-center group">
                    <div class="w-16 h-16 bg-black text-white rounded-full flex items-center justify-center mx-auto mb-4 group-hover:bg-gray-800 transition">
                        <span class="text-3xl">💳</span>
                    </div>
                    <h3 class="text-xl font-bold text-black mb-2">Registro de Cobros</h3>
                    <p class="text-gray-600 text-sm">Historial de pagos y transacciones</p>
                </a>

                <!-- Gestionar Deudas (Destacado) -->
                <a href="{{ route('deudas.index') }}" class="card-hover card-deudas border-2 border-black rounded-xl p-6 text-center group">
                    <div class="w-16 h-16 bg-red-500 text-white rounded-full flex items-center justify-center mx-auto mb-4 group-hover:bg-red-600 transition">
                        <span class="text-3xl">💰</span>
                    </div>
                    <h3 class="text-xl font-bold text-red-700 mb-2">Gestionar Deudas</h3>
                    <p class="text-red-600 text-sm">Control de cuentas pendientes</p>
                </a>

                <!-- Gestionar Bonos -->
                <a href="{{ route('bonos.index') }}" class="card-hover border-2 border-black rounded-xl p-6 text-center group">
                    <div class="w-16 h-16 bg-purple-500 text-white rounded-full flex items-center justify-center mx-auto mb-4 group-hover:bg-purple-600 transition">
                        <span class="text-3xl">🎫</span>
                    </div>
                    <h3 class="text-xl font-bold text-purple-700 mb-2">Gestionar Bonos</h3>
                    <p class="text-purple-600 text-sm">Crear y vender bonos de servicios</p>
                </a>

                <!-- Clientes con Bonos -->
                <a href="{{ route('bonos.clientesConBonos') }}" class="card-hover border-2 border-black rounded-xl p-6 text-center group">
                    <div class="w-16 h-16 bg-indigo-500 text-white rounded-full flex items-center justify-center mx-auto mb-4 group-hover:bg-indigo-600 transition">
                        <span class="text-3xl">👥</span>
                    </div>
                    <h3 class="text-xl font-bold text-indigo-700 mb-2">Clientes con Bonos</h3>
                    <p class="text-indigo-600 text-sm">Ver clientes con bonos activos</p>
                </a>

                <!-- Caja del Día (Destacado) -->
                <a href="{{ route('caja.index') }}" class="card-hover card-caja border-2 border-black rounded-xl p-6 text-center group">
                    <div class="w-16 h-16 bg-green-600 text-white rounded-full flex items-center justify-center mx-auto mb-4 group-hover:bg-green-700 transition">
                        <span class="text-3xl">💵</span>
                    </div>
                    <h3 class="text-xl font-bold text-green-700 mb-2">Caja del Día</h3>
                    <p class="text-green-600 text-sm">Resumen de ingresos diarios</p>
                </a>

                <!-- Facturación Mensual -->
                <a href="{{ route('facturacion.index') }}" class="card-hover bg-gradient-to-br from-emerald-50 to-green-50 border-2 border-black rounded-xl p-6 text-center group">
                    <div class="w-16 h-16 bg-emerald-600 text-white rounded-full flex items-center justify-center mx-auto mb-4 group-hover:bg-emerald-700 transition">
                        <span class="text-3xl">💰</span>
                    </div>
                    <h3 class="text-xl font-bold text-emerald-700 mb-2">Facturación</h3>
                    <p class="text-emerald-600 text-sm">Informe mensual detallado</p>
                </a>

                <!-- Horarios de Trabajo -->
                <a href="{{ route('horarios.index') }}" class="card-hover bg-white border-2 border-black rounded-xl p-6 text-center group">
                    <div class="w-16 h-16 bg-black text-white rounded-full flex items-center justify-center mx-auto mb-4 group-hover:bg-gray-800 transition">
                        <span class="text-3xl">⏰</span>
                    </div>
                    <h3 class="text-xl font-bold text-black mb-2">Horarios</h3>
                    <p class="text-gray-600 text-sm">Configurar turnos de trabajo</p>
                </a>

                <!-- Control de Asistencia -->
                <a href="{{ route('asistencia.index') }}" class="card-hover bg-gradient-to-br from-cyan-50 to-blue-50 border-2 border-black rounded-xl p-6 text-center group">
                    <div class="w-16 h-16 bg-cyan-500 text-white rounded-full flex items-center justify-center mx-auto mb-4 group-hover:bg-cyan-600 transition">
                        <span class="text-3xl">🕐</span>
                    </div>
                    <h3 class="text-xl font-bold text-cyan-700 mb-2">Control de Asistencia</h3>
                    <p class="text-cyan-600 text-sm">Entrada/salida de empleados</p>
                </a>

                <!-- Gestionar Usuarios -->
                <a href="{{ route('users.index') }}" class="card-hover bg-white border-2 border-black rounded-xl p-6 text-center group">
                    <div class="w-16 h-16 bg-black text-white rounded-full flex items-center justify-center mx-auto mb-4 group-hover:bg-gray-800 transition">
                        <span class="text-3xl">⚙️</span>
                    </div>
                    <h3 class="text-xl font-bold text-black mb-2">Usuarios del Sistema</h3>
                    <p class="text-gray-600 text-sm">Administración de accesos</p>
                </a>

            </div>
        @endif

        @if ($rol === 'gerente')
            <!-- Grid de Tarjetas para Gerente (igual que admin, sin Usuarios del Sistema) -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                
                <!-- Gestionar Citas -->
                <a href="{{ route('citas.index') }}" class="card-hover bg-white border-2 border-black rounded-xl p-6 text-center group">
                    <div class="w-16 h-16 bg-black text-white rounded-full flex items-center justify-center mx-auto mb-4 group-hover:bg-gray-800 transition">
                        <span class="text-3xl">📅</span>
                    </div>
                    <h3 class="text-xl font-bold text-black mb-2">Gestionar Citas</h3>
                    <p class="text-gray-600 text-sm">Ver y administrar todas las citas programadas</p>
                </a>

                <!-- Gestionar Clientes -->
                <a href="{{ route('clientes.index') }}" class="card-hover bg-white border-2 border-black rounded-xl p-6 text-center group">
                    <div class="w-16 h-16 bg-black text-white rounded-full flex items-center justify-center mx-auto mb-4 group-hover:bg-gray-800 transition">
                        <span class="text-3xl">👥</span>
                    </div>
                    <h3 class="text-xl font-bold text-black mb-2">Clientes</h3>
                    <p class="text-gray-600 text-sm">Gestionar información de clientes</p>
                </a>

                <!-- Gestionar Empleados -->
                <a href="{{ route('empleados.index') }}" class="card-hover bg-white border-2 border-black rounded-xl p-6 text-center group">
                    <div class="w-16 h-16 bg-black text-white rounded-full flex items-center justify-center mx-auto mb-4 group-hover:bg-gray-800 transition">
                        <span class="text-3xl">💼</span>
                    </div>
                    <h3 class="text-xl font-bold text-black mb-2">Empleados</h3>
                    <p class="text-gray-600 text-sm">Administrar personal del salón</p>
                </a>

                <!-- Gestionar Servicios -->
                <a href="{{ route('servicios.index') }}" class="card-hover bg-white border-2 border-black rounded-xl p-6 text-center group">
                    <div class="w-16 h-16 bg-black text-white rounded-full flex items-center justify-center mx-auto mb-4 group-hover:bg-gray-800 transition">
                        <span class="text-3xl">💇</span>
                    </div>
                    <h3 class="text-xl font-bold text-black mb-2">Servicios</h3>
                    <p class="text-gray-600 text-sm">Gestionar servicios de belleza</p>
                </a>

                <!-- Gestionar Productos -->
                <a href="{{ route('productos.index') }}" class="card-hover bg-white border-2 border-black rounded-xl p-6 text-center group">
                    <div class="w-16 h-16 bg-black text-white rounded-full flex items-center justify-center mx-auto mb-4 group-hover:bg-gray-800 transition">
                        <span class="text-3xl">🛍️</span>
                    </div>
                    <h3 class="text-xl font-bold text-black mb-2">Productos</h3>
                    <p class="text-gray-600 text-sm">Control de inventario y productos</p>
                </a>

                <!-- Registro de Cobros -->
                <a href="{{ route('cobros.index') }}" class="card-hover bg-white border-2 border-black rounded-xl p-6 text-center group">
                    <div class="w-16 h-16 bg-black text-white rounded-full flex items-center justify-center mx-auto mb-4 group-hover:bg-gray-800 transition">
                        <span class="text-3xl">💳</span>
                    </div>
                    <h3 class="text-xl font-bold text-black mb-2">Registro de Cobros</h3>
                    <p class="text-gray-600 text-sm">Historial de pagos y transacciones</p>
                </a>

                <!-- Gestionar Deudas (Destacado) -->
                <a href="{{ route('deudas.index') }}" class="card-hover card-deudas border-2 border-black rounded-xl p-6 text-center group">
                    <div class="w-16 h-16 bg-red-500 text-white rounded-full flex items-center justify-center mx-auto mb-4 group-hover:bg-red-600 transition">
                        <span class="text-3xl">💰</span>
                    </div>
                    <h3 class="text-xl font-bold text-red-700 mb-2">Gestionar Deudas</h3>
                    <p class="text-red-600 text-sm">Control de cuentas pendientes</p>
                </a>

                <!-- Gestionar Bonos -->
                <a href="{{ route('bonos.index') }}" class="card-hover border-2 border-black rounded-xl p-6 text-center group">
                    <div class="w-16 h-16 bg-purple-500 text-white rounded-full flex items-center justify-center mx-auto mb-4 group-hover:bg-purple-600 transition">
                        <span class="text-3xl">🎫</span>
                    </div>
                    <h3 class="text-xl font-bold text-purple-700 mb-2">Gestionar Bonos</h3>
                    <p class="text-purple-600 text-sm">Crear y vender bonos de servicios</p>
                </a>

                <!-- Clientes con Bonos -->
                <a href="{{ route('bonos.clientesConBonos') }}" class="card-hover border-2 border-black rounded-xl p-6 text-center group">
                    <div class="w-16 h-16 bg-indigo-500 text-white rounded-full flex items-center justify-center mx-auto mb-4 group-hover:bg-indigo-600 transition">
                        <span class="text-3xl">👥</span>
                    </div>
                    <h3 class="text-xl font-bold text-indigo-700 mb-2">Clientes con Bonos</h3>
                    <p class="text-indigo-600 text-sm">Ver clientes con bonos activos</p>
                </a>

                <!-- Caja del Día (Destacado) -->
                <a href="{{ route('caja.index') }}" class="card-hover card-caja border-2 border-black rounded-xl p-6 text-center group">
                    <div class="w-16 h-16 bg-green-600 text-white rounded-full flex items-center justify-center mx-auto mb-4 group-hover:bg-green-700 transition">
                        <span class="text-3xl">💵</span>
                    </div>
                    <h3 class="text-xl font-bold text-green-700 mb-2">Caja del Día</h3>
                    <p class="text-green-600 text-sm">Resumen de ingresos diarios</p>
                </a>

                <!-- Facturación Mensual -->
                <a href="{{ route('facturacion.index') }}" class="card-hover bg-gradient-to-br from-emerald-50 to-green-50 border-2 border-black rounded-xl p-6 text-center group">
                    <div class="w-16 h-16 bg-emerald-600 text-white rounded-full flex items-center justify-center mx-auto mb-4 group-hover:bg-emerald-700 transition">
                        <span class="text-3xl">💰</span>
                    </div>
                    <h3 class="text-xl font-bold text-emerald-700 mb-2">Facturación</h3>
                    <p class="text-emerald-600 text-sm">Informe mensual detallado</p>
                </a>

                <!-- Horarios de Trabajo -->
                <a href="{{ route('horarios.index') }}" class="card-hover bg-white border-2 border-black rounded-xl p-6 text-center group">
                    <div class="w-16 h-16 bg-black text-white rounded-full flex items-center justify-center mx-auto mb-4 group-hover:bg-gray-800 transition">
                        <span class="text-3xl">⏰</span>
                    </div>
                    <h3 class="text-xl font-bold text-black mb-2">Horarios</h3>
                    <p class="text-gray-600 text-sm">Configurar turnos de trabajo</p>
                </a>

                <!-- Control de Asistencia -->
                <a href="{{ route('asistencia.index') }}" class="card-hover bg-gradient-to-br from-cyan-50 to-blue-50 border-2 border-black rounded-xl p-6 text-center group">
                    <div class="w-16 h-16 bg-cyan-500 text-white rounded-full flex items-center justify-center mx-auto mb-4 group-hover:bg-cyan-600 transition">
                        <span class="text-3xl">🕐</span>
                    </div>
                    <h3 class="text-xl font-bold text-cyan-700 mb-2">Control de Asistencia</h3>
                    <p class="text-cyan-600 text-sm">Entrada/salida de empleados</p>
                </a>

            </div>
        @endif

        @if ($rol === 'empleado')
            <!-- Widget de Asistencia para Empleados -->
            <!-- LÓGICA PARA OCULTAR EN MÓVIL -->
            <div class="mb-8">
                @if($agent->isDesktop())
                    {{-- Si es ordenador, mostramos el botón de registrar --}}
                    @include('asistencia.widget-empleado')
                @else
                    {{-- Si es móvil/tablet, mostramos aviso y ocultamos el botón --}}
                    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded shadow-sm">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <span class="text-2xl">📱</span>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-blue-700 font-bold">
                                    Modo móvil detectado
                                </p>
                                <p class="text-sm text-blue-600">
                                    El registro de entrada y salida de jornada solo está disponible desde el ordenador del salón.
                                </p>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Grid para Empleados - Solo acceso a operaciones diarias -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 max-w-6xl mx-auto">
                
                <!-- Gestionar Citas -->
                <a href="{{ route('citas.index') }}" class="card-hover bg-white border-2 border-black rounded-xl p-6 text-center group">
                    <div class="w-16 h-16 bg-black text-white rounded-full flex items-center justify-center mx-auto mb-4 group-hover:bg-gray-800 transition">
                        <span class="text-3xl">📅</span>
                    </div>
                    <h3 class="text-xl font-bold text-black mb-2">Gestionar Citas</h3>
                    <p class="text-gray-600 text-sm">Ver y administrar todas las citas programadas</p>
                </a>

                <!-- Gestionar Clientes -->
                <a href="{{ route('clientes.index') }}" class="card-hover bg-white border-2 border-black rounded-xl p-6 text-center group">
                    <div class="w-16 h-16 bg-black text-white rounded-full flex items-center justify-center mx-auto mb-4 group-hover:bg-gray-800 transition">
                        <span class="text-3xl">👥</span>
                    </div>
                    <h3 class="text-xl font-bold text-black mb-2">Clientes</h3>
                    <p class="text-gray-600 text-sm">Gestionar información de clientes</p>
                </a>

                <!-- Registro de Cobros -->
                <a href="{{ route('cobros.index') }}" class="card-hover bg-white border-2 border-black rounded-xl p-6 text-center group">
                    <div class="w-16 h-16 bg-black text-white rounded-full flex items-center justify-center mx-auto mb-4 group-hover:bg-gray-800 transition">
                        <span class="text-3xl">💳</span>
                    </div>
                    <h3 class="text-xl font-bold text-black mb-2">Registro de Cobros</h3>
                    <p class="text-gray-600 text-sm">Historial de pagos y transacciones</p>
                </a>

                <!-- Gestionar Deudas -->
                <a href="{{ route('deudas.index') }}" class="card-hover card-deudas border-2 border-black rounded-xl p-6 text-center group">
                    <div class="w-16 h-16 bg-red-500 text-white rounded-full flex items-center justify-center mx-auto mb-4 group-hover:bg-red-600 transition">
                        <span class="text-3xl">💰</span>
                    </div>
                    <h3 class="text-xl font-bold text-red-700 mb-2">Gestionar Deudas</h3>
                    <p class="text-red-600 text-sm">Control de cuentas pendientes</p>
                </a>

                <!-- Caja del Día (Destacado) -->
                <a href="{{ route('caja.index') }}" class="card-hover card-caja border-2 border-black rounded-xl p-6 text-center group">
                    <div class="w-16 h-16 bg-green-600 text-white rounded-full flex items-center justify-center mx-auto mb-4 group-hover:bg-green-700 transition">
                        <span class="text-3xl">💵</span>
                    </div>
                    <h3 class="text-xl font-bold text-green-700 mb-2">Caja del Día</h3>
                    <p class="text-green-600 text-sm">Resumen de ingresos diarios</p>
                </a>

            </div>
        @endif

        @if ($rol === 'cliente')
            <!-- Grid para Clientes -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 max-w-3xl mx-auto">
                
                <!-- Reservar Cita (Destacado) -->
                <a href="{{ route('citas.create') }}" class="card-hover bg-gradient-to-br from-purple-50 to-pink-50 border-2 border-black rounded-xl p-8 text-center group">
                    <div class="w-20 h-20 bg-purple-500 text-white rounded-full flex items-center justify-center mx-auto mb-4 group-hover:bg-purple-600 transition">
                        <span class="text-4xl">➕</span>
                    </div>
                    <h3 class="text-2xl font-bold text-purple-700 mb-2">Reservar Cita</h3>
                    <p class="text-purple-600">Agenda tu próxima visita al salón</p>
                </a>

                <!-- Mis Citas -->
                <a href="{{ route('citas.index') }}" class="card-hover bg-white border-2 border-black rounded-xl p-8 text-center group">
                    <div class="w-20 h-20 bg-black text-white rounded-full flex items-center justify-center mx-auto mb-4 group-hover:bg-gray-800 transition">
                        <span class="text-4xl">📋</span>
                    </div>
                    <h3 class="text-2xl font-bold text-black mb-2">Mis Citas</h3>
                    <p class="text-gray-600">Ver tus citas programadas</p>
                </a>

            </div>
        @endif

        <!-- Sección de Acciones de Perfil -->
        <div class="mt-16 pt-12 pb-8 border-t border-black">
            <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
                <a href="{{ route('profile.edit') }}"
                   class="inline-flex items-center gap-2 bg-white text-black px-8 py-3 rounded-lg border-2 border-black hover:bg-gray-100 transition font-semibold">
                    <span>✏️</span>
                    Editar Mi Perfil
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                            class="inline-flex items-center gap-2 bg-black text-white px-8 py-3 rounded-lg hover:bg-gray-800 transition font-semibold">
                        <span>🚪</span>
                        Cerrar Sesión
                    </button>
                </form>
            </div>
        </div>

    </main>

    <!-- Footer -->
    <footer class="bg-black text-white py-8 mt-8">
        <div class="max-w-7xl mx-auto px-6 text-center">
            <p class="text-sm text-gray-400">© {{ date('Y') }} Salón de Belleza - Sistema de Gestión</p>
        </div>
    </footer>

</body>
</html>
