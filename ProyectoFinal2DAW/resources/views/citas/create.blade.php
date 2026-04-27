<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Nueva Cita</title>
    {!! vite_asset(['resources/css/app.css', 'resources/js/app.js', 'resources/css/citas-create.css', 'resources/js/citas-create.js']) !!}
    <style>
        :root { --sidebar-w: 210px; }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', 'Segoe UI', sans-serif; background: #f3f4f8; }
        .sidebar {
            position: fixed; top: 0; left: 0; width: var(--sidebar-w);
            height: 100vh; background: #1e1a4b;
            display: flex; flex-direction: column; z-index: 50;
            overflow-y: auto; transition: transform .3s ease;
        }
        body.sidebar-collapsed .sidebar { transform: translateX(calc(-1 * var(--sidebar-w))); }
        body.sidebar-collapsed .main-wrapper { margin-left: 0; }
        .sidebar-logo { padding: 14px 14px 10px; border-bottom: 1px solid rgba(255,255,255,.1); }
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
        @media (max-width:768px) { .sidebar{transform:translateX(-100%)} .main-wrapper{margin-left:0} }
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
        <a href="{{ route('citas.index') }}" class="nav-item active"><span class="nav-icon">📅</span> Citas</a>
        <a href="{{ route('clientes.index') }}" class="nav-item"><span class="nav-icon">👤</span> Clientes</a>
        @if(in_array($rol,['admin','gerente']))
        <a href="{{ route('empleados.index') }}" class="nav-item"><span class="nav-icon">👔</span> Empleados</a>
        <a href="{{ route('servicios.index') }}" class="nav-item"><span class="nav-icon">✂️</span> Servicios</a>
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
        <span class="page-title">📅 Nueva Cita</span>
        <div style="flex:1"></div>
        <a href="{{ route('citas.index') }}" style="font-size:12px;color:#a855f7;font-weight:600;text-decoration:none;margin-right:12px">← Volver al calendario</a>
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
    <!-- Contenedor Principal -->
    <div style="max-width:1100px;margin:0 auto">
        
        <!-- Header con Progreso -->
        <div style="margin-bottom:24px">
            <!-- Barra de Progreso -->
            <div style="display:flex;align-items:center;gap:0;margin-bottom:8px">
                <div style="flex:1;display:flex;align-items:center">
                    <div id="step-1-indicator" class="step-indicator active">1</div>
                    <div style="flex:1;height:4px;background:#e5e7eb;margin:0 8px;border-radius:2px">
                        <div id="progress-1" style="height:100%;background:linear-gradient(135deg,#f472b6,#a855f7);transition:width .3s;width:0%;border-radius:2px"></div>
                    </div>
                </div>
                <div style="flex:1;display:flex;align-items:center">
                    <div id="step-2-indicator" class="step-indicator">2</div>
                    <div style="flex:1;height:4px;background:#e5e7eb;margin:0 8px;border-radius:2px">
                        <div id="progress-2" style="height:100%;background:linear-gradient(135deg,#f472b6,#a855f7);transition:width .3s;width:0%;border-radius:2px"></div>
                    </div>
                </div>
                <div id="step-3-indicator" class="step-indicator">3</div>
            </div>
            <div style="display:flex;justify-content:space-between;font-size:12px;color:#6b7280">
                <span id="step-1-label" style="font-weight:700;color:#a855f7">1. Servicios</span>
                <span id="step-2-label">2. Empleado y Hora</span>
                <span id="step-3-label">3. Confirmar</span>
            </div>
        </div>

        {{-- Mensajes de Error --}}
        @if ($errors->any())
            <div style="margin-bottom:16px;background:#fef2f2;border-left:4px solid #ef4444;padding:12px 16px;border-radius:8px">
                <strong style="color:#991b1b;font-size:13px">❌ Error al crear la cita</strong>
                <ul style="margin-top:6px;padding-left:18px;color:#b91c1c;font-size:12px">
                    @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        @endif

        <form id="cita-form" action="{{ route('citas.store') }}" method="POST" novalidate>
            @csrf
            
            <!-- PASO 1: Seleccionar Servicios -->
            <div id="step-1" class="step-content active">
                <div style="background:#fff;border-radius:14px;box-shadow:0 1px 4px rgba(0,0,0,.06);overflow:hidden">
                    <div style="padding:14px 20px;background:#1e1a4b">
                        <h2 style="color:#fff;font-size:15px;font-weight:700">🔍 Paso 1 — Buscar y Seleccionar Servicios</h2>
                        <p style="color:rgba(255,255,255,.6);font-size:12px;margin-top:2px">Selecciona uno o más servicios para la cita</p>
                    </div>
                    <div style="padding:20px">

                    <!-- Barra de Búsqueda y Filtros -->
                    <div style="margin-bottom:20px">
                        <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:10px">
                            <div style="flex:1;min-width:260px;position:relative">
                                <input type="text" id="search-servicios" placeholder="🔍 Buscar servicios por nombre..."
                                       style="width:100%;padding:9px 14px;border:1.5px solid #e5e7eb;border-radius:9px;font-size:13px;outline:none;transition:border .2s" onfocus="this.style.borderColor='#a855f7'" onblur="this.style.borderColor='#e5e7eb'">
                            </div>
                            <select id="filter-categoria" style="padding:9px 14px;border:1.5px solid #e5e7eb;border-radius:9px;font-size:13px;outline:none;background:#fff">
                                <option value="">Todas las categorías</option>
                                <option value="peluqueria">✂️ Peluquería</option>
                                <option value="estetica">💆 Estética</option>
                            </select>
                            <button type="button" id="clear-filters" style="padding:9px 16px;background:#f3f4f6;border:1.5px solid #e5e7eb;border-radius:9px;font-size:12px;font-weight:600;cursor:pointer">Limpiar</button>
                            <button type="button" id="btn-next-step-2-top" disabled
                                    style="padding:9px 20px;background:linear-gradient(135deg,#f472b6,#a855f7);color:#fff;border:none;border-radius:9px;font-size:12px;font-weight:700;cursor:pointer">
                                Continuar →
                            </button>
                        </div>
                        <div style="font-size:12px;color:#6b7280">
                            <span id="services-count">{{ $servicios->count() }}</span> servicios disponibles
                            <span id="selected-count" style="margin-left:12px;font-weight:700;color:#a855f7"></span>
                        </div>
                    </div>

                    <!-- Grid de Servicios -->
                    <div id="servicios-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(210px,1fr));gap:12px;margin-bottom:20px">
                        @foreach($servicios as $servicio)
                            <div class="servicio-card"
                                 data-id="{{ $servicio->id }}"
                                 data-nombre="{{ strtolower($servicio->nombre) }}"
                                 data-categoria="{{ $servicio->categoria }}"
                                 data-precio="{{ $servicio->precio }}"
                                 data-tiempo="{{ $servicio->tiempo_estimado }}">
                                <div style="border:1.5px solid #e5e7eb;border-radius:12px;padding:14px;cursor:pointer;transition:all .2s;background:#fff">
                                    <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:8px">
                                        <h3 style="font-weight:700;color:#1e1a4b;font-size:13px;flex:1">{{ $servicio->nombre }}</h3>
                                        <span class="categoria-badge {{ $servicio->categoria }}" style="font-size:16px;margin-left:6px">
                                            {{ $servicio->categoria === 'peluqueria' ? '✂️' : '💆' }}
                                        </span>
                                    </div>
                                    <div style="font-size:12px;color:#6b7280">
                                        <div>⏱️ {{ $servicio->tiempo_estimado }} min</div>
                                        <div style="font-weight:700;color:#a855f7;margin-top:3px">💰 {{ number_format($servicio->precio, 2) }} €</div>
                                    </div>
                                    <div class="mt-3">
                                        <div class="checkmark-container">
                                            <svg class="checkmark" width="20" height="20" viewBox="0 0 20 20">
                                                <path d="M7 10l2 2 4-4" stroke="white" stroke-width="2" fill="none"/>
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Servicios Seleccionados -->
                    <div id="selected-services-container" style="display:none;margin-bottom:16px;padding:14px;background:#f5f3ff;border:1.5px solid #e9d5ff;border-radius:10px">
                        <h3 style="font-weight:700;color:#1e1a4b;font-size:13px;margin-bottom:10px">✅ Servicios Seleccionados</h3>
                        <div id="selected-services-list"></div>
                        <div style="margin-top:10px;padding-top:10px;border-top:1px solid #e9d5ff">
                            <div style="display:flex;justify-content:space-between;font-size:12px;color:#6b7280">
                                <span>Tiempo Total:</span><span id="total-tiempo" style="font-weight:700">0 min</span>
                            </div>
                            <div style="display:flex;justify-content:space-between;font-size:16px;font-weight:800;color:#a855f7;margin-top:4px">
                                <span>Total:</span><span id="total-precio">0.00 €</span>
                            </div>
                        </div>
                    </div>

                    <!-- Botón Continuar -->
                    <div style="display:flex;justify-content:flex-end">
                        <button type="button" id="btn-next-step-2" disabled
                                style="padding:10px 24px;background:linear-gradient(135deg,#f472b6,#a855f7);color:#fff;border:none;border-radius:9px;font-size:13px;font-weight:700;cursor:pointer">
                            Continuar → Seleccionar Empleado
                        </button>
                    </div>
                    </div>{{-- /padding --}}
                </div>
            </div>

            <!-- PASO 2: Seleccionar Empleado y Fecha/Hora -->
            <div id="step-2" class="step-content">
                <div style="background:#fff;border-radius:14px;box-shadow:0 1px 4px rgba(0,0,0,.06);overflow:hidden">
                    <div style="padding:14px 20px;background:#1e1a4b">
                        <h2 style="color:#fff;font-size:15px;font-weight:700">👤 Paso 2 — Empleado, Fecha y Hora</h2>
                        <p style="color:rgba(255,255,255,.6);font-size:12px;margin-top:2px">Selecciona quién realizará los servicios y cuándo</p>
                    </div>
                    <div style="padding:20px">
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px" class="step2-grid">
                        
                        <!-- Columna Izquierda: Cliente y Empleado -->
                        <div style="display:flex;flex-direction:column;gap:20px">
                            
                            @if(Auth::user()->rol === 'cliente')
                                <!-- Cliente autenticado -->
                                <input type="hidden" name="id_cliente" value="{{ $clientes->id }}">
                                <div style="padding:14px;background:#f9fafb;border-radius:10px;border:1.5px solid #e5e7eb">
                                    <label style="display:block;font-size:12px;font-weight:700;color:#374151;margin-bottom:8px">Cliente</label>
                                    <div style="display:flex;align-items:center;gap:10px">
                                        <div style="width:38px;height:38px;background:linear-gradient(135deg,#f472b6,#a855f7);border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:14px">
                                            {{ strtoupper(substr($clientes->user->nombre ?? '', 0, 1)) }}
                                        </div>
                                        <div>
                                            <p style="font-weight:600;color:#1e1a4b;font-size:13px">{{ $clientes->user->nombre }} {{ $clientes->user->apellidos }}</p>
                                            <p style="font-size:11px;color:#6b7280">{{ $clientes->user->email ?? '' }}</p>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <!-- Admin o empleado: selección de cliente con buscador -->
                                <div>
                                    <label for="search-cliente" style="display:block;font-size:12px;font-weight:700;color:#374151;margin-bottom:8px">
                                        Cliente <span style="color:#ef4444">*</span>
                                    </label>
                                    <!-- Buscador de Cliente -->
                                    <div style="margin-bottom:10px">
                                        <input type="text" id="search-cliente" placeholder="🔍 Buscar cliente por nombre o email..."
                                               style="width:100%;padding:9px 14px;border:1.5px solid #e5e7eb;border-radius:9px;font-size:13px;outline:none" autocomplete="off"
                                               onfocus="this.style.borderColor='#a855f7'" onblur="this.style.borderColor='#e5e7eb'">
                                    </div>
                                    
                                    <!-- Select oculto para el formulario -->
                                    <select name="id_cliente" id="id_cliente" required style="display:none">
                                        <option value="">-- Seleccione un cliente --</option>
                                        @foreach($clientes as $cliente)
                                            <option value="{{ $cliente->id }}" data-nombre="{{ strtolower($cliente->user->nombre.' '.$cliente->user->apellidos) }}" data-email="{{ strtolower($cliente->user->email??'') }}">
                                                {{ $cliente->user->nombre }} {{ $cliente->user->apellidos }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <!-- Lista de clientes filtrable -->
                                    <div id="clientes-list" style="border:1.5px solid #e5e7eb;border-radius:9px;max-height:220px;overflow-y:auto">
                                        @foreach($clientes as $cliente)
                                            <div class="cliente-item" style="padding:10px 14px;border-bottom:1px solid #f3f4f6;cursor:pointer;transition:background .15s"
                                                 data-cliente-id="{{ $cliente->id }}" data-nombre="{{ strtolower($cliente->user->nombre.' '.$cliente->user->apellidos) }}" data-email="{{ strtolower($cliente->user->email??'') }}"
                                                 onmouseover="this.style.background='#f5f3ff'" onmouseout="this.style.background='#fff'">
                                                <div style="display:flex;align-items:center;gap:10px">
                                                    <div style="width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,#f472b6,#a855f7);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:13px;flex-shrink:0">
                                                        {{ strtoupper(substr($cliente->user->nombre??'',0,1)) }}
                                                    </div>
                                                    <div>
                                                        <p style="font-weight:600;color:#1e1a4b;font-size:13px">{{ $cliente->user->nombre }} {{ $cliente->user->apellidos }}</p>
                                                        <p style="font-size:11px;color:#6b7280">{{ $cliente->user->email??'Sin email' }}</p>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                    <!-- Botón Crear Cliente -->
                                    <div style="margin-top:10px">
                                        <button type="button" id="btn-crear-cliente-modal" style="width:100%;background:linear-gradient(135deg,#f472b6,#a855f7);color:#fff;border:none;padding:10px;border-radius:9px;font-size:12px;font-weight:700;cursor:pointer">
                                            ➕ Crear Nuevo Cliente
                                        </button>
                                    </div>
                                    <!-- Cliente seleccionado -->
                                    <div id="selected-cliente" style="display:none;margin-top:10px;padding:12px;background:#f5f3ff;border:1.5px solid #e9d5ff;border-radius:9px">
                                        <div style="display:flex;align-items:center;justify-content:space-between">
                                            <div style="display:flex;align-items:center;gap:10px">
                                                <div style="width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,#f472b6,#a855f7);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:13px">
                                                    <span id="selected-cliente-inicial"></span>
                                                </div>
                                                <div>
                                                    <p style="font-weight:600;color:#1e1a4b;font-size:13px" id="selected-cliente-nombre"></p>
                                                    <p style="font-size:11px;color:#6b7280" id="selected-cliente-email"></p>
                                                </div>
                                            </div>
                                            <button type="button" id="clear-cliente" style="color:#dc2626;font-size:11px;font-weight:700;background:none;border:none;cursor:pointer">✕ Cambiar</button>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <!-- Selección de Empleado -->
                            <div>
                                <label style="display:block;font-size:12px;font-weight:700;color:#374151;margin-bottom:8px">
                                    Empleado <span style="color:#ef4444">*</span>
                                </label>
                                <div id="empleados-container" style="display:flex;flex-direction:column;gap:8px">
                                    @foreach($empleados as $empleado)
                                        <label class="empleado-option" style="display:block;padding:12px 14px;border:1.5px solid #e5e7eb;border-radius:10px;cursor:pointer;transition:all .2s"
                                               data-empleado-id="{{ $empleado->id }}" data-categoria="{{ $empleado->categoria }}"
                                               onmouseover="this.style.borderColor='#a855f7'" onmouseout="if(!this.querySelector('input').checked)this.style.borderColor='#e5e7eb'">
                                            <input type="radio" name="id_empleado" value="{{ $empleado->id }}"
                                                   class="hidden empleado-radio" required
                                                   {{ request('empleado_id') == $empleado->id ? 'checked' : '' }}>
                                            <div style="display:flex;align-items:center;justify-content:space-between">
                                                <div style="display:flex;align-items:center;gap:10px">
                                                    <div style="width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,#f472b6,#a855f7);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:14px">
                                                        {{ strtoupper(substr($empleado->user->nombre??'',0,1)) }}
                                                    </div>
                                                    <div>
                                                        <p style="font-weight:600;color:#1e1a4b;font-size:13px">{{ $empleado->user->nombre }} {{ $empleado->user->apellidos }}</p>
                                                        <p style="font-size:11px;color:#6b7280">{{ $empleado->categoria==='peluqueria'?'✂️ Peluquería':'💆 Estética' }}</p>
                                                    </div>
                                                </div>
                                                <div class="radio-checkmark">
                                                    <svg width="20" height="20" viewBox="0 0 24 24">
                                                        <circle cx="12" cy="12" r="10" stroke="#a855f7" stroke-width="2" fill="none"/>
                                                        <circle cx="12" cy="12" r="6" fill="#a855f7" class="inner-circle"/>
                                                    </svg>
                                                </div>
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <!-- Columna Derecha: Fecha y Hora -->
                        <div style="display:flex;flex-direction:column;gap:16px">
                            <div>
                                <label style="display:block;font-size:12px;font-weight:700;color:#374151;margin-bottom:6px">Fecha <span style="color:#ef4444">*</span></label>
                                <input type="date" id="fecha_cita" name="fecha_cita"
                                       min="{{ \Carbon\Carbon::now()->format('Y-m-d') }}"
                                       value="{{ request('fecha_hora') ? \Carbon\Carbon::parse(request('fecha_hora'))->format('Y-m-d') : \Carbon\Carbon::now()->format('Y-m-d') }}"
                                       required style="width:100%;padding:9px 14px;border:1.5px solid #e5e7eb;border-radius:9px;font-size:13px;outline:none">
                            </div>
                            <div>
                                <label style="display:block;font-size:12px;font-weight:700;color:#374151;margin-bottom:6px">Hora <span style="color:#ef4444">*</span></label>
                                <input type="time" id="hora_cita" name="hora_cita"
                                       value="{{ request('fecha_hora') ? \Carbon\Carbon::parse(request('fecha_hora'))->format('H:i') : '' }}"
                                       required style="width:100%;padding:9px 14px;border:1.5px solid #e5e7eb;border-radius:9px;font-size:13px;outline:none">
                                <p style="font-size:11px;color:#9ca3af;margin-top:4px">Invierno L-V 9:00-20:00, Sáb 8:30-14:00 · Verano L-S 8:30-14:00 (Mié 8:30-19:00)</p>
                            </div>
                            <div>
                                <label style="display:block;font-size:12px;font-weight:700;color:#374151;margin-bottom:6px">Notas adicionales (opcional)</label>
                                <textarea name="notas_adicionales" id="notas_adicionales" rows="5"
                                          placeholder="Comentarios especiales, preferencias, alergias..."
                                          style="width:100%;padding:9px 14px;border:1.5px solid #e5e7eb;border-radius:9px;font-size:13px;outline:none;resize:vertical"></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Botones Paso 2 -->
                    <div style="display:flex;justify-content:space-between;margin-top:20px;padding-top:16px;border-top:1px solid #f3f4f6">
                        <button type="button" id="btn-back-step-1" style="padding:9px 20px;background:#f3f4f6;border:1.5px solid #e5e7eb;border-radius:9px;font-size:12px;font-weight:600;cursor:pointer;color:#374151">← Volver a Servicios</button>
                        <button type="button" id="btn-next-step-3" style="padding:9px 24px;background:linear-gradient(135deg,#f472b6,#a855f7);color:#fff;border:none;border-radius:9px;font-size:13px;font-weight:700;cursor:pointer">Continuar → Confirmar</button>
                    </div>
                    </div>{{-- /padding --}}
                </div>
            </div>

            <!-- PASO 3: Confirmar -->
            <div id="step-3" class="step-content">
                <div style="background:#fff;border-radius:14px;box-shadow:0 1px 4px rgba(0,0,0,.06);overflow:hidden">
                    <div style="padding:14px 20px;background:#1e1a4b">
                        <h2 style="color:#fff;font-size:15px;font-weight:700">✅ Paso 3 — Confirmar Cita</h2>
                        <p style="color:rgba(255,255,255,.6);font-size:12px;margin-top:2px">Revisa los detalles antes de guardar</p>
                    </div>
                    <div style="padding:20px;display:flex;flex-direction:column;gap:16px">
                        <!-- Servicios -->
                        <div style="padding:16px;background:#f5f3ff;border:1.5px solid #e9d5ff;border-radius:10px">
                            <h3 style="font-weight:700;color:#1e1a4b;font-size:13px;margin-bottom:10px">📋 Servicios Seleccionados</h3>
                            <div id="confirm-services-list"></div>
                            <div style="margin-top:10px;padding-top:10px;border-top:1px solid #e9d5ff">
                                <div style="display:flex;justify-content:space-between;font-size:12px;color:#6b7280"><span>Tiempo Total:</span><span id="confirm-tiempo" style="font-weight:700"></span></div>
                                <div style="display:flex;justify-content:space-between;font-size:16px;font-weight:800;color:#a855f7;margin-top:4px"><span>Total a Pagar:</span><span id="confirm-precio"></span></div>
                            </div>
                        </div>
                        <!-- Detalles -->
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                            <div style="padding:12px 14px;background:#f9fafb;border-radius:10px;border:1.5px solid #e5e7eb">
                                <p style="font-size:11px;font-weight:700;color:#6b7280;margin-bottom:4px">👤 Cliente</p>
                                <p id="confirm-cliente" style="font-weight:600;color:#1e1a4b;font-size:13px"></p>
                            </div>
                            <div style="padding:12px 14px;background:#f9fafb;border-radius:10px;border:1.5px solid #e5e7eb">
                                <p style="font-size:11px;font-weight:700;color:#6b7280;margin-bottom:4px">👔 Empleado</p>
                                <p id="confirm-empleado" style="font-weight:600;color:#1e1a4b;font-size:13px"></p>
                            </div>
                            <div style="padding:12px 14px;background:#f9fafb;border-radius:10px;border:1.5px solid #e5e7eb">
                                <p style="font-size:11px;font-weight:700;color:#6b7280;margin-bottom:4px">📅 Fecha</p>
                                <p id="confirm-fecha" style="font-weight:700;color:#1e1a4b;font-size:14px"></p>
                            </div>
                            <div style="padding:12px 14px;background:#f9fafb;border-radius:10px;border:1.5px solid #e5e7eb">
                                <p style="font-size:11px;font-weight:700;color:#6b7280;margin-bottom:4px">🕐 Hora</p>
                                <p id="confirm-hora" style="font-weight:700;color:#1e1a4b;font-size:14px"></p>
                            </div>
                        </div>
                        <div id="confirm-notas-container" style="display:none;padding:12px 14px;background:#fffbeb;border:1.5px solid #fde68a;border-radius:10px">
                            <p style="font-size:11px;font-weight:700;color:#92400e;margin-bottom:4px">📝 Notas</p>
                            <p id="confirm-notas" style="font-size:13px;color:#374151"></p>
                        </div>
                        <!-- Hidden fields -->
                        <input type="hidden" name="fecha_hora" id="fecha_hora_combined">
                        <div id="hidden-services-inputs"></div>
                        <!-- Botones Paso 3 -->
                        <div style="display:flex;justify-content:space-between;padding-top:12px;border-top:1px solid #f3f4f6">
                            <button type="button" id="btn-back-step-2" style="padding:9px 20px;background:#f3f4f6;border:1.5px solid #e5e7eb;border-radius:9px;font-size:12px;font-weight:600;cursor:pointer;color:#374151">← Volver a Editar</button>
                            <button type="submit" id="btn-submit" style="padding:10px 28px;background:linear-gradient(135deg,#22c55e,#16a34a);color:#fff;border:none;border-radius:9px;font-size:13px;font-weight:700;cursor:pointer">✅ Confirmar y Guardar Cita</button>
                        </div>
                    </div>
                </div>
            </div>

        </form>
    </div>

    <!-- Modal Crear Cliente -->
    <div id="modal-crear-cliente" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);align-items:center;justify-content:center;z-index:9999">
        <div style="background:#fff;border-radius:14px;box-shadow:0 20px 60px rgba(0,0,0,.25);max-width:600px;width:calc(100% - 32px);max-height:90vh;overflow-y:auto">
            <div style="position:sticky;top:0;background:#1e1a4b;padding:14px 20px;display:flex;justify-content:space-between;align-items:center;border-radius:14px 14px 0 0">
                <h2 style="color:#fff;font-size:15px;font-weight:700">➕ Crear Nuevo Cliente</h2>
                <button type="button" id="btn-cerrar-modal-cliente" style="color:rgba(255,255,255,.7);background:none;border:none;font-size:20px;cursor:pointer;line-height:1">×</button>
            </div>
            
            <form id="form-crear-cliente" style="padding:20px">
                <div id="error-crear-cliente" style="display:none;background:#fef2f2;border-left:4px solid #ef4444;padding:10px 14px;border-radius:8px;margin-bottom:14px">
                    <p style="font-size:12px;color:#b91c1c"></p>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                    @php $inputStyle = "width:100%;padding:8px 12px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:13px;outline:none"; $labelStyle = "display:block;font-size:12px;font-weight:700;color:#374151;margin-bottom:5px"; @endphp
                    <div><label style="{{ $labelStyle }}">Nombre <span style="color:#ef4444">*</span></label><input type="text" name="nombre" required style="{{ $inputStyle }}"></div>
                    <div><label style="{{ $labelStyle }}">Apellidos <span style="color:#ef4444">*</span></label><input type="text" name="apellidos" required style="{{ $inputStyle }}"></div>
                    <div><label style="{{ $labelStyle }}">Teléfono</label><input type="text" name="telefono" style="{{ $inputStyle }}"></div>
                    <div><label style="{{ $labelStyle }}">Email <span style="color:#ef4444">*</span></label><input type="email" name="email" required style="{{ $inputStyle }}"></div>
                    <div>
                        <label style="{{ $labelStyle }}">Género <span style="color:#ef4444">*</span></label>
                        <select name="genero" required style="{{ $inputStyle }};background:#fff">
                            <option value="">Seleccionar...</option>
                            <option value="Hombre">Hombre</option>
                            <option value="Mujer">Mujer</option>
                            <option value="Otro">Otro</option>
                        </select>
                    </div>
                    <div><label style="{{ $labelStyle }}">Edad <span style="color:#ef4444">*</span></label><input type="number" name="edad" required min="16" max="120" style="{{ $inputStyle }}"><p style="font-size:10px;color:#9ca3af;margin-top:3px">Mínimo 16 años</p></div>
                    <div style="grid-column:1/-1"><label style="{{ $labelStyle }}">Dirección <span style="color:#ef4444">*</span></label><input type="text" name="direccion" required style="{{ $inputStyle }}"></div>
                    <div style="grid-column:1/-1"><label style="{{ $labelStyle }}">Contraseña <span style="color:#ef4444">*</span></label><input type="password" name="password" required minlength="6" style="{{ $inputStyle }}"><p style="font-size:10px;color:#9ca3af;margin-top:3px">Mínimo 6 caracteres</p></div>
                    <div style="grid-column:1/-1"><label style="{{ $labelStyle }}">Notas Adicionales</label><textarea name="notas_adicionales" rows="3" style="{{ $inputStyle }};resize:vertical"></textarea></div>
                </div>
                <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:16px">
                    <button type="button" id="btn-cancelar-cliente" style="padding:8px 18px;background:#f3f4f6;border:1.5px solid #e5e7eb;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer">Cancelar</button>
                    <button type="submit" style="padding:8px 18px;background:linear-gradient(135deg,#22c55e,#16a34a);color:#fff;border:none;border-radius:8px;font-size:12px;font-weight:700;cursor:pointer">✅ Guardar Cliente</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Asegurar que fecha_hora se actualice antes de enviar el formulario
        document.addEventListener('DOMContentLoaded', function() {
            const citaForm = document.getElementById('cita-form');
            
            if (citaForm) {
                citaForm.addEventListener('submit', function(e) {
                    const fechaCita = document.getElementById('fecha_cita').value;
                    const horaCita = document.getElementById('hora_cita').value;
                    
                    console.log('Antes de enviar - Fecha input:', fechaCita);
                    console.log('Antes de enviar - Hora input:', horaCita);
                    
                    if (fechaCita && horaCita) {
                        const fechaHoraCombined = `${fechaCita} ${horaCita}:00`;
                        document.getElementById('fecha_hora_combined').value = fechaHoraCombined;
                        console.log('Fecha/hora combinada antes de enviar:', fechaHoraCombined);
                    } else {
                        console.error('Fecha u hora vacía!', {fechaCita, horaCita});
                    }
                });
            }
        });

        // Esperar a que el DOM esté completamente cargado
        window.addEventListener('load', function() {
            // Modal crear cliente
            const modalCrearCliente = document.getElementById('modal-crear-cliente');
            const btnAbrirModal = document.getElementById('btn-crear-cliente-modal');
            
            // Solo ejecutar si el botón existe (solo para admin/empleado)
            if (!btnAbrirModal) {
                console.log('Botón crear cliente no disponible (usuario es cliente)');
                return;
            }
            
            const btnCerrarModal = document.getElementById('btn-cerrar-modal-cliente');
            const btnCancelarCliente = document.getElementById('btn-cancelar-cliente');
            const formCrearCliente = document.getElementById('form-crear-cliente');
            const errorDiv = document.getElementById('error-crear-cliente');
            const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

            console.log('Inicializando modal crear cliente...');

            // Abrir modal
            btnAbrirModal.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                console.log('Abriendo modal crear cliente');
                modalCrearCliente.style.display = 'flex';
                errorDiv.classList.add('hidden');
                formCrearCliente.reset();
            });

            // Cerrar modal
            function cerrarModal() {
                modalCrearCliente.style.display = 'none';
            }
            
            btnCerrarModal.addEventListener('click', cerrarModal);
            btnCancelarCliente.addEventListener('click', cerrarModal);

        // Cerrar al hacer clic fuera del modal
        modalCrearCliente.addEventListener('click', (e) => {
            if (e.target === modalCrearCliente) {
                cerrarModal();
            }
        });

        // Enviar formulario
        formCrearCliente.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const formData = new FormData(formCrearCliente);
            const submitBtn = formCrearCliente.querySelector('button[type="submit"]');
            
            submitBtn.disabled = true;
            submitBtn.textContent = 'Guardando...';
            errorDiv.classList.add('hidden');

            try {
                const response = await fetch('{{ route("clientes.store") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: formData
                });

                const data = await response.json();

                if (!response.ok) {
                    // Manejar errores de validación
                    if (data.errors) {
                        const errores = Object.values(data.errors).flat().join(', ');
                        throw new Error(errores);
                    }
                    throw new Error(data.message || 'Error al crear el cliente');
                }

                // Cliente creado exitosamente
                const nuevoCliente = data.data;
                
                // Añadir cliente al select oculto
                const selectCliente = document.getElementById('id_cliente');
                const option = document.createElement('option');
                option.value = nuevoCliente.id;
                option.setAttribute('data-nombre', (nuevoCliente.nombre_completo || nuevoCliente.nombre + ' ' + nuevoCliente.apellidos).toLowerCase());
                option.setAttribute('data-email', (nuevoCliente.email || '').toLowerCase());
                option.textContent = nuevoCliente.nombre_completo || nuevoCliente.nombre + ' ' + nuevoCliente.apellidos;
                selectCliente.appendChild(option);
                selectCliente.value = nuevoCliente.id;

                // Añadir cliente a la lista visual
                const clientesList = document.getElementById('clientes-list');
                const divCliente = document.createElement('div');
                divCliente.className = 'cliente-item p-3 border-b border-gray-200 hover:bg-blue-50 cursor-pointer transition';
                divCliente.setAttribute('data-cliente-id', nuevoCliente.id);
                divCliente.setAttribute('data-nombre', (nuevoCliente.nombre_completo || nuevoCliente.nombre + ' ' + nuevoCliente.apellidos).toLowerCase());
                divCliente.setAttribute('data-email', (nuevoCliente.email || '').toLowerCase());
                divCliente.innerHTML = `
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 rounded-full bg-indigo-500 flex items-center justify-center text-white font-bold">
                            ${nuevoCliente.nombre.charAt(0).toUpperCase()}
                        </div>
                        <div>
                            <p class="font-semibold text-gray-900">${nuevoCliente.nombre_completo || nuevoCliente.nombre + ' ' + nuevoCliente.apellidos}</p>
                            <p class="text-sm text-gray-600">${nuevoCliente.email || 'Sin email'}</p>
                        </div>
                    </div>
                `;
                clientesList.insertBefore(divCliente, clientesList.firstChild);

                // Seleccionar automáticamente el nuevo cliente
                divCliente.click();

                // Cerrar modal
                cerrarModal();
                formCrearCliente.reset();

            } catch (error) {
                errorDiv.classList.remove('hidden');
                errorDiv.querySelector('p').textContent = error.message;
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Guardar Cliente';
            }
        });
        }); // Fin window.load
    </script>

    </div>{{-- /max-width --}}
    </div>{{-- /content --}}
</div>{{-- /main-wrapper --}}
</body>
</html>
