<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Cita</title>
    <link rel="stylesheet" href="{{ asset('css/styles.css') }}">
</head>
<body>
    <h1>Editar Cita</h1>

    <form action="{{ route('Citas.update', $cita->id) }}" method="POST">
        @csrf
        @method('PUT')

        <label for="fecha_hora">Fecha y Hora:</label>
        <input type="datetime-local" name="fecha_hora" value="{{ \Carbon\Carbon::parse($cita->fecha_hora)->format('Y-m-d\TH:i') }}" required>

        <label for="estado">Estado:</label>
        <select name="estado" required>
            <option value="pendiente" {{ $cita->estado == 'pendiente' ? 'selected' : '' }}>Pendiente</option>
            <option value="confirmada" {{ $cita->estado == 'confirmada' ? 'selected' : '' }}>Confirmada</option>
            <option value="completada" {{ $cita->estado == 'completada' ? 'selected' : '' }}>Completada</option>
            <option value="cancelada" {{ $cita->estado == 'cancelada' ? 'selected' : '' }}>Cancelada</option>
        </select>

        @if(auth()->user()->rol === 'admin')
            <label for="id_cliente">Cliente:</label>
            <select name="id_cliente" required>
                @foreach ($clientes as $cliente)
                    <option value="{{ $cliente->id }}" {{ $cita->id_cliente == $cliente->id ? 'selected' : '' }}>
                        {{ $cliente->usuario->nombre }} {{ $cliente->usuario->apellidos }}
                    </option>
                @endforeach
            </select>
        @else
            <input type="hidden" name="id_cliente" value="{{ $cita->id_cliente }}">
            <p>Cliente: {{ $cita->cliente->usuario->nombre }} {{ $cita->cliente->usuario->apellidos }}</p>
        @endif


        <label for="id_empleado">Empleado:</label>
        <select name="id_empleado" required>
            @foreach ($empleados as $empleado)
                <option value="{{ $empleado->id }}" {{ $cita->id_empleado == $empleado->id ? 'selected' : '' }}>
                    {{ $empleado->usuario->nombre }} {{ $empleado->usuario->apellidos }}
                </option>
            @endforeach
        </select>

        <label for="id_servicio">Servicio:</label>
        <select name="id_servicio" required>
            @foreach ($servicios as $servicio)
                <option value="{{ $servicio->id }}" {{ $cita->id_servicio == $servicio->id ? 'selected' : '' }}>
                    {{ $servicio->nombre }}
                </option>
            @endforeach
        </select>

        <br><br>
        <button type="submit">Actualizar</button>
    </form>

    <a href="{{ route('Citas.index') }}">Volver</a>
</body>
</html>
