<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Servicio</title>
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
        .content { flex:1;padding:18px 20px; }
        .panel { background:#fff;border-radius:14px;box-shadow:0 1px 4px rgba(0,0,0,.06);overflow:hidden;max-width:640px;margin:0 auto; }
        .panel-header { padding:14px 20px;background:#1e1a4b;display:flex;align-items:center;justify-content:space-between; }
        .panel-header h2 { color:#fff;font-size:15px;font-weight:700; }
        .panel-body { padding:24px; }
        .form-row { display:grid;grid-template-columns:1fr 1fr;gap:14px; }
        .form-group { margin-bottom:16px; }
        .form-label { display:block;font-size:12px;font-weight:700;color:#374151;margin-bottom:6px; }
        .form-control { width:100%;padding:9px 14px;border:1.5px solid #e5e7eb;border-radius:9px;font-size:13px;outline:none;transition:border .2s;background:#fff;color:#1f2937; }
        .form-control:focus { border-color:#a855f7;box-shadow:0 0 0 3px rgba(168,85,247,.08); }
        select.form-control { cursor:pointer; }
        textarea.form-control { resize:vertical; }
        .btn-primary { padding:10px 24px;background:linear-gradient(135deg,#f472b6,#a855f7);color:#fff;border:none;border-radius:9px;font-size:13px;font-weight:700;cursor:pointer; }
        .btn-secondary { padding:10px 20px;background:#f3f4f6;border:1.5px solid #e5e7eb;border-radius:9px;font-size:13px;font-weight:600;cursor:pointer;color:#374151;text-decoration:none;display:inline-flex;align-items:center; }
        .color-dot { display:inline-block;width:10px;height:10px;border-radius:50%;margin-right:4px;vertical-align:middle; }
        @media (max-width:768px) { .sidebar{transform:translateX(-100%)} .main-wrapper{margin-left:0} .form-row{grid-template-columns:1fr} }
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
        <a href="{{ route('servicios.index') }}" class="nav-item active"><span class="nav-icon">✂️</span> Servicios</a>
        <a href="{{ route('subcategorias.index') }}" class="nav-item"><span class="nav-icon">🏷️</span> Subcategorías</a>
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
        <span class="page-title">✏️ Editar Servicio</span>
        <div style="flex:1"></div>
        <a href="{{ route('servicios.show', $servicio) }}" style="font-size:12px;color:#a855f7;font-weight:600;text-decoration:none;margin-right:12px">← Ver detalle</a>
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
        <div class="panel">
            <div class="panel-header">
                <h2>✏️ Editar: {{ $servicio->nombre }}</h2>
                <a href="{{ route('servicios.index') }}" style="font-size:11px;color:rgba(255,255,255,.7);text-decoration:none">← Volver</a>
            </div>
            <div class="panel-body">

                @if ($errors->any())
                    <div style="margin-bottom:16px;background:#fef2f2;border-left:4px solid #ef4444;padding:12px 16px;border-radius:8px">
                        <strong style="color:#991b1b;font-size:13px">❌ Hay errores en el formulario</strong>
                        <ul style="margin-top:6px;padding-left:18px;color:#b91c1c;font-size:12px">
                            @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('servicios.update', $servicio) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="form-group">
                        <label class="form-label">✂️ Nombre <span style="color:#ef4444">*</span></label>
                        <input type="text" name="nombre" value="{{ old('nombre', $servicio->nombre) }}" required class="form-control">
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">⏱️ Tiempo Estimado (min) <span style="color:#ef4444">*</span></label>
                            <input type="number" name="tiempo_estimado" value="{{ old('tiempo_estimado', $servicio->tiempo_estimado) }}" required min="1" class="form-control">
                        </div>
                        <div class="form-group">
                            <label class="form-label">💶 Precio (€) <span style="color:#ef4444">*</span></label>
                            <input type="number" step="0.01" name="precio" value="{{ old('precio', $servicio->precio) }}" required min="0" class="form-control">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">🏷️ Categoría <span style="color:#ef4444">*</span></label>
                            <select name="categoria" id="select-categoria" required class="form-control" onchange="filtrarSubcategorias()">
                                <option value="peluqueria" {{ old('categoria', $servicio->categoria) == 'peluqueria' ? 'selected' : '' }}>✂️ Peluquería</option>
                                <option value="estetica"   {{ old('categoria', $servicio->categoria) == 'estetica'   ? 'selected' : '' }}>💅 Estética</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">🏷️ Subcategoría <span style="color:#9ca3af;font-weight:400">(opcional)</span></label>
                            <select name="subcategoria_id" id="select-subcategoria" class="form-control" onchange="mostrarColorSubcategoria()">
                                <option value="">— Sin subcategoría —</option>
                                @foreach($subcategorias as $sub)
                                    <option value="{{ $sub->id }}"
                                            data-categoria="{{ $sub->categoria }}"
                                            data-color="{{ $sub->color }}"
                                            {{ old('subcategoria_id', $servicio->subcategoria_id) == $sub->id ? 'selected' : '' }}>
                                        {{ $sub->nombre }}
                                    </option>
                                @endforeach
                            </select>
                            <div id="sub-color-preview" style="display:none;margin-top:6px;align-items:center;gap:6px">
                                <span class="color-dot" id="sub-color-dot"></span>
                                <span style="font-size:11px;color:#6b7280" id="sub-color-texto"></span>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">📝 Descripción</label>
                        <textarea name="descripcion" rows="3" class="form-control">{{ old('descripcion', $servicio->descripcion) }}</textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label">✅ Activo</label>
                        <select name="activo" class="form-control">
                            <option value="1" {{ $servicio->activo ? 'selected' : '' }}>Sí</option>
                            <option value="0" {{ !$servicio->activo ? 'selected' : '' }}>No</option>
                        </select>
                    </div>

                    <div style="display:flex;justify-content:space-between;align-items:center;margin-top:8px;padding-top:16px;border-top:1px solid #f3f4f6">
                        <a href="{{ route('servicios.index') }}" class="btn-secondary">← Volver</a>
                        <button type="submit" class="btn-primary">💾 Actualizar Servicio</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function filtrarSubcategorias() {
    var cat = document.getElementById('select-categoria').value;
    var sel = document.getElementById('select-subcategoria');
    sel.querySelectorAll('option[data-categoria]').forEach(function(opt) {
        opt.style.display = opt.dataset.categoria === cat ? '' : 'none';
    });
    var cur = sel.options[sel.selectedIndex];
    if (cur && cur.dataset.categoria && cur.dataset.categoria !== cat) sel.value = '';
    mostrarColorSubcategoria();
}
function mostrarColorSubcategoria() {
    var sel     = document.getElementById('select-subcategoria');
    var opt     = sel.options[sel.selectedIndex];
    var preview = document.getElementById('sub-color-preview');
    if (opt && opt.dataset.color) {
        document.getElementById('sub-color-dot').style.background = opt.dataset.color;
        document.getElementById('sub-color-texto').textContent    = 'Color en agenda: ' + opt.dataset.color;
        preview.style.display = 'flex';
    } else {
        preview.style.display = 'none';
    }
}
filtrarSubcategorias();
</script>
</body>
</html>
