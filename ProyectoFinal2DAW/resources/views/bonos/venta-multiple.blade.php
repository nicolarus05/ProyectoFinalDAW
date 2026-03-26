<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Venta Múltiple de Bonos</title>
    {!! vite_asset(['resources/css/app.css', 'resources/js/app.js']) !!}
</head>
<body class="bg-gray-100 p-6">
    <div class="max-w-5xl mx-auto bg-white shadow-md rounded p-6">
        <h1 class="text-3xl font-bold mb-6">🛒 Venta Múltiple de Bonos</h1>

        @if ($errors->any())
            <div class="bg-red-100 text-red-700 p-4 mb-4 rounded">
                <strong>Errores:</strong>
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('bonos.procesarVentaMultiple') }}" method="POST" id="formVentaMultiple">
            @csrf

            <!-- Cliente y Empleado -->
            <div class="grid grid-cols-2 gap-6 mb-6">
                <div>
                    <label for="cliente_id" class="block font-semibold mb-2">Seleccionar Cliente</label>
                    <select name="cliente_id" id="cliente_id" required class="w-full border rounded px-3 py-2">
                        <option value="">Seleccione un cliente</option>
                        @foreach($clientes as $cliente)
                            <option value="{{ $cliente->id }}" {{ old('cliente_id') == $cliente->id ? 'selected' : '' }}>
                                {{ $cliente->user->nombre }} {{ $cliente->user->apellidos }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="id_empleado" class="block font-semibold mb-2">Empleado que Realiza la Venta</label>
                    <select name="id_empleado" id="id_empleado" required class="w-full border rounded px-3 py-2">
                        <option value="">Seleccione un empleado</option>
                        @foreach($empleados as $empleado)
                            <option value="{{ $empleado->id }}" {{ old('id_empleado') == $empleado->id ? 'selected' : '' }}>
                                {{ $empleado->user->nombre }} {{ $empleado->user->apellidos }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Selección de Bonos -->
            <div class="mb-6">
                <h3 class="font-bold text-lg mb-3">📋 Seleccionar Bonos a Vender</h3>
                <p class="text-sm text-gray-500 mb-4">Marca los bonos que deseas vender. No se pueden seleccionar bonos que compartan servicios.</p>

                @if($plantillas->count() > 0)
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach($plantillas as $plantilla)
                            <label class="border rounded-lg p-4 cursor-pointer transition hover:shadow-md plantilla-card" 
                                   id="card-{{ $plantilla->id }}"
                                   data-plantilla-id="{{ $plantilla->id }}"
                                   data-precio="{{ $plantilla->precio }}">
                                <div class="flex items-start gap-3">
                                    <input type="checkbox" name="plantillas[]" value="{{ $plantilla->id }}"
                                           class="mt-1 plantilla-checkbox"
                                           data-plantilla-id="{{ $plantilla->id }}"
                                           {{ is_array(old('plantillas')) && in_array($plantilla->id, old('plantillas')) ? 'checked' : '' }}
                                           onchange="validarSeleccion()">
                                    <div class="flex-1">
                                        <h4 class="font-bold text-lg">{{ $plantilla->nombre }}</h4>
                                        @if($plantilla->descripcion)
                                            <p class="text-gray-600 text-sm mb-2">{{ $plantilla->descripcion }}</p>
                                        @endif
                                        <p class="text-green-600 font-bold text-xl mb-2">€{{ number_format($plantilla->precio, 2) }}</p>
                                        <p class="text-sm text-gray-500 mb-1">
                                            @if($plantilla->duracion_dias)
                                                Válido por {{ $plantilla->duracion_dias }} días
                                            @else
                                                <span class="text-purple-600">✨ Sin límite</span>
                                            @endif
                                        </p>
                                        <div class="mt-2">
                                            <p class="text-sm font-semibold">Servicios:</p>
                                            <ul class="list-disc pl-5 text-sm">
                                                @foreach($plantilla->servicios as $servicio)
                                                    <li>
                                                        {{ $servicio->nombre }}
                                                        @if($servicio->tipo === 'peluqueria')
                                                            <span class="text-blue-600">💇</span>
                                                        @else
                                                            <span class="text-pink-600">💅</span>
                                                        @endif
                                                        <span class="font-semibold">(x{{ $servicio->pivot->cantidad }})</span>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </label>
                        @endforeach
                    </div>
                @else
                    <p class="text-gray-500 text-center py-4">No hay bonos disponibles.</p>
                @endif
            </div>

            <!-- Aviso de conflicto de servicios -->
            <div id="avisoConflicto" class="bg-red-100 text-red-700 p-4 mb-4 rounded" style="display: none;">
                <strong>⚠️ Conflicto:</strong> <span id="avisoConflictoTexto"></span>
            </div>

            <!-- Resumen de selección -->
            <div id="resumenSeleccion" class="bg-blue-50 border border-blue-200 rounded p-4 mb-6" style="display: none;">
                <h3 class="font-bold text-lg mb-2">📊 Resumen de la Venta</h3>
                <div id="resumenBonos" class="mb-2"></div>
                <div class="border-t pt-2 mt-2">
                    <p class="text-2xl font-bold text-green-600">Total: €<span id="precioTotalMostrado">0.00</span></p>
                </div>
            </div>

            <!-- Método de Pago -->
            <div id="seccionPago" class="mb-6 bg-green-50 border border-green-200 rounded p-4" style="display: none;">
                <h3 class="font-bold text-lg mb-3">💳 Método de Pago</h3>

                <div class="mb-4">
                    <label class="block font-semibold mb-2">Seleccione el método de pago:</label>
                    <div class="flex gap-4">
                        <label class="flex items-center cursor-pointer">
                            <input type="radio" name="metodo_pago" value="efectivo"
                                   {{ old('metodo_pago') == 'efectivo' ? 'checked' : '' }}
                                   class="mr-2" required onchange="toggleMetodoPago()">
                            <span class="font-semibold">💵 Efectivo</span>
                        </label>
                        <label class="flex items-center cursor-pointer">
                            <input type="radio" name="metodo_pago" value="tarjeta"
                                   {{ old('metodo_pago') == 'tarjeta' ? 'checked' : '' }}
                                   class="mr-2" required onchange="toggleMetodoPago()">
                            <span class="font-semibold">💳 Tarjeta</span>
                        </label>
                        <label class="flex items-center cursor-pointer">
                            <input type="radio" name="metodo_pago" value="mixto"
                                   {{ old('metodo_pago') == 'mixto' ? 'checked' : '' }}
                                   class="mr-2" required onchange="toggleMetodoPago()">
                            <span class="font-semibold">💳💵 Mixto</span>
                        </label>
                    </div>
                </div>

                <!-- Campos para pago en efectivo -->
                <div id="dineroClienteDiv" style="display: none;">
                    <label for="dinero_cliente" class="block font-semibold mb-2">💰 Dinero del Cliente:</label>
                    <input type="number" name="dinero_cliente" id="dinero_cliente"
                           value="{{ old('dinero_cliente') }}"
                           step="0.01" min="0"
                           class="w-full border rounded px-3 py-2"
                           placeholder="Ingrese el dinero que entrega el cliente"
                           oninput="calcularCambio()">
                </div>

                <!-- Campos para pago mixto -->
                <div id="pagoMixtoDiv" style="display: none;">
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label for="pago_efectivo" class="block font-semibold mb-2">💵 Pago en Efectivo:</label>
                            <input type="number" name="pago_efectivo" id="pago_efectivo"
                                   value="{{ old('pago_efectivo') }}"
                                   step="0.01" min="0"
                                   class="w-full border rounded px-3 py-2"
                                   placeholder="€0.00"
                                   oninput="calcularTotalMixto()">
                        </div>
                        <div>
                            <label for="pago_tarjeta" class="block font-semibold mb-2">💳 Pago con Tarjeta:</label>
                            <input type="number" name="pago_tarjeta" id="pago_tarjeta"
                                   value="{{ old('pago_tarjeta') }}"
                                   step="0.01" min="0"
                                   class="w-full border rounded px-3 py-2"
                                   placeholder="€0.00"
                                   oninput="calcularTotalMixto()">
                        </div>
                    </div>
                    <div id="totalMixtoDiv" class="bg-white border-2 border-blue-500 rounded p-3">
                        <div class="flex justify-between items-center">
                            <div>
                                <p class="text-sm text-gray-600">Total pagado:</p>
                                <p id="totalMixtoMostrado" class="text-xl font-bold text-blue-600">€0.00</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Precio total:</p>
                                <p id="precioTotalMixto" class="text-xl font-bold text-gray-700">€0.00</p>
                            </div>
                            <div id="diferenciaMixtoDiv">
                                <p class="text-sm text-gray-600">Diferencia:</p>
                                <p id="diferenciaMixtoMostrado" class="text-xl font-bold text-red-600">€0.00</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="cambioDiv" class="mt-4" style="display: none;">
                    <div class="bg-white border-2 border-green-500 rounded p-3">
                        <p class="text-sm text-gray-600">Cambio a devolver:</p>
                        <p id="cambioMostrado" class="text-2xl font-bold text-green-600">€0.00</p>
                    </div>
                </div>
            </div>

            <div class="flex justify-between items-center">
                <a href="{{ route('bonos.index') }}" class="text-blue-600 hover:underline">← Cancelar</a>
                <button type="submit" id="btnConfirmar" class="bg-green-600 text-white px-6 py-2 rounded hover:bg-green-700 font-bold disabled:opacity-50 disabled:cursor-not-allowed" 
                        style="background-color: #16a34a; color: white; padding: 0.5rem 1.5rem; border-radius: 0.375rem; font-weight: bold; border: none; cursor: pointer;"
                        disabled>
                    ✅ Confirmar Venta Múltiple
                </button>
            </div>
        </form>

        <script>
            // Mapa de servicios por plantilla (para validación de conflictos)
            const plantillasServicios = @json($plantillasServicios);

            // Mapa de precios por plantilla
            const plantillasPrecios = {};
            document.querySelectorAll('.plantilla-card').forEach(card => {
                plantillasPrecios[card.dataset.plantillaId] = parseFloat(card.dataset.precio);
            });

            let precioTotal = 0;

            function validarSeleccion() {
                const checkboxes = document.querySelectorAll('.plantilla-checkbox');
                const seleccionados = [];
                const avisoDiv = document.getElementById('avisoConflicto');
                const avisoTexto = document.getElementById('avisoConflictoTexto');

                // Recoger seleccionados
                checkboxes.forEach(cb => {
                    if (cb.checked) {
                        seleccionados.push(parseInt(cb.dataset.plantillaId));
                    }
                });

                // Verificar conflictos de servicios entre seleccionados
                let conflicto = false;
                let serviciosVistos = {};

                for (const plantillaId of seleccionados) {
                    const servicios = plantillasServicios[plantillaId] || [];
                    for (const servicioId of servicios) {
                        if (serviciosVistos[servicioId] !== undefined) {
                            conflicto = true;
                            avisoTexto.textContent = 'Los bonos seleccionados comparten servicios. Desmarca uno de los bonos en conflicto.';
                            break;
                        }
                        serviciosVistos[servicioId] = plantillaId;
                    }
                    if (conflicto) break;
                }

                // Mostrar/ocultar aviso
                avisoDiv.style.display = conflicto ? 'block' : 'none';

                // Actualizar estilos de tarjetas
                document.querySelectorAll('.plantilla-card').forEach(card => {
                    const id = parseInt(card.dataset.plantillaId);
                    const cb = card.querySelector('.plantilla-checkbox');
                    if (cb.checked) {
                        card.style.borderColor = '#16a34a';
                        card.style.backgroundColor = '#f0fdf4';
                    } else {
                        card.style.borderColor = '#e5e7eb';
                        card.style.backgroundColor = '';
                    }
                });

                // Calcular precio total
                precioTotal = 0;
                const resumenBonos = document.getElementById('resumenBonos');
                resumenBonos.innerHTML = '';

                seleccionados.forEach(id => {
                    const precio = plantillasPrecios[id] || 0;
                    precioTotal += precio;
                    const card = document.getElementById('card-' + id);
                    const nombre = card.querySelector('h4').textContent;
                    resumenBonos.innerHTML += '<p class="text-sm">• ' + nombre + ' — <span class="font-semibold">€' + precio.toFixed(2) + '</span></p>';
                });

                document.getElementById('precioTotalMostrado').textContent = precioTotal.toFixed(2);
                document.getElementById('precioTotalMixto').textContent = '€' + precioTotal.toFixed(2);

                // Mostrar/ocultar secciones
                const haySeleccion = seleccionados.length > 0 && !conflicto;
                document.getElementById('resumenSeleccion').style.display = haySeleccion ? 'block' : 'none';
                document.getElementById('seccionPago').style.display = haySeleccion ? 'block' : 'none';
                document.getElementById('btnConfirmar').disabled = !haySeleccion;

                // Recalcular pago si hay método seleccionado
                const metodoPago = document.querySelector('input[name="metodo_pago"]:checked');
                if (metodoPago) {
                    if (metodoPago.value === 'efectivo') calcularCambio();
                    if (metodoPago.value === 'mixto') calcularTotalMixto();
                }
            }

            function toggleMetodoPago() {
                const metodoPago = document.querySelector('input[name="metodo_pago"]:checked')?.value;
                const dineroDiv = document.getElementById('dineroClienteDiv');
                const cambioDiv = document.getElementById('cambioDiv');
                const dineroInput = document.getElementById('dinero_cliente');
                const pagoMixtoDiv = document.getElementById('pagoMixtoDiv');
                const pagoEfectivoInput = document.getElementById('pago_efectivo');
                const pagoTarjetaInput = document.getElementById('pago_tarjeta');

                dineroDiv.style.display = 'none';
                cambioDiv.style.display = 'none';
                pagoMixtoDiv.style.display = 'none';
                dineroInput.required = false;
                pagoEfectivoInput.required = false;
                pagoTarjetaInput.required = false;

                if (metodoPago === 'efectivo') {
                    dineroDiv.style.display = 'block';
                    dineroInput.required = true;
                } else if (metodoPago === 'mixto') {
                    pagoMixtoDiv.style.display = 'block';
                    pagoEfectivoInput.required = true;
                    pagoTarjetaInput.required = true;
                    calcularTotalMixto();
                }
            }

            function calcularCambio() {
                const dineroCliente = parseFloat(document.getElementById('dinero_cliente').value) || 0;
                const cambioDiv = document.getElementById('cambioDiv');
                const cambioMostrado = document.getElementById('cambioMostrado');

                if (dineroCliente >= precioTotal) {
                    const cambio = dineroCliente - precioTotal;
                    cambioMostrado.textContent = '€' + cambio.toFixed(2);
                    cambioDiv.style.display = 'block';
                } else {
                    cambioDiv.style.display = 'none';
                }
            }

            function calcularTotalMixto() {
                const pagoEfectivo = parseFloat(document.getElementById('pago_efectivo').value) || 0;
                const pagoTarjeta = parseFloat(document.getElementById('pago_tarjeta').value) || 0;
                const totalPagado = pagoEfectivo + pagoTarjeta;
                const diferencia = precioTotal - totalPagado;

                document.getElementById('totalMixtoMostrado').textContent = '€' + totalPagado.toFixed(2);

                const diferenciaEl = document.getElementById('diferenciaMixtoMostrado');
                if (diferencia > 0) {
                    diferenciaEl.textContent = '-€' + diferencia.toFixed(2);
                    diferenciaEl.className = 'text-xl font-bold text-red-600';
                } else if (diferencia < 0) {
                    diferenciaEl.textContent = '+€' + Math.abs(diferencia).toFixed(2);
                    diferenciaEl.className = 'text-xl font-bold text-orange-600';
                } else {
                    diferenciaEl.textContent = '€0.00 ✓';
                    diferenciaEl.className = 'text-xl font-bold text-green-600';
                }
            }

            // Inicializar al cargar
            document.addEventListener('DOMContentLoaded', function() {
                validarSeleccion();
                const metodoPagoChecked = document.querySelector('input[name="metodo_pago"]:checked');
                if (metodoPagoChecked) {
                    toggleMetodoPago();
                }
            });
        </script>
    </div>
</body>
</html>
