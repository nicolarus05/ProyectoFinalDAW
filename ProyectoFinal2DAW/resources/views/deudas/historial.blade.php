<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial Completo - {{ $cliente->user->nombre }}</title>
    {!! vite_asset(['resources/css/app.css', 'resources/js/app.js']) !!}
</head>
<body class="bg-gray-100 p-6">
    <div class="w-full max-w-6xl mx-auto bg-white shadow-md rounded p-6">
        <h1 class="text-3xl font-bold mb-4">Historial Completo de Deuda</h1>

        <!-- Información del Cliente -->
        <div class="mb-6 p-4 bg-gray-50 rounded">
            <h2 class="text-xl font-semibold mb-2">Cliente</h2>
            <p><strong>Nombre:</strong> {{ $cliente->user->nombre }} {{ $cliente->user->apellidos }}</p>
            <p><strong>Deuda Actual:</strong> 
                <span class="text-xl font-bold {{ $deuda->saldo_pendiente > 0 ? 'text-red-600' : 'text-green-600' }}">
                    €{{ number_format($deuda->saldo_pendiente, 2) }}
                </span>
            </p>
        </div>

        <!-- Tabla de Movimientos -->
        <div class="mt-6">
            <table class="w-full table-auto text-sm text-left">
                <thead class="bg-gray-200">
                    <tr>
                        <th class="px-4 py-2">#</th>
                        <th class="px-4 py-2">Fecha</th>
                        <th class="px-4 py-2">Tipo</th>
                        <th class="px-4 py-2">Monto</th>
                        <th class="px-4 py-2">Método</th>
                        <th class="px-4 py-2">Nota</th>
                        <th class="px-4 py-2">Usuario</th>
                        <th class="px-4 py-2">Saldo Resultante</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $saldoActual = $deuda->saldo_total;
                    @endphp
                    @forelse ($movimientos as $index => $movimiento)
                    @php
                        if ($movimiento->tipo === 'cargo') {
                            $saldoAnterior = $saldoActual - $movimiento->monto;
                        } else {
                            $saldoAnterior = $saldoActual + $movimiento->monto;
                        }
                    @endphp
                    <tr class="border-t hover:bg-gray-50">
                        <td class="px-4 py-2">{{ $movimientos->total() - $index }}</td>
                        <td class="px-4 py-2">
                            {{ $movimiento->created_at->format('d/m/Y') }}<br>
                            <span class="text-xs text-gray-500">{{ $movimiento->created_at->format('H:i') }}</span>
                        </td>
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
                        <td class="px-4 py-2 max-w-xs truncate" title="{{ $movimiento->nota }}">
                            {{ $movimiento->nota ?? '-' }}
                        </td>
                        <td class="px-4 py-2">
                            {{ $movimiento->usuarioRegistro->nombre ?? '-' }}
                        </td>
                        <td class="px-4 py-2 font-semibold">
                            €{{ number_format($saldoActual, 2) }}
                        </td>
                    </tr>
                    @php
                        $saldoActual = $saldoAnterior;
                    @endphp
                    @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-gray-500">
                            No hay movimientos registrados
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-6 flex gap-4">
            <a href="{{ route('deudas.show', $cliente) }}" 
               class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                Volver al Detalle
            </a>
            <a href="{{ route('deudas.index') }}" 
               class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700">
                Volver a Deudas
            </a>
        </div>
    </div>
</body>
</html>
