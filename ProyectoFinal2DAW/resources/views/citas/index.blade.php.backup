<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Lista de Citas</title>
    @vite(['resources/js/app.js'])
    <script>
        function confirmarEliminacion(id) {
            if (confirm('¿Estás seguro de que quieres eliminar esta cita?')) {
                document.getElementById('delete-form-' + id).submit();
            }
        }
    </script>
</head>
<body class="min-h-screen bg-gray-100 flex flex-col items-center p-6">

    <header class="text-center mb-8">
        <h1 class="text-4xl font-extrabold text-black mb-2">Citas Registradas</h1>
        <a href="{{ route('citas.create') }}"
           class="inline-block bg-black text-white px-4 py-2 rounded hover:bg-gray-800 transition-colors duration-300 font-semibold mt-4">
            Añadir Nueva Cita
        </a>
    </header>

    <div class="overflow-x-auto w-full max-w-7xl bg-white shadow-md rounded-lg">
        <table class="min-w-full divide-y divide-gray-200 text-sm text-gray-800">
            <thead class="bg-gray-50 text-xs uppercase text-gray-600">
                <tr>
                    <th class="px-4 py-3 text-left">Cliente</th>
                    <th class="px-4 py-3 text-left">Empleado</th>
                    <th class="px-4 py-3 text-left">Servicio</th>
                    <th class="px-4 py-3 text-left">Notas Adicionales</th>
                    <th class="px-4 py-3 text-left">Fecha y Hora</th>
                    <th class="px-4 py-3 text-left">Estado</th>
                    <th class="px-4 py-3 text-left">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach ($citas as $cita)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-2">
                            {{ $cita->cliente->user->nombre ?? '-' }} {{ $cita->cliente->user->apellidos ?? '' }}
                        </td>
                        <td class="px-4 py-2">
                            {{ $cita->empleado->user->nombre ?? '-' }} {{ $cita->empleado->user->apellidos ?? '' }}
                        </td>
                        <td class="px-4 py-2">
                            @if ($cita->servicios && count($cita->servicios))
                                @foreach ($cita->servicios as $servicio)
                                    {{ $servicio->nombre }}@if (!$loop->last), @endif
                                @endforeach
                            @else
                                <span class="text-gray-400 italic">No hay servicios</span>
                            @endif
                        </td>
                        <td class="px-4 py-2">{{ $cita->notas_adicionales ?? '-' }}</td>
                        <td class="px-4 py-2">{{ $cita->fecha_hora }}</td>
                        <td class="px-4 py-2">{{ ucfirst($cita->estado) }}</td>
                        <td class="px-4 py-2">
                            <div class="flex flex-col gap-2">
                                <div class="flex flex-wrap gap-2">
                                    <a href="{{ route('citas.show', $cita->id) }}"
                                       class="text-blue-600 hover:underline">Ver</a>
                                    <a href="{{ route('citas.edit', $cita->id) }}"
                                       class="text-yellow-600 hover:underline">Editar</a>
                                    <form id="delete-form-{{ $cita->id }}"
                                          action="{{ route('citas.destroy', $cita->id) }}"
                                          method="POST"
                                          onsubmit="return false;"
                                          class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button"
                                                onclick="confirmarEliminacion({{ $cita->id }})"
                                                class="text-red-600 hover:underline">
                                            Eliminar
                                        </button>
                                    </form>
                                </div>
                                @php
                                    $tieneCobro = DB::table('registro_cobros')->where('id_cita', $cita->id)->exists();
                                @endphp
                                @if(!$tieneCobro)
                                    <a href="{{ route('cobros.create', ['cita_id' => $cita->id]) }}"
                                        class="inline-flex items-center gap-1 text-green-600 hover:underline font-semibold">
                                        <span>💰</span>
                                        <span>Pasar a Caja</span>
                                    </a>
                                @else
                                    <span class="text-green-600 text-sm font-semibold">✓ Cobrada</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-6">
        <a href="{{ route('dashboard') }}"
           class="text-black px-4 py-2 rounded border border-black hover:bg-gray-200 transition-colors duration-300 font-semibold">
            Volver al Inicio
        </a>
    </div>

</body>
</html>
