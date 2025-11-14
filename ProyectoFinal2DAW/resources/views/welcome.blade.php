<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Salón Lola Hernández - Gestión Multi-Salón</title>
    {!! vite_asset(['resources/css/app.css', 'resources/js/app.js']) !!}
</head>
<body class="min-h-screen bg-gradient-to-br from-pink-50 to-purple-100">
    
    <!-- Navbar -->
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <div class="flex-shrink-0">
                    <h1 class="text-2xl font-bold text-purple-600">💇‍♀️ Salón Lola Hernández</h1>
                </div>
                <div class="hidden md:block">
                    <div class="ml-10 flex items-baseline space-x-4">
                        <a href="#caracteristicas" class="text-gray-700 hover:text-purple-600 px-3 py-2 rounded-md text-sm font-medium">
                            Características
                        </a>
                        <a href="#planes" class="text-gray-700 hover:text-purple-600 px-3 py-2 rounded-md text-sm font-medium">
                            Planes
                        </a>
                        <a href="{{ route('tenant.register.create') }}" class="bg-purple-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-purple-700 transition">
                            🚀 Crear Mi Salón
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
        <div class="text-center">
            <h1 class="text-5xl md:text-6xl font-extrabold text-gray-900 mb-6">
                Tu Salón de Belleza
                <span class="text-purple-600">en la Nube</span>
            </h1>
            <p class="text-xl md:text-2xl text-gray-600 mb-8 max-w-3xl mx-auto">
                Sistema de gestión completo para salones de belleza. 
                Gestiona clientes, citas, empleados, inventario y más desde cualquier lugar.
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
                <a href="{{ route('tenant.register.create') }}" 
                   class="inline-flex items-center px-8 py-4 bg-purple-600 text-white text-lg font-semibold rounded-lg hover:bg-purple-700 transition shadow-lg transform hover:scale-105">
                    🚀 Empezar Gratis - 30 días
                </a>
                <a href="#caracteristicas" 
                   class="inline-flex items-center px-8 py-4 bg-white text-purple-600 text-lg font-semibold rounded-lg hover:bg-gray-50 transition border-2 border-purple-600">
                    📖 Ver Características
                </a>
            </div>
        </div>
    </div>

    <!-- Características -->
    <div id="caracteristicas" class="bg-white py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-4xl font-bold text-center text-gray-900 mb-12">
                Todo lo que necesitas para tu salón
            </h2>
            <div class="grid md:grid-cols-3 gap-8">
                <!-- Característica 1 -->
                <div class="text-center p-6 rounded-lg hover:shadow-xl transition">
                    <div class="text-5xl mb-4">📅</div>
                    <h3 class="text-xl font-bold mb-2">Gestión de Citas</h3>
                    <p class="text-gray-600">
                        Sistema completo de agenda con recordatorios automáticos por email
                    </p>
                </div>
                <!-- Característica 2 -->
                <div class="text-center p-6 rounded-lg hover:shadow-xl transition">
                    <div class="text-5xl mb-4">👥</div>
                    <h3 class="text-xl font-bold mb-2">Clientes y Empleados</h3>
                    <p class="text-gray-600">
                        Gestiona perfiles completos, historiales y horarios de trabajo
                    </p>
                </div>
                <!-- Característica 3 -->
                <div class="text-center p-6 rounded-lg hover:shadow-xl transition">
                    <div class="text-5xl mb-4">🎟️</div>
                    <h3 class="text-xl font-bold mb-2">Bonos y Descuentos</h3>
                    <p class="text-gray-600">
                        Sistema de bonos personalizables para fidelizar clientes
                    </p>
                </div>
                <!-- Característica 4 -->
                <div class="text-center p-6 rounded-lg hover:shadow-xl transition">
                    <div class="text-5xl mb-4">📦</div>
                    <h3 class="text-xl font-bold mb-2">Inventario</h3>
                    <p class="text-gray-600">
                        Control de productos y stock en tiempo real
                    </p>
                </div>
                <!-- Característica 5 -->
                <div class="text-center p-6 rounded-lg hover:shadow-xl transition">
                    <div class="text-5xl mb-4">💰</div>
                    <h3 class="text-xl font-bold mb-2">Control Financiero</h3>
                    <p class="text-gray-600">
                        Gestión de deudas, pagos y reportes financieros
                    </p>
                </div>
                <!-- Característica 6 -->
                <div class="text-center p-6 rounded-lg hover:shadow-xl transition">
                    <div class="text-5xl mb-4">📊</div>
                    <h3 class="text-xl font-bold mb-2">Reportes y Análisis</h3>
                    <p class="text-gray-600">
                        Estadísticas detalladas para tomar mejores decisiones
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Planes -->
    <div id="planes" class="bg-gradient-to-br from-purple-50 to-pink-50 py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-4xl font-bold text-center text-gray-900 mb-4">
                Comienza hoy mismo
            </h2>
            <p class="text-center text-gray-600 mb-12 text-lg">
                Crea tu salón en minutos. Sin tarjeta de crédito requerida.
            </p>
            <div class="max-w-md mx-auto bg-white rounded-2xl shadow-2xl p-8 border-4 border-purple-600">
                <div class="text-center mb-6">
                    <h3 class="text-2xl font-bold text-gray-900 mb-2">Plan Estándar</h3>
                    <div class="text-5xl font-bold text-purple-600 mb-2">
                        GRATIS
                    </div>
                    <p class="text-gray-600">30 días de prueba gratuita</p>
                </div>
                <ul class="space-y-3 mb-8">
                    <li class="flex items-center text-gray-700">
                        <svg class="w-5 h-5 mr-2 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                        Clientes ilimitados
                    </li>
                    <li class="flex items-center text-gray-700">
                        <svg class="w-5 h-5 mr-2 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                        Citas ilimitadas
                    </li>
                    <li class="flex items-center text-gray-700">
                        <svg class="w-5 h-5 mr-2 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                        Recordatorios por email
                    </li>
                    <li class="flex items-center text-gray-700">
                        <svg class="w-5 h-5 mr-2 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                        Gestión de inventario
                    </li>
                    <li class="flex items-center text-gray-700">
                        <svg class="w-5 h-5 mr-2 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                        Soporte por email
                    </li>
                </ul>
                <a href="{{ route('tenant.register.create') }}" 
                   class="block w-full bg-purple-600 text-white text-center px-6 py-4 rounded-lg font-bold text-lg hover:bg-purple-700 transition transform hover:scale-105 shadow-lg">
                    🚀 Crear Mi Salón Ahora
                </a>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <p class="text-gray-400">
                © {{ date('Y') }} Salón Lola Hernández. Sistema de Gestión Multi-Salón.
            </p>
            <p class="text-gray-500 text-sm mt-2">
                Proyecto Final - 2º DAW
            </p>
        </div>
    </footer>

</body>
</html>
