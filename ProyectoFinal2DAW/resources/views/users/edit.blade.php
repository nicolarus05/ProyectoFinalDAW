<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Usuario</title>
    {!! vite_asset(['resources/css/app.css', 'resources/js/app.js']) !!}
    <style>
        :root { --sidebar-w: 210px; }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', 'Segoe UI', sans-serif; background: #f3f4f8; }
        .main-wrapper { display: flex; min-height: 100vh; }
        .sidebar { position:fixed; top:0; left:0; width:var(--sidebar-w); height:100vh; background:#1e1a4b; display:flex; flex-direction:column; z-index:100; overflow-y:auto; }
        .sidebar-logo { padding:20px 16px 12px; border-bottom:1px solid rgba(255,255,255,.08); }
        .sidebar-logo .logo-icon { font-size:1.6rem; }
        .sidebar-logo .logo-name { color:#fff; font-weight:700; font-size:.95rem; line-height:1.2; }
        .sidebar-logo .logo-sub { color:rgba(255,255,255,.5); font-size:.72rem; }
        nav.sidebar-nav { flex:1; padding:12px 0; }
        .nav-item { display:flex; align-items:center; gap:10px; padding:9px 16px; color:rgba(255,255,255,.75); font-size:.82rem; font-weight:500; cursor:pointer; border-left:3px solid transparent; transition:all .18s; text-decoration:none; }
        .nav-item:hover { background:rgba(255,255,255,.07); color:#fff; }
        .nav-item.active { background:linear-gradient(135deg,#f472b6,#a855f7); color:#fff; border-left-color:transparent; }
        .nav-icon { font-size:1rem; width:20px; text-align:center; }
        .sidebar-footer { padding:12px 16px; border-top:1px solid rgba(255,255,255,.08); }
        .sidebar-footer p { color:rgba(255,255,255,.4); font-size:.68rem; }
        .content { margin-left:var(--sidebar-w); flex:1; display:flex; flex-direction:column; }
        .topbar { background:#fff; padding:14px 24px; display:flex; align-items:center; justify-content:space-between; box-shadow:0 1px 4px rgba(0,0,0,.08); position:sticky; top:0; z-index:50; }
        .topbar-title { font-size:1.1rem; font-weight:700; color:#1e1a4b; }
        .topbar-actions { display:flex; align-items:center; gap:12px; }
        .topbar-user { display:flex; align-items:center; gap:8px; text-decoration:none; }
        .topbar-user .avatar { width:34px; height:34px; background:linear-gradient(135deg,#f472b6,#a855f7); border-radius:50%; display:flex; align-items:center; justify-content:center; color:#fff; font-weight:700; font-size:.9rem; }
        .topbar-user .user-info .name { font-size:.82rem; font-weight:600; color:#1e1a4b; }
        .topbar-user .user-info .role { font-size:.7rem; color:#6b7280; }
        .main-content { padding:20px 24px; flex:1; }
        .btn-navy { background:#1e1a4b; color:#fff !important; border:none; padding:8px 18px; border-radius:8px; font-weight:600; font-size:.82rem; cursor:pointer; text-decoration:none; display:inline-flex; align-items:center; gap:6px; }
        .btn-navy:hover { opacity:.9; }
    </style>
</head>
@php $authUser = auth()->user(); $rol = $authUser->rol ?? ''; @endphp
<body>
<div class="main-wrapper">
    <aside class="sidebar">
        <div class="sidebar-logo">
            <div class="logo-icon">💇‍♀️</div>
            <div class="logo-name">Salón de Belleza</div>
            <div class="logo-sub">Sistema de Gestión</div>
        </div>
        <nav class="sidebar-nav">
            <a href="{{ route('dashboard') }}" class="nav-item"><span class="nav-icon">🏠</span> Inicio</a>
            <a href="{{ route('citas.index') }}" class="nav-item"><span class="nav-icon">📅</span> Citas</a>
            <a href="{{ route('clientes.index') }}" class="nav-item"><span class="nav-icon">👤</span> Clientes</a>
            <a href="{{ route('empleados.index') }}" class="nav-item"><span class="nav-icon">👔</span> Empleados</a>
            <a href="{{ route('servicios.index') }}" class="nav-item"><span class="nav-icon">✂️</span> Servicios</a>
            <a href="{{ route('subcategorias.index') }}" class="nav-item"><span class="nav-icon">🏷️</span> Subcategorías</a>
            <a href="{{ route('productos.index') }}" class="nav-item"><span class="nav-icon">🛍️</span> Productos</a>
            <a href="{{ route('cobros.index') }}" class="nav-item"><span class="nav-icon">💳</span> Cobros</a>
            <a href="{{ route('deudas.index') }}" class="nav-item"><span class="nav-icon">💰</span> Deudas</a>
            <a href="{{ route('bonos.index') }}" class="nav-item"><span class="nav-icon">🎫</span> Bonos</a>
            <a href="{{ route('bonos.clientesConBonos') }}" class="nav-item"><span class="nav-icon">👥</span> Clientes con Bonos</a>
            <a href="{{ route('caja.index') }}" class="nav-item"><span class="nav-icon">💵</span> Caja del Día</a>
            <a href="{{ route('facturacion.index') }}" class="nav-item"><span class="nav-icon">📊</span> Facturación</a>
            <a href="{{ route('horarios.index') }}" class="nav-item"><span class="nav-icon">⏰</span> Horarios</a>
            <a href="{{ route('asistencia.index') }}" class="nav-item"><span class="nav-icon">🕐</span> Asistencia</a>
            <a href="{{ route('users.index') }}" class="nav-item active"><span class="nav-icon">⚙️</span> Usuarios</a>
        </nav>
        <div class="sidebar-footer"><p>© 2026 Salón de Belleza</p></div>
    </aside>
    <div class="content">
        <header class="topbar">
            <span class="topbar-title">⚙️ Editar Usuario</span>
            <div class="topbar-actions">
                <a href="{{ route('users.index') }}" class="btn-navy">← Volver</a>
                <a href="{{ route('profile.edit') }}" class="topbar-user">
                    <div class="avatar">{{ strtoupper(substr($authUser->nombre ?? $authUser->name ?? 'U', 0, 1)) }}</div>
                    <div class="user-info">
                        <div class="name">{{ $authUser->nombre ?? $authUser->name ?? '' }} {{ $authUser->apellidos ?? '' }}</div>
                        <div class="role">{{ $rol }}</div>
                    </div>
                </a>
            </div>
        </header>
        <main class="main-content">
    <div class="max-w-xl mx-auto bg-white p-8 rounded-lg shadow-md">

        {{-- Mostrar errores de validación --}}
        @if ($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('users.update', $user->id) }}" method="POST" class="space-y-4">
            @csrf
            @method('PUT')

            <!-- Campos generales -->
            <div>
                <label class="block font-semibold mb-1">Nombre:</label>
                <input type="text" name="nombre" value="{{ old('nombre', $user->nombre) }}" required class="w-full border rounded px-3 py-2 focus:outline-none focus:ring focus:border-blue-300">
            </div>

            <div>
                <label class="block font-semibold mb-1">Apellidos:</label>
                <input type="text" name="apellidos" value="{{ old('apellidos', $user->apellidos) }}" required class="w-full border rounded px-3 py-2 focus:outline-none focus:ring focus:border-blue-300">
            </div>

            <div>
                <label class="block font-semibold mb-1">Email:</label>
                <input type="email" name="email" value="{{ old('email', $user->email) }}" required class="w-full border rounded px-3 py-2 focus:outline-none focus:ring focus:border-blue-300">
            </div>

            <div>
                <label class="block font-semibold mb-1">Teléfono:</label>
                <input type="text" name="telefono" value="{{ old('telefono', $user->telefono) }}" class="w-full border rounded px-3 py-2 focus:outline-none focus:ring focus:border-blue-300">
            </div>

            <div>
                <label class="block font-semibold mb-1">Edad:</label>
                <input type="number" name="edad" value="{{ old('edad', $user->edad) }}" class="w-full border rounded px-3 py-2 focus:outline-none focus:ring focus:border-blue-300">
            </div>

            <div>
                <label class="block font-semibold mb-1">Género:</label>
                <select name="genero" class="w-full border rounded px-3 py-2 focus:outline-none focus:ring focus:border-blue-300">
                    <option value="">-- Selecciona --</option>
                    <option value="Masculino" {{ old('genero', $user->genero) == 'Masculino' ? 'selected' : '' }}>Masculino</option>
                    <option value="Femenino" {{ old('genero', $user->genero) == 'Femenino' ? 'selected' : '' }}>Femenino</option>
                    <option value="Otro" {{ old('genero', $user->genero) == 'Otro' ? 'selected' : '' }}>Otro</option>
                </select>
            </div>

            <div>
                <label class="block font-semibold mb-1">Contraseña (solo si deseas cambiarla):</label>
                <input type="password" name="password" class="w-full border rounded px-3 py-2 focus:outline-none focus:ring focus:border-blue-300">
            </div>

            <div>
                <label class="block font-semibold mb-1">Rol:</label>
                <select name="rol" id="rol" onchange="mostrarCamposEspecificos()" class="w-full border rounded px-3 py-2 focus:outline-none focus:ring focus:border-blue-300">
                    <option value="">-- Selecciona un rol --</option>
                    <option value="cliente" {{ old('rol', $user->rol) == 'cliente' ? 'selected' : '' }}>Cliente</option>
                    <option value="empleado" {{ old('rol', $user->rol) == 'empleado' ? 'selected' : '' }}>Empleado</option>
                    <option value="gerente" {{ old('rol', $user->rol) == 'gerente' ? 'selected' : '' }}>Gerente</option>
                </select>
            </div>

            <!-- Campos específicos para empleados -->
            <div id="campos-empleado" style="display: none;">
                <label for="categoria" class="block font-semibold mb-1">Categoría:</label>
                <select name="categoria" class="w-full border rounded px-3 py-2 focus:outline-none focus:ring focus:border-blue-300">
                    <option value="">-- Seleccione --</option>
                    <option value="peluqueria" {{ old('categoria', optional($user->empleado)->categoria) == 'peluqueria' ? 'selected' : '' }}>Peluquería</option>
                    <option value="estetica" {{ old('categoria', optional($user->empleado)->categoria) == 'estetica' ? 'selected' : '' }}>Estética</option>
                </select>
            </div>

            <!-- Campos específicos para clientes -->
            <div id="campos-cliente" style="display: none;">
                <div>
                    <label class="block font-semibold mb-1">Dirección:</label>
                    <input type="text" name="direccion" value="{{ old('direccion', optional($user->cliente)->direccion) }}" class="w-full border rounded px-3 py-2 focus:outline-none focus:ring focus:border-blue-300">
                </div>
                <div>
                    <label class="block font-semibold mb-1">Fecha de Registro:</label>
                    <input type="date" name="fecha_registro" value="{{ old('fecha_registro', optional($user->cliente)->fecha_registro) }}" class="w-full border rounded px-3 py-2 focus:outline-none focus:ring focus:border-blue-300">
                </div>
                <div>
                    <label class="block font-semibold mb-1">Notas Adicionales:</label>
                    <textarea name="notas_adicionales" rows="4" class="w-full border rounded px-3 py-2 focus:outline-none focus:ring focus:border-blue-300">{{ old('notas_adicionales', optional($user->cliente)->notas_adicionales) }}</textarea>
                </div>
            </div>

            <div class="flex items-center justify-between">
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 font-semibold">Actualizar</button>
                <a href="{{ route('users.index') }}" class="text-blue-600 hover:underline">Volver a la lista</a>
            </div>
        </form>
    </div>
        </main>
    </div>
</div>

    <script>
        function mostrarCamposEspecificos() {
            const rol = document.getElementById('rol').value;

            const clienteFields = document.getElementById('campos-cliente');
            const empleadoFields = document.getElementById('campos-empleado');

            clienteFields.style.display = 'none';
            empleadoFields.style.display = 'none';

            if (rol === 'cliente') {
                clienteFields.style.display = 'block';
            } else if (rol === 'empleado') {
                empleadoFields.style.display = 'block';
            }
        }

        // Mostrar los campos correspondientes al cargar la página
        document.addEventListener('DOMContentLoaded', function () {
            mostrarCamposEspecificos();
        });
    </script>
        </main>
    </div>
</div>
</body>
</html>
