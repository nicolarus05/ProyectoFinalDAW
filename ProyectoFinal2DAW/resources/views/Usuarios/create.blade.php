<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Crear Usuario</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
    <h1>Crear nuevo usuario</h1>

    <form action="{{ route('Usuarios.store') }}" method="POST">
        @csrf
        <!--Campos Generales de todos los usuarios -->

        <label>Nombre:</label>
        <input type="text" name="nombre" required><br>

        <label>Apellidos:</label>
        <input type="text" name="apellidos" required><br>

        <label>Email:</label>
        <input type="email" name="email" required><br>

        <label>Teléfono:</label>
        <input type="text" name="telefono"><br>

        <label>Edad:</label>
        <input type="number" name="edad"><br>

        <label>Género:</label>
        <select name="genero">
            <option value="Masculino">Masculino</option>
            <option value="Femenino">Femenino</option>
            <option value="Otro">Otro</option>
        </select><br>

        <label>Contraseña:</label>
        <input type="password" name="password" required><br>

        <label>Rol:</label>
        <select name="rol" id="rol" onchange="mostrarCamposEspecificos()">
            <option value="">-- Selecciona un rol --</option>
            <option value="cliente">Cliente</option>
            <option value="empleado">Empleado</option>
            <option value="admin">Administrador</option>
        </select><br>

        <!-- Campos específicos para empleados -->
        <div id="campos-empleado" style="display:none;">

            <label for="especializacion">Especializacion:</label>
            <select name="especializacion">
                <option value="">-- Seleccione --</option>
                <option value="Esteticien">Esteticista</option>
                <option value="Peluquera">Peluquera</option>
            </select>
        </div>

        <!-- Campos específicos para Clientes -->
        <div id="campos-cliente" style="display:none;">

            <label>Dirección:</label>
            <input type="text" name="direccion"><br>

            <label>Fecha de Registro</label>
            <input type="date" name="fecha_registro" value="{{ date('Y-m-d') }}" readonly><br>

            <label>Notas Adicionales:</label>
            <textarea name="notas_adicionales" rows="4" cols="50"></textarea><br>
        </div>
        
        <button type="submit">Guardar</button>
    </form>

    <a href="{{ route('Usuarios.index') }}">Volver a la lista</a>

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

        document.addEventListener('DOMContentLoaded', function () {
            mostrarCamposEspecificos(); // por si ya está preseleccionado
        });
    </script>
</body>
</html>
