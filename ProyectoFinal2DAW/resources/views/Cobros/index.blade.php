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
            <a href="{{ route('Cobros.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Registrar nuevo cobro</a>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full border border-gray-300">
                <thead class="bg-gray-200">
                    <tr>
                        <th class="p-2 border">Cliente</th>
                        <th class="p-2 border">Empleado</th>
                        <th class="p-2 border">Servicio</th>
                        <th class="p-2 border">Coste</th>
                        <th class="p-2 border">Descuento %</th>
                        <th class="p-2 border">Descuento €</th>
                        <th class="p-2 border">Total Final</th>
                        <th class="p-2 border">Dinero Cliente</th>
                        <th class="p-2 border">Cambio</th>
                        <th class="p-2 border">Método Pago</th>
                        <th class="p-2 border">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($cobros as $cobro)
                        <tr class="text-center border-t">
                            <td class="p-2 border">{{ $cobro->cita->cliente->user->nombre ?? '-' }}</td>
                            <td class="p-2 border">{{ $cobro->cita->empleado->user->nombre ?? '-' }}</td>
                            <td class="p-2 border">
                                @php
                                    $servicios = $cobro->cita->servicios->pluck('nombre')->implode(', ');
                                @endphp
                                {{ $servicios ?: '-' }}
                            </td>

                            <td class="p-2 border">{{ $cobro->coste }}</td>
                            <td class="p-2 border">{{ $cobro->descuento_porcentaje ?? 0 }}%</td>
                            <td class="p-2 border">{{ $cobro->descuento_euro ?? 0 }} €</td>
                            <td class="p-2 border">{{ $cobro->total_final }} €</td>
                            <td class="p-2 border">{{ $cobro->dinero_cliente }} €</td>
                            <td class="p-2 border">{{ $cobro->cambio }} €</td>
                            <td class="p-2 border">{{ ucfirst($cobro->metodo_pago) }}</td>
                            <td class="p-2 border space-y-1">
                                <a href="{{ route('Cobros.show', $cobro->id) }}" class="text-blue-600 hover:underline block">Ver</a>
                                <a href="{{ route('Cobros.edit', $cobro->id) }}" class="text-yellow-600 hover:underline block">Editar</a>
                                <form action="{{ route('Cobros.destroy', $cobro->id) }}" method="POST" onsubmit="return confirm('¿Eliminar este cobro?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:underline">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            <a href="{{ route('dashboard') }}" class="text-blue-600 hover:underline">Volver al inicio</a>
        </div>
    </div>
</body>
</html>
