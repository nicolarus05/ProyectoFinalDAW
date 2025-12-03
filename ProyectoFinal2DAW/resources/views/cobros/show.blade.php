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
                <span>
                    @php
                        $nombreCliente = '-';
                        if ($cobro->cita && $cobro->cita->cliente && $cobro->cita->cliente->user) {
                            $nombreCliente = $cobro->cita->cliente->user->nombre . ' ' . $cobro->cita->cliente->user->apellidos;
                        } elseif ($cobro->cliente && $cobro->cliente->user) {
                            $nombreCliente = $cobro->cliente->user->nombre . ' ' . $cobro->cliente->user->apellidos;
                        } elseif ($cobro->citasAgrupadas && $cobro->citasAgrupadas->count() > 0) {
                            $primeraCita = $cobro->citasAgrupadas->first();
                            if ($primeraCita && $primeraCita->cliente && $primeraCita->cliente->user) {
                                $nombreCliente = $primeraCita->cliente->user->nombre . ' ' . $primeraCita->cliente->user->apellidos;
                            }
                        }
                    @endphp
                    {{ $nombreCliente }}
                </span>
            </div>
            <div class="flex justify-between border-b pb-2">
                <span class="font-semibold">Empleado:</span>
                <span>
                    @php
                        $nombreEmpleado = '-';
                        if ($cobro->cita && $cobro->cita->empleado && $cobro->cita->empleado->user) {
                            $nombreEmpleado = $cobro->cita->empleado->user->nombre . ' ' . $cobro->cita->empleado->user->apellidos;
                        } elseif ($cobro->empleado && $cobro->empleado->user) {
                            $nombreEmpleado = $cobro->empleado->user->nombre . ' ' . $cobro->empleado->user->apellidos;
                        } elseif ($cobro->citasAgrupadas && $cobro->citasAgrupadas->count() > 0) {
                            $primeraCita = $cobro->citasAgrupadas->first();
                            if ($primeraCita && $primeraCita->empleado && $primeraCita->empleado->user) {
                                $nombreEmpleado = $primeraCita->empleado->user->nombre . ' ' . $primeraCita->empleado->user->apellidos;
                            }
                        }
                    @endphp
                    {{ $nombreEmpleado }}
                </span>
            </div>
            <div class="flex justify-between border-b pb-2">
                <span class="font-semibold">Servicio:</span>
                <span>
                    @php
                        $serviciosNombres = [];
                        $yaContados = false;
                        
                        // PRIORIDAD 1: Servicios de cita individual
                        if ($cobro->cita && $cobro->cita->servicios && $cobro->cita->servicios->count() > 0) {
                            $serviciosNombres = $cobro->cita->servicios->pluck('nombre')->toArray();
                            $yaContados = true;
                        }
                        
                        // PRIORIDAD 2: Servicios de citas agrupadas (solo si no tiene cita individual)
                        if (!$yaContados && $cobro->citasAgrupadas && $cobro->citasAgrupadas->count() > 0) {
                            foreach ($cobro->citasAgrupadas as $citaGrupo) {
                                if ($citaGrupo->servicios) {
                                    $serviciosNombres = array_merge($serviciosNombres, $citaGrupo->servicios->pluck('nombre')->toArray());
                                }
                            }
                            $yaContados = true;
                        }
                        
                        // PRIORIDAD 3: Servicios directos (solo si no tiene citas)
                        if (!$yaContados && $cobro->servicios && $cobro->servicios->count() > 0) {
                            $serviciosNombres = $cobro->servicios->pluck('nombre')->toArray();
                        }
                        
                        $servicios = !empty($serviciosNombres) ? implode(', ', $serviciosNombres) : '-';
                    @endphp
                    {{ $servicios }}
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
            <a href="{{ route('cobros.index') }}" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Volver a la lista</a>
            <a href="{{ route('dashboard') }}" class="text-blue-600 hover:underline self-center">Volver al inicio</a>
        </div>
    </div>
</body>
</html>
