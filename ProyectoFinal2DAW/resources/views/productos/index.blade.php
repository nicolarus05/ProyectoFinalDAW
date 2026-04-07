<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Productos</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto bg-white p-6 rounded shadow">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-3xl font-bold">Productos</h1>
            <div class="flex gap-3">
                <a href="{{ route('productos.create') }}" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">Nuevo producto</a>                <a href="{{ route('productos.exportar') }}" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">&#8595; Exportar CSV</a>                <a href="{{ route('dashboard') }}" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700">← Volver al inicio</a>
            </div>
        </div>

        @if(session('success'))
            <div class="bg-green-100 text-green-700 p-4 mb-4 rounded">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="bg-red-100 text-red-700 p-4 mb-4 rounded">
                <strong>Errores encontrados:</strong>
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Barra de búsqueda -->
        <div class="mb-4">
            <input type="text" 
                   id="buscar-producto" 
                   placeholder="🔍 Buscar por nombre, categoría o descripción..."
                   class="w-full border border-gray-300 rounded px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                   autocomplete="off">
            <p class="text-sm text-gray-600 mt-2" id="result-count"></p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full table-auto text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left">Nombre</th>
                        <th class="px-4 py-2 text-left">Categoría</th>
                        <th class="px-4 py-2 text-left">Descripción</th>
                        <th class="px-4 py-2 text-left">Precio venta</th>
                        <th class="px-4 py-2 text-left">Precio coste</th>
                        <th class="px-4 py-2 text-left">Stock</th>
                        <th class="px-4 py-2 text-left">Activo</th>
                        <th class="px-4 py-2 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($productos as $producto)
                        <tr class="border-t">
                            <td class="px-4 py-2">{{ $producto->nombre }}</td>
                            <td class="px-4 py-2">
                                @if($producto->categoria === 'peluqueria')
                                    <span class="inline-flex items-center px-2 py-1 text-xs font-medium text-blue-700 bg-blue-100 rounded">💇 Peluquería</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-1 text-xs font-medium text-pink-700 bg-pink-100 rounded">💅 Estética</span>
                                @endif
                            </td>
                            <td class="px-4 py-2">{{ Str::limit($producto->descripcion, 80) }}</td>
                            <td class="px-4 py-2">{{ number_format($producto->precio_venta, 2) }} €</td>
                            <td class="px-4 py-2">{{ number_format($producto->precio_coste, 2) }} €</td>
                            <td class="px-4 py-2">{{ $producto->stock }}</td>
                            <td class="px-4 py-2">{{ $producto->activo ? 'Sí' : 'No' }}</td>
                            <td class="px-4 py-2 text-right">
                                <a href="{{ route('productos.edit', $producto) }}" class="text-yellow-600 mr-3">Editar</a>
                                <form action="{{ route('productos.destroy', $producto) }}" method="POST" class="inline-block" onsubmit="return confirm('¿Eliminar producto?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-4 py-6 text-center text-gray-600">No hay productos.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{-- Paginación --}}
            @if(method_exists($productos,'links'))
                {{ $productos->links() }}
            @endif
        </div>
    </div>

    <script>
        // Búsqueda instantánea con AJAX en toda la base de datos
        const searchInput = document.getElementById('buscar-producto');
        const tbody = document.querySelector('tbody');
        const resultCount = document.getElementById('result-count');
        let searchTimeout;

        searchInput.addEventListener('input', function() {
            const searchValue = this.value.trim();
            
            // Limpiar timeout anterior
            clearTimeout(searchTimeout);
            
            // Búsqueda instantánea con pequeño debounce (300ms)
            searchTimeout = setTimeout(() => {
                searchProducts(searchValue);
            }, 300);
        });

        async function searchProducts(query) {
            try {
                // Mostrar indicador de carga
                tbody.innerHTML = '<tr><td colspan="8" class="px-4 py-6 text-center text-gray-600">🔍 Buscando...</td></tr>';
                
                // Construir URL con parámetro de búsqueda
                const url = query ? '{{ route("productos.index") }}?q=' + encodeURIComponent(query) : '{{ route("productos.index") }}';
                
                // Hacer petición AJAX
                const response = await fetch(url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'text/html'
                    }
                });
                
                if (!response.ok) throw new Error('Error en la búsqueda');
                
                // Obtener el HTML completo
                const html = await response.text();
                
                // Crear un elemento temporal para parsear el HTML
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                
                // Extraer solo el tbody del resultado
                const newTbody = doc.querySelector('tbody');
                const newResultInfo = doc.querySelector('#result-count');
                
                if (newTbody) {
                    tbody.innerHTML = newTbody.innerHTML;
                }
                
                // Actualizar contador de resultados
                if (newResultInfo && query) {
                    const rows = tbody.querySelectorAll('tr:not([colspan])');
                    resultCount.textContent = `Mostrando ${rows.length} ${rows.length === 1 ? 'producto' : 'productos'}`;
                } else {
                    resultCount.textContent = '';
                }
                
            } catch (error) {
                console.error('Error:', error);
                tbody.innerHTML = '<tr><td colspan="8" class="px-4 py-6 text-center text-red-600">Error al buscar productos</td></tr>';
            }
        }
        
        // Búsqueda inicial si hay parámetro en la URL
        const urlParams = new URLSearchParams(window.location.search);
        const initialQuery = urlParams.get('q');
        if (initialQuery) {
            searchInput.value = initialQuery;
        }
    </script>
</body>
</html>
