<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Lista de Cobros</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-7xl mx-auto bg-white p-6 rounded shadow">
        <h1 class="text-3xl font-bold mb-6">Registros de Cobro</h1>

        <div class="mb-4">
            <a href="{{ route('cobros.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                Registrar nuevo cobro
            </a>
        </div>

        <div class="overflow-x-auto rounded-lg">
            <table class="min-w-full border border-gray-300 text-sm rounded-lg overflow-hidden">
                <thead class="bg-gray-200 text-gray-700">
                    <tr>
                        <th class="p-2 border">Cliente</th>
                        <th class="p-2 border">Empleado</th>
                        <th class="p-2 border">Servicio</th>
                        <th class="p-2 border">Productos</th>
                        <th class="p-2 border">Coste</th>
                        <th class="p-2 border">Desc. %</th>
                        <th class="p-2 border">Desc. €</th>
                        <th class="p-2 border">Total Final</th>
                        <th class="p-2 border">Dinero Cliente</th>
                        <th class="p-2 border">Cambio</th>
                        <th class="p-2 border">Método Pago</th>
                        <th class="p-2 border">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($cobros as $cobro)
                        <tr class="text-center border-t hover:bg-gray-50">
                            <td class="p-2 border">{{ $cobro->cita->cliente->user->nombre ?? '-' }}</td>
                            <td class="p-2 border">{{ $cobro->cita->empleado->user->nombre ?? '-' }}</td>

                            <!-- Servicios -->
                            <td class="p-2 border">
                                @php
                                    $servicios = $cobro->cita->servicios->pluck('nombre')->implode(', ');
                                @endphp
                                {{ $servicios ?: '-' }}
                            </td>

                            <!-- Productos -->
                            <td class="p-2 border text-left">
                                @php
                                    $productos = $cobro->productos()->withPivot('cantidad')->get();
                                @endphp

                                @if($productos->isEmpty())
                                    <span class="text-gray-400 italic">—</span>
                                @else
                                    <ul class="list-disc list-inside">
                                        @foreach($productos as $prod)
                                            <li>
                                                {{ $prod->nombre }}
                                                <span class="text-gray-500">(x{{ $prod->pivot->cantidad }})</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </td>

                            <td class="p-2 border">{{ number_format($cobro->coste, 2) }} €</td>
                            <td class="p-2 border">{{ $cobro->descuento_porcentaje ?? 0 }}%</td>
                            <td class="p-2 border">{{ number_format($cobro->descuento_euro ?? 0, 2) }} €</td>
                            <td class="p-2 border font-semibold">{{ number_format($cobro->total_final, 2) }} €</td>
                            <td class="p-2 border">{{ number_format($cobro->dinero_cliente, 2) }} €</td>
                            <td class="p-2 border">{{ number_format($cobro->cambio, 2) }} €</td>
                            <td class="p-2 border capitalize">{{ $cobro->metodo_pago }}</td>

                            <td class="p-2 border space-y-1">
                                <a href="{{ route('cobros.show', $cobro->id) }}" class="text-blue-600 hover:underline block">Ver</a>
                                <a href="{{ route('cobros.edit', $cobro->id) }}" class="text-yellow-600 hover:underline block">Editar</a>
                                <form action="{{ route('cobros.destroy', $cobro->id) }}" method="POST" onsubmit="return confirm('¿Eliminar este cobro?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:underline">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" class="text-center py-4 text-gray-500">No hay cobros registrados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            <a href="{{ route('dashboard') }}" class="text-blue-600 hover:underline">Volver al inicio</a>
        </div>
    </div>
</body>
</html>
