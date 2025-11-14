<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Clientes</title>
    {!! vite_asset(['resources/css/app.css', 'resources/js/app.js', 'resources/css/clientes.css', 'resources/js/clientes.js']) !!}
</head>
<body class="bg-gray-100 p-6">
    <div class="w-full max-w-none mx-auto bg-white shadow-md rounded p-6">
        <h1 class="text-3xl font-bold mb-4">Clientes registrados</h1>
        <a href="{{ route('clientes.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Añadir un nuevo cliente</a>

        <!-- Barra de búsqueda y controles -->
        <div class="search-container">
            <input 
                type="text" 
                id="searchInput" 
                class="search-input" 
                placeholder="🔍 Buscar por nombre, apellidos, email o teléfono..."
                autocomplete="off"
            >
            
            <button id="sortAscBtn" class="sort-btn active" onclick="sortClients('asc')" title="Ordenar A-Z">
                <span>↑</span> A-Z
            </button>
            
            <button id="sortDescBtn" class="sort-btn" onclick="sortClients('desc')" title="Ordenar Z-A">
                <span>↓</span> Z-A
            </button>
            
            <button id="clearBtn" class="clear-btn" onclick="clearSearch()" title="Limpiar búsqueda">
                ✕ Limpiar
            </button>
            
            <span id="resultsInfo" class="results-info"></span>
        </div>

        <div class="mt-4">
            <table class="w-full table-auto text-sm text-left break-words">
                <thead class="bg-gray-200">
                    <tr>
                        <th class="px-4 py-2">Nombre</th>
                        <th class="px-4 py-2">Apellidos</th>
                        <th class="px-4 py-2">Teléfono</th>
                        <th class="px-4 py-2">Email</th>
                        <th class="px-4 py-2">Genero</th>
                        <th class="px-4 py-2">Edad</th>
                        <th class="px-4 py-2">Direccion</th>
                        <th class="px-4 py-2">Notas</th>
                        <th class="px-4 py-2">Registro</th>
                        <th class="px-4 py-2">Rol</th>
                        <th class="px-4 py-2">Acciones</th>
                    </tr>
                </thead>
                <tbody id="clientsTableBody">
                    @foreach ($clientes as $cliente)
                    <tr class="border-t cliente-row" 
                        data-nombre="{{ strtolower($cliente->user->nombre ?? '') }}"
                        data-apellidos="{{ strtolower($cliente->user->apellidos ?? '') }}"
                        data-email="{{ strtolower($cliente->user->email ?? '') }}"
                        data-telefono="{{ $cliente->user->telefono ?? '' }}"
                        data-fullname="{{ strtolower(($cliente->user->apellidos ?? '') . ' ' . ($cliente->user->nombre ?? '')) }}">
                        <td class="px-4 py-2">{{ $cliente->user->nombre ?? '-' }}</td>
                        <td class="px-4 py-2">{{ $cliente->user->apellidos ?? '-' }}</td>
                        <td class="px-4 py-2">{{ $cliente->user->telefono ?? '-' }}</td>
                        <td class="px-4 py-2">{{ $cliente->user->email ?? '-' }}</td>
                        <td class="px-4 py-2">{{ $cliente->user->genero ?? '-' }}</td>
                        <td class="px-4 py-2">{{ $cliente->user->edad ?? '-' }}</td>
                        <td class="px-4 py-2">{{ $cliente->direccion ?? '-' }}</td>
                        <td class="px-4 py-2">{{ $cliente->notas_adicionales ?? '-' }}</td>
                        <td class="px-4 py-2">{{ $cliente->fecha_registro ?? '-' }}</td>
                        <td class="px-4 py-2">{{ $cliente->user->rol ?? '-' }}</td>
                        <td class="px-4 py-2 space-x-2">
                            <a href="{{ route('clientes.show', $cliente->id) }}" class="text-blue-600 hover:underline">Ver</a>
                            <a href="{{ route('clientes.edit', $cliente->id) }}" class="text-yellow-600 hover:underline">Editar</a>
                            <a href="{{ route('bonos.misClientes', $cliente->id) }}" class="text-purple-600 hover:underline">🎫 Bonos</a>
                            <form id="delete-form-{{ $cliente->id }}" action="{{ route('clientes.destroy', $cliente->id) }}" method="POST" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="button" onclick="confirmarEliminacion({{ $cliente->id }})" class="text-red-600 hover:underline">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <a href="{{ route('dashboard') }}" class="inline-block mt-6 text-gray-700 hover:underline">Volver al Inicio</a>
    </div>
</body>
</html>