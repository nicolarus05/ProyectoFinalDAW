<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Usuario</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
    <h1>Editar Usuario</h1>

    {{-- Mostrar errores de validación --}}
    @if ($errors->any())
        <div style="color: red;">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('users.update', $user->id) }}" method="POST">
        @csrf
        @method('PUT')

        <!-- Campos generales -->
        <label>Nombre:</label>
        <input type="text" name="nombre" value="{{ old('nombre', $user->nombre) }}" required><br>

        <label>Apellidos:</label>
        <input type="text" name="apellidos" value="{{ old('apellidos', $user->apellidos) }}" required><br>

        <label>Email:</label>
        <input type="email" name="email" value="{{ old('email', $user->email) }}" required><br>

        <label>Teléfono:</label>
        <input type="text" name="telefono" value="{{ old('telefono', $user->telefono) }}"><br>

        <label>Edad:</label>
        <input type="number" name="edad" value="{{ old('edad', $user->edad) }}"><br>

        <label>Género:</label>
        <select name="genero">
            <option value="">-- Selecciona --</option>
            <option value="Masculino" {{ old('genero', $user->genero) == 'Masculino' ? 'selected' : '' }}>Masculino</option>
            <option value="Femenino" {{ old('genero', $user->genero) == 'Femenino' ? 'selected' : '' }}>Femenino</option>
            <option value="Otro" {{ old('genero', $user->genero) == 'Otro' ? 'selected' : '' }}>Otro</option>
        </select><br>

        <label>Contraseña (solo si deseas cambiarla):</label>
        <input type="password" name="password"><br>

        <label>Rol:</label>
        <select name="rol" id="rol" onchange="mostrarCamposEspecificos()">
            <option value="">-- Selecciona un rol --</option>
            <option value="cliente" {{ old('rol', $user->rol) == 'cliente' ? 'selected' : '' }}>Cliente</option>
            <option value="empleado" {{ old('rol', $user->rol) == 'empleado' ? 'selected' : '' }}>Empleado</option>
        </select><br>

        <!-- Campos específicos para empleados -->
        <div id="campos-empleado" style="display: none;">
            <label for="especializacion">Especialización:</label>
            <select name="especializacion">
                <option value="">-- Seleccione --</option>
                <option value="Esteticien" {{ old('especializacion', optional($user->empleado)->especializacion) == 'Esteticien' ? 'selected' : '' }}>Esteticista</option>
                <option value="Peluquera" {{ old('especializacion', optional($user->empleado)->especializacion) == 'Peluquera' ? 'selected' : '' }}>Peluquera</option>
            </select>
        </div>

        <!-- Campos específicos para clientes -->
        <div id="campos-cliente" style="display: none;">
            <label>Dirección:</label>
            <input type="text" name="direccion" value="{{ old('direccion', optional($user->cliente)->direccion) }}"><br>

            <label>Fecha de Registro:</label>
            <input type="date" name="fecha_registro" value="{{ old('fecha_registro', optional($user->cliente)->fecha_registro) }}"><br>

            <label>Notas Adicionales:</label>
            <textarea name="notas_adicionales" rows="4" cols="50">{{ old('notas_adicionales', optional($user->cliente)->notas_adicionales) }}</textarea><br>
        </div>

        <button type="submit">Actualizar</button>
    </form>

    <a href="{{ route('users.index') }}">Volver a la lista</a>

    <script>
        function mostrarCamposEspecificos() {
            const rol = document.getElementById('rol').value;

            const clienteFields = document.getElementById('campos-cliente');
            const empleadoFields = document.getElementById('campos-empleado');

            clienteFields.style.display = 'none';
            empleadoFields.style.display = 'none';

            if (rol === 'cliente') {
                clienteFields.style.display = 'block';
            } else if (rol === 'empleado') {
                empleadoFields.style.display = 'block';
            }
        }

        // Mostrar los campos correspondientes al cargar la página
        document.addEventListener('DOMContentLoaded', function () {
            mostrarCamposEspecificos();
        });
    </script>
</body>
</html>
