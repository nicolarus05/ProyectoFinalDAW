<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Pago - {{ $cliente->user->nombre }}</title>
    @vite(['resources/js/app.js'])
</head>
<body class="bg-gray-100 p-6">
    <div class="w-full max-w-2xl mx-auto bg-white shadow-md rounded p-6">
        <h1 class="text-3xl font-bold mb-4">Registrar Pago de Deuda</h1>

        <!-- Información del Cliente -->
        <div class="mb-6 p-4 bg-gray-50 rounded">
            <h2 class="text-xl font-semibold mb-2">Cliente</h2>
            <p><strong>Nombre:</strong> {{ $cliente->user->nombre }} {{ $cliente->user->apellidos }}</p>
            <p><strong>Deuda Pendiente:</strong> 
                <span class="text-2xl font-bold text-red-600">€{{ number_format($deuda->saldo_pendiente, 2) }}</span>
            </p>
        </div>

        <!-- Formulario de Pago -->
        <form action="{{ route('deudas.pago.store', $cliente) }}" method="POST" class="space-y-4">
            @csrf

            <!-- Monto -->
            <div>
                <label for="monto" class="block text-sm font-medium text-gray-700 mb-1">
                    Monto del Pago <span class="text-red-500">*</span>
                </label>
                <input type="number" 
                       name="monto" 
                       id="monto" 
                       step="0.01" 
                       min="0.01" 
                       max="{{ $deuda->saldo_pendiente }}"
                       value="{{ old('monto') }}"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                       required>
                @error('monto')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
                <p class="text-sm text-gray-500 mt-1">
                    Máximo: €{{ number_format($deuda->saldo_pendiente, 2) }}
                </p>
            </div>

            <!-- Método de Pago -->
            <div>
                <label for="metodo_pago" class="block text-sm font-medium text-gray-700 mb-1">
                    Método de Pago <span class="text-red-500">*</span>
                </label>
                <select name="metodo_pago" 
                        id="metodo_pago" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                        required>
                    <option value="">Seleccione un método</option>
                    <option value="efectivo" {{ old('metodo_pago') === 'efectivo' ? 'selected' : '' }}>Efectivo</option>
                    <option value="tarjeta" {{ old('metodo_pago') === 'tarjeta' ? 'selected' : '' }}>Tarjeta</option>
                    <option value="transferencia" {{ old('metodo_pago') === 'transferencia' ? 'selected' : '' }}>Transferencia</option>
                </select>
                @error('metodo_pago')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Nota (Opcional) -->
            <div>
                <label for="nota" class="block text-sm font-medium text-gray-700 mb-1">
                    Nota (Opcional)
                </label>
                <textarea name="nota" 
                          id="nota" 
                          rows="3"
                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                          placeholder="Ej: Pago parcial, acordado plazo, etc.">{{ old('nota') }}</textarea>
                @error('nota')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Botones de Acción -->
            <div class="flex gap-4 mt-6">
                <button type="submit" 
                        class="bg-green-600 text-white px-6 py-2 rounded hover:bg-green-700">
                    Registrar Pago
                </button>
                <a href="{{ route('deudas.show', $cliente) }}" 
                   class="bg-gray-600 text-white px-6 py-2 rounded hover:bg-gray-700 inline-block">
                    Cancelar
                </a>
            </div>
        </form>

        <!-- Botones Rápidos -->
        <div class="mt-6 p-4 bg-blue-50 rounded">
            <p class="text-sm font-medium text-gray-700 mb-2">Acciones Rápidas:</p>
            <div class="flex gap-2 flex-wrap">
                <button type="button" 
                        onclick="document.getElementById('monto').value = '{{ $deuda->saldo_pendiente }}'"
                        class="bg-blue-600 text-white px-4 py-1 rounded text-sm hover:bg-blue-700">
                    Pagar Todo (€{{ number_format($deuda->saldo_pendiente, 2) }})
                </button>
                <button type="button" 
                        onclick="document.getElementById('monto').value = '{{ number_format($deuda->saldo_pendiente / 2, 2) }}'"
                        class="bg-blue-600 text-white px-4 py-1 rounded text-sm hover:bg-blue-700">
                    Pagar Mitad (€{{ number_format($deuda->saldo_pendiente / 2, 2) }})
                </button>
            </div>
        </div>
    </div>
</body>
</html>
