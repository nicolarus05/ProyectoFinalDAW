<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Registrar Nuevo Salón - {{ config('app.name') }}</title>
    {!! vite_asset(['resources/css/app.css', 'resources/js/app.js']) !!}
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0">
        <!-- Logo / Header -->
        <div class="text-center mb-6">
            <h1 class="text-4xl font-bold text-gray-800 mb-2">Salón Lola Hernández</h1>
            <p class="text-gray-600">Crea tu propio salón de belleza online</p>
        </div>

        <!-- Formulario de Registro -->
        <div class="w-full sm:max-w-2xl mt-6 px-6 py-8 bg-white shadow-md overflow-hidden sm:rounded-lg">
            <h2 class="text-2xl font-semibold text-gray-800 mb-6 text-center">Registro de Nuevo Salón</h2>

            @if (session('success'))
                <div class="mb-6 bg-green-50 border border-green-400 text-green-800 px-6 py-4 rounded relative" role="alert">
                    <h3 class="font-bold text-lg mb-2">¡Salón creado exitosamente! 🎉</h3>
                    <p class="mb-3">Tu salón <strong>{{ session('tenant_name') }}</strong> ha sido creado correctamente.</p>
                    <div class="bg-white border border-green-300 rounded p-4 mb-3">
                        <p class="text-sm text-gray-700 mb-2"><strong>Email:</strong> {{ session('admin_email') }}</p>
                        <p class="text-sm text-gray-700"><strong>Contraseña:</strong> La que ingresaste en el formulario</p>
                    </div>
                    <a href="{{ session('tenant_url') }}" 
                       class="inline-block bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-6 rounded transition">
                        Ir al Login de mi Salón →
                    </a>
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-4 bg-red-50 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                    <strong class="font-bold">¡Ups!</strong>
                    <ul class="mt-2 list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('tenant.register.store') }}" class="space-y-6">
                @csrf

                <!-- Sección: Información del Salón -->
                <div class="border-b border-gray-200 pb-4">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">📍 Información del Salón</h3>
                    
                    <div class="space-y-4">
                        <!-- Nombre del Salón -->
                        <div>
                            <label for="salon_name" class="block text-sm font-medium text-gray-700">
                                Nombre del Salón <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="text" 
                                name="salon_name" 
                                id="salon_name" 
                                value="{{ old('salon_name') }}"
                                required
                                autofocus
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="Ej: Salón María Belleza">
                            <p class="mt-1 text-xs text-gray-500">Este será el nombre público de tu salón</p>
                        </div>

                        <!-- Identificador del Salón (Slug) -->
                        <div>
                            <label for="salon_slug" class="block text-sm font-medium text-gray-700">
                                Identificador Único (URL) <span class="text-red-500">*</span>
                            </label>
                            <div class="mt-1 flex rounded-md shadow-sm">
                                <input 
                                    type="text" 
                                    name="salon_slug" 
                                    id="salon_slug" 
                                    value="{{ old('salon_slug') }}"
                                    required
                                    pattern="[a-z0-9-_]+"
                                    class="block w-full rounded-l-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                    placeholder="mi-salon">
                                <span class="inline-flex items-center px-3 rounded-r-md border border-l-0 border-gray-300 bg-gray-50 text-gray-500 text-sm">
                                    .localhost
                                </span>
                            </div>
                            <p class="mt-1 text-xs text-gray-500">
                                Solo letras minúsculas, números, guiones (-) y guiones bajos (_). 
                                <span id="slug-status" class="font-semibold"></span>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Sección: Información del Administrador -->
                <div class="border-b border-gray-200 pb-4">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">👤 Información del Administrador</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Nombre -->
                        <div>
                            <label for="admin_name" class="block text-sm font-medium text-gray-700">
                                Nombre <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="text" 
                                name="admin_name" 
                                id="admin_name" 
                                value="{{ old('admin_name') }}"
                                required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>

                        <!-- Apellidos -->
                        <div>
                            <label for="admin_apellidos" class="block text-sm font-medium text-gray-700">
                                Apellidos <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="text" 
                                name="admin_apellidos" 
                                id="admin_apellidos" 
                                value="{{ old('admin_apellidos') }}"
                                required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>

                        <!-- Email -->
                        <div>
                            <label for="admin_email" class="block text-sm font-medium text-gray-700">
                                Email <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="email" 
                                name="admin_email" 
                                id="admin_email" 
                                value="{{ old('admin_email') }}"
                                required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>

                        <!-- Teléfono -->
                        <div>
                            <label for="admin_telefono" class="block text-sm font-medium text-gray-700">
                                Teléfono <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="tel" 
                                name="admin_telefono" 
                                id="admin_telefono" 
                                value="{{ old('admin_telefono') }}"
                                required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="Ej: +34 600 123 456">
                        </div>

                        <!-- Género -->
                        <div>
                            <label for="admin_genero" class="block text-sm font-medium text-gray-700">
                                Género <span class="text-red-500">*</span>
                            </label>
                            <select 
                                name="admin_genero" 
                                id="admin_genero" 
                                required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Selecciona...</option>
                                <option value="masculino" {{ old('admin_genero') == 'masculino' ? 'selected' : '' }}>Masculino</option>
                                <option value="femenino" {{ old('admin_genero') == 'femenino' ? 'selected' : '' }}>Femenino</option>
                                <option value="otro" {{ old('admin_genero') == 'otro' ? 'selected' : '' }}>Otro</option>
                            </select>
                        </div>

                        <!-- Edad -->
                        <div>
                            <label for="admin_edad" class="block text-sm font-medium text-gray-700">
                                Edad <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="number" 
                                name="admin_edad" 
                                id="admin_edad" 
                                value="{{ old('admin_edad') }}"
                                required
                                min="18"
                                max="100"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                    </div>
                </div>

                <!-- Sección: Contraseña -->
                <div class="pb-4">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">🔒 Contraseña de Acceso</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Contraseña -->
                        <div>
                            <label for="admin_password" class="block text-sm font-medium text-gray-700">
                                Contraseña <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="password" 
                                name="admin_password" 
                                id="admin_password" 
                                required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <p class="mt-1 text-xs text-gray-500">Mínimo 8 caracteres</p>
                        </div>

                        <!-- Confirmar Contraseña -->
                        <div>
                            <label for="admin_password_confirmation" class="block text-sm font-medium text-gray-700">
                                Confirmar Contraseña <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="password" 
                                name="admin_password_confirmation" 
                                id="admin_password_confirmation" 
                                required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                    </div>
                </div>

                <!-- Botones de Acción -->
                <div class="flex items-center justify-between pt-4">
                    <a href="{{ url('/') }}" class="text-sm text-gray-600 hover:text-gray-900 underline">
                        ← Volver al inicio
                    </a>
                    <button 
                        type="submit" 
                        class="inline-flex items-center px-6 py-3 bg-indigo-600 border border-transparent rounded-md font-semibold text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition">
                        🚀 Crear Mi Salón
                    </button>
                </div>
            </form>
        </div>

        <!-- Footer -->
        <div class="mt-8 text-center text-sm text-gray-600">
            <p>© {{ date('Y') }} Salón Lola Hernández. Todos los derechos reservados.</p>
        </div>
    </div>

    <!-- Script para verificación de slug en tiempo real -->
    <script>
        const slugInput = document.getElementById('salon_slug');
        const slugStatus = document.getElementById('slug-status');
        let typingTimer;
        const doneTypingInterval = 500; // 500ms después de dejar de escribir

        slugInput.addEventListener('input', function() {
            clearTimeout(typingTimer);
            
            // Convertir a minúsculas y eliminar caracteres no permitidos
            this.value = this.value.toLowerCase().replace(/[^a-z0-9-_]/g, '');
            
            if (this.value.length >= 3) {
                typingTimer = setTimeout(() => checkSlugAvailability(this.value), doneTypingInterval);
            } else {
                slugStatus.textContent = '';
            }
        });

        async function checkSlugAvailability(slug) {
            try {
                const response = await fetch(`{{ route('tenant.register.check-slug') }}?slug=${slug}`, {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                const data = await response.json();
                
                if (data.available) {
                    slugStatus.textContent = '✓ Disponible';
                    slugStatus.classList.remove('text-red-600');
                    slugStatus.classList.add('text-green-600');
                } else {
                    slugStatus.textContent = '✗ No disponible';
                    slugStatus.classList.remove('text-green-600');
                    slugStatus.classList.add('text-red-600');
                }
            } catch (error) {
                console.error('Error checking slug:', error);
            }
        }
    </script>
</body>
</html>
