<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar Cobro</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
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
<body class="bg-gray-100 p-8">
    <div class="max-w-xl mx-auto bg-white p-6 rounded shadow">
        <h1 class="text-3xl font-bold mb-6">Registrar Cobro</h1>

        <form action="{{ route('Cobros.store') }}" method="POST" oninput="calcularTotales()" onchange="actualizarCosteYTotales()" class="space-y-4">
            @csrf

            <div>
                <label for="id_cita" class="block font-semibold mb-1">Cita:</label>
                <select name="id_cita" id="id_cita" required class="w-full border rounded px-3 py-2">
                    @foreach($citas as $cita)
                        @php
                            $costeTotal = $cita->servicios->sum('precio');
                            $nombresServicios = $cita->servicios->pluck('nombre')->implode(', ');
                        @endphp
                        <option value="{{ $cita->id }}" data-coste="{{ $costeTotal }}">
                            {{ $cita->cliente->user->nombre ?? '' }} - {{ $nombresServicios }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="coste" class="block font-semibold mb-1">Coste:</label>
                <input type="number" name="coste" id="coste" required class="w-full border rounded px-3 py-2" step="0.01">
            </div>

            <div class="flex space-x-4">
                <div class="flex-1">
                    <label for="descuento_porcentaje" class="block font-semibold mb-1">Descuento %:</label>
                    <input type="number" name="descuento_porcentaje" id="descuento_porcentaje" class="w-full border rounded px-3 py-2" step="0.01">
                </div>
                <div class="flex-1">
                    <label for="descuento_euro" class="block font-semibold mb-1">Descuento €:</label>
                    <input type="number" name="descuento_euro" id="descuento_euro" class="w-full border rounded px-3 py-2" step="0.01">
                </div>
            </div>

            <div>
                <label for="total_final" class="block font-semibold mb-1">Total Final:</label>
                <input type="number" name="total_final" id="total_final" required class="w-full border rounded px-3 py-2" step="0.01">
            </div>

            <div>
                <label for="dinero_cliente" class="block font-semibold mb-1">Dinero del Cliente:</label>
                <input type="number" name="dinero_cliente" id="dinero_cliente" required class="w-full border rounded px-3 py-2" step="0.01">
            </div>

            <div>
                <label for="cambio" class="block font-semibold mb-1">Cambio:</label>
                <input type="number" name="cambio" id="cambio" class="w-full border rounded px-3 py-2" step="0.01">
            </div>

            <div>
                <label for="metodo_pago" class="block font-semibold mb-1">Método de Pago:</label>
                <select name="metodo_pago" required class="w-full border rounded px-3 py-2">
                    <option value="efectivo">Efectivo</option>
                    <option value="tarjeta">Tarjeta</option>
                </select>
            </div>

            <div class="flex justify-between items-center mt-6">
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Registrar</button>
                <a href="{{ route('Cobros.index') }}" class="text-blue-600 hover:underline">Volver</a>
            </div>
        </form>
    </div>
    <script>
        window.onload = actualizarCosteYTotales;
    </script>
</body>
</html>
