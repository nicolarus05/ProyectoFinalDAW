<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle de Deuda - {{ $cliente->user->nombre }}</title>
    @vite(['resources/js/app.js'])
</head>
<body class="bg-gray-100 p-6">
    <div class="w-full max-w-6xl mx-auto bg-white shadow-md rounded p-6">
        <h1 class="text-3xl font-bold mb-4">Detalle de Deuda</h1>
        
        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                {{ session('success') }}
            </div>
        @endif

        @if(session('info'))
            <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded mb-4">
                {{ session('info') }}
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                {{ session('error') }}
            </div>
        @endif

        <!-- Información del Cliente -->
        <div class="mb-6 p-4 bg-gray-50 rounded">
            <h2 class="text-xl font-semibold mb-2">Información del Cliente</h2>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p><strong>Nombre:</strong> {{ $cliente->user->nombre }} {{ $cliente->user->apellidos }}</p>
                    <p><strong>Teléfono:</strong> {{ $cliente->user->telefono }}</p>
                </div>
                <div>
                    <p><strong>Email:</strong> {{ $cliente->user->email }}</p>
                    <p><strong>Cliente desde:</strong> {{ $cliente->fecha_registro->format('d/m/Y') }}</p>
                </div>
            </div>
        </div>

        <!-- Resumen de Deuda -->
        <div class="mb-6 p-4 bg-red-50 rounded border border-red-200">
            <h2 class="text-xl font-semibold mb-2">Resumen de Deuda</h2>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-sm text-gray-600">Deuda Total Acumulada</p>
                    <p class="text-2xl font-bold text-gray-800">€{{ number_format($deuda->saldo_total, 2) }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Deuda Pendiente</p>
                    <p class="text-3xl font-bold text-red-600">€{{ number_format($deuda->saldo_pendiente, 2) }}</p>
                </div>
            </div>
        </div>

        <!-- Acciones -->
        @if($deuda->tieneDeuda())
        <div class="mb-6">
            <a href="{{ route('deudas.pago.create', $cliente) }}" 
               class="bg-green-600 text-white px-6 py-3 rounded hover:bg-green-700 inline-block">
                Registrar Pago
            </a>
        </div>
        @else
        <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded">
            <p class="text-green-700 font-semibold">✓ Este cliente no tiene deudas pendientes</p>
        </div>
        @endif

        <!-- Historial de Movimientos -->
        <div class="mt-6">
            <h2 class="text-xl font-semibold mb-4">Historial de Movimientos</h2>
            <table class="w-full table-auto text-sm text-left">
                <thead class="bg-gray-200">
                    <tr>
                        <th class="px-4 py-2">Fecha</th>
                        <th class="px-4 py-2">Tipo</th>
                        <th class="px-4 py-2">Monto</th>
                        <th class="px-4 py-2">Método Pago</th>
                        <th class="px-4 py-2">Nota</th>
                        <th class="px-4 py-2">Registrado por</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($movimientos as $movimiento)
                    <tr class="border-t hover:bg-gray-50">
                        <td class="px-4 py-2">{{ $movimiento->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-2">
                            @if($movimiento->tipo === 'cargo')
                                <span class="bg-red-100 text-red-800 px-2 py-1 rounded text-xs font-semibold">CARGO</span>
                            @else
                                <span class="bg-green-100 text-green-800 px-2 py-1 rounded text-xs font-semibold">PAGO</span>
                            @endif
                        </td>
                        <td class="px-4 py-2">
                            @if($movimiento->tipo === 'cargo')
                                <span class="text-red-600 font-semibold">+€{{ number_format($movimiento->monto, 2) }}</span>
                            @else
                                <span class="text-green-600 font-semibold">-€{{ number_format($movimiento->monto, 2) }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-2">
                            {{ $movimiento->metodo_pago ? ucfirst($movimiento->metodo_pago) : '-' }}
                        </td>
                        <td class="px-4 py-2">{{ $movimiento->nota ?? '-' }}</td>
                        <td class="px-4 py-2">
                            {{ $movimiento->usuarioRegistro->nombre ?? '-' }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                            No hay movimientos registrados
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>

            <!-- Paginación -->
            <div class="mt-4">
                {{ $movimientos->links() }}
            </div>
        </div>

        <div class="mt-6">
            <a href="{{ route('deudas.index') }}" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700">
                Volver a Deudas
            </a>
        </div>
    </div>
</body>
</html>
