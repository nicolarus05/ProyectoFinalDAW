<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Editar Producto — {{ $producto->nombre }}</title>
    {!! vite_asset(['resources/css/app.css', 'resources/js/app.js']) !!}
    <style>
        :root { --sidebar-w: 210px; }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', 'Segoe UI', sans-serif; background: #f3f4f8; }
        .sidebar { position:fixed;top:0;left:0;width:var(--sidebar-w);height:100vh;background:#1e1a4b;display:flex;flex-direction:column;z-index:50;overflow-y:auto;transition:transform .3s ease; }
        body.sidebar-collapsed .sidebar { transform:translateX(calc(-1 * var(--sidebar-w))); }
        body.sidebar-collapsed .main-wrapper { margin-left:0; }
        .sidebar-logo { padding:14px 14px 10px;border-bottom:1px solid rgba(255,255,255,.1); }
        .logo-icon { width:36px;height:36px;background:linear-gradient(135deg,#f472b6,#a855f7);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0; }
        .logo-text { color:#fff;font-size:12.5px;font-weight:700;line-height:1.2; }
        .logo-sub  { color:rgba(255,255,255,.55);font-size:10px; }
        .sidebar-nav { flex:1;padding:8px; }
        .nav-item { display:flex;align-items:center;gap:8px;padding:7px 10px;border-radius:8px;margin-bottom:1px;color:rgba(255,255,255,.7);font-size:12.5px;font-weight:500;text-decoration:none;transition:all .2s; }
        .nav-item:hover { background:rgba(255,255,255,.1);color:#fff; }
        .nav-item.active { background:linear-gradient(135deg,#f472b6,#a855f7);color:#fff;font-weight:600; }
        .nav-item .nav-icon { width:16px;text-align:center;flex-shrink:0;font-size:13px; }
        .sidebar-help { margin:0 8px 8px;background:linear-gradient(135deg,#f97316,#ec4899);border-radius:10px;padding:10px;color:#fff;font-size:11px; }
        .sidebar-footer { padding:6px 14px 12px;color:rgba(255,255,255,.35);font-size:9.5px; }
        .main-wrapper { margin-left:var(--sidebar-w);min-height:100vh;display:flex;flex-direction:column;transition:margin-left .3s ease; }
        .topbar { background:#fff;padding:8px 20px;display:flex;align-items:center;gap:12px;border-bottom:1px solid #e5e7eb;position:sticky;top:0;z-index:40; }
        .menu-btn { width:32px;height:32px;border-radius:8px;background:linear-gradient(135deg,#f472b6,#a855f7);display:flex;align-items:center;justify-content:center;color:#fff;font-size:14px;cursor:pointer;flex-shrink:0; }
        .page-title { font-size:15px;font-weight:700;color:#1f2937; }
        .user-area { display:flex;align-items:center;gap:8px;text-decoration:none;color:inherit; }
        .user-avatar { width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,#f472b6,#a855f7);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:13px;flex-shrink:0; }
        .content { flex:1;padding:18px 20px;display:flex;justify-content:center; }
        .form-card { background:#fff;border-radius:14px;box-shadow:0 1px 4px rgba(0,0,0,.06);overflow:hidden;width:100%;max-width:620px;align-self:flex-start; }
        .panel-header { padding:14px 20px;background:#1e1a4b; }
        .panel-header h2 { color:#fff;font-size:14px;font-weight:700; }
        .form-body { padding:24px; }
        .form-group { margin-bottom:18px; }
        .form-label { display:block;font-size:12px;font-weight:700;color:#374151;margin-bottom:6px; }
        .form-label span { color:#ef4444; }
        .form-control { width:100%;padding:9px 14px;border:1.5px solid #e5e7eb;border-radius:9px;font-size:13px;outline:none;transition:border .2s;background:#fff;color:#1f2937; }
        .form-control:focus { border-color:#a855f7;box-shadow:0 0 0 3px rgba(168,85,247,.08); }
        .grid-3 { display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px; }
        .btn-primary { padding:10px 20px;background:linear-gradient(135deg,#f472b6,#a855f7);color:#fff;border:none;border-radius:9px;font-size:13px;font-weight:700;cursor:pointer; }
        .btn-secondary { padding:10px 20px;background:#f3f4f6;border:1.5px solid #e5e7eb;border-radius:9px;font-size:13px;font-weight:600;color:#374151;text-decoration:none; }
        .error-box { background:#fef2f2;border:1.5px solid #fca5a5;border-radius:9px;padding:12px 16px;margin-bottom:18px;font-size:12px;color:#dc2626; }
        .toggle-wrap { display:flex;align-items:center;gap:10px;padding:10px 14px;background:#f9fafb;border-radius:9px;border:1.5px solid #e5e7eb; }
        .toggle-wrap label { font-size:13px;font-weight:600;color:#374151;cursor:pointer;user-select:none; }
        @media (max-width:900px) { .sidebar{transform:translateX(-100%)} .main-wrapper{margin-left:0} .grid-3{grid-template-columns:1fr} }
    </style>
</head>
<body>
@php $user = Auth::user(); $rol = $user->rol ?? null; @endphp

<!-- SIDEBAR -->
<aside class="sidebar">
    <div class="sidebar-logo">
        <div style="display:flex;align-items:center;gap:10px">
            <div class="logo-icon">💇‍♀️</div>
            <div><div class="logo-text">Salón de Belleza</div><div class="logo-sub">Sistema de Gestión</div></div>
        </div>
    </div>
    <nav class="sidebar-nav">
        <a href="{{ route('dashboard') }}" class="nav-item"><span class="nav-icon">🏠</span> Inicio</a>
        <a href="{{ route('citas.index') }}" class="nav-item"><span class="nav-icon">📅</span> Citas</a>
        <a href="{{ route('clientes.index') }}" class="nav-item"><span class="nav-icon">👤</span> Clientes</a>
        @if(in_array($rol,['admin','gerente']))
        <a href="{{ route('empleados.index') }}" class="nav-item"><span class="nav-icon">👔</span> Empleados</a>
        <a href="{{ route('servicios.index') }}" class="nav-item"><span class="nav-icon">✂️</span> Servicios</a>
        <a href="{{ route('subcategorias.index') }}" class="nav-item"><span class="nav-icon">🏷️</span> Subcategorías</a>
        <a href="{{ route('productos.index') }}" class="nav-item active"><span class="nav-icon">🛍️</span> Productos</a>
        @endif
        <a href="{{ route('cobros.index') }}" class="nav-item"><span class="nav-icon">💳</span> Cobros</a>
        <a href="{{ route('deudas.index') }}" class="nav-item"><span class="nav-icon">💰</span> Deudas</a>
        <a href="{{ route('bonos.index') }}" class="nav-item"><span class="nav-icon">🎫</span> Bonos</a>
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
    <div class="sidebar-help">
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:5px"><span style="font-size:20px">❓</span><span style="font-weight:700;font-size:12px">¿Necesitas ayuda?</span></div>
        <p style="opacity:.85;font-size:11px;line-height:1.4">Consulta nuestra guía o contacta soporte</p>
    </div>
    <div class="sidebar-footer">© {{ date('Y') }} Salón de Belleza</div>
</aside>

<!-- MAIN -->
<div class="main-wrapper">
    <header class="topbar">
        <div class="menu-btn" onclick="document.body.classList.toggle('sidebar-collapsed')">☰</div>
        <span class="page-title">✏️ Editar: {{ $producto->nombre }}</span>
        <div style="flex:1"></div>
        <a href="{{ route('productos.index') }}" style="font-size:12px;color:#a855f7;font-weight:600;text-decoration:none;margin-right:12px">← Volver</a>
        <a href="{{ route('profile.edit') }}" class="user-area">
            @if($user && $user->foto_perfil)
                <img src="{{ route('tenant.file',$user->foto_perfil) }}" style="width:32px;height:32px;border-radius:50%;object-fit:cover">
            @else
                <div class="user-avatar">{{ strtoupper(substr($user->nombre??'U',0,1)) }}</div>
            @endif
            <div style="line-height:1.2">
                <div style="font-weight:600;font-size:13px;color:#1f2937">{{ $user->nombre??'' }} {{ $user->apellidos??'' }}</div>
                <div style="font-size:11px;color:#6b7280;text-transform:capitalize">{{ ucfirst($user->rol??'') }}</div>
            </div>
        </a>
    </header>

    <div class="content">
        <div class="form-card">
            <div class="panel-header">
                <h2>✏️ Editar Producto</h2>
            </div>
            <div class="form-body">

                @if($errors->any())
                    <div class="error-box">
                        <ul style="list-style:disc;padding-left:16px">
                            @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('productos.update', $producto) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="form-group">
                        <label class="form-label" for="nombre">Nombre <span>*</span></label>
                        <input type="text" id="nombre" name="nombre"
                               value="{{ old('nombre', $producto->nombre) }}"
                               required class="form-control">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="categoria">Categoría <span>*</span></label>
                        <select id="categoria" name="categoria" required class="form-control">
                            <option value="">— Seleccionar categoría —</option>
                            <option value="peluqueria" {{ old('categoria', $producto->categoria) == 'peluqueria' ? 'selected' : '' }}>✂️ Peluquería</option>
                            <option value="estetica"   {{ old('categoria', $producto->categoria) == 'estetica'   ? 'selected' : '' }}>💅 Estética</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="descripcion">Descripción</label>
                        <textarea id="descripcion" name="descripcion" rows="3"
                                  class="form-control">{{ old('descripcion', $producto->descripcion) }}</textarea>
                    </div>

                    <div class="grid-3">
                        <div class="form-group">
                            <label class="form-label" for="precio_venta">Precio venta (€) <span>*</span></label>
                            <input type="number" id="precio_venta" name="precio_venta"
                                   value="{{ old('precio_venta', $producto->precio_venta) }}"
                                   step="0.01" min="0" required class="form-control">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="precio_coste">Precio coste (€)</label>
                            <input type="number" id="precio_coste" name="precio_coste"
                                   value="{{ old('precio_coste', $producto->precio_coste) }}"
                                   step="0.01" min="0" class="form-control">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="stock">Stock <span>*</span></label>
                            <input type="number" id="stock" name="stock"
                                   value="{{ old('stock', $producto->stock) }}"
                                   min="0" required class="form-control">
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="toggle-wrap">
                            <input type="checkbox" id="activo" name="activo" value="1"
                                   {{ old('activo', $producto->activo) ? 'checked' : '' }}
                                   style="width:16px;height:16px;accent-color:#a855f7;cursor:pointer">
                            <label for="activo">Producto activo</label>
                        </div>
                    </div>

                    <div style="display:flex;align-items:center;justify-content:space-between;margin-top:24px">
                        <button type="submit" class="btn-primary">✓ Guardar Cambios</button>
                        <a href="{{ route('productos.index') }}" class="btn-secondary">Cancelar</a>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>
</body>
</html>
