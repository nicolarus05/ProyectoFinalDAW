<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Crear Cobro</title>
    <link rel="stylesheet" href="{{ asset('css/styles.css') }}">
</head>
<body>
    <h1>Registrar un nuevo cobro</h1>

    <form method="POST" action="{{ route('Cobros.store') }}">
        @csrf

        <label for="id_cita">Cita:</label>
        <select name="id_cita" required>
            @foreach($citas as $cita)
                <option value="{{ $cita->id }}">
                    {{ $cita->cliente->usuario->nombre ?? '' }} - {{ $cita->servicio->nombre ?? '' }}
                </option>
            @endforeach
        </select>

        <label for="coste">Coste:</label>
        <input type="number" name="coste" step="0.01" required>

        <label for="descuento_porcentaje">Descuento (%)</label>
        <input type="number" name="descuento_porcentaje" step="0.01" min="0" max="100">

        <label for="descuento_euros">Descuento (€)</label>
        <input type="number" name="descuento_euros" step="0.01" min="0">

        <label for="total_final">Total Final:</label>
        <input type="number" name="total_final" step="0.01" required>

        <label for="metodo_pago">Método de Pago:</label>
        <select name="metodo_pago" required>
            <option value="efectivo">Efectivo</option>
            <option value="tarjeta">Tarjeta</option>
        </select>

        <label for="cambio">Cambio:</label>
        <input type="number" name="cambio" step="0.01">

        <button type="submit">Guardar</button>
    </form>

    <a href="{{ route('Cobros.index') }}">Volver a la lista</a>
</body>
</html>
