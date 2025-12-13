<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Demasiadas Peticiones - {{ config('app.name', 'Laravel') }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="antialiased bg-gray-100">
    <div class="min-h-screen flex items-center justify-center px-4">
        <div class="max-w-md w-full bg-white shadow-lg rounded-lg p-8">
            <div class="text-center">
                <!-- Icono de advertencia -->
                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-yellow-100 mb-4">
                    <svg class="h-10 w-10 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                
                <!-- Código de error -->
                <h1 class="text-6xl font-bold text-yellow-600 mb-2">429</h1>
                
                <!-- Título -->
                <h2 class="text-2xl font-semibold text-gray-800 mb-4">
                    Demasiadas Peticiones
                </h2>
                
                <!-- Mensaje -->
                <p class="text-gray-600 mb-6">
                    Has excedido el límite de peticiones permitidas. Por favor, espera 
                    <strong class="text-yellow-600">{{ $retry_after ?? 60 }} segundos</strong> 
                    antes de intentar nuevamente.
                </p>
                
                <!-- Información adicional -->
                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6 text-left">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-yellow-700">
                                Esta protección ayuda a mantener el servicio estable para todos los usuarios.
                            </p>
                        </div>
                    </div>
                </div>
                
                <!-- Botones de acción -->
                <div class="flex flex-col sm:flex-row gap-3 justify-center">
                    <a href="{{ route('dashboard') }}" 
                       class="inline-flex items-center justify-center px-6 py-3 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition duration-150 ease-in-out">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        Volver al Inicio
                    </a>
                    
                    <button onclick="window.location.reload()" 
                            class="inline-flex items-center justify-center px-6 py-3 bg-gray-200 text-gray-700 font-medium rounded-lg hover:bg-gray-300 transition duration-150 ease-in-out">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Reintentar
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Auto-reintento después del tiempo especificado -->
    <script>
        // Cuenta regresiva y auto-recarga
        let retryAfter = {{ $retry_after ?? 60 }};
        
        if (retryAfter > 0 && retryAfter < 120) {
            setTimeout(() => {
                window.location.reload();
            }, retryAfter * 1000);
        }
    </script>
</body>
</html>
