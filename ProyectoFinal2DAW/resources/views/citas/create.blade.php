<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Crear Cita - Flujo Rápido</title>
    {!! vite_asset(['resources/css/app.css', 'resources/js/app.js', 'resources/css/citas-create.css', 'resources/js/citas-create.js']) !!}
</head>
<body class="bg-gray-50">
    
    <!-- Contenedor Principal -->
    <div class="max-w-7xl mx-auto px-4 py-6">
        
        <!-- Header con Progreso -->
        <div class="mb-8">
            <div class="flex items-center justify-between mb-4">
                <h1 class="text-3xl font-bold text-gray-900">✨ Crear Nueva Cita</h1>
                <a href="{{ route('citas.index') }}" class="text-gray-600 hover:text-gray-900 font-semibold">
                    ← Volver al calendario
                </a>
            </div>
            
            <!-- Barra de Progreso -->
            <div class="flex items-center justify-between mb-2">
                <div class="flex items-center space-x-4 w-full">
                    <div class="flex-1 flex items-center">
                        <div id="step-1-indicator" class="step-indicator active">1</div>
                        <div class="flex-1 h-1 bg-gray-300 mx-2">
                            <div id="progress-1" class="h-full bg-blue-600 transition-all duration-300" style="width: 0%"></div>
                        </div>
                    </div>
                    <div class="flex-1 flex items-center">
                        <div id="step-2-indicator" class="step-indicator">2</div>
                        <div class="flex-1 h-1 bg-gray-300 mx-2">
                            <div id="progress-2" class="h-full bg-blue-600 transition-all duration-300" style="width: 0%"></div>
                        </div>
                    </div>
                    <div id="step-3-indicator" class="step-indicator">3</div>
                </div>
            </div>
            <div class="flex justify-between text-sm text-gray-600">
                <span id="step-1-label" class="font-semibold text-blue-600">1. Servicios</span>
                <span id="step-2-label">2. Empleado y Hora</span>
                <span id="step-3-label">3. Confirmar</span>
            </div>
        </div>

        {{-- Mensajes de Error --}}
        @if ($errors->any())
            <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded">
                <div class="flex items-center mb-2">
                    <svg class="w-5 h-5 text-red-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"/>
                    </svg>
                    <strong class="text-red-800">Error al crear la cita</strong>
                </div>
                <ul class="list-disc list-inside text-red-700 text-sm">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form id="cita-form" action="{{ route('citas.store') }}" method="POST">
            @csrf
            
            <!-- PASO 1: Seleccionar Servicios -->
            <div id="step-1" class="step-content active">
                <div class="bg-white rounded-lg shadow-md p-6">
                    
                    <!-- Header del Paso -->
                    <div class="mb-6">
                        <h2 class="text-2xl font-bold text-gray-900 mb-2">🔍 Buscar y Seleccionar Servicios</h2>
                        <p class="text-gray-600">Selecciona uno o más servicios para la cita</p>
                    </div>

                    <!-- Barra de Búsqueda y Filtros -->
                    <div class="mb-6 space-y-4">
                        <div class="flex gap-4 flex-wrap">
                            <!-- Búsqueda -->
                            <div class="flex-1 min-w-[300px]">
                                <div class="relative">
                                    <input type="text" 
                                           id="search-servicios" 
                                           placeholder="🔍 Buscar servicios por nombre..."
                                           class="w-full pl-10 pr-4 py-3 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:outline-none">
                                    <svg class="absolute left-3 top-3.5 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                    </svg>
                                </div>
                            </div>
                            
                            <!-- Filtro por Categoría -->
                            <div class="w-48">
                                <select id="filter-categoria" class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:outline-none">
                                    <option value="">Todas las categorías</option>
                                    <option value="peluqueria">✂️ Peluquería</option>
                                    <option value="estetica">💆 Estética</option>
                                </select>
                            </div>
                            
                            <!-- Botón Limpiar -->
                            <button type="button" id="clear-filters" class="px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition font-semibold">
                                Limpiar
                            </button>
                        </div>
                        
                        <!-- Contador de resultados -->
                        <div class="text-sm text-gray-600">
                            <span id="services-count">{{ $servicios->count() }}</span> servicios disponibles
                            <span id="selected-count" class="ml-4 font-semibold text-blue-600"></span>
                        </div>
                    </div>

                    <!-- Grid de Servicios -->
                    <div id="servicios-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
                        @foreach($servicios as $servicio)
                            <div class="servicio-card" 
                                 data-id="{{ $servicio->id }}"
                                 data-nombre="{{ strtolower($servicio->nombre) }}"
                                 data-categoria="{{ $servicio->categoria }}"
                                 data-precio="{{ $servicio->precio }}"
                                 data-tiempo="{{ $servicio->tiempo_estimado }}">
                                <div class="border-2 border-gray-200 rounded-lg p-4 cursor-pointer hover:border-blue-400 hover:shadow-md transition-all duration-200">
                                    <div class="flex items-start justify-between mb-2">
                                        <h3 class="font-bold text-gray-900 flex-1">{{ $servicio->nombre }}</h3>
                                        <span class="categoria-badge {{ $servicio->categoria }}">
                                            {{ $servicio->categoria === 'peluqueria' ? '✂️' : '💆' }}
                                        </span>
                                    </div>
                                    <div class="text-sm text-gray-600 space-y-1">
                                        <p>⏱️ {{ $servicio->tiempo_estimado }} min</p>
                                        <p class="font-semibold text-blue-600">💰 {{ number_format($servicio->precio, 2) }} €</p>
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
                    <div id="selected-services-container" class="hidden mb-6 p-4 bg-blue-50 border-2 border-blue-200 rounded-lg">
                        <h3 class="font-bold text-gray-900 mb-3">✓ Servicios Seleccionados</h3>
                        <div id="selected-services-list" class="space-y-2"></div>
                        <div class="mt-4 pt-4 border-t border-blue-200">
                            <div class="flex justify-between text-sm">
                                <span>Tiempo Total:</span>
                                <span id="total-tiempo" class="font-semibold">0 min</span>
                            </div>
                            <div class="flex justify-between text-lg font-bold text-blue-600 mt-2">
                                <span>Total:</span>
                                <span id="total-precio">0.00 €</span>
                            </div>
                        </div>
                    </div>

                    <!-- Botón Continuar -->
                    <div class="flex justify-end">
                        <button type="button" id="btn-next-step-2" disabled
                                class="px-8 py-3 bg-blue-600 text-white rounded-lg font-semibold hover:bg-blue-700 transition disabled:bg-gray-300 disabled:cursor-not-allowed">
                            Continuar → Seleccionar Empleado
                        </button>
                    </div>
                </div>
            </div>

            <!-- PASO 2: Seleccionar Empleado y Fecha/Hora -->
            <div id="step-2" class="step-content">
                <div class="bg-white rounded-lg shadow-md p-6">
                    
                    <!-- Header del Paso -->
                    <div class="mb-6">
                        <h2 class="text-2xl font-bold text-gray-900 mb-2">👤 Empleado, Fecha y Hora</h2>
                        <p class="text-gray-600">Selecciona quién realizará los servicios y cuándo</p>
                    </div>

                    <div class="grid md:grid-cols-2 gap-6">
                        
                        <!-- Columna Izquierda: Cliente y Empleado -->
                        <div class="space-y-6">
                            
                            @if(Auth::user()->rol === 'cliente')
                                <!-- Cliente autenticado -->
                                <input type="hidden" name="id_cliente" value="{{ $clientes->id }}">
                                <div class="p-4 bg-gray-50 rounded-lg border-2 border-gray-200">
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Cliente</label>
                                    <div class="flex items-center space-x-3">
                                        <div class="w-12 h-12 bg-blue-500 rounded-full flex items-center justify-center text-white font-bold text-lg">
                                            {{ strtoupper(substr($clientes->user->nombre ?? '', 0, 1)) }}
                                        </div>
                                        <div>
                                            <p class="font-semibold text-gray-900">{{ $clientes->user->nombre }} {{ $clientes->user->apellidos }}</p>
                                            <p class="text-sm text-gray-600">{{ $clientes->user->email ?? '' }}</p>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <!-- Admin o empleado: selección de cliente con buscador -->
                                <div>
                                    <label for="search-cliente" class="block text-sm font-semibold text-gray-700 mb-2">
                                        Cliente <span class="text-red-500">*</span>
                                    </label>
                                    
                                    <!-- Buscador de Cliente -->
                                    <div class="relative mb-3">
                                        <input type="text" 
                                               id="search-cliente" 
                                               placeholder="🔍 Buscar cliente por nombre o email..."
                                               class="w-full pl-10 pr-4 py-3 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:outline-none"
                                               autocomplete="off">
                                        <svg class="absolute left-3 top-3.5 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                        </svg>
                                    </div>
                                    
                                    <!-- Select oculto para el formulario -->
                                    <select name="id_cliente" id="id_cliente" required class="hidden">
                                        <option value="">-- Seleccione un cliente --</option>
                                        @foreach($clientes as $cliente)
                                            <option value="{{ $cliente->id }}"
                                                    data-nombre="{{ strtolower($cliente->user->nombre . ' ' . $cliente->user->apellidos) }}"
                                                    data-email="{{ strtolower($cliente->user->email ?? '') }}">
                                                {{ $cliente->user->nombre }} {{ $cliente->user->apellidos }}
                                            </option>
                                        @endforeach
                                    </select>
                                    
                                    <!-- Lista de clientes filtrable -->
                                    <div id="clientes-list" class="border-2 border-gray-300 rounded-lg max-h-64 overflow-y-auto">
                                        @foreach($clientes as $cliente)
                                            <div class="cliente-item p-3 border-b border-gray-200 hover:bg-blue-50 cursor-pointer transition"
                                                 data-cliente-id="{{ $cliente->id }}"
                                                 data-nombre="{{ strtolower($cliente->user->nombre . ' ' . $cliente->user->apellidos) }}"
                                                 data-email="{{ strtolower($cliente->user->email ?? '') }}">
                                                <div class="flex items-center space-x-3">
                                                    <div class="w-10 h-10 rounded-full bg-indigo-500 flex items-center justify-center text-white font-bold">
                                                        {{ strtoupper(substr($cliente->user->nombre ?? '', 0, 1)) }}
                                                    </div>
                                                    <div>
                                                        <p class="font-semibold text-gray-900">{{ $cliente->user->nombre }} {{ $cliente->user->apellidos }}</p>
                                                        <p class="text-sm text-gray-600">{{ $cliente->user->email ?? 'Sin email' }}</p>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                    
                                    <!-- Cliente seleccionado -->
                                    <div id="selected-cliente" class="mt-3 p-4 bg-blue-50 border-2 border-blue-300 rounded-lg hidden">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center space-x-3">
                                                <div class="w-10 h-10 rounded-full bg-indigo-500 flex items-center justify-center text-white font-bold">
                                                    <span id="selected-cliente-inicial"></span>
                                                </div>
                                                <div>
                                                    <p class="font-semibold text-gray-900" id="selected-cliente-nombre"></p>
                                                    <p class="text-sm text-gray-600" id="selected-cliente-email"></p>
                                                </div>
                                            </div>
                                            <button type="button" id="clear-cliente" class="text-red-600 hover:text-red-800 font-semibold">
                                                ✕ Cambiar
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <!-- Selección de Empleado -->
                            <div>
                                <label for="id_empleado" class="block text-sm font-semibold text-gray-700 mb-2">
                                    Empleado <span class="text-red-500">*</span>
                                </label>
                                <div id="empleados-container" class="space-y-2">
                                    @foreach($empleados as $empleado)
                                        <label class="empleado-option block p-4 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-blue-400 hover:bg-blue-50 transition"
                                               data-empleado-id="{{ $empleado->id }}"
                                               data-categoria="{{ $empleado->categoria }}">
                                            <input type="radio" name="id_empleado" value="{{ $empleado->id }}" 
                                                   class="hidden empleado-radio" required
                                                   {{ request('empleado_id') == $empleado->id ? 'checked' : '' }}>
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center space-x-3">
                                                    <div class="w-10 h-10 rounded-full flex items-center justify-center text-white font-bold
                                                                {{ $empleado->categoria === 'peluqueria' ? 'bg-blue-500' : 'bg-pink-500' }}">
                                                        {{ strtoupper(substr($empleado->user->nombre ?? '', 0, 1)) }}
                                                    </div>
                                                    <div>
                                                        <p class="font-semibold text-gray-900">{{ $empleado->user->nombre }} {{ $empleado->user->apellidos }}</p>
                                                        <p class="text-sm text-gray-600">
                                                            {{ $empleado->categoria === 'peluqueria' ? '✂️ Peluquería' : '💆 Estética' }}
                                                        </p>
                                                    </div>
                                                </div>
                                                <div class="radio-checkmark">
                                                    <svg width="24" height="24" viewBox="0 0 24 24">
                                                        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" fill="none"/>
                                                        <circle cx="12" cy="12" r="6" fill="currentColor" class="inner-circle"/>
                                                    </svg>
                                                </div>
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <!-- Columna Derecha: Fecha y Hora -->
                        <div class="space-y-6">
                            
                            <!-- Fecha -->
                            <div>
                                <label for="fecha_cita" class="block text-sm font-semibold text-gray-700 mb-2">
                                    Fecha <span class="text-red-500">*</span>
                                </label>
                                <input type="date" 
                                       id="fecha_cita" 
                                       name="fecha_cita"
                                       min="{{ \Carbon\Carbon::now()->format('Y-m-d') }}"
                                       value="{{ request('fecha_hora') ? \Carbon\Carbon::parse(request('fecha_hora'))->format('Y-m-d') : \Carbon\Carbon::now()->format('Y-m-d') }}"
                                       required
                                       class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:outline-none">
                            </div>

                            <!-- Hora -->
                            <div>
                                <label for="hora_cita" class="block text-sm font-semibold text-gray-700 mb-2">
                                    Hora <span class="text-red-500">*</span>
                                </label>
                                <input type="time" 
                                       id="hora_cita" 
                                       name="hora_cita"
                                       value="{{ request('fecha_hora') ? \Carbon\Carbon::parse(request('fecha_hora'))->format('H:i') : '' }}"
                                       required
                                       class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:outline-none">
                                <p class="text-xs text-gray-500 mt-1">Horario disponible: 08:00 - 20:00</p>
                            </div>

                            <!-- Notas Adicionales -->
                            <div>
                                <label for="notas_adicionales" class="block text-sm font-semibold text-gray-700 mb-2">
                                    Notas adicionales (opcional)
                                </label>
                                <textarea name="notas_adicionales" 
                                          id="notas_adicionales"
                                          rows="4"
                                          placeholder="Comentarios especiales, preferencias, alergias..."
                                          class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:outline-none resize-none"></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Botones de Navegación -->
                    <div class="flex justify-between mt-6 pt-6 border-t">
                        <button type="button" id="btn-back-step-1"
                                class="px-6 py-3 bg-gray-200 text-gray-700 rounded-lg font-semibold hover:bg-gray-300 transition">
                            ← Volver a Servicios
                        </button>
                        <button type="button" id="btn-next-step-3"
                                class="px-8 py-3 bg-blue-600 text-white rounded-lg font-semibold hover:bg-blue-700 transition">
                            Continuar → Confirmar
                        </button>
                    </div>
                </div>
            </div>

            <!-- PASO 3: Confirmar -->
            <div id="step-3" class="step-content">
                <div class="bg-white rounded-lg shadow-md p-6">
                    
                    <!-- Header del Paso -->
                    <div class="mb-6">
                        <h2 class="text-2xl font-bold text-gray-900 mb-2">✓ Confirmar Cita</h2>
                        <p class="text-gray-600">Revisa los detalles antes de guardar</p>
                    </div>

                    <!-- Resumen de la Cita -->
                    <div class="space-y-6">
                        
                        <!-- Servicios -->
                        <div class="p-6 bg-blue-50 border-2 border-blue-200 rounded-lg">
                            <h3 class="font-bold text-gray-900 mb-4 flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/>
                                    <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z"/>
                                </svg>
                                Servicios Seleccionados
                            </h3>
                            <div id="confirm-services-list" class="space-y-2"></div>
                            <div class="mt-4 pt-4 border-t border-blue-300">
                                <div class="flex justify-between text-sm mb-1">
                                    <span>Tiempo Total:</span>
                                    <span id="confirm-tiempo" class="font-semibold"></span>
                                </div>
                                <div class="flex justify-between text-xl font-bold text-blue-600">
                                    <span>Total a Pagar:</span>
                                    <span id="confirm-precio"></span>
                                </div>
                            </div>
                        </div>

                        <!-- Detalles de la Cita -->
                        <div class="grid md:grid-cols-2 gap-6">
                            
                            <!-- Cliente y Empleado -->
                            <div class="space-y-4">
                                <div class="p-4 bg-gray-50 rounded-lg border-2 border-gray-200">
                                    <p class="text-sm font-semibold text-gray-700 mb-2">Cliente</p>
                                    <p id="confirm-cliente" class="font-semibold text-gray-900"></p>
                                </div>
                                
                                <div class="p-4 bg-gray-50 rounded-lg border-2 border-gray-200">
                                    <p class="text-sm font-semibold text-gray-700 mb-2">Empleado</p>
                                    <p id="confirm-empleado" class="font-semibold text-gray-900"></p>
                                </div>
                            </div>

                            <!-- Fecha y Hora -->
                            <div class="space-y-4">
                                <div class="p-4 bg-gray-50 rounded-lg border-2 border-gray-200">
                                    <p class="text-sm font-semibold text-gray-700 mb-2">📅 Fecha</p>
                                    <p id="confirm-fecha" class="font-semibold text-gray-900 text-lg"></p>
                                </div>
                                
                                <div class="p-4 bg-gray-50 rounded-lg border-2 border-gray-200">
                                    <p class="text-sm font-semibold text-gray-700 mb-2">🕐 Hora</p>
                                    <p id="confirm-hora" class="font-semibold text-gray-900 text-lg"></p>
                                </div>
                            </div>
                        </div>

                        <!-- Notas -->
                        <div id="confirm-notas-container" class="hidden p-4 bg-yellow-50 border-2 border-yellow-200 rounded-lg">
                            <p class="text-sm font-semibold text-gray-700 mb-2">📝 Notas</p>
                            <p id="confirm-notas" class="text-gray-900"></p>
                        </div>
                    </div>

                    <!-- Hidden field for datetime -->
                    <input type="hidden" name="fecha_hora" id="fecha_hora_combined">

                    <!-- Inputs ocultos para servicios (se llenarán dinámicamente) -->
                    <div id="hidden-services-inputs"></div>

                    <!-- Botones de Navegación -->
                    <div class="flex justify-between mt-6 pt-6 border-t">
                        <button type="button" id="btn-back-step-2"
                                class="px-6 py-3 bg-gray-200 text-gray-700 rounded-lg font-semibold hover:bg-gray-300 transition">
                            ← Volver a Editar
                        </button>
                        <button type="submit" id="btn-submit"
                                class="px-8 py-3 bg-green-600 text-white rounded-lg font-semibold hover:bg-green-700 transition flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/>
                            </svg>
                            Confirmar y Guardar Cita
                        </button>
                    </div>
                </div>
            </div>

        </form>
    </div>

</body>
</html>
