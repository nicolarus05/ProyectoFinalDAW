<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar Cobro</title>
    <link rel="stylesheet" href="{{ asset('css/styles.css') }}">
    <script>
        function actualizarCosteYTotales() {
            const select = document.getElementById('id_cita');
            const selectedOption = select.options[select.selectedIndex];
            const costeServicio = parseFloat(selectedOption.getAttribute('data-coste')) || 0;

            document.getElementById('coste').value = costeServicio.toFixed(2);
            calcularTotales();
        }

        function calcularTotales() {
            const coste = parseFloat(document.getElementById('coste').value) || 0;
            const descPor = parseFloat(document.getElementById('descuento_porcentaje').value) || 0;
            const descEur = parseFloat(document.getElementById('descuento_euro').value) || 0;
            const dineroCliente = parseFloat(document.getElementById('dinero_cliente').value) || 0;

            const descuentoTotal = (coste * (descPor / 100)) + descEur;
            const totalFinal = Math.max(coste - descuentoTotal, 0);
            const cambio = Math.max(dineroCliente - totalFinal, 0);

            document.getElementById('total_final').value = totalFinal.toFixed(2);
            document.getElementById('cambio').value = cambio.toFixed(2);
        }
    </script>
</head>
<body>
    <h1>Registrar Cobro</h1>

    <form action="{{ route('Cobros.store') }}" method="POST" oninput="calcularTotales()" onchange="actualizarCosteYTotales()">
        @csrf

        <label for="id_cita">Cita:</label>
        <select name="id_cita" id="id_cita" required>
            @foreach($citas as $cita)
                // Muestro el nombre del cliente, el nombre del servicio y el precio de la cita seleccionada
                
                <option value="{{ $cita->id }}" data-coste="{{ $cita->servicio->precio ?? 0 }}">
                    {{ $cita->cliente->usuario->nombre ?? '' }} - {{ $cita->servicio->nombre ?? '' }}
                </option>
            @endforeach
        </select>

        <label for="coste">Coste:</label>
        <input type="number" name="coste" id="coste" step="0.01" readonly>

        <label for="descuento_porcentaje">Descuento %:</label>
        <input type="number" name="descuento_porcentaje" id="descuento_porcentaje" step="0.01">

        <label for="descuento_euro">Descuento €:</label>
        <input type="number" name="descuento_euro" id="descuento_euro" step="0.01">

        <label for="total_final">Total Final:</label>
        <input type="number" name="total_final" id="total_final" step="0.01" readonly>

        <label for="dinero_cliente">Dinero del Cliente:</label>
        <input type="number" name="dinero_cliente" id="dinero_cliente" step="0.01" required>

        <label for="cambio">Cambio:</label>
        <input type="number" name="cambio" id="cambio" step="0.01" readonly>

        <label for="metodo_pago">Método de Pago:</label>
        <select name="metodo_pago" required>
            <option value="efectivo">Efectivo</option>
            <option value="tarjeta">Tarjeta</option>
        </select>

        <button type="submit">Registrar</button>
    </form>

    <a href="{{ route('Cobros.index') }}">Volver</a>

    <script>
        window.onload = actualizarCosteYTotales;
    </script>
</body>
</html>
