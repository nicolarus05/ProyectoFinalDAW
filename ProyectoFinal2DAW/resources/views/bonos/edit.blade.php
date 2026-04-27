<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Bono</title>
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
                <div class="topbar-title">✏️ Editar Bono: {{ $plantilla->nombre }}</div>
            </div>
            <a href="{{ route('profile.edit') }}" class="user-badge">
                <div class="user-avatar">{{ strtoupper(substr($user->nombre ?? 'U', 0, 1)) }}</div>
                <div style="display:flex;flex-direction:column"><span style="font-weight:600;font-size:13px">{{ $user->nombre ?? '' }} {{ $user->apellidos ?? '' }}</span><span style="font-size:11px;color:#888;text-transform:capitalize">{{ $rol }}</span></div>
            </a>
        </header>
        <main class="main-content">
    <div class="bg-white rounded-xl shadow-sm p-6" style="max-width:800px">

        @if ($errors->any())
            <div class="bg-red-100 text-red-700 p-4 mb-4 rounded">
                <strong>Errores encontrados:</strong>
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('bonos.update', $plantilla->id) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <div>
                <label for="nombre" class="block font-semibold mb-1">Nombre del Bono</label>
                <input type="text" name="nombre" id="nombre" value="{{ old('nombre', $plantilla->nombre) }}" required 
                    class="w-full border rounded px-3 py-2">
            </div>

            <div>
                <label for="descripcion" class="block font-semibold mb-1">Descripción</label>
                <textarea name="descripcion" id="descripcion" rows="3" 
                    class="w-full border rounded px-3 py-2">{{ old('descripcion', $plantilla->descripcion) }}</textarea>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="precio" class="block font-semibold mb-1">Precio (€)</label>
                    <input type="number" name="precio" id="precio" value="{{ old('precio', $plantilla->precio) }}" step="0.01" min="0" required 
                        class="w-full border rounded px-3 py-2">
                </div>

                <div>
                    <label for="duracion_tipo" class="block font-semibold mb-1">Duración</label>
                    <select name="duracion_tipo" id="duracion_tipo" required class="w-full border rounded px-3 py-2">
                        <option value="30" {{ old('duracion_tipo', $plantilla->duracion_dias ? '30' : 'sin_limite') == '30' ? 'selected' : '' }}>30 días</option>
                        <option value="sin_limite" {{ old('duracion_tipo', $plantilla->duracion_dias ? '30' : 'sin_limite') == 'sin_limite' ? 'selected' : '' }}>Sin límite</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="inline-flex items-center">
                    <input type="checkbox" name="activo" value="1" {{ old('activo', $plantilla->activo) ? 'checked' : '' }} class="form-checkbox mr-2">
                    <span>Activo</span>
                </label>
            </div>

            <div class="bg-yellow-50 border border-yellow-200 rounded p-4">
                <p class="text-sm text-gray-700">
                    <strong>Nota:</strong> Los servicios incluidos en este bono no se pueden editar aquí. 
                    Si necesitas cambiar los servicios, crea un nuevo bono.
                </p>
                <div class="mt-3">
                    <p class="font-semibold mb-2">Servicios actuales:</p>
                    <ul class="list-disc pl-5">
                        @foreach($plantilla->servicios as $servicio)
                            <li>{{ $servicio->nombre }} (x{{ $servicio->pivot->cantidad }})</li>
                        @endforeach
                    </ul>
                </div>
            </div>

            <div class="flex justify-between items-center mt-6">
                <a href="{{ route('bonos.index') }}" class="text-blue-600 hover:underline">Cancelar</a>
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700">
                    Guardar Cambios
                </button>
            </div>
        </form>
    </div>
        </main>
    </div>
</div>
</body>
</html>
