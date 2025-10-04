<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Caja del día</title>
    @vite(['resources/js/app.js'])
</head>
<body class="bg-gray-100 p-6">
    <div class="w-full max-w-none mx-auto bg-white shadow-md rounded p-6">
        <h1 class="text-3xl font-bold mb-4">Caja del día: {{ $fecha }}</h1>

        <div class="mb-6">
            <h3 class="text-xl font-semibold mb-2">Totales</h3>
            <ul class="list-disc pl-6 space-y-1">
                <li>Efectivo: €{{ number_format($totalEfectivo, 2) }}</li>
                <li>Tarjeta: €{{ number_format($totalTarjeta, 2) }}</li>
                <li>Bono: €{{ number_format($totalBono, 2) }}</li>
                <li><strong>Total pagado (sum dinero recibido): €{{ number_format($totalPagado, 2) }}</strong></li>
                <li>Total de servicios (sum total_final): €{{ number_format($totalServicios, 2) }}</li>
            </ul>
        </div>

        <div class="mb-6">
            <h3 class="text-xl font-semibold mb-2">Clientes con deuda hoy</h3>
            <table class="w-full table-auto text-sm text-left break-words">
                <thead class="bg-gray-200">
                    <tr>
                        <th class="px-4 py-2">Cliente</th>
                        <th class="px-4 py-2">Deuda</th>
                        <th class="px-4 py-2">Cita / Hora</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($deudas as $d)
                        <tr class="border-t">
                            <td class="px-4 py-2">{{ optional($d->cliente->user)->nombre }} {{ optional($d->cliente->user)->apellidos }}</td>
                            <td class="px-4 py-2">€{{ number_format($d->deuda, 2) }}</td>
                            <td class="px-4 py-2">{{ optional($d->cita)->fecha_hora }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-4 py-2">No hay deudas hoy.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>
            <h3 class="text-xl font-semibold mb-2">Detalle de servicios realizados</h3>
            <table class="w-full table-auto text-sm text-left break-words">
                <thead class="bg-gray-200">
                    <tr>
                        <th class="px-4 py-2">Hora</th>
                        <th class="px-4 py-2">Cliente</th>
                        <th class="px-4 py-2">Servicio(s)</th>
                        <th class="px-4 py-2">Empleado</th>
                        <th class="px-4 py-2">Método de pago</th>
                        <th class="px-4 py-2">Pagado</th>
                        <th class="px-4 py-2">Deuda</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($detalleServicios as $item)
                        <tr class="border-t">
                            <td class="px-4 py-2">{{ $item->created_at->format('H:i') }}</td>
                            <td class="px-4 py-2">{{ optional($item->cliente->user)->nombre }} {{ optional($item->cliente->user)->apellidos }}</td>
                            <td class="px-4 py-2">
                                @if($item->cita && $item->cita->servicios)
                                    {{ $item->cita->servicios->pluck('nombre')->implode(', ') }}
                                @else
                                    -
                                @endif
                            </td>
                            <td class="px-4 py-2">{{ optional($item->empleado->user)->nombre ?? (optional($item->cita->empleado->user)->nombre ?? '-') }}</td>
                            <td class="px-4 py-2">{{ ucfirst($item->metodo_pago) }}</td>
                            <td class="px-4 py-2">€{{ number_format($item->dinero_cliente, 2) }}</td>
                            <td class="px-4 py-2">€{{ number_format($item->deuda ?? 0, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <a href="{{ route('dashboard') }}" class="inline-block mt-6 text-gray-700 hover:underline">Volver al Inicio</a>
    </div>
</body>
</html>
