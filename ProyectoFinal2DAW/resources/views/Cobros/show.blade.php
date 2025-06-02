<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Detalle del Cobro</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-2xl mx-auto bg-white p-8 rounded shadow">
        <h1 class="text-3xl font-bold mb-6 text-center">Detalle del Cobro</h1>

        <div class="space-y-4">
            <div class="flex justify-between border-b pb-2">
                <span class="font-semibold">Cliente:</span>
                <span>{{ $cobro->cita->cliente->user->nombre ?? '-' }}</span>
            </div>
            <div class="flex justify-between border-b pb-2">
                <span class="font-semibold">Empleado:</span>
                <span>{{ $cobro->cita->empleado->user->nombre ?? '-' }}</span>
            </div>
            <div class="flex justify-between border-b pb-2">
                <span class="font-semibold">Servicio:</span>
                <span>
                    @php
                        $servicios = $cobro->cita->servicios->pluck('nombre')->implode(', ') ?? ($cobro->cita->servicio->nombre ?? '-');
                    @endphp
                    {{ $servicios ?: '-' }}
                </span>
            </div>
            <div class="flex justify-between border-b pb-2">
                <span class="font-semibold">Coste:</span>
                <span>{{ $cobro->coste }} €</span>
            </div>
            <div class="flex justify-between border-b pb-2">
                <span class="font-semibold">Descuento %:</span>
                <span>{{ $cobro->descuento_porcentaje ?? 0 }}%</span>
            </div>
            <div class="flex justify-between border-b pb-2">
                <span class="font-semibold">Descuento €:</span>
                <span>{{ $cobro->descuento_euro ?? 0 }} €</span>
            </div>
            <div class="flex justify-between border-b pb-2">
                <span class="font-semibold">Total Final:</span>
                <span>{{ $cobro->total_final }} €</span>
            </div>
            <div class="flex justify-between border-b pb-2">
                <span class="font-semibold">Dinero Cliente:</span>
                <span>{{ $cobro->dinero_cliente }} €</span>
            </div>
            <div class="flex justify-between border-b pb-2">
                <span class="font-semibold">Cambio:</span>
                <span>{{ $cobro->cambio }} €</span>
            </div>
            <div class="flex justify-between">
                <span class="font-semibold">Método de Pago:</span>
                <span>{{ ucfirst($cobro->metodo_pago) }}</span>
            </div>
        </div>

        <div class="mt-8 flex justify-between">
            <a href="{{ route('Cobros.index') }}" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Volver a la lista</a>
            <a href="{{ route('dashboard') }}" class="text-blue-600 hover:underline self-center">Volver al inicio</a>
        </div>
    </div>
</body>
</html>
