<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Deudas</title>
    @vite(['resources/js/app.js'])
</head>
<body class="bg-gray-100 p-6">
    <div class="w-full max-w-none mx-auto bg-white shadow-md rounded p-6">
        <h1 class="text-3xl font-bold mb-4">Gestión de Deudas</h1>
        
        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                {{ session('success') }}
            </div>
        @endif

        <div class="mb-4 p-4 bg-blue-50 rounded">
            <h2 class="text-xl font-semibold">Resumen Total</h2>
            <p class="text-2xl font-bold text-red-600">€{{ number_format($totalDeuda, 2) }}</p>
            <p class="text-sm text-gray-600">Deuda total pendiente de todos los clientes</p>
        </div>

        <div class="mt-4">
            <table class="w-full table-auto text-sm text-left">
                <thead class="bg-gray-200">
                    <tr>
                        <th class="px-4 py-2">Cliente</th>
                        <th class="px-4 py-2">Teléfono</th>
                        <th class="px-4 py-2">Email</th>
                        <th class="px-4 py-2">Deuda Pendiente</th>
                        <th class="px-4 py-2">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($clientes as $cliente)
                    <tr class="border-t hover:bg-gray-50">
                        <td class="px-4 py-2">
                            <span class="font-semibold">{{ $cliente->user->nombre ?? '-' }} {{ $cliente->user->apellidos ?? '-' }}</span>
                        </td>
                        <td class="px-4 py-2">{{ $cliente->user->telefono ?? '-' }}</td>
                        <td class="px-4 py-2">{{ $cliente->user->email ?? '-' }}</td>
                        <td class="px-4 py-2">
                            <span class="font-bold text-red-600">€{{ number_format($cliente->deuda->saldo_pendiente, 2) }}</span>
                        </td>
                        <td class="px-4 py-2">
                            <a href="{{ route('deudas.show', $cliente) }}" 
                               class="bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700 mr-2">
                                Ver Detalle
                            </a>
                            <a href="{{ route('deudas.pago.create', $cliente) }}" 
                               class="bg-green-600 text-white px-3 py-1 rounded hover:bg-green-700">
                                Registrar Pago
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                            No hay clientes con deudas pendientes
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            <a href="{{ route('dashboard') }}" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700">
                Volver al Dashboard
            </a>
        </div>
    </div>
</body>
</html>
