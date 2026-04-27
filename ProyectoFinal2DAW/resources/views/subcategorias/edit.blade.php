<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Subcategoría — {{ $subcategoria->nombre }}</title>
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
        .form-card { background:#fff;border-radius:14px;box-shadow:0 1px 4px rgba(0,0,0,.06);overflow:hidden;width:100%;max-width:560px;align-self:flex-start; }
        .panel-header { padding:14px 20px;background:#1e1a4b; }
        .panel-header h2 { color:#fff;font-size:14px;font-weight:700; }
        .form-body { padding:24px; }
        .form-group { margin-bottom:18px; }
        .form-label { display:block;font-size:12px;font-weight:700;color:#374151;margin-bottom:6px; }
        .form-label span { color:#ef4444; }
        .form-control { width:100%;padding:9px 14px;border:1.5px solid #e5e7eb;border-radius:9px;font-size:13px;outline:none;transition:border .2s;background:#fff;color:#1f2937; }
        .form-control:focus { border-color:#a855f7;box-shadow:0 0 0 3px rgba(168,85,247,.08); }
        .form-hint { font-size:11px;color:#9ca3af;margin-top:4px; }
        .btn-primary { padding:10px 20px;background:linear-gradient(135deg,#f472b6,#a855f7);color:#fff;border:none;border-radius:9px;font-size:13px;font-weight:700;cursor:pointer; }
        .btn-secondary { padding:10px 20px;background:#f3f4f6;border:1.5px solid #e5e7eb;border-radius:9px;font-size:13px;font-weight:600;color:#374151;text-decoration:none; }
        .error-box { background:#fef2f2;border:1.5px solid #fca5a5;border-radius:9px;padding:12px 16px;margin-bottom:18px;font-size:12px;color:#dc2626; }
        .color-row { display:flex;align-items:center;gap:10px; }
        .color-picker-wrap { position:relative;width:42px;height:42px;border-radius:9px;overflow:hidden;border:1.5px solid #e5e7eb;cursor:pointer; }
        .color-picker-wrap input[type=color] { position:absolute;top:-4px;left:-4px;width:calc(100%+8px);height:calc(100%+8px);border:none;cursor:pointer;padding:0; }
        #preview-bloque { border-radius:8px;padding:6px 16px;font-size:13px;font-weight:700;transition:background .2s,color .2s; }
        .info-chip { display:inline-flex;align-items:center;gap:6px;background:#f3f4f6;border-radius:20px;padding:3px 10px;font-size:12px;color:#374151; }
        @media (max-width:900px) { .sidebar{transform:translateX(-100%)} .main-wrapper{margin-left:0} }
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
        <a href="{{ route('subcategorias.index') }}" class="nav-item active"><span class="nav-icon">🏷️</span> Subcategorías</a>
        <a href="{{ route('productos.index') }}" class="nav-item"><span class="nav-icon">🛍️</span> Productos</a>
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
        <span class="page-title">✏️ Editar: {{ $subcategoria->nombre }}</span>
        <div style="flex:1"></div>
        <a href="{{ route('subcategorias.index') }}" style="font-size:12px;color:#a855f7;font-weight:600;text-decoration:none;margin-right:12px">← Volver</a>
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
                <h2>✏️ Editar Subcategoría</h2>
            </div>
            <div class="form-body">

                @if($errors->any())
                    <div class="error-box">
                        <ul style="list-style:disc;padding-left:16px">
                            @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('subcategorias.update', $subcategoria) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="form-group">
                        <label class="form-label" for="nombre">Nombre <span>*</span></label>
                        <input type="text" id="nombre" name="nombre"
                               value="{{ old('nombre', $subcategoria->nombre) }}"
                               required maxlength="100"
                               class="form-control">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="categoria">Categoría principal <span>*</span></label>
                        <select id="categoria" name="categoria" required class="form-control">
                            <option value="peluqueria" {{ old('categoria', $subcategoria->categoria) == 'peluqueria' ? 'selected' : '' }}>✂️ Peluquería</option>
                            <option value="estetica"   {{ old('categoria', $subcategoria->categoria) == 'estetica'   ? 'selected' : '' }}>💅 Estética</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="color-picker">Color del bloque en agenda <span>*</span></label>
                        <div class="color-row">
                            <div class="color-picker-wrap">
                                <input type="color" id="color-picker" value="{{ old('color', $subcategoria->color) }}"
                                       oninput="document.getElementById('color-hex').value=this.value;actualizarPreview()">
                            </div>
                            <input type="text" name="color" id="color-hex" value="{{ old('color', $subcategoria->color) }}"
                                   pattern="^#[0-9A-Fa-f]{6}$" maxlength="7" required
                                   placeholder="#8b5cf6"
                                   style="width:110px;font-family:monospace"
                                   class="form-control"
                                   oninput="sincronizarColor(this.value)">
                            <div id="preview-bloque">Vista previa</div>
                        </div>
                        <p class="form-hint">
                            Servicios asociados a esta subcategoría:
                            <span class="info-chip">{{ $subcategoria->servicios()->count() }} servicio(s)</span>
                        </p>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="activo">Estado</label>
                        <select id="activo" name="activo" class="form-control">
                            <option value="1" {{ old('activo', $subcategoria->activo) ? 'selected' : '' }}>✓ Activa</option>
                            <option value="0" {{ !old('activo', $subcategoria->activo) ? 'selected' : '' }}>✗ Inactiva</option>
                        </select>
                    </div>

                    <div style="display:flex;align-items:center;justify-content:space-between;margin-top:24px">
                        <button type="submit" class="btn-primary">✓ Actualizar Subcategoría</button>
                        <a href="{{ route('subcategorias.index') }}" class="btn-secondary">Cancelar</a>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>

<script>
function actualizarPreview() {
    var color = document.getElementById('color-picker').value;
    var prev  = document.getElementById('preview-bloque');
    prev.style.background = color;
    var r = parseInt(color.slice(1,3),16),
        g = parseInt(color.slice(3,5),16),
        b = parseInt(color.slice(5,7),16);
    var luminancia = (0.299*r + 0.587*g + 0.114*b) / 255;
    prev.style.color = luminancia > 0.6 ? '#1f2937' : '#ffffff';
}
function sincronizarColor(val) {
    if (/^#[0-9A-Fa-f]{6}$/.test(val)) {
        document.getElementById('color-picker').value = val;
        actualizarPreview();
    }
}
actualizarPreview();
</script>
</body>
</html>
