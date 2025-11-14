<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bonos - Plantillas</title>
    {!! vite_asset(['resources/css/app.css', 'resources/js/app.js']) !!}
    <style>
        .btn-float {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            z-index: 1000;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .btn-float:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.4);
        }
        .btn-crear-top {
            display: inline-block;
            background-color: #16a34a;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            text-decoration: none;
            font-weight: 500;
            transition: background-color 0.2s;
        }
        .btn-crear-top:hover {
            background-color: #15803d;
        }
    </style>
</head>
<body class="bg-gray-100 p-6">
    <div class="max-w-7xl mx-auto bg-white shadow-md rounded p-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold">Bonos Disponibles</h1>
            <a href="{{ route('bonos.create') }}" class="btn-crear-top">
                ➕ Crear Nuevo Bono
            </a>
        </div>

        @if(session('success'))
            <div class="bg-green-100 text-green-700 p-4 mb-4 rounded">
                {{ session('success') }}
            </div>
        @endif

        @if($plantillas->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($plantillas as $plantilla)
                    <div class="border rounded-lg p-6 shadow hover:shadow-lg transition">
                        <h3 class="text-xl font-bold mb-2">{{ $plantilla->nombre }}</h3>
                        
                        @if($plantilla->descripcion)
                            <p class="text-gray-600 mb-4">{{ $plantilla->descripcion }}</p>
                        @endif

                        <div class="mb-4">
                            <p class="font-semibold text-2xl text-green-600">€{{ number_format($plantilla->precio, 2) }}</p>
                            <p class="text-sm text-gray-500">
                                @if($plantilla->duracion_dias)
                                    Válido por {{ $plantilla->duracion_dias }} días
                                @else
                                    <span class="text-purple-600 font-semibold">✨ Sin límite de tiempo</span>
                                @endif
                            </p>
                        </div>

                        <div class="mb-4">
                            <p class="font-semibold mb-2">Servicios incluidos:</p>
                            <ul class="list-disc pl-5 space-y-1">
                                @foreach($plantilla->servicios as $servicio)
                                    <li class="text-sm">
                                        {{ $servicio->nombre }} 
                                        @if($servicio->tipo === 'peluqueria')
                                            <span class="text-blue-600">💇</span>
                                        @else
                                            <span class="text-pink-600">💅</span>
                                        @endif
                                        <span class="font-semibold">(x{{ $servicio->pivot->cantidad }})</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>

                        <div class="flex gap-2">
                            <a href="{{ route('bonos.comprar', $plantilla->id) }}" class="flex-1 bg-blue-600 text-white px-4 py-2 rounded text-center hover:bg-blue-700">
                                Vender
                            </a>
                            <a href="{{ route('bonos.edit', $plantilla->id) }}" class="flex-1 bg-gray-600 text-white px-4 py-2 rounded text-center hover:bg-gray-700">
                                Editar
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-12">
                <p class="text-gray-500 mb-4">No hay bonos creados aún.</p>
                <a href="{{ route('bonos.create') }}" class="text-blue-600 hover:underline">Crear el primer bono</a>
            </div>
        @endif

        <div class="mt-6">
            <a href="{{ route('dashboard') }}" class="text-blue-600 hover:underline">Volver al Inicio</a>
        </div>
    </div>

    <!-- Botón flotante para crear bono -->
    <a href="{{ route('bonos.create') }}" class="btn-float" 
       style="background-color: #16a34a; color: white; text-decoration: none;" 
       title="Crear Nuevo Bono">
        <span style="font-weight: bold; font-size: 32px; line-height: 1;">+</span>
    </a>
</body>
</html>
