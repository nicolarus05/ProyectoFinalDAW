<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Crear Cita</title>
    @vite(['resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-100 flex flex-col items-center justify-center p-6">

    <header class="text-center mb-8">
        <h1 class="text-4xl font-extrabold text-black mb-2">Crear Nueva Cita</h1>
    </header>

    {{-- Visualización de errores --}}
    @if ($errors->any())
        <div class="mb-4 w-full max-w-xl bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
            <strong>¡Ups! Algo salió mal:</strong>
            <ul class="mt-2 list-disc list-inside text-sm">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('Citas.store') }}" method="POST"
          class="bg-white shadow-md rounded px-8 pt-6 pb-8 w-full max-w-xl space-y-4">
        @csrf

        <div>
            <label for="fecha_hora" class="block text-gray-700 font-semibold mb-1">Fecha y Hora:</label>
            <input type="datetime-local" name="fecha_hora" required
                   class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-400">
        </div>

        <div>
            <label for="estado" class="block text-gray-700 font-semibold mb-1">Estado:</label>
            <select name="estado" required
                    class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-400">
                <option value="">Seleccione</option>
                <option value="pendiente">Pendiente</option>
                <option value="confirmada">Confirmada</option>
                <option value="completada">Completada</option>
                <option value="cancelada">Cancelada</option>
            </select>
        </div>

        <div>
            <label for="notas_adicionales" class="block text-gray-700 font-semibold mb-1">Notas adicionales:</label>
            <textarea name="notas_adicionales" rows="3"
                      class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-400"></textarea>
        </div>

        @if(Auth::user()->rol === 'cliente')
            {{-- Cliente autenticado: campo oculto --}}
            <input type="hidden" name="id_cliente" value="{{ $clientes->id }}">
            <p class="text-gray-800 text-sm font-medium">
                Cliente: {{ $clientes->user->nombre }} {{ $clientes->user->apellidos }}
            </p>
        @else
            {{-- Admin o empleado: selección de cliente --}}
            <div>
                <label for="id_cliente" class="block text-gray-700 font-semibold mb-1">Cliente:</label>
                <select name="id_cliente" required
                        class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-400">
                    <option value="">Seleccione un cliente</option>
                    @foreach($clientes as $cliente)
                        <option value="{{ $cliente->id }}">
                            {{ $cliente->user->nombre }} {{ $cliente->user->apellidos }}
                        </option>
                    @endforeach
                </select>
            </div>
        @endif


        <div>
            <label for="id_empleado" class="block text-gray-700 font-semibold mb-1">Empleado:</label>
            <select name="id_empleado" required
                    class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-400">
                @foreach ($empleados as $empleado)
                    <option value="{{ $empleado->id }}">{{ $empleado->user->nombre }} {{ $empleado->user->apellidos }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="servicios" class="block text-gray-700 font-semibold mb-1">Servicios:</label>
            <select name="servicios[]" multiple required
                    class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-400">
                @foreach($servicios as $servicio)
                    <option value="{{ $servicio->id }}">{{ $servicio->nombre }}</option>
                @endforeach
            </select>
        </div>

        <div class="flex justify-between items-center mt-6">
            <a href="{{ route('dashboard') }}"
               class="text-black px-4 py-2 rounded border border-black hover:bg-gray-200 transition-colors duration-300 font-semibold">
                Volver
            </a>
            <button type="submit"
                    class="bg-black text-white px-6 py-2 rounded hover:bg-gray-800 transition-colors duration-300 font-semibold">
                Guardar
            </button>
        </div>
    </form>

</body>
</html>
