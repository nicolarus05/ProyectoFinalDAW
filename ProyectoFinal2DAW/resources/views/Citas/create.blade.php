<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Crear Cita</title>
    <link rel="stylesheet" href="{{ asset('css/styles.css') }}">
</head>
<body>
    <h1>Crear nueva Cita</h1>

    {{-- Visualización de errores --}}
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('Citas.store') }}" method="POST">
        @csrf

        <label for="fecha_hora">Fecha y Hora:</label>
        <input type="datetime-local" name="fecha_hora" required>

        <label for="estado">Estado:</label>
        <select name="estado" required>
            <option value="">Seleccione</option>
            <option value="pendiente">Pendiente</option>
            <option value="confirmada">Confirmada</option>
            <option value="completada">Completada</option>
            <option value="cancelada">Cancelada</option>
        </select>

        <label for="notas_adicionales">Notas adicionales</label>
        <textarea name="notas_adicionales"></textarea>

        <label for="id_cliente">Cliente:</label>
        <select name="id_cliente" required>
            @foreach ($clientes as $cliente)
                <option value="{{ $cliente->id }}">{{ $cliente->usuario->nombre }} {{ $cliente->usuario->apellidos }}</option>
            @endforeach
        </select>

        <label for="id_empleado">Empleado:</label>
        <select name="id_empleado" required>
            @foreach ($empleados as $empleado)
                <option value="{{ $empleado->id }}">{{ $empleado->usuario->nombre }} {{ $empleado->usuario->apellidos }}</option>
            @endforeach
        </select>

        <label for="servicios">Servicios:</label>
        <select name="servicios[]" multiple required>
            @foreach($servicios as $servicio)
                <option value="{{ $servicio->id }}">{{ $servicio->nombre }}</option>
            @endforeach
        </select>

        <br><br>
        <button type="submit">Guardar</button>
    </form>

    <a href="{{ route('Citas.index') }}">Volver</a>
</body>
</html>
