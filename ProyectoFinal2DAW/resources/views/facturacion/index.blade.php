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

        </div>
    </div>
</body>
</html>
