<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Lista de Cobros</title>
    <link rel="stylesheet" href="{{ asset('css/styles.css') }}">
</head>
<body>
    <h1>Registros de Cobro</h1>
    <a href="{{ route('Cobros.create') }}">Registrar nuevo cobro</a>

    <table>
        <thead>
            <tr>
                <th>Cliente</th>
                <th>Empleado</th>
                <th>Servicio</th>
                <th>Coste</th>
                <th>Descuento %</th>
                <th>Descuento €</th>
                <th>Total Final</th>
                <th>Dinero Cliente</th>
                <th>Cambio</th>
                <th>Método Pago</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($cobros as $cobro)
                <tr>
                    <td>{{ $cobro->cita->cliente->usuario->nombre ?? '-' }}</td>
                    <td>{{ $cobro->cita->empleado->usuario->nombre ?? '-' }}</td>
                    <td>{{ $cobro->cita->servicio->nombre ?? '-' }}</td>
                    <td>{{ $cobro->coste }}</td>
                    <td>{{ $cobro->descuento_porcentaje ?? 0 }}%</td>
                    <td>{{ $cobro->descuento_euro ?? 0 }} €</td>
                    <td>{{ $cobro->total_final }} €</td>
                    <td>{{ $cobro->dinero_cliente }} €</td>
                    <td>{{ $cobro->cambio }} €</td>
                    <td>{{ ucfirst($cobro->metodo_pago) }}</td>
                    <td>
                        <a href="{{ route('Cobros.show', $cobro->id) }}">Ver</a>
                        <a href="{{ route('Cobros.edit', $cobro->id) }}">Editar</a>
                        <form action="{{ route('Cobros.destroy', $cobro->id) }}" method="POST" style="display:inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" onclick="return confirm('¿Eliminar este cobro?')">Eliminar</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <a href="{{ route('dashboard') }}">Volver al inicio</a>
</body>
</html>
