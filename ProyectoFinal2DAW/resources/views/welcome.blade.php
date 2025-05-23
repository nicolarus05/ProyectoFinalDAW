<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Bienvenido al Sistema de Gestión</title>
    <link rel="stylesheet" href="{{ asset('css/styles.css') }}">
</head>
<body>
    <h1>Bienvenido a Nuestro Sistema de Gestión de Citas</h1>
    <p>Gestiona clientes, empleados, citas y más de forma sencilla.</p>

    <div class="actions">
        @auth
            <a href="{{ route('dashboard') }}">Ir al Panel</a>
            <form method="POST" action="{{ route('logout') }}" style="display:inline;">
                @csrf
                <button type="submit" style="background:none;border:none;color:#4f46e5;font-weight:bold;cursor:pointer;">Cerrar sesión</button>
            </form>
        @else
            <a href="{{ route('login') }}">Iniciar Sesión</a>
            <a href="{{ route('register.cliente') }}">Registrarse</a>
        @endauth
    </div>
</body>
</html>
