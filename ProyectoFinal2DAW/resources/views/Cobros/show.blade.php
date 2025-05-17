<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Detalle del Cobro</title>
    <link rel="stylesheet" href="{{ asset('css/styles.css') }}">
</head>
<body>
    <h1>Detalle del Cobro</h1>

    <p><strong>Cliente:</strong> {{ $cobro->cita->cliente->usuario->nombre ?? '-' }}</p>
    <p><strong>Empleado:</strong> {{ $cobro->cita->empleado->usuario->nombre ?? '-' }}</p>
    <p><strong>Servicio:</strong> {{ $cobro->cita->servicio->nombre ?? '-' }}</p>
    <p><strong>Coste:</strong> {{ $cobro->coste }}</p>
    <p><strong>Descuento (%):</strong> {{ $cobro->descuento_porcentaje ?? 0 }}</p>
    <p><strong>Descuento (€):</strong> {{ $cobro->descuento_euro ?? 0 }}</p>
    <p><strong>Total Final:</strong> {{ $cobro->total_final }}</p>
    <p><strong>Método de Pago:</strong> {{ ucfirst($cobro->metodo_pago) }}</p>
    <p><strong>Cambio:</strong> {{ $cobro->cambio ?? '-' }}</p>

    <a href="{{ route('Cobros.index') }}">Volver a la lista</a>
</body>
</html>
