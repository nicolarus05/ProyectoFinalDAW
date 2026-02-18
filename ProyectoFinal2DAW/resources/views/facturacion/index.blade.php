<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facturación Mensual</title>
    {!! vite_asset(['resources/css/app.css', 'resources/js/app.js']) !!}
</head>
<body class="bg-gray-50">
    <div class="min-h-screen p-6">
        <div class="max-w-7xl mx-auto">
            
            <!-- Header -->
            <div class="mb-6">
                <a href="{{ route('dashboard') }}" class="text-blue-600 hover:text-blue-800 mb-4 inline-block">
                    ← Volver al Dashboard
                </a>
                <h1 class="text-4xl font-bold text-gray-800 flex items-center gap-3">
                    💰 Facturación Mensual
                </h1>
            </div>

            <!-- Selector de Mes y Año -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <form method="GET" action="{{ route('facturacion.index') }}" class="flex flex-wrap gap-4 items-end">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Mes</label>
                        <select name="mes" class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                            @foreach($meses as $num => $nombre)
                                <option value="{{ $num }}" {{ $mes == $num ? 'selected' : '' }}>
                                    {{ $nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Año</label>
                        <select name="anio" class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                            @for($y = now()->year; $y >= now()->year - 5; $y--)
                                <option value="{{ $y }}" {{ $anio == $y ? 'selected' : '' }}>
                                    {{ $y }}
                                </option>
                            @endfor
                        </select>
                    </div>
                    <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition font-semibold">
                        🔍 Consultar
                    </button>
                </form>
                <p class="text-sm text-gray-600 mt-3">
                    Mostrando facturación de <strong>{{ $meses[$mes] }} {{ $anio }}</strong>
                    ({{ $fechaInicio->format('d/m/Y') }} - {{ $fechaFin->format('d/m/Y') }})
                </p>
            </div>

            <!-- Resumen Total Destacado -->
            <div class="bg-gradient-to-r from-green-500 to-emerald-600 rounded-lg shadow-lg p-8 mb-8 text-white">
                <div class="text-center">
                    <h2 class="text-2xl font-bold mb-2">Total Facturado</h2>
                    <p class="text-6xl font-bold">€{{ number_format($totalGeneral, 2) }}</p>
                    <p class="text-sm opacity-90 mt-2">{{ $meses[$mes] }} {{ $anio }}</p>
                </div>
            </div>

            <!-- Grid de Desglose -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                
                <!-- SERVICIOS -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6 flex items-center gap-2">
                        💇 Servicios
                        <span class="text-lg font-normal text-gray-600">
                            (€{{ number_format($totalServicios, 2) }})
                        </span>
                    </h2>

                    <!-- Peluquería -->
                    <div class="mb-4 p-4 bg-blue-50 rounded-lg border-2 border-blue-200">
                        <div class="flex justify-between items-center">
                            <div class="flex items-center gap-2">
                                <span class="text-3xl">✂️</span>
                                <span class="font-semibold text-gray-800 text-lg">Peluquería</span>
                            </div>
                            <span class="text-2xl font-bold text-blue-700">
                                €{{ number_format($serviciosPeluqueria, 2) }}
                            </span>
                        </div>
                    </div>

                    <!-- Estética -->
                    <div class="p-4 bg-pink-50 rounded-lg border-2 border-pink-200">
                        <div class="flex justify-between items-center">
                            <div class="flex items-center gap-2">
                                <span class="text-3xl">💆</span>
                                <span class="font-semibold text-gray-800 text-lg">Estética</span>
                            </div>
                            <span class="text-2xl font-bold text-pink-700">
                                €{{ number_format($serviciosEstetica, 2) }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- PRODUCTOS -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6 flex items-center gap-2">
                        📦 Productos
                        <span class="text-lg font-normal text-gray-600">
                            (€{{ number_format($totalProductos, 2) }})
                        </span>
                    </h2>

                    <!-- Peluquería -->
                    <div class="mb-4 p-4 bg-purple-50 rounded-lg border-2 border-purple-200">
                        <div class="flex justify-between items-center">
                            <div class="flex items-center gap-2">
                                <span class="text-3xl">✂️</span>
                                <span class="font-semibold text-gray-800 text-lg">Peluquería</span>
                            </div>
                            <span class="text-2xl font-bold text-purple-700">
                                €{{ number_format($productosPeluqueria, 2) }}
                            </span>
                        </div>
                    </div>

                    <!-- Estética -->
                    <div class="p-4 bg-orange-50 rounded-lg border-2 border-orange-200">
                        <div class="flex justify-between items-center">
                            <div class="flex items-center gap-2">
                                <span class="text-3xl">💆</span>
                                <span class="font-semibold text-gray-800 text-lg">Estética</span>
                            </div>
                            <span class="text-2xl font-bold text-orange-700">
                                €{{ number_format($productosEstetica, 2) }}
                            </span>
                        </div>
                    </div>
                </div>

            </div>

            <!-- BONOS VENDIDOS -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-2xl font-bold text-gray-800 mb-4 flex items-center gap-2">
                    🎫 Bonos Vendidos
                </h2>
                
                <!-- Bonos Peluquería -->
                <div class="p-4 bg-blue-50 rounded-lg border-2 border-blue-200 mb-4">
                    <div class="flex justify-between items-center">
                        <span class="font-semibold text-gray-800">💇 Bonos Peluquería</span>
                        <span class="text-2xl font-bold text-blue-700">
                            €{{ number_format($bonosPeluqueria, 2) }}
                        </span>
                    </div>
                </div>
                
                <!-- Bonos Estética -->
                <div class="p-4 bg-pink-50 rounded-lg border-2 border-pink-200 mb-4">
                    <div class="flex justify-between items-center">
                        <span class="font-semibold text-gray-800">✨ Bonos Estética</span>
                        <span class="text-2xl font-bold text-pink-700">
                            €{{ number_format($bonosEstetica, 2) }}
                        </span>
                    </div>
                </div>
                
                <!-- Total Bonos -->
                <div class="p-4 bg-indigo-50 rounded-lg border-2 border-indigo-200">
                    <div class="flex justify-between items-center">
                        <span class="font-semibold text-gray-800 text-lg">Total Bonos</span>
                        <span class="text-3xl font-bold text-indigo-700">
                            €{{ number_format($bonosVendidos, 2) }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Desglose por Categoría (Resumen Visual) -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">📊 Desglose por Categoría</h2>
                
                <div class="space-y-4">
                    <!-- Servicios -->
                    <div>
                        <div class="flex justify-between mb-2">
                            <span class="font-semibold text-gray-800">Servicios</span>
                            <span class="font-bold text-gray-900">
                                €{{ number_format($totalServicios, 2) }}
                                <span class="text-sm text-gray-600">
                                    ({{ $totalGeneral > 0 ? number_format(($totalServicios / $totalGeneral) * 100, 1) : 0 }}%)
                                </span>
                            </span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-3">
                            <div class="bg-blue-600 h-3 rounded-full" 
                                 style="width: {{ $totalGeneral > 0 ? ($totalServicios / $totalGeneral) * 100 : 0 }}%">
                            </div>
                        </div>
                    </div>

                    <!-- Productos -->
                    <div>
                        <div class="flex justify-between mb-2">
                            <span class="font-semibold text-gray-800">Productos</span>
                            <span class="font-bold text-gray-900">
                                €{{ number_format($totalProductos, 2) }}
                                <span class="text-sm text-gray-600">
                                    ({{ $totalGeneral > 0 ? number_format(($totalProductos / $totalGeneral) * 100, 1) : 0 }}%)
                                </span>
                            </span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-3">
                            <div class="bg-purple-600 h-3 rounded-full" 
                                 style="width: {{ $totalGeneral > 0 ? ($totalProductos / $totalGeneral) * 100 : 0 }}%">
                            </div>
                        </div>
                    </div>

                    <!-- Bonos -->
                    <div>
                        <div class="flex justify-between mb-2">
                            <span class="font-semibold text-gray-800">Bonos</span>
                            <span class="font-bold text-gray-900">
                                €{{ number_format($bonosVendidos, 2) }}
                                <span class="text-sm text-gray-600">
                                    ({{ $totalGeneral > 0 ? number_format(($bonosVendidos / $totalGeneral) * 100, 1) : 0 }}%)
                                </span>
                            </span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-3">
                            <div class="bg-indigo-600 h-3 rounded-full" 
                                 style="width: {{ $totalGeneral > 0 ? ($bonosVendidos / $totalGeneral) * 100 : 0 }}%">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Resumen y Verificación -->
            <div class="bg-blue-50 border-2 border-blue-200 rounded-lg shadow-md p-6 mt-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4">📊 Resumen de Verificación</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-white p-4 rounded-lg border border-gray-300">
                        <div class="text-sm text-gray-600 mb-1">Total Facturado</div>
                        <div class="text-2xl font-bold text-gray-900">€{{ number_format($totalGeneral, 2) }}</div>
                        <div class="text-xs text-gray-500 mt-1">Servicios + Productos + Bonos</div>
                    </div>
                    <div class="bg-white p-4 rounded-lg border border-red-300">
                        <div class="text-sm text-gray-600 mb-1">Deuda Pendiente</div>
                        <div class="text-2xl font-bold text-red-600">€{{ number_format($deudaTotal, 2) }}</div>
                        <div class="text-xs text-gray-500 mt-1">Pendiente de cobro</div>
                    </div>
                    <div class="bg-white p-4 rounded-lg border border-green-300">
                        <div class="text-sm text-gray-600 mb-1">Total Cobrado (Cajas)</div>
                        <div class="text-2xl font-bold text-green-600">€{{ number_format($sumaCajasDiarias, 2) }}</div>
                        <div class="text-xs text-gray-500 mt-1">Efectivo + Tarjeta recibidos</div>
                    </div>
                </div>
                @php
                    $diferencia = abs($totalRealmenteCobrado - $sumaCajasDiarias);
                @endphp
                @if($diferencia < 0.01)
                    <div class="mt-4 p-3 bg-green-100 border border-green-300 rounded text-center">
                        <span class="text-green-800 font-semibold">✓ Los cálculos son correctos</span>
                        <span class="text-sm text-gray-600 ml-2">(Total Facturado - Deuda = Suma de Cajas)</span>
                    </div>
                @else
                    <div class="mt-4 p-3 bg-yellow-100 border border-yellow-300 rounded text-center">
                        <span class="text-yellow-800 font-semibold">⚠ Diferencia detectada: €{{ number_format($diferencia, 2) }}</span>
                    </div>
                @endif
            </div>

            <!-- Cajas Diarias -->
            <div class="bg-white rounded-lg shadow-md p-6 mt-6">
                <h2 class="text-2xl font-bold text-gray-800 mb-4">📅 Cajas Diarias</h2>
                <div class="grid grid-cols-3 md:grid-cols-5 lg:grid-cols-7 xl:grid-cols-10 gap-2">
                    @foreach($cajasDiarias as $fecha => $datos)
                        <div class="p-2 rounded {{ $datos['total'] > 0 ? 'bg-green-50 border border-green-200' : 'bg-gray-50 border border-gray-200 opacity-60' }}">
                            <div class="text-[10px] text-gray-500 mb-1 uppercase font-semibold text-center">
                                {{ \Carbon\Carbon::parse($fecha)->translatedFormat('D d') }}
                            </div>
                            <div class="text-center mb-1">
                                <div class="font-bold text-sm {{ $datos['total'] > 0 ? 'text-green-700' : 'text-gray-400' }}">
                                    €{{ number_format($datos['total'], 2) }}
                                </div>
                            </div>
                            @if($datos['total'] > 0)
                                <div class="border-t border-green-200 pt-1 space-y-0.5">
                                    <div class="flex justify-between items-center text-[9px]">
                                        <span class="text-green-600 flex items-center">
                                            <svg class="w-2.5 h-2.5 mr-0.5" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4z"/>
                                                <path fill-rule="evenodd" d="M18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm5-1a1 1 0 100 2h1a1 1 0 100-2H9z" clip-rule="evenodd"/>
                                            </svg>
                                            Efec.
                                        </span>
                                        <span class="font-semibold text-green-700">€{{ number_format($datos['efectivo'], 2) }}</span>
                                    </div>
                                    <div class="flex justify-between items-center text-[9px]">
                                        <span class="text-blue-600 flex items-center">
                                            <svg class="w-2.5 h-2.5 mr-0.5" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M4 4a2 2 0 00-2 2v8a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2H4zm0 2h12v2H4V6zm0 4h12v4H4v-4z"/>
                                            </svg>
                                            Tarj.
                                        </span>
                                        <span class="font-semibold text-blue-700">€{{ number_format($datos['tarjeta'], 2) }}</span>
                                    </div>
                                </div>
                                @if(($datos['peluqueria'] ?? 0) > 0 || ($datos['estetica'] ?? 0) > 0)
                                    <div class="border-t border-green-200 pt-1 space-y-0.5">
                                        <div class="flex justify-between items-center text-[9px]">
                                            <span class="text-pink-600">✂️ Pelu.</span>
                                            <span class="font-semibold text-pink-700">€{{ number_format($datos['peluqueria'] ?? 0, 2) }}</span>
                                        </div>
                                        <div class="flex justify-between items-center text-[9px]">
                                            <span class="text-purple-600">💆 Esté.</span>
                                            <span class="font-semibold text-purple-700">€{{ number_format($datos['estetica'] ?? 0, 2) }}</span>
                                        </div>
                                    </div>
                                @endif
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</body>
</html>
