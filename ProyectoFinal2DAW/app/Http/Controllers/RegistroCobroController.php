<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RegistroCobro;
use App\Models\Cita;
use App\Models\user;
use App\Models\Productos;
use App\Models\Cliente;
use App\Models\BonoCliente;
use App\Models\BonoUsoDetalle;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;


class RegistroCobroController extends Controller{
    /**
     * Display a listing of the resource.
     */
    public function index(){
        $cobros = RegistroCobro::with([
            'cita.cliente.user',
            'cita.empleado.user',
            'cita.servicios',
            'cliente.user',
            'empleado.user',
            'productos'
        ])->get();
        return view('cobros.index', compact('cobros'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request){
        $citas = Cita::whereDoesntHave('cobro')
            ->with(['cliente.user', 'cliente.deuda', 'servicios'])
            ->get();
        
        // Si viene un parámetro cita_id, precargar esa cita
        $citaSeleccionada = null;
        if ($request->has('cita_id')) {
            $citaSeleccionada = Cita::with(['cliente.user', 'cliente.deuda', 'servicios', 'empleado'])
                ->find($request->cita_id);
        }
        
        return view('cobros.create', compact('citas', 'citaSeleccionada'));
    }

    /**
     * Mostrar formulario para cobro directo (sin cita)
     */
    public function createDirect(){
        $clientes = Cliente::with(['user', 'deuda'])->get();
        $empleados = \App\Models\Empleado::with('user')->get();
        $servicios = \App\Models\Servicio::where('activo', true)->get();
        
        return view('cobros.create-direct', compact('clientes', 'empleados', 'servicios'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request){
        // --- Validación base ---
        $data = $request->validate([
            'id_cita' => 'nullable|exists:citas,id',
            'id_cliente' => 'nullable|exists:clientes,id',
            'id_empleado' => 'nullable|exists:empleados,id',
            'coste' => 'required|numeric|min:0',
            'descuento_porcentaje' => 'nullable|numeric|min:0',
            'descuento_euro' => 'nullable|numeric|min:0',
            'total_final' => 'required|numeric|min:0',
            'metodo_pago' => 'required|in:efectivo,tarjeta,mixto',
            'dinero_cliente' => 'nullable|numeric|min:0',
            'cambio' => 'nullable|numeric|min:0',
            'pago_efectivo' => 'nullable|numeric|min:0',
            'pago_tarjeta' => 'nullable|numeric|min:0',
            'productos_data' => 'nullable|json',
            'servicios_data' => 'nullable|json',
        ]);

        // Validar que al menos tenga una cita O un cliente
        if (empty($data['id_cita']) && empty($data['id_cliente'])) {
            return back()
                ->withErrors(['id_cliente' => 'Debe seleccionar una cita o un cliente.'])
                ->withInput();
        }

        // --- Lógica según método de pago ---
        if ($data['metodo_pago'] === 'efectivo') {
            // Si es efectivo, el dinero_cliente puede ser menor que el total (genera deuda)
            // Solo es obligatorio si no está vacío, validar que sea >= 0
            if (!isset($data['dinero_cliente'])) {
                $data['dinero_cliente'] = 0; // Si está vacío, se asume que no paga nada (deuda completa)
            }

            if ($data['dinero_cliente'] < 0) {
                return back()
                    ->withErrors(['dinero_cliente' => 'El dinero del cliente no puede ser negativo.'])
                    ->withInput();
            }

            // Calcular el cambio (solo si paga más del total)
            $data['cambio'] = max(0, $data['dinero_cliente'] - $data['total_final']);
        } 
        elseif ($data['metodo_pago'] === 'tarjeta') {
            // Si es tarjeta → se llena automáticamente (no genera deuda)
            $data['dinero_cliente'] = $data['total_final'];
            $data['cambio'] = 0;
        }
        elseif ($data['metodo_pago'] === 'mixto') {
            // Pago mixto: efectivo + tarjeta
            $pagoEfectivo = $data['pago_efectivo'] ?? 0;
            $pagoTarjeta = $data['pago_tarjeta'] ?? 0;
            $totalPagado = $pagoEfectivo + $pagoTarjeta;
            
            // Validar que la suma sea exactamente igual al total
            if (abs($totalPagado - $data['total_final']) > 0.01) {
                return back()
                    ->withErrors(['metodo_pago' => 'El total de efectivo + tarjeta debe ser igual al total a pagar. Total pagado: €' . number_format($totalPagado, 2) . ', Total requerido: €' . number_format($data['total_final'], 2)])
                    ->withInput();
            }
            
            $data['dinero_cliente'] = $totalPagado;
            $data['cambio'] = 0; // No hay cambio en pago mixto
        }

        // --- VERIFICAR Y APLICAR BONOS ---
        $cita = Cita::with(['servicios', 'cliente'])->find($data['id_cita']);
        $serviciosAplicados = [];
        $descuentoBonos = 0; // Total descontado por bonos
        
        if ($cita && $cita->cliente) {
            // Obtener bonos activos del cliente
            $bonosActivos = BonoCliente::with('servicios')
                ->where('cliente_id', $cita->cliente->id)
                ->where('estado', 'activo')
                ->where('fecha_expiracion', '>=', Carbon::now())
                ->get();

            // Iterar sobre los servicios de la cita
            foreach ($cita->servicios as $servicioCita) {
                // Buscar si hay un bono que incluya este servicio
                foreach ($bonosActivos as $bono) {
                    $servicioBono = $bono->servicios()
                        ->where('servicio_id', $servicioCita->id)
                        ->wherePivot('cantidad_usada', '<', DB::raw('cantidad_total'))
                        ->first();

                    if ($servicioBono) {
                        // Hay disponibilidad en el bono, deducir 1
                        $cantidadUsada = $servicioBono->pivot->cantidad_usada + 1;
                        
                        $bono->servicios()->updateExistingPivot($servicioCita->id, [
                            'cantidad_usada' => $cantidadUsada
                        ]);

                        $serviciosAplicados[] = $servicioCita->nombre;
                        
                        // Acumular el descuento del servicio cubierto por el bono
                        $descuentoBonos += $servicioCita->precio;

                        // Registrar el uso detallado del bono
                        BonoUsoDetalle::create([
                            'bono_cliente_id' => $bono->id,
                            'cita_id' => $cita->id,
                            'servicio_id' => $servicioCita->id,
                            'cantidad_usada' => 1
                        ]);

                        // Verificar si el bono está completamente usado
                        if ($bono->estaCompletamenteUsado()) {
                            $bono->update(['estado' => 'usado']);
                        }

                        break; // Ya se aplicó un bono para este servicio, pasar al siguiente
                    }
                }
            }
        }

        // Ajustar el total final restando los servicios cubiertos por bonos
        $totalAjustado = max(0, $data['total_final'] - $descuentoBonos);

        // Recalcular el cambio con el total ajustado
        if ($data['metodo_pago'] === 'efectivo' && $descuentoBonos > 0) {
            $data['cambio'] = max(0, ($data['dinero_cliente'] ?? 0) - $totalAjustado);
        }

        // --- Calcular deuda si el dinero del cliente es menor que el total ajustado ---
        $deuda = max(0, $totalAjustado - ($data['dinero_cliente'] ?? 0));

        // Obtener el ID del cliente (puede venir de la cita o directamente)
        $clienteId = $data['id_cliente'] ?? null;
        if (!$clienteId && !empty($data['id_cita'])) {
            $cita = Cita::find($data['id_cita']);
            $clienteId = $cita ? $cita->id_cliente : null;
        }

        // --- Crear el registro principal ---
        $cobro = RegistroCobro::create([
            'id_cita' => $data['id_cita'] ?? null,
            'coste' => $data['coste'],
            'descuento_porcentaje' => $data['descuento_porcentaje'] ?? 0,
            'descuento_euro' => ($data['descuento_euro'] ?? 0) + $descuentoBonos, // Sumar descuento por bonos
            'total_final' => $totalAjustado, // Guardar el total ajustado
            'dinero_cliente' => $data['dinero_cliente'] ?? 0,
            'pago_efectivo' => $data['metodo_pago'] === 'mixto' ? ($data['pago_efectivo'] ?? 0) : null,
            'pago_tarjeta' => $data['metodo_pago'] === 'mixto' ? ($data['pago_tarjeta'] ?? 0) : null,
            'cambio' => $data['cambio'] ?? 0,
            'metodo_pago' => $descuentoBonos > 0 && $totalAjustado == 0 ? 'bono' : $data['metodo_pago'], // Si se pagó todo con bono, método = bono
            'id_cliente' => $clienteId,
            'id_empleado' => $data['id_empleado'] ?? null,
            'deuda' => $deuda,
        ]);

        // --- Si hay deuda, registrarla en el sistema de deudas ---
        if ($deuda > 0 && $clienteId) {
            $cliente = Cliente::find($clienteId);
            
            if ($cliente) {
                $deudaCliente = $cliente->obtenerDeuda();
                $nota = "Cobro #" . $cobro->id . ($data['id_cita'] ? " - Cita #" . $data['id_cita'] : " - Venta directa");
                $deudaCliente->registrarCargo($deuda, $nota, null, $cobro->id);
            }
        }

        // --- Para cobros directos: procesar productos_data y servicios_data ---
        if ($request->has('productos_data') && !empty($data['productos_data'])) {
            $productosData = json_decode($data['productos_data'], true);
            if (is_array($productosData)) {
                foreach ($productosData as $p) {
                    $cantidad = (int) $p['cantidad'];
                    $precio = (float) $p['precio'];
                    $subtotal = $cantidad * $precio;

                    $producto = Productos::find($p['id']);
                    
                    if (!$producto) {
                        return back()
                            ->withErrors(['products' => 'Producto no encontrado: ID ' . $p['id']])
                            ->withInput();
                    }

                    if ($producto->stock < $cantidad) {
                        return back()
                            ->withErrors(['products' => 'Stock insuficiente para el producto: ' . $producto->nombre . '. Stock disponible: ' . $producto->stock])
                            ->withInput();
                    }

                    $producto->stock -= $cantidad;
                    $producto->save();

                    $cobro->productos()->attach($p['id'], [
                        'cantidad' => $cantidad,
                        'precio_unitario' => $precio,
                        'subtotal' => $subtotal,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        // --- Guardar productos asociados (si existen - formato antiguo) ---
        if ($request->has('products')) {
            foreach ($request->products as $p) {
                $cantidad = (int) $p['cantidad'];
                $precio = (float) $p['precio_venta'];
                $subtotal = $cantidad * $precio;

                // Obtener el producto para actualizar el stock
                $producto = Productos::find($p['id']);
                
                if (!$producto) {
                    return back()
                        ->withErrors(['products' => 'Producto no encontrado: ID ' . $p['id']])
                        ->withInput();
                }

                // Verificar que hay suficiente stock
                if ($producto->stock < $cantidad) {
                    return back()
                        ->withErrors(['products' => 'Stock insuficiente para el producto: ' . $producto->nombre . '. Stock disponible: ' . $producto->stock])
                        ->withInput();
                }

                // Descontar del stock
                $producto->stock -= $cantidad;
                $producto->save();

                // Asociar el producto al cobro en la tabla pivot
                $cobro->productos()->attach($p['id'], [
                    'cantidad' => $cantidad,
                    'precio_unitario' => $precio,
                    'subtotal' => $subtotal,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Mensaje de éxito con información de deuda y bonos si aplica
        $mensaje = 'Cobro registrado correctamente.';
        if ($deuda > 0) {
            $mensaje .= ' Deuda registrada: €' . number_format($deuda, 2);
        }
        if (!empty($serviciosAplicados)) {
            $mensaje .= ' Servicios aplicados desde bono: ' . implode(', ', $serviciosAplicados) . '.';
            $mensaje .= ' Descuento por bonos: €' . number_format($descuentoBonos, 2) . '.';
        }
        if ($descuentoBonos > 0 && $totalAjustado == 0) {
            $mensaje .= ' ¡Pago completo con bono!';
        }

        return redirect()
            ->route('cobros.index')
            ->with('success', $mensaje);
    }


    /**
     * Display the specified resource.
     */
    public function show(RegistroCobro $cobro){
        return view('cobros.show', compact('cobro'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(RegistroCobro $cobro){
        $citas = Cita::whereDoesntHave('cobro')
            ->orWhere('id', $cobro->id_cita)
            ->with('cliente.user', 'servicios')
            ->get();

        return view('cobros.edit', compact('cobro', 'citas'));
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, RegistroCobro $cobro){
        $data = $request->validate([
            'id_cita' => 'required|exists:citas,id',
            'coste' => 'required|numeric|min:0',
            'total_final' => 'required|numeric|min:0',
            'dinero_cliente' => 'required|numeric|min:0',
            'descuento_porcentaje' => 'nullable|numeric|min:0|max:100',
            'descuento_euro' => 'nullable|numeric|min:0',
            'metodo_pago' => 'required|in:efectivo,tarjeta',
            'cambio' => 'nullable|numeric|min:0'
        ]);

        // Calcular totales
        $coste = $data['coste'];
        $descuentoPorcentaje = $data['descuento_porcentaje'] ?? 0;
        $descuentoEuro = $data['descuento_euro'] ?? 0;
        $dineroCliente = $data['dinero_cliente'] ?? 0;

        $descuentoTotal = ($coste * ($descuentoPorcentaje / 100)) + $descuentoEuro;
        $totalFinal = $coste - $descuentoTotal;
        $data['total_final'] = round($totalFinal, 2);

        $data['cambio'] = $dineroCliente > 0 ? round($dineroCliente - $data['total_final'], 2) : null;

        // Actualizar la cita asociada (en caso de que se haya cambiado)
        $cobro->update([
            'id_cita' => $data['id_cita'],
            'coste' => $data['coste'],
            'descuento_porcentaje' => $descuentoPorcentaje,
            'descuento_euro' => $descuentoEuro,
            'total_final' => $data['total_final'],
            'dinero_cliente' => $dineroCliente,
            'cambio' => $data['cambio'],
            'metodo_pago' => $data['metodo_pago'],
        ]);

        return redirect()->route('cobros.index')->with('success', 'Cobro actualizado correctamente.');
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(RegistroCobro $cobro){
        // Restaurar el stock de los productos antes de eliminar el cobro
        foreach ($cobro->productos as $producto) {
            $cantidad = $producto->pivot->cantidad;
            $producto->stock += $cantidad;
            $producto->save();
        }

        $cobro->delete();
        return redirect()->route('cobros.index')->with('success', 'Cobro eliminado y stock restaurado.');
    }
}
