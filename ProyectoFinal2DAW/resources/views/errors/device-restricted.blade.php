<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Acceso restringido</title>
    <style>
        :root { color-scheme: light; font-family: Arial, sans-serif; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; background: #f3f4f6; color: #1f2937; }
        .card { width: min(92%, 430px); box-sizing: border-box; padding: 32px 24px; text-align: center; background: #fff; border-radius: 14px; box-shadow: 0 8px 30px rgba(31, 41, 55, .12); }
        .icon { width: 64px; height: 64px; margin: 0 auto 18px; display: grid; place-items: center; border-radius: 50%; background: #fef3c7; font-size: 30px; }
        h1 { margin: 0 0 12px; font-size: 24px; }
        p { margin: 0 0 24px; line-height: 1.5; color: #4b5563; }
        button { border: 0; border-radius: 8px; padding: 11px 18px; background: #7c3aed; color: #fff; font-weight: 700; cursor: pointer; }
        button:hover { background: #6d28d9; }
    </style>
</head>
<body>
    <main class="card" role="alert">
        <div class="icon" aria-hidden="true">💻</div>
        <h1>Acceso desde ordenador requerido</h1>
        <p>Las cuentas de empleado solo pueden acceder al programa desde un ordenador. Abre el sistema desde un equipo de sobremesa o portátil.</p>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit">Cerrar sesión</button>
        </form>
    </main>
</body>
</html>
