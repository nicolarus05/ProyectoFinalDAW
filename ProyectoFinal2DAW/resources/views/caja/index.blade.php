<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Caja del día</title>
    {!! vite_asset(['resources/css/app.css', 'resources/js/app.js']) !!}
    <script>
        // Sistema de modo oscuro - modo claro por defecto
        if (localStorage.theme === 'dark') {
            document.documentElement.classList.add('dark')
        } else {
            document.documentElement.classList.remove('dark')
            // Establecer modo claro por defecto
            if (!('theme' in localStorage)) {
                localStorage.theme = 'light'
            }
        }
        
        function toggleDarkMode() {
            if (document.documentElement.classList.contains('dark')) {
                document.documentElement.classList.remove('dark')
                localStorage.theme = 'light'
            } else {
                document.documentElement.classList.add('dark')
                localStorage.theme = 'dark'
            }
        }
    </script>
</head>
<body class="bg-gray-50 dark:bg-gray-900 min-h-screen transition-colors duration-200">
    <div class="max-w-7xl mx-auto px-4 py-6 sm:px-6 lg:px-8">
        
        <!-- Header -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 mb-6 border border-gray-200 dark:border-gray-700">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div class="flex-1">
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-1">Caja del Día</h1>
                    <p class="text-gray-600 dark:text-gray-400 text-sm">
                        {{ \Carbon\Carbon::parse($fecha)->locale('es')->isoFormat('dddd, D [de] MMMM [de] YYYY') }}
                    </p>
                </div>
                <div class="flex gap-3">
                    <!-- Botón modo oscuro -->
                    <button onclick="toggleDarkMode()" class="inline-flex items-center justify-center w-10 h-10 rounded-lg bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 transition-colors">
                        <svg class="w-5 h-5 hidden dark:block" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z"></path>
                        </svg>
                        <svg class="w-5 h-5 block dark:hidden" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"></path>
                        </svg>
                    </button>
                    <!-- Botón volver -->
                    <a href="{{ route('dashboard') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 text-white text-sm font-medium rounded-lg transition-colors shadow-sm">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        Volver
                    </a>
                </div>
            </div>
        </div>

        <!-- Resumen General -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 mb-6 border border-gray-200 dark:border-gray-700">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-6">Resumen General</h2>
            
            <!-- Grid de estadísticas principales -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <!-- Total Ingresado -->
                <div class="relative overflow-hidden rounded-lg bg-gradient-to-br from-emerald-50 to-emerald-100 dark:from-emerald-900/30 dark:to-emerald-800/30 p-5 border border-emerald-200 dark:border-emerald-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-emerald-700 dark:text-emerald-300 mb-1">Total Ingresado</p>
                            <p class="text-2xl font-bold text-emerald-900 dark:text-emerald-100">€{{ number_format($totalPagado, 2) }}</p>
                        </div>
                        <div class="text-3xl">💰</div>
                    </div>
                </div>
                
                <!-- Efectivo -->
                <div class="relative overflow-hidden rounded-lg bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/30 dark:to-green-800/30 p-5 border border-green-200 dark:border-green-700">
                    <div class="flex items-center justify-between mb-3">
                        <div>
                            <p class="text-sm font-medium text-green-700 dark:text-green-300 mb-1">Efectivo Total</p>
                            <p class="text-2xl font-bold text-green-900 dark:text-green-100">€{{ number_format($totalEfectivo + $totalBonosEfectivo, 2) }}</p>
                        </div>
                        <div class="text-3xl">💵</div>
                    </div>
                    <div class="text-xs text-green-700/70 dark:text-green-300/70 space-y-0.5">
                        <div>Servicios: €{{ number_format($totalEfectivo, 2) }}</div>
                        <div>Bonos: €{{ number_format($totalBonosEfectivo, 2) }}</div>
                    </div>
                </div>
                
                <!-- Tarjeta -->
                <div class="relative overflow-hidden rounded-lg bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/30 dark:to-blue-800/30 p-5 border border-blue-200 dark:border-blue-700">
                    <div class="flex items-center justify-between mb-3">
                        <div>
                            <p class="text-sm font-medium text-blue-700 dark:text-blue-300 mb-1">Tarjeta Total</p>
                            <p class="text-2xl font-bold text-blue-900 dark:text-blue-100">€{{ number_format($totalTarjeta + $totalBonosTarjeta, 2) }}</p>
                        </div>
                        <div class="text-3xl">💳</div>
                    </div>
                    <div class="text-xs text-blue-700/70 dark:text-blue-300/70 space-y-0.5">
                        <div>Servicios: €{{ number_format($totalTarjeta, 2) }}</div>
                        <div>Bonos: €{{ number_format($totalBonosTarjeta, 2) }}</div>
                    </div>
                </div>
                
                <!-- Deudas -->
                <div class="relative overflow-hidden rounded-lg bg-gradient-to-br from-red-50 to-red-100 dark:from-red-900/30 dark:to-red-800/30 p-5 border border-red-200 dark:border-red-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-red-700 dark:text-red-300 mb-1">Deudas Generadas</p>
                            <p class="text-2xl font-bold text-red-900 dark:text-red-100">€{{ number_format($totalDeuda, 2) }}</p>
                        </div>
                        <div class="text-3xl">⚠️</div>
                    </div>
                </div>
            </div>
            
            <!-- Desglose adicional -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                <div class="flex items-center gap-3 p-4 bg-purple-50 dark:bg-purple-900/20 rounded-lg border border-purple-100 dark:border-purple-800">
                    <div class="text-2xl">🎫</div>
                    <div>
                        <p class="text-lg font-bold text-purple-900 dark:text-purple-100">€{{ number_format($totalBono, 2) }}</p>
                        <p class="text-xs text-purple-700 dark:text-purple-300">Servicios con Bono</p>
                    </div>
                </div>
                <div class="flex items-center gap-3 p-4 bg-indigo-50 dark:bg-indigo-900/20 rounded-lg border border-indigo-100 dark:border-indigo-800">
                    <div class="text-2xl">🎁</div>
                    <div>
                        <p class="text-lg font-bold text-indigo-900 dark:text-indigo-100">€{{ number_format($totalBonosVendidos, 2) }}</p>
                        <p class="text-xs text-indigo-700 dark:text-indigo-300">Bonos Vendidos</p>
                    </div>
                </div>
                <div class="flex items-center gap-3 p-4 bg-amber-50 dark:bg-amber-900/20 rounded-lg border border-amber-100 dark:border-amber-800">
                    <div class="text-2xl">💼</div>
                    <div>
                        <p class="text-lg font-bold text-amber-900 dark:text-amber-100">€{{ number_format($totalServicios, 2) }}</p>
                        <p class="text-xs text-amber-700 dark:text-amber-300">Total Servicios</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Peluquería y Estética -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            
            <!-- PELUQUERÍA -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="bg-gradient-to-r from-blue-500 to-blue-600 dark:from-blue-600 dark:to-blue-700 px-6 py-4">
                    <h3 class="text-lg font-semibold text-white flex items-center gap-2">
                        <span class="text-2xl">💇</span>
                        Peluquería
                    </h3>
                </div>
                
                <div class="p-6">
                    <!-- Totales -->
                    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 mb-5 border border-blue-100 dark:border-blue-800">
                        <div class="grid grid-cols-2 gap-3 mb-3">
                            <div class="text-center">
                                <div class="text-xl mb-1">💵</div>
                                <div class="text-lg font-bold text-green-600 dark:text-green-400">€{{ number_format($totalPeluqueriaEfectivo, 2) }}</div>
                                <div class="text-xs text-gray-600 dark:text-gray-400">Efectivo</div>
                            </div>
                            <div class="text-center">
                                <div class="text-xl mb-1">💳</div>
                                <div class="text-lg font-bold text-blue-600 dark:text-blue-400">€{{ number_format($totalPeluqueriaTarjeta, 2) }}</div>
                                <div class="text-xs text-gray-600 dark:text-gray-400">Tarjeta</div>
                            </div>
                        </div>
                        <div class="text-center pt-3 border-t border-blue-200 dark:border-blue-700">
                            <div class="text-xs text-gray-600 dark:text-gray-400 mb-1">🎫 Bono: €{{ number_format($totalPeluqueriaBono, 2) }}</div>
                            <div class="text-2xl font-bold text-blue-700 dark:text-blue-300">€{{ number_format($totalPeluqueria, 2) }}</div>
                            <div class="text-xs text-gray-600 dark:text-gray-400">Total</div>
                        </div>
                    </div>

                    <!-- Servicios -->
                    <div class="mb-4">
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Servicios</h4>
                        @php
                            $serviciosPeluqueria = [];
                            $serviciosPeluqueriaBono = [];
                            
                            foreach($detalleServicios as $cobro) {
                                $yaContados = false;
                                $esBono = $cobro->metodo_pago === 'bono';
                                
                                if ($cobro->cita && $cobro->cita->servicios && $cobro->cita->servicios->count() > 0) {
                                    foreach($cobro->cita->servicios as $servicio) {
                                        if ($servicio->categoria === 'peluqueria') {
                                            $precio = $servicio->pivot->precio ?? $servicio->precio;
                                            $nombre = $servicio->nombre;
                                            
                                            if ($esBono) {
                                                $clave = $nombre . '_bono';
                                                if (!isset($serviciosPeluqueriaBono[$clave])) {
                                                    $serviciosPeluqueriaBono[$clave] = ['nombre' => $nombre, 'cantidad' => 0];
                                                }
                                                $serviciosPeluqueriaBono[$clave]['cantidad']++;
                                            } else {
                                                $clave = $nombre . '_' . $precio;
                                                if (!isset($serviciosPeluqueria[$clave])) {
                                                    $serviciosPeluqueria[$clave] = ['nombre' => $nombre, 'precio_unitario' => $precio, 'cantidad' => 0, 'precio_total' => 0];
                                                }
                                                $serviciosPeluqueria[$clave]['cantidad']++;
                                                $serviciosPeluqueria[$clave]['precio_total'] += $precio;
                                            }
                                        }
                                    }
                                    $yaContados = true;
                                }
                                
                                if (!$yaContados && $cobro->citasAgrupadas && $cobro->citasAgrupadas->count() > 0) {
                                    foreach($cobro->citasAgrupadas as $citaGrupo) {
                                        if ($citaGrupo->servicios && $citaGrupo->servicios->count() > 0) {
                                            foreach($citaGrupo->servicios as $servicio) {
                                                if ($servicio->categoria === 'peluqueria') {
                                                    $precio = $servicio->pivot->precio ?? $servicio->precio;
                                                    $nombre = $servicio->nombre;
                                                    
                                                    if ($esBono) {
                                                        $clave = $nombre . '_bono';
                                                        if (!isset($serviciosPeluqueriaBono[$clave])) {
                                                            $serviciosPeluqueriaBono[$clave] = ['nombre' => $nombre, 'cantidad' => 0];
                                                        }
                                                        $serviciosPeluqueriaBono[$clave]['cantidad']++;
                                                    } else {
                                                        $clave = $nombre . '_' . $precio;
                                                        if (!isset($serviciosPeluqueria[$clave])) {
                                                            $serviciosPeluqueria[$clave] = ['nombre' => $nombre, 'precio_unitario' => $precio, 'cantidad' => 0, 'precio_total' => 0];
                                                        }
                                                        $serviciosPeluqueria[$clave]['cantidad']++;
                                                        $serviciosPeluqueria[$clave]['precio_total'] += $precio;
                                                    }
                                                }
                                            }
                                        }
                                    }
                                    $yaContados = true;
                                }
                                
                                if (!$yaContados && $cobro->servicios && $cobro->servicios->count() > 0) {
                                    foreach($cobro->servicios as $servicio) {
                                        if ($servicio->categoria === 'peluqueria') {
                                            $precio = $servicio->pivot->precio ?? $servicio->precio;
                                            $nombre = $servicio->nombre;
                                            
                                            if ($esBono) {
                                                $clave = $nombre . '_bono';
                                                if (!isset($serviciosPeluqueriaBono[$clave])) {
                                                    $serviciosPeluqueriaBono[$clave] = ['nombre' => $nombre, 'cantidad' => 0];
                                                }
                                                $serviciosPeluqueriaBono[$clave]['cantidad']++;
                                            } else {
                                                $clave = $nombre . '_' . $precio;
                                                if (!isset($serviciosPeluqueria[$clave])) {
                                                    $serviciosPeluqueria[$clave] = ['nombre' => $nombre, 'precio_unitario' => $precio, 'cantidad' => 0, 'precio_total' => 0];
                                                }
                                                $serviciosPeluqueria[$clave]['cantidad']++;
                                                $serviciosPeluqueria[$clave]['precio_total'] += $precio;
                                            }
                                        }
                                    }
                                }
                            }
                        @endphp
                        
                        @if(count($serviciosPeluqueria) > 0 || count($serviciosPeluqueriaBono) > 0)
                            <div class="space-y-2">
                                @foreach($serviciosPeluqueria as $datos)
                                    <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $datos['nombre'] }}</span>
                                        <div class="flex items-center gap-2">
                                            @if($datos['cantidad'] > 1)
                                                <span class="px-2 py-0.5 bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300 rounded-full text-xs font-semibold">x{{ $datos['cantidad'] }}</span>
                                            @endif
                                            <span class="text-sm font-bold text-blue-600 dark:text-blue-400">€{{ number_format($datos['precio_total'], 2) }}</span>
                                        </div>
                                    </div>
                                @endforeach
                                @foreach($serviciosPeluqueriaBono as $datos)
                                    <div class="flex items-center justify-between p-3 bg-purple-50 dark:bg-purple-900/30 rounded-lg border border-purple-200 dark:border-purple-800 hover:bg-purple-100 dark:hover:bg-purple-900/40 transition-colors">
                                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $datos['nombre'] }}</span>
                                        <div class="flex items-center gap-2">
                                            <span class="px-2 py-0.5 bg-purple-100 dark:bg-purple-900/50 text-purple-700 dark:text-purple-300 rounded-full text-xs font-semibold">🎫 Bono</span>
                                            @if($datos['cantidad'] > 1)
                                                <span class="px-2 py-0.5 bg-purple-100 dark:bg-purple-900/50 text-purple-700 dark:text-purple-300 rounded-full text-xs font-semibold">x{{ $datos['cantidad'] }}</span>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-sm text-gray-400 dark:text-gray-500 text-center py-4">Sin servicios registrados</p>
                        @endif
                    </div>

                    <!-- Productos -->
                    <div>
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Productos</h4>
                        @php
                            $productosPeluqueria = [];
                            foreach($detalleServicios as $cobro) {
                                if ($cobro->productos) {
                                    foreach($cobro->productos as $producto) {
                                        if ($producto->categoria === 'peluqueria') {
                                            $cantidad = $producto->pivot->cantidad ?? 1;
                                            $subtotal = $producto->pivot->subtotal ?? 0;
                                            $nombre = $producto->nombre;
                                            
                                            if (!isset($productosPeluqueria[$nombre])) {
                                                $productosPeluqueria[$nombre] = ['cantidad' => 0, 'precio_total' => 0];
                                            }
                                            $productosPeluqueria[$nombre]['cantidad'] += $cantidad;
                                            $productosPeluqueria[$nombre]['precio_total'] += $subtotal;
                                        }
                                    }
                                }
                            }
                        @endphp
                        
                        @if(count($productosPeluqueria) > 0)
                            <div class="space-y-1.5">
                                @foreach($productosPeluqueria as $nombre => $datos)
                                    <div class="flex justify-between items-center text-sm text-gray-700 dark:text-gray-300">
                                        <span>• {{ $nombre }} <span class="text-blue-600 dark:text-blue-400 font-semibold">(x{{ $datos['cantidad'] }})</span></span>
                                        <span class="font-medium">€{{ number_format($datos['precio_total'], 2) }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-xs text-gray-400 dark:text-gray-500 italic">Sin productos</p>
                        @endif
                    </div>
                </div>
            </div>

            <!-- ESTÉTICA -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="bg-gradient-to-r from-pink-500 to-pink-600 dark:from-pink-600 dark:to-pink-700 px-6 py-4">
                    <h3 class="text-lg font-semibold text-white flex items-center gap-2">
                        <span class="text-2xl">💅</span>
                        Estética
                    </h3>
                </div>
                
                <div class="p-6">
                    <!-- Totales -->
                    <div class="bg-pink-50 dark:bg-pink-900/20 rounded-lg p-4 mb-5 border border-pink-100 dark:border-pink-800">
                        <div class="grid grid-cols-2 gap-3 mb-3">
                            <div class="text-center">
                                <div class="text-xl mb-1">💵</div>
                                <div class="text-lg font-bold text-green-600 dark:text-green-400">€{{ number_format($totalEsteticaEfectivo, 2) }}</div>
                                <div class="text-xs text-gray-600 dark:text-gray-400">Efectivo</div>
                            </div>
                            <div class="text-center">
                                <div class="text-xl mb-1">💳</div>
                                <div class="text-lg font-bold text-blue-600 dark:text-blue-400">€{{ number_format($totalEsteticaTarjeta, 2) }}</div>
                                <div class="text-xs text-gray-600 dark:text-gray-400">Tarjeta</div>
                            </div>
                        </div>
                        <div class="text-center pt-3 border-t border-pink-200 dark:border-pink-700">
                            <div class="text-xs text-gray-600 dark:text-gray-400 mb-1">🎫 Bono: €{{ number_format($totalEsteticaBono, 2) }}</div>
                            <div class="text-2xl font-bold text-pink-700 dark:text-pink-300">€{{ number_format($totalEstetica, 2) }}</div>
                            <div class="text-xs text-gray-600 dark:text-gray-400">Total</div>
                        </div>
                    </div>

                    <!-- Servicios -->
                    <div class="mb-4">
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Servicios</h4>
                        @php
                            $serviciosEstetica = [];
                            $serviciosEsteticaBono = [];
                            
                            foreach($detalleServicios as $cobro) {
                                $yaContados = false;
                                $esBono = $cobro->metodo_pago === 'bono';
                                
                                if ($cobro->cita && $cobro->cita->servicios && $cobro->cita->servicios->count() > 0) {
                                    foreach($cobro->cita->servicios as $servicio) {
                                        if ($servicio->categoria === 'estetica') {
                                            $precio = $servicio->pivot->precio ?? $servicio->precio;
                                            $nombre = $servicio->nombre;
                                            
                                            if ($esBono) {
                                                $clave = $nombre . '_bono';
                                                if (!isset($serviciosEsteticaBono[$clave])) {
                                                    $serviciosEsteticaBono[$clave] = ['nombre' => $nombre, 'cantidad' => 0];
                                                }
                                                $serviciosEsteticaBono[$clave]['cantidad']++;
                                            } else {
                                                $clave = $nombre . '_' . $precio;
                                                if (!isset($serviciosEstetica[$clave])) {
                                                    $serviciosEstetica[$clave] = ['nombre' => $nombre, 'precio_unitario' => $precio, 'cantidad' => 0, 'precio_total' => 0];
                                                }
                                                $serviciosEstetica[$clave]['cantidad']++;
                                                $serviciosEstetica[$clave]['precio_total'] += $precio;
                                            }
                                        }
                                    }
                                    $yaContados = true;
                                }
                                
                                if (!$yaContados && $cobro->citasAgrupadas && $cobro->citasAgrupadas->count() > 0) {
                                    foreach($cobro->citasAgrupadas as $citaGrupo) {
                                        if ($citaGrupo->servicios && $citaGrupo->servicios->count() > 0) {
                                            foreach($citaGrupo->servicios as $servicio) {
                                                if ($servicio->categoria === 'estetica') {
                                                    $precio = $servicio->pivot->precio ?? $servicio->precio;
                                                    $nombre = $servicio->nombre;
                                                    
                                                    if ($esBono) {
                                                        $clave = $nombre . '_bono';
                                                        if (!isset($serviciosEsteticaBono[$clave])) {
                                                            $serviciosEsteticaBono[$clave] = ['nombre' => $nombre, 'cantidad' => 0];
                                                        }
                                                        $serviciosEsteticaBono[$clave]['cantidad']++;
                                                    } else {
                                                        $clave = $nombre . '_' . $precio;
                                                        if (!isset($serviciosEstetica[$clave])) {
                                                            $serviciosEstetica[$clave] = ['nombre' => $nombre, 'precio_unitario' => $precio, 'cantidad' => 0, 'precio_total' => 0];
                                                        }
                                                        $serviciosEstetica[$clave]['cantidad']++;
                                                        $serviciosEstetica[$clave]['precio_total'] += $precio;
                                                    }
                                                }
                                            }
                                        }
                                    }
                                    $yaContados = true;
                                }
                                
                                if (!$yaContados && $cobro->servicios && $cobro->servicios->count() > 0) {
                                    foreach($cobro->servicios as $servicio) {
                                        if ($servicio->categoria === 'estetica') {
                                            $precio = $servicio->pivot->precio ?? $servicio->precio;
                                            $nombre = $servicio->nombre;
                                            
                                            if ($esBono) {
                                                $clave = $nombre . '_bono';
                                                if (!isset($serviciosEsteticaBono[$clave])) {
                                                    $serviciosEsteticaBono[$clave] = ['nombre' => $nombre, 'cantidad' => 0];
                                                }
                                                $serviciosEsteticaBono[$clave]['cantidad']++;
                                            } else {
                                                $clave = $nombre . '_' . $precio;
                                                if (!isset($serviciosEstetica[$clave])) {
                                                    $serviciosEstetica[$clave] = ['nombre' => $nombre, 'precio_unitario' => $precio, 'cantidad' => 0, 'precio_total' => 0];
                                                }
                                                $serviciosEstetica[$clave]['cantidad']++;
                                                $serviciosEstetica[$clave]['precio_total'] += $precio;
                                            }
                                        }
                                    }
                                }
                            }
                        @endphp
                        
                        @if(count($serviciosEstetica) > 0 || count($serviciosEsteticaBono) > 0)
                            <div class="space-y-2">
                                @foreach($serviciosEstetica as $datos)
                                    <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $datos['nombre'] }}</span>
                                        <div class="flex items-center gap-2">
                                            @if($datos['cantidad'] > 1)
                                                <span class="px-2 py-0.5 bg-pink-100 dark:bg-pink-900/50 text-pink-700 dark:text-pink-300 rounded-full text-xs font-semibold">x{{ $datos['cantidad'] }}</span>
                                            @endif
                                            <span class="text-sm font-bold text-pink-600 dark:text-pink-400">€{{ number_format($datos['precio_total'], 2) }}</span>
                                        </div>
                                    </div>
                                @endforeach
                                @foreach($serviciosEsteticaBono as $datos)
                                    <div class="flex items-center justify-between p-3 bg-purple-50 dark:bg-purple-900/30 rounded-lg border border-purple-200 dark:border-purple-800 hover:bg-purple-100 dark:hover:bg-purple-900/40 transition-colors">
                                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $datos['nombre'] }}</span>
                                        <div class="flex items-center gap-2">
                                            <span class="px-2 py-0.5 bg-purple-100 dark:bg-purple-900/50 text-purple-700 dark:text-purple-300 rounded-full text-xs font-semibold">🎫 Bono</span>
                                            @if($datos['cantidad'] > 1)
                                                <span class="px-2 py-0.5 bg-purple-100 dark:bg-purple-900/50 text-purple-700 dark:text-purple-300 rounded-full text-xs font-semibold">x{{ $datos['cantidad'] }}</span>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-sm text-gray-400 dark:text-gray-500 text-center py-4">Sin servicios registrados</p>
                        @endif
                    </div>

                    <!-- Productos -->
                    <div>
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Productos</h4>
                        @php
                            $productosEstetica = [];
                            foreach($detalleServicios as $cobro) {
                                if ($cobro->productos) {
                                    foreach($cobro->productos as $producto) {
                                        if ($producto->categoria === 'estetica') {
                                            $cantidad = $producto->pivot->cantidad ?? 1;
                                            $subtotal = $producto->pivot->subtotal ?? 0;
                                            $nombre = $producto->nombre;
                                            
                                            if (!isset($productosEstetica[$nombre])) {
                                                $productosEstetica[$nombre] = ['cantidad' => 0, 'precio_total' => 0];
                                            }
                                            $productosEstetica[$nombre]['cantidad'] += $cantidad;
                                            $productosEstetica[$nombre]['precio_total'] += $subtotal;
                                        }
                                    }
                                }
                            }
                        @endphp
                        
                        @if(count($productosEstetica) > 0)
                            <div class="space-y-1.5">
                                @foreach($productosEstetica as $nombre => $datos)
                                    <div class="flex justify-between items-center text-sm text-gray-700 dark:text-gray-300">
                                        <span>• {{ $nombre }} <span class="text-pink-600 dark:text-pink-400 font-semibold">(x{{ $datos['cantidad'] }})</span></span>
                                        <span class="font-medium">€{{ number_format($datos['precio_total'], 2) }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-xs text-gray-400 dark:text-gray-500 italic">Sin productos</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Servicios Realizados -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 mb-6 border border-gray-200 dark:border-gray-700">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Servicios Realizados</h2>
            
            @if($detalleServicios->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-gray-700">
                                <th class="text-left py-3 px-4 font-semibold text-gray-700 dark:text-gray-300">Hora</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-700 dark:text-gray-300">Cliente</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-700 dark:text-gray-300">Servicios</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-700 dark:text-gray-300">Empleado</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-700 dark:text-gray-300">Método</th>
                                <th class="text-right py-3 px-4 font-semibold text-gray-700 dark:text-gray-300">Total</th>
                                <th class="text-right py-3 px-4 font-semibold text-gray-700 dark:text-gray-300">Deuda</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach($detalleServicios as $item)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                    <td class="py-3 px-4 font-medium text-gray-900 dark:text-gray-100">
                                        @php
                                            $horaCita = null;
                                            if ($item->cita && $item->cita->fecha_hora) {
                                                $horaCita = \Carbon\Carbon::parse($item->cita->fecha_hora)->format('H:i');
                                            } elseif ($item->citasAgrupadas && $item->citasAgrupadas->count() > 0) {
                                                $primeraCita = $item->citasAgrupadas->first();
                                                if ($primeraCita && $primeraCita->fecha_hora) {
                                                    $horaCita = \Carbon\Carbon::parse($primeraCita->fecha_hora)->format('H:i');
                                                }
                                            }
                                        @endphp
                                        {{ $horaCita ?? '-' }}
                                    </td>
                                    <td class="py-3 px-4 text-gray-700 dark:text-gray-300">
                                        @if($item->cliente && $item->cliente->user)
                                            {{ $item->cliente->user->nombre }} {{ $item->cliente->user->apellidos }}
                                        @elseif($item->cita && $item->cita->cliente && $item->cita->cliente->user)
                                            {{ $item->cita->cliente->user->nombre }} {{ $item->cita->cliente->user->apellidos }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="py-3 px-4">
                                        @php
                                            $serviciosMostrados = false;
                                            $yaContados = false;
                                            
                                            if ($item->cita && $item->cita->servicios && $item->cita->servicios->count() > 0) {
                                                foreach($item->cita->servicios as $servicio) {
                                                    if($servicio->categoria === 'peluqueria') {
                                                        echo '<span class="inline-flex items-center px-2 py-1 bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300 rounded text-xs mr-1 mb-1">💇 ' . $servicio->nombre . '</span>';
                                                    } elseif($servicio->categoria === 'estetica') {
                                                        echo '<span class="inline-flex items-center px-2 py-1 bg-pink-100 dark:bg-pink-900/50 text-pink-700 dark:text-pink-300 rounded text-xs mr-1 mb-1">💅 ' . $servicio->nombre . '</span>';
                                                    }
                                                    $serviciosMostrados = true;
                                                }
                                                $yaContados = true;
                                            }
                                            
                                            if (!$yaContados && $item->citasAgrupadas && $item->citasAgrupadas->count() > 0) {
                                                foreach($item->citasAgrupadas as $citaGrupo) {
                                                    if ($citaGrupo->servicios && $citaGrupo->servicios->count() > 0) {
                                                        foreach($citaGrupo->servicios as $servicio) {
                                                            if($servicio->categoria === 'peluqueria') {
                                                                echo '<span class="inline-flex items-center px-2 py-1 bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300 rounded text-xs mr-1 mb-1">💇 ' . $servicio->nombre . '</span>';
                                                            } elseif($servicio->categoria === 'estetica') {
                                                                echo '<span class="inline-flex items-center px-2 py-1 bg-pink-100 dark:bg-pink-900/50 text-pink-700 dark:text-pink-300 rounded text-xs mr-1 mb-1">💅 ' . $servicio->nombre . '</span>';
                                                            }
                                                            $serviciosMostrados = true;
                                                        }
                                                    }
                                                }
                                                $yaContados = true;
                                            }
                                            
                                            if (!$yaContados && $item->servicios && $item->servicios->count() > 0) {
                                                foreach($item->servicios as $servicio) {
                                                    if($servicio->categoria === 'peluqueria') {
                                                        echo '<span class="inline-flex items-center px-2 py-1 bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300 rounded text-xs mr-1 mb-1">💇 ' . $servicio->nombre . '</span>';
                                                    } elseif($servicio->categoria === 'estetica') {
                                                        echo '<span class="inline-flex items-center px-2 py-1 bg-pink-100 dark:bg-pink-900/50 text-pink-700 dark:text-pink-300 rounded text-xs mr-1 mb-1">💅 ' . $servicio->nombre . '</span>';
                                                    }
                                                    $serviciosMostrados = true;
                                                }
                                            }
                                            
                                            if ($item->productos && $item->productos->count() > 0) {
                                                foreach($item->productos as $producto) {
                                                    echo '<span class="inline-flex items-center px-2 py-1 bg-green-100 dark:bg-green-900/50 text-green-700 dark:text-green-300 rounded text-xs mr-1 mb-1">🛍️ ' . $producto->nombre . ' (x' . $producto->pivot->cantidad . ')</span>';
                                                    $serviciosMostrados = true;
                                                }
                                            }
                                            
                                            if (!$serviciosMostrados) {
                                                echo '<span class="text-gray-400 dark:text-gray-500">-</span>';
                                            }
                                        @endphp
                                    </td>
                                    <td class="py-3 px-4 text-gray-700 dark:text-gray-300">
                                        @if($item->empleado && $item->empleado->user)
                                            {{ $item->empleado->user->nombre }}
                                        @elseif($item->cita && $item->cita->empleado && $item->cita->empleado->user)
                                            {{ $item->cita->empleado->user->nombre }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="py-3 px-4">
                                        @if($item->metodo_pago === 'efectivo')
                                            <span class="inline-flex items-center px-2 py-1 bg-green-100 dark:bg-green-900/50 text-green-700 dark:text-green-300 rounded-full text-xs font-semibold">💵 Efectivo</span>
                                        @elseif($item->metodo_pago === 'tarjeta')
                                            <span class="inline-flex items-center px-2 py-1 bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300 rounded-full text-xs font-semibold">💳 Tarjeta</span>
                                        @elseif($item->metodo_pago === 'bono')
                                            <span class="inline-flex items-center px-2 py-1 bg-purple-100 dark:bg-purple-900/50 text-purple-700 dark:text-purple-300 rounded-full text-xs font-semibold">🎫 Bono</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 text-right font-bold text-green-600 dark:text-green-400">€{{ number_format($item->total_final, 2) }}</td>
                                    <td class="py-3 px-4 text-right font-bold {{ $item->deuda > 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-400 dark:text-gray-500' }}">€{{ number_format($item->deuda ?? 0, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-12">
                    <p class="text-gray-400 dark:text-gray-500">No se realizaron servicios este día</p>
                </div>
            @endif
        </div>

        <!-- Bonos Vendidos y Deudas -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            
            <!-- BONOS VENDIDOS -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="bg-gradient-to-r from-purple-500 to-purple-600 dark:from-purple-600 dark:to-purple-700 px-6 py-4">
                    <h3 class="text-lg font-semibold text-white flex items-center gap-2">
                        <span class="text-2xl">🎫</span>
                        Bonos Vendidos
                    </h3>
                </div>
                
                <div class="p-6">
                    @if($bonosVendidos->count() > 0)
                        <div class="space-y-3">
                            @foreach($bonosVendidos as $bono)
                                <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-4 hover:bg-purple-100 dark:hover:bg-purple-900/30 transition-colors border border-purple-200 dark:border-purple-800">
                                    <div class="flex justify-between items-start mb-3">
                                        <div class="flex-1">
                                            <div class="font-bold text-gray-900 dark:text-gray-100 mb-1">{{ $bono->plantilla->nombre }}</div>
                                            <div class="text-xs text-gray-600 dark:text-gray-400 space-y-0.5">
                                                @if($bono->cliente && $bono->cliente->user)
                                                    <div>👤 {{ $bono->cliente->user->nombre }} {{ $bono->cliente->user->apellidos }}</div>
                                                @endif
                                                @if($bono->empleado && $bono->empleado->user)
                                                    <div>👨‍💼 {{ $bono->empleado->user->nombre }}</div>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="text-right ml-4">
                                            <div class="text-xl font-bold text-purple-700 dark:text-purple-300">€{{ number_format($bono->precio_pagado, 2) }}</div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ \Carbon\Carbon::parse($bono->created_at)->format('H:i') }}</div>
                                        </div>
                                    </div>
                                    <div class="flex justify-between items-center pt-3 border-t border-purple-200 dark:border-purple-700">
                                        <div>
                                            @if($bono->metodo_pago === 'efectivo')
                                                <span class="inline-flex items-center px-2 py-1 bg-green-100 dark:bg-green-900/50 text-green-700 dark:text-green-300 rounded-full text-xs font-semibold">💵 Efectivo</span>
                                            @elseif($bono->metodo_pago === 'tarjeta')
                                                <span class="inline-flex items-center px-2 py-1 bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300 rounded-full text-xs font-semibold">💳 Tarjeta</span>
                                            @endif
                                        </div>
                                        @if($bono->plantilla->duracion_dias)
                                            <span class="text-xs text-gray-500 dark:text-gray-400">⏰ {{ $bono->plantilla->duracion_dias }} días</span>
                                        @else
                                            <span class="text-xs text-purple-600 dark:text-purple-400 font-semibold">♾️ Sin límite</span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-12">
                            <div class="text-5xl mb-3">🎫</div>
                            <p class="text-gray-400 dark:text-gray-500">No se vendieron bonos este día</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- DEUDAS GENERADAS -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="bg-gradient-to-r from-red-500 to-red-600 dark:from-red-600 dark:to-red-700 px-6 py-4">
                    <h3 class="text-lg font-semibold text-white flex items-center gap-2">
                        <span class="text-2xl">⚠️</span>
                        Deudas Generadas
                    </h3>
                </div>
                
                <div class="p-6">
                    @if($deudas->count() > 0)
                        <div class="space-y-3">
                            @foreach($deudas as $deuda)
                                <div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-4 hover:bg-red-100 dark:hover:bg-red-900/30 transition-colors border border-red-200 dark:border-red-800">
                                    <div class="flex justify-between items-start mb-3">
                                        <div class="flex-1">
                                            <div class="font-bold text-gray-900 dark:text-gray-100 mb-2">
                                                @if($deuda->cliente && $deuda->cliente->user)
                                                    {{ $deuda->cliente->user->nombre }} {{ $deuda->cliente->user->apellidos }}
                                                @elseif($deuda->cita && $deuda->cita->cliente && $deuda->cita->cliente->user)
                                                    {{ $deuda->cita->cliente->user->nombre }} {{ $deuda->cita->cliente->user->apellidos }}
                                                @else
                                                    Cliente desconocido
                                                @endif
                                            </div>
                                            <div class="flex flex-wrap gap-1">
                                                @if($deuda->cita && $deuda->cita->servicios && $deuda->cita->servicios->count() > 0)
                                                    @foreach($deuda->cita->servicios as $servicio)
                                                        <span class="inline-flex items-center px-2 py-0.5 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded text-xs border border-red-200 dark:border-red-700">{{ $servicio->nombre }}</span>
                                                    @endforeach
                                                @else
                                                    <span class="text-xs text-gray-400 dark:text-gray-500 italic">Sin servicios</span>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="text-right ml-4">
                                            <div class="text-xl font-bold text-red-600 dark:text-red-400">€{{ number_format($deuda->deuda, 2) }}</div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">Deuda</div>
                                        </div>
                                    </div>
                                    <div class="flex justify-between items-center pt-3 border-t border-red-200 dark:border-red-700 text-sm">
                                        <div class="text-gray-600 dark:text-gray-400">
                                            Total: <span class="font-semibold text-gray-900 dark:text-gray-100">€{{ number_format($deuda->total_final + $deuda->deuda, 2) }}</span>
                                        </div>
                                        <div class="font-semibold text-green-600 dark:text-green-400">
                                            Pagado: €{{ number_format($deuda->total_final, 2) }}
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                            
                            <!-- Total de deudas -->
                            <div class="bg-red-100 dark:bg-red-900/40 rounded-lg p-4 border-2 border-red-300 dark:border-red-700">
                                <div class="flex justify-between items-center">
                                    <span class="text-red-800 dark:text-red-300 font-bold">Total Deuda del Día</span>
                                    <span class="text-2xl font-bold text-red-700 dark:text-red-400">€{{ number_format($totalDeuda, 2) }}</span>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="text-center py-12">
                            <div class="text-5xl mb-3">✅</div>
                            <p class="text-emerald-600 dark:text-emerald-400 font-semibold">No se generaron deudas este día</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</body>
</html>
