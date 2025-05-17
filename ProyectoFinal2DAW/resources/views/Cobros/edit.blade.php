<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Cobro</title>
    <link rel="stylesheet" href="{{ asset('css/styles.css') }}">
</head>
<body>
    <h1>Editar Cobro</h1>

    <form method="POST" action="{{ route('Cobros.update', $cobro->id) }}">
        @csrf
        @method('PUT')

        <label>Cita (no editable):</label>
        <input type="text" disabled value="{{ $cobro->cita->cliente->usuario->nombre ?? '' }} - {{ $cobro->cita->servicio->nombre ?? '' }}">

        <label for="metodo_pago">Método de Pago:</label>
        <select name="metodo_pago" required>
            <option value="efectivo" {{ $cobro->metodo_pago === 'efectivo' ? 'selected' : '' }}>Efectivo</option>
            <option value="tarjeta" {{ $cobro->metodo_pago === 'tarjeta' ? 'selected' : '' }}>Tarjeta</option>
        </select>

        <label for="cambio">Cambio:</label>
        <input type="number" name="cambio" step="0.01" value="{{ $cobro->cambio }}">

        <button type="submit">Actualizar</button>
    </form>

    <a href="{{ route('Cobros.index') }}">Volver a la lista</a>
</body>
</html>
