<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Cobro</title>
    <link rel="stylesheet" href="{{ asset('css/styles.css') }}">
</head>
<body>
    <h1>Editar Cobro</h1>

    <form action="{{ route('Cobros.update', $cobro->id) }}" method="POST">
        @csrf
        @method('PUT')

        <p><strong>Cliente:</strong> {{ $cobro->cita->cliente->usuario->nombre ?? '-' }}</p>
        <p><strong>Servicio:</strong> {{ $cobro->cita->servicio->nombre ?? '-' }}</p>
        <p><strong>Coste:</strong> {{ $cobro->coste }} €</p>
        <p><strong>Total Final:</strong> {{ $cobro->total_final }} €</p>

        <label for="metodo_pago">Método de Pago:</label>
        <select name="metodo_pago" required>
            <option value="efectivo" {{ $cobro->metodo_pago === 'efectivo' ? 'selected' : '' }}>Efectivo</option>
            <option value="tarjeta" {{ $cobro->metodo_pago === 'tarjeta' ? 'selected' : '' }}>Tarjeta</option>
        </select>

        <label for="dinero_cliente">Dinero del Cliente:</label>
        <input type="number" name="dinero_cliente" value="{{ $cobro->dinero_cliente }}" step="0.01" required>

        <label for="cambio">Cambio:</label>
        <input type="number" name="cambio" value="{{ $cobro->cambio }}" step="0.01" readonly>

        <button type="submit">Actualizar</button>
    </form>

    <a href="{{ route('Cobros.index') }}">Volver</a>
</body>
</html>
