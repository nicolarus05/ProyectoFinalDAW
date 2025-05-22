<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel de Control</title>
    <link rel="stylesheet" href="{{ asset('css/styles.css') }}">
</head>
<body>
    <h1>Panel de Control</h1>

    <p>Bienvenido, {{ Auth::user()->nombre }} {{ Auth::user()->apellidos }} ({{ Auth::user()->rol }})</p>

    @php
        $rol = Auth::user()->rol;
    @endphp

    <ul>
        {{-- Panel del ADMIN --}}
        @if ($rol === 'admin')
            <li><a href="{{ route('Usuarios.index') }}">Gestionar Usuarios</a></li>
            <li><a href="{{ route('Clientes.index') }}">Gestionar Clientes</a></li>
            <li><a href="{{ route('Empleados.index') }}">Gestionar Empleados</a></li>
            <li><a href="{{ route('Servicios.index') }}">Gestionar Servicios</a></li>
            <li><a href="{{ route('Horarios.index') }}">Gestionar Horarios</a></li>
            <li><a href="{{ route('Cobros.index') }}">Registro de Cobros</a></li>
            <li><a href="{{ route('Citas.index') }}">Todas las Citas</a></li>
        @endif

        {{-- Panel del EMPLEADO --}}
        @if ($rol === 'empleado')
            <li><a href="{{ route('Citas.index') }}">Mis Citas</a></li>
            <li><a href="{{ route('Servicios.index') }}">Servicios</a></li>
        @endif

        {{-- Panel del CLIENTE --}}
        @if ($rol === 'cliente')
            <li><a href="{{ route('Citas.create') }}">Reservar Cita</a></li>
            <li><a href="{{ route('Citas.index') }}">Mis Citas</a></li>
        @endif
    </ul>

    <br>
    <a href="{{ route('profile.edit') }}">Editar Perfil</a>
    <br>
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit">Cerrar sesión</button>
    </form>
</body>
</html>
