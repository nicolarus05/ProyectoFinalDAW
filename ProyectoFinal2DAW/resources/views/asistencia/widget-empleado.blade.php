@php
    use App\Models\RegistroEntradaSalida;
    use Carbon\Carbon;
    
    $empleado = Auth::user()->empleado;
    $registroActivo = null;
    $estado = 'sin_fichar';
    $horasActuales = null;
    $registrosHoy = [];
    
    if ($empleado) {
        // Buscar si hay un registro activo (entrada sin salida)
        $registroActivo = RegistroEntradaSalida::registroActivoActual($empleado->id);
        
        // Obtener todos los registros del día para mostrar el historial
        $registrosHoy = RegistroEntradaSalida::where('id_empleado', $empleado->id)
            ->whereDate('fecha', Carbon::today())
            ->orderBy('hora_entrada', 'asc')
            ->get();
        
        if ($registroActivo) {
            $estado = 'trabajando';
            $horasActuales = $registroActivo->calcularHorasActuales();
        }
    }
@endphp

<div class="bg-white rounded-lg shadow-md p-6 mb-6">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-2xl font-bold text-gray-800">📅 Asistencia</h2>
        <div class="text-sm text-gray-600">
            {{ Carbon::now()->locale('es')->isoFormat('dddd, D [de] MMMM [de] YYYY') }}
        </div>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 rounded">
            <p class="font-semibold">{{ session('success') }}</p>
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded">
            <p class="font-semibold">{{ session('error') }}</p>
        </div>
    @endif

    @if(!$empleado)
        <div class="text-center py-8 text-gray-600">
            <p>No tienes un perfil de empleado asociado.</p>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Estado actual -->
            <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg p-6 border border-blue-200">
                <h3 class="text-lg font-semibold mb-3 text-blue-900">Estado Actual</h3>
                
                @if($estado === 'sin_fichar')
                    <div class="flex items-center mb-4">
                        <span class="text-4xl mr-3">⚪</span>
                        <div>
                            <p class="text-lg font-semibold text-gray-700">
                                @if($registrosHoy->count() > 0)
                                    Fuera de jornada
                                @else
                                    No has fichado hoy
                                @endif
                            </p>
                            <p class="text-sm text-gray-600">{{ Carbon::now()->format('H:i') }}</p>
                        </div>
                    </div>
                    
                    @if($registrosHoy->count() > 0)
                        <div class="bg-white rounded p-3 mt-3">
                            <p class="text-sm text-gray-600 mb-2">Resumen del día:</p>
                            @php
                                $totalMinutos = 0;
                                foreach($registrosHoy as $reg) {
                                    $horas = $reg->calcularHorasTrabajadas();
                                    if ($horas) {
                                        $totalMinutos += $horas['total_minutos'];
                                    }
                                }
                            @endphp
                            <p class="text-lg font-bold text-blue-600">
                                {{ sprintf('%dh %02dmin', floor($totalMinutos / 60), $totalMinutos % 60) }}
                            </p>
                            <p class="text-xs text-gray-500 mt-1">{{ $registrosHoy->count() }} registro(s)</p>
                        </div>
                    @endif
                @elseif($estado === 'trabajando')
                    <div class="flex items-center mb-4">
                        <span class="text-4xl mr-3 animate-pulse">🟢</span>
                        <div>
                            <p class="text-lg font-semibold text-green-700">Trabajando</p>
                            <p class="text-sm text-gray-600">Desde las {{ Carbon::parse($registroActivo->hora_entrada)->format('H:i') }}</p>
                        </div>
                    </div>
                    <div class="bg-white rounded p-3 mt-3">
                        <p class="text-sm text-gray-600">Tiempo trabajado:</p>
                        <p class="text-2xl font-bold text-blue-600" id="horas-actuales">{{ $horasActuales['formatted'] }}</p>
                    </div>
                @endif
            </div>

            <!-- Acciones -->
            <div class="bg-gradient-to-br from-gray-50 to-gray-100 rounded-lg p-6 border border-gray-200">
                <h3 class="text-lg font-semibold mb-4 text-gray-900">Acciones</h3>
                
                <div class="space-y-3">
                    @if($estado === 'sin_fichar')
                        <form action="{{ route('asistencia.entrada') }}" method="POST">
                            @csrf
                            <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-4 rounded-lg transition-colors duration-200 flex items-center justify-center gap-2 shadow-lg">
                                <span class="text-2xl">🟢</span>
                                <span>Registrar Entrada</span>
                            </button>
                        </form>
                    @elseif($estado === 'trabajando')
                        <form action="{{ route('asistencia.salida') }}" method="POST">
                            @csrf
                            <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-3 px-4 rounded-lg transition-colors duration-200 flex items-center justify-center gap-2 shadow-lg">
                                <span class="text-2xl">🔴</span>
                                <span>Registrar Salida</span>
                            </button>
                        </form>
                    @endif

                    <a href="{{ route('asistencia.mi-historial') }}" class="block w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-4 rounded-lg transition-colors duration-200 text-center">
                        📊 Ver Mi Historial
                    </a>
                </div>
            </div>
        </div>

        <!-- Reloj en tiempo real -->
        <div class="mt-4 text-center">
            <p class="text-sm text-gray-600">Hora actual:</p>
            <p class="text-3xl font-bold text-gray-800" id="reloj-actual">{{ Carbon::now()->format('H:i:s') }}</p>
        </div>
    @endif
</div>

@if($estado === 'trabajando')
<script>
    // Actualizar horas trabajadas cada minuto
    setInterval(function() {
        const entrada = new Date('{{ $registroActivo->fecha }} {{ $registroActivo->hora_entrada }}');
        const ahora = new Date();
        const diff = ahora - entrada;
        
        const horas = Math.floor(diff / (1000 * 60 * 60));
        const minutos = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
        
        document.getElementById('horas-actuales').textContent = `${horas}h ${String(minutos).padStart(2, '0')}min`;
    }, 60000); // Actualizar cada minuto

    // Actualizar reloj cada segundo
    setInterval(function() {
        const ahora = new Date();
        const horas = String(ahora.getHours()).padStart(2, '0');
        const minutos = String(ahora.getMinutes()).padStart(2, '0');
        const segundos = String(ahora.getSeconds()).padStart(2, '0');
        document.getElementById('reloj-actual').textContent = `${horas}:${minutos}:${segundos}`;
    }, 1000);
</script>
@else
<script>
    // Actualizar reloj cada segundo
    setInterval(function() {
        const ahora = new Date();
        const horas = String(ahora.getHours()).padStart(2, '0');
        const minutos = String(ahora.getMinutes()).padStart(2, '0');
        const segundos = String(ahora.getSeconds()).padStart(2, '0');
        document.getElementById('reloj-actual').textContent = `${horas}:${minutos}:${segundos}`;
    }, 1000);
</script>
@endif
