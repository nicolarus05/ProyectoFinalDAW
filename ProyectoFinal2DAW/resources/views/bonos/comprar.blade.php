<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vender Bono</title>
    {!! vite_asset(['resources/css/app.css', 'resources/js/app.js']) !!}
    <style>
        :root { --sidebar-w: 210px; }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', 'Segoe UI', sans-serif; background: #f3f4f8; }
        .main-wrapper { display: flex; min-height: 100vh; }
        .sidebar { position:fixed; top:0; left:0; width:var(--sidebar-w); height:100vh; background:#1e1a4b; display:flex; flex-direction:column; z-index:100; overflow-y:auto; }
        .sidebar-logo { padding:18px 16px 14px; border-bottom:1px solid rgba(255,255,255,.1); }
        .logo-icon { width:36px; height:36px; background:linear-gradient(135deg,#f472b6,#a855f7); border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:18px; }
        .logo-text { font-weight:700; font-size:14px; color:#fff; }
        .logo-sub { font-size:10px; color:rgba(255,255,255,.6); }
        .sidebar-nav { flex:1; padding:10px 0; }
        .nav-item { display:flex; align-items:center; gap:9px; padding:9px 16px; color:rgba(255,255,255,.75); text-decoration:none; font-size:13px; font-weight:500; transition:.15s; border-left:3px solid transparent; }
        .nav-item:hover { background:rgba(255,255,255,.08); color:#fff; }
        .nav-item.active { background:linear-gradient(135deg,#f472b6,#a855f7); color:#fff; border-left-color:transparent; }
        .nav-icon { font-size:15px; min-width:18px; }
        .sidebar-help { margin:10px 12px; background:rgba(255,255,255,.08); border-radius:10px; padding:12px; color:rgba(255,255,255,.8); }
        .sidebar-footer { padding:12px 16px; font-size:10px; color:rgba(255,255,255,.4); border-top:1px solid rgba(255,255,255,.08); }
        .content { margin-left:var(--sidebar-w); flex:1; display:flex; flex-direction:column; }
        .topbar { background:#fff; padding:14px 24px; display:flex; align-items:center; justify-content:space-between; box-shadow:0 1px 4px rgba(0,0,0,.08); position:sticky; top:0; z-index:50; }
        .topbar-title { font-size:18px; font-weight:700; color:#1e1a4b; }
        .user-badge { display:flex; align-items:center; gap:8px; padding:6px 12px; background:#f3f4f8; border-radius:20px; text-decoration:none; color:#1e1a4b; font-size:13px; }
        .user-avatar { width:30px; height:30px; background:linear-gradient(135deg,#f472b6,#a855f7); border-radius:50%; display:flex; align-items:center; justify-content:center; color:#fff; font-weight:700; font-size:13px; }
        .main-content { padding:20px 24px; flex:1; }
    </style>
</head>
<body>
@php $user = Auth::user(); $rol = $user->rol ?? null; @endphp
<div class="main-wrapper">
    <aside class="sidebar">
        <div class="sidebar-logo"><div style="display:flex;align-items:center;gap:10px"><div class="logo-icon">💇‍♀️</div><div><div class="logo-text">Salón de Belleza</div><div class="logo-sub">Sistema de Gestión</div></div></div></div>
        <nav class="sidebar-nav">
            <a href="{{ route('dashboard') }}" class="nav-item"><span class="nav-icon">🏠</span> Inicio</a>
            <a href="{{ route('citas.index') }}" class="nav-item"><span class="nav-icon">📅</span> Citas</a>
            <a href="{{ route('clientes.index') }}" class="nav-item"><span class="nav-icon">👤</span> Clientes</a>
            @if(in_array($rol,['admin','gerente']))
            <a href="{{ route('empleados.index') }}" class="nav-item"><span class="nav-icon">👔</span> Empleados</a>
            <a href="{{ route('servicios.index') }}" class="nav-item"><span class="nav-icon">✂️</span> Servicios</a>
            <a href="{{ route('subcategorias.index') }}" class="nav-item"><span class="nav-icon">🏷️</span> Subcategorías</a>
            <a href="{{ route('productos.index') }}" class="nav-item"><span class="nav-icon">🛍️</span> Productos</a>
            @endif
            <a href="{{ route('cobros.index') }}" class="nav-item"><span class="nav-icon">💳</span> Cobros</a>
            <a href="{{ route('deudas.index') }}" class="nav-item"><span class="nav-icon">💰</span> Deudas</a>
            <a href="{{ route('bonos.index') }}" class="nav-item active"><span class="nav-icon">🎫</span> Bonos</a>
            <a href="{{ route('bonos.clientesConBonos') }}" class="nav-item"><span class="nav-icon">👥</span> Clientes con Bonos</a>
            <a href="{{ route('caja.index') }}" class="nav-item"><span class="nav-icon">💵</span> Caja del Día</a>
            @if(in_array($rol,['admin','gerente']))
            <a href="{{ route('facturacion.index') }}" class="nav-item"><span class="nav-icon">📊</span> Facturación</a>
            <a href="{{ route('horarios.index') }}" class="nav-item"><span class="nav-icon">⏰</span> Horarios</a>
            <a href="{{ route('asistencia.index') }}" class="nav-item"><span class="nav-icon">🕐</span> Asistencia</a>
            @endif
            @if($rol==='admin')
            <a href="{{ route('users.index') }}" class="nav-item"><span class="nav-icon">⚙️</span> Usuarios</a>
            @endif
        </nav>
        <div class="sidebar-help"><div style="display:flex;align-items:center;gap:8px;margin-bottom:5px"><span style="font-size:20px">❓</span><span style="font-weight:700;font-size:12px">¿Necesitas ayuda?</span></div><p style="opacity:.85;font-size:11px;line-height:1.4">Consulta nuestra guía o contacta soporte</p></div>
        <div class="sidebar-footer">© {{ date('Y') }} Salón de Belleza</div>
    </aside>
    <div class="content">
        <header class="topbar">
            <div style="display:flex;align-items:center;gap:14px">
                <a href="{{ route('bonos.index') }}" style="color:#1e1a4b;font-size:20px;text-decoration:none">←</a>
                <div class="topbar-title">🛒 Vender Bono: {{ $plantilla->nombre }}</div>
            </div>
            <a href="{{ route('profile.edit') }}" class="user-badge">
                <div class="user-avatar">{{ strtoupper(substr($user->nombre ?? 'U', 0, 1)) }}</div>
                <div style="display:flex;flex-direction:column"><span style="font-weight:600;font-size:13px">{{ $user->nombre ?? '' }} {{ $user->apellidos ?? '' }}</span><span style="font-size:11px;color:#888;text-transform:capitalize">{{ $rol }}</span></div>
            </a>
        </header>
        <main class="main-content">
    <div class="bg-white rounded-xl shadow-sm p-6" style="max-width:900px">

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

        <!-- Información del bono -->
        <div class="bg-blue-50 border border-blue-200 rounded p-4 mb-6">
            <h3 class="font-bold text-lg mb-2">Detalles del Bono</h3>
            
            @if($plantilla->descripcion)
                <p class="text-gray-700 mb-3">{{ $plantilla->descripcion }}</p>
            @endif

            <div class="grid grid-cols-2 gap-4 mb-3">
                <div>
                    <p class="text-sm text-gray-600">Precio:</p>
                    <p class="text-2xl font-bold text-green-600">€{{ number_format($plantilla->precio, 2) }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Validez:</p>
                    @if($plantilla->duracion_dias)
                        <p class="text-lg font-semibold">{{ $plantilla->duracion_dias }} días</p>
                    @else
                        <p class="text-lg font-semibold text-purple-600">✨ Sin límite</p>
                    @endif
                </div>
            </div>

            <div>
                <p class="font-semibold mb-2">Servicios incluidos:</p>
                <ul class="list-disc pl-5 space-y-1">
                    @foreach($plantilla->servicios as $servicio)
                        <li>
                            {{ $servicio->nombre }}
                            @if($servicio->tipo === 'peluqueria')
                                <span class="text-blue-600">💇</span>
                            @else
                                <span class="text-pink-600">💅</span>
                            @endif
                            <span class="font-semibold">({{ $servicio->pivot->cantidad }} {{ $servicio->pivot->cantidad > 1 ? 'veces' : 'vez' }})</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        <!-- Formulario de compra -->
        <form action="{{ route('bonos.procesarCompra', $plantilla->id) }}" method="POST" id="formCompraBono">
            @csrf

            <div class="mb-6">
                <label for="cliente_id" class="block font-semibold mb-2">Seleccionar Cliente</label>
                <select name="cliente_id" id="cliente_id" required class="w-full border rounded px-3 py-2">
                    <option value="">Seleccione un cliente</option>
                    @foreach($clientes as $cliente)
                        <option value="{{ $cliente->id }}" {{ old('cliente_id') == $cliente->id ? 'selected' : '' }}>
                            {{ $cliente->user->nombre }} {{ $cliente->user->apellidos }} - {{ $cliente->user->email }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="mb-6">
                <label for="id_empleado" class="block font-semibold mb-2">Empleado que Realiza la Venta</label>
                <select name="id_empleado" id="id_empleado" required class="w-full border rounded px-3 py-2">
                    <option value="">Seleccione un empleado</option>
                    @foreach($empleados as $empleado)
                        <option value="{{ $empleado->id }}" {{ old('id_empleado') == $empleado->id ? 'selected' : '' }}>
                            {{ $empleado->user->nombre }} {{ $empleado->user->apellidos }}
                            @if($empleado->categoria === 'peluqueria')
                                <span class="text-blue-600">(Peluquería 💇)</span>
                            @else
                                <span class="text-pink-600">(Estética 💅)</span>
                            @endif
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Método de Pago -->
            <div class="mb-6 bg-green-50 border border-green-200 rounded p-4">
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
                                <p class="text-sm text-gray-600">Precio bono:</p>
                                <p class="text-xl font-bold text-gray-700">€{{ number_format($plantilla->precio, 2) }}</p>
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

            <div class="bg-yellow-50 border border-yellow-200 rounded p-4 mb-6">
                <p class="text-sm text-gray-700">
                    <strong>Nota:</strong> El cliente no podrá comprar este bono si ya tiene otro bono activo que incluya alguno de estos servicios.
                </p>
            </div>

            <div class="flex justify-between items-center">
                <a href="{{ route('bonos.index') }}" class="text-blue-600 hover:underline">← Cancelar</a>
                <button type="submit" class="bg-green-600 text-white px-6 py-2 rounded hover:bg-green-700 font-bold" style="background-color: #16a34a; color: white; padding: 0.5rem 1.5rem; border-radius: 0.375rem; font-weight: bold; border: none; cursor: pointer;">
                    ✅ Confirmar Venta
                </button>
            </div>
        </form>

        <script>
            const precioTotal = {{ $plantilla->precio }};

            function toggleMetodoPago() {
                const metodoPago = document.querySelector('input[name="metodo_pago"]:checked')?.value;
                const dineroDiv = document.getElementById('dineroClienteDiv');
                const cambioDiv = document.getElementById('cambioDiv');
                const dineroInput = document.getElementById('dinero_cliente');
                const pagoMixtoDiv = document.getElementById('pagoMixtoDiv');
                const pagoEfectivoInput = document.getElementById('pago_efectivo');
                const pagoTarjetaInput = document.getElementById('pago_tarjeta');

                // Ocultar todo primero
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
                // Tarjeta: no muestra nada extra
            }

            function toggleDineroCliente() {
                toggleMetodoPago();
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

            // Inicializar estado al cargar
            document.addEventListener('DOMContentLoaded', function() {
                const metodoPagoChecked = document.querySelector('input[name="metodo_pago"]:checked');
                if (metodoPagoChecked) {
                    toggleMetodoPago();
                }
            });
        </script>
    </div>
        </main>
    </div>
</div>
</body>
</html>
