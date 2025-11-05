<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Crear Usuario</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-xl mx-auto bg-white p-8 rounded shadow">
        <h1 class="text-3xl font-bold mb-6">Crear nuevo usuario</h1>

        <form action="{{ route('users.store') }}" method="POST" class="space-y-4">
            @csrf

            <!-- Nombre y Apellidos en línea -->
            <div class="flex space-x-4">
                <div class="flex-1">
                    <label class="block font-semibold mb-1">Nombre:</label>
                    <input type="text" name="nombre" required class="w-full border rounded px-3 py-2 focus:outline-none focus:ring focus:border-blue-300">
                </div>
                <div class="flex-1">
                    <label class="block font-semibold mb-1">Apellidos:</label>
                    <input type="text" name="apellidos" required class="w-full border rounded px-3 py-2 focus:outline-none focus:ring focus:border-blue-300">
                </div>
            </div>

            <div>
                <label class="block font-semibold mb-1">Email:</label>
                <input type="email" name="email" required class="w-full border rounded px-3 py-2 focus:outline-none focus:ring focus:border-blue-300">
            </div>

            <!-- Teléfono y Edad en línea -->
            <div class="flex space-x-4">
                <div class="flex-1">
                    <label class="block font-semibold mb-1">Teléfono:</label>
                    <input type="text" name="telefono" class="w-full border rounded px-3 py-2 focus:outline-none focus:ring focus:border-blue-300">
                </div>
                <div class="flex-1">
                    <label class="block font-semibold mb-1">Edad:</label>
                    <input type="number" name="edad" class="w-full border rounded px-3 py-2 focus:outline-none focus:ring focus:border-blue-300">
                </div>
            </div>

            <div>
                <label class="block font-semibold mb-1">Género:</label>
                <select name="genero" class="w-full border rounded px-3 py-2 focus:outline-none focus:ring focus:border-blue-300">
                    <option value="Masculino">Masculino</option>
                    <option value="Femenino">Femenino</option>
                    <option value="Otro">Otro</option>
                </select>
            </div>

            <div>
                <label class="block font-semibold mb-1">Contraseña:</label>
                <input type="password" name="password" required class="w-full border rounded px-3 py-2 focus:outline-none focus:ring focus:border-blue-300">
            </div>

            <div>
                <label class="block font-semibold mb-1">Rol:</label>
                <select name="rol" id="rol" onchange="mostrarCamposEspecificos()" class="w-full border rounded px-3 py-2 focus:outline-none focus:ring focus:border-blue-300">
                    <option value="">-- Selecciona un rol --</option>
                    <option value="cliente">Cliente</option>
                    <option value="empleado">Empleado</option>
                    <option value="admin">Administrador</option>
                </select>
            </div>

            <!-- Campos específicos para empleados -->
            <div id="campos-empleado" style="display:none;">
                <label for="categoria" class="block font-semibold mb-1">Categoría:</label>
                <select name="categoria" class="w-full border rounded px-3 py-2 focus:outline-none focus:ring focus:border-blue-300">
                    <option value="">-- Seleccione --</option>
                    <option value="peluqueria">Peluquería</option>
                    <option value="estetica">Estética</option>
                </select>
            </div>

            <!-- Campos específicos para Clientes -->
            <div id="campos-cliente" style="display:none;">
                <div class="mt-4">
                    <label class="block font-semibold mb-1">Dirección:</label>
                    <input type="text" name="direccion" class="w-full border rounded px-3 py-2 focus:outline-none focus:ring focus:border-blue-300">
                </div>
                <div class="mt-4">
                    <label class="block font-semibold mb-1">Fecha de Registro</label>
                    <input type="date" name="fecha_registro" value="{{ date('Y-m-d') }}" readonly class="w-full border rounded px-3 py-2 bg-gray-100">
                </div>
                <div class="mt-4">
                    <label class="block font-semibold mb-1">Notas Adicionales:</label>
                    <textarea name="notas_adicionales" rows="4" class="w-full border rounded px-3 py-2 focus:outline-none focus:ring focus:border-blue-300"></textarea>
                </div>
            </div>
            
            <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700 font-semibold">Guardar</button>
        </form>

        <div class="mt-6">
            <a href="{{ route('users.index') }}" class="text-blue-600 hover:underline">Volver a la lista</a>
        </div>
    </div>

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
            mostrarCamposEspecificos();
        });
    </script>
</body>
</html>
