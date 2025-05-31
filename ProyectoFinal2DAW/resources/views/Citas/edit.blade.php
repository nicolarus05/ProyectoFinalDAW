<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Cita</title>
    @vite(['resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-100 flex flex-col items-center justify-center p-6">

    <header class="text-center mb-8">
        <h1 class="text-4xl font-extrabold text-black mb-2">Editar Cita</h1>
    </header>

    <form action="{{ route('Citas.update', $cita->id) }}" method="POST"
          class="bg-white shadow-md rounded px-8 pt-6 pb-8 w-full max-w-xl space-y-4">
        @csrf
        @method('PUT')

        <div>
            <label for="fecha_hora" class="block text-gray-700 font-semibold mb-1">Fecha y Hora:</label>
            <input type="datetime-local" name="fecha_hora"
                   value="{{ \Carbon\Carbon::parse($cita->fecha_hora)->format('Y-m-d\TH:i') }}"
                   required
                   class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-400">
        </div>

        <div>
            <label for="estado" class="block text-gray-700 font-semibold mb-1">Estado:</label>
            <select name="estado" required
                    class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-400">
                <option value="pendiente" {{ $cita->estado == 'pendiente' ? 'selected' : '' }}>Pendiente</option>
                <option value="confirmada" {{ $cita->estado == 'confirmada' ? 'selected' : '' }}>Confirmada</option>
                <option value="completada" {{ $cita->estado == 'completada' ? 'selected' : '' }}>Completada</option>
                <option value="cancelada" {{ $cita->estado == 'cancelada' ? 'selected' : '' }}>Cancelada</option>
            </select>
        </div>

        @if(auth()->user()->rol === 'admin')
            <div>
                <label for="id_cliente" class="block text-gray-700 font-semibold mb-1">Cliente:</label>
                <select name="id_cliente" required
                        class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-400">
                    @foreach ($clientes as $cliente)
                        <option value="{{ $cliente->id }}" {{ $cita->id_cliente == $cliente->id ? 'selected' : '' }}>
                            {{ $cliente->user->nombre }} {{ $cliente->user->apellidos }}
                        </option>
                    @endforeach
                </select>
            </div>
        @else
            <input type="hidden" name="id_cliente" value="{{ $cita->id_cliente }}">
            <p class="text-gray-800 text-sm font-medium">Cliente: {{ $cita->cliente->user->nombre }} {{ $cita->cliente->user->apellidos }}</p>
        @endif

        <div>
            <label for="id_empleado" class="block text-gray-700 font-semibold mb-1">Empleado:</label>
            <select name="id_empleado" required
                    class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-400">
                @foreach ($empleados as $empleado)
                    <option value="{{ $empleado->id }}" {{ $cita->id_empleado == $empleado->id ? 'selected' : '' }}>
                        {{ $empleado->user->nombre }} {{ $empleado->user->apellidos }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="id_servicio" class="block text-gray-700 font-semibold mb-1">Servicio:</label>
            <select name="id_servicio" required
                    class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-400">
                @foreach ($servicios as $servicio)
                    <option value="{{ $servicio->id }}" {{ $cita->id_servicio == $servicio->id ? 'selected' : '' }}>
                        {{ $servicio->nombre }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="flex justify-between items-center mt-6">
            <a href="{{ route('Citas.index') }}"
               class="text-black px-4 py-2 rounded border border-black hover:bg-gray-200 transition-colors duration-300 font-semibold">
                Volver
            </a>
            <button type="submit"
                    class="bg-black text-white px-6 py-2 rounded hover:bg-gray-800 transition-colors duration-300 font-semibold">
                Actualizar
            </button>
        </div>
    </form>

</body>
</html>
