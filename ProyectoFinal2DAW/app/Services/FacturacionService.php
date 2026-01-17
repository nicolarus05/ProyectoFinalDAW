<?php

namespace App\Services;

use App\Models\RegistroCobro;
use App\Models\Empleado;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Servicio centralizado para cálculos de facturación
 * 
 * Aplica la lógica de distribución proporcional de descuentos
 * que se usa en FacturacionController para garantizar consistencia
 * entre facturación mensual y facturación por empleado.
 */
class FacturacionService
{
    /**
     * Desglosa un cobro individual por empleado aplicando distribución proporcional
     * 
     * @param RegistroCobro $cobro
     * @return array ['servicios' => [empleadoId => monto], 'productos' => [empleadoId => monto], 'es_pago_deuda' => bool]
     */
    public function desglosarCobroPorEmpleado(RegistroCobro $cobro): array
    {
        $resultado = [
            'servicios' => [],
            'productos' => [],
            'es_pago_deuda' => $this->esPagoDeuda($cobro->id),
        ];
        
        // Si es pago de deuda, no desglosar (ya se contó en el cobro original)
        if ($resultado['es_pago_deuda']) {
            return $resultado;
        }
        
        // SERVICIOS: Sumar precio directo de cada servicio según empleado
        $servicios = $this->obtenerServiciosCobro($cobro);
        
        if ($servicios && $servicios->count() > 0) {
            foreach ($servicios as $servicio) {
                $precioServicio = $servicio->pivot->precio ?? $servicio->precio;
                
                // Obtener empleado responsable del servicio
                $empleadoId = $this->obtenerEmpleadoServicio($servicio, $cobro);
                
                if ($empleadoId) {
                    if (!isset($resultado['servicios'][$empleadoId])) {
                        $resultado['servicios'][$empleadoId] = 0;
                    }
                    $resultado['servicios'][$empleadoId] += $precioServicio;
                }
            }
        }
        
        // PRODUCTOS: Sumar precio directo de cada producto
        if ($cobro->productos && $cobro->productos->count() > 0) {
            $empleadoIdProductos = $cobro->id_empleado;
            
            if ($empleadoIdProductos) {
                $totalProductos = 0;
                foreach ($cobro->productos as $producto) {
                    $subtotal = $producto->pivot->subtotal ?? 0;
                    $totalProductos += $subtotal;
                }
                
                if ($totalProductos > 0) {
                    if (!isset($resultado['productos'][$empleadoIdProductos])) {
                        $resultado['productos'][$empleadoIdProductos] = 0;
                    }
                    $resultado['productos'][$empleadoIdProductos] += $totalProductos;
                }
            }
        }
        
        return $resultado;
    }
    
    /**
     * Calcula facturación de un empleado en un rango de fechas
     * 
     * @param Empleado $empleado
     * @param Carbon $fechaInicio
     * @param Carbon $fechaFin
     * @return array ['servicios' => float, 'productos' => float, 'bonos' => float, 'total' => float]
     */
    public function facturacionPorFechasEmpleado(Empleado $empleado, Carbon $fechaInicio, Carbon $fechaFin): array
    {
        $resultado = [
            'servicios' => 0,
            'productos' => 0,
            'bonos' => 0,
            'total' => 0,
        ];
        
        // Obtener todos los cobros del periodo con sus relaciones
        $cobros = RegistroCobro::with(['servicios', 'productos', 'cita.servicios', 'citasAgrupadas.servicios'])
            ->whereBetween('created_at', [$fechaInicio, $fechaFin])
            ->where('metodo_pago', '!=', 'bono') // Excluir consumo de bonos
            ->get();
        
        // Procesar cada cobro
        foreach ($cobros as $cobro) {
            $desglose = $this->desglosarCobroPorEmpleado($cobro);
            
            // Sumar servicios del empleado
            if (isset($desglose['servicios'][$empleado->id])) {
                $resultado['servicios'] += $desglose['servicios'][$empleado->id];
            }
            
            // Sumar productos del empleado
            if (isset($desglose['productos'][$empleado->id])) {
                $resultado['productos'] += $desglose['productos'][$empleado->id];
            }
        }
        
        // BONOS - Calcular por separado (lógica ya correcta)
        $resultado['bonos'] = $this->calcularBonosEmpleado($empleado, $fechaInicio, $fechaFin);
        
        // Total
        $resultado['total'] = $resultado['servicios'] + $resultado['productos'] + $resultado['bonos'];
        
        return $resultado;
    }
    
    /**
     * Verifica si un cobro es un pago de deuda
     * 
     * @param int $cobroId
     * @return bool
     */
    private function esPagoDeuda(int $cobroId): bool
    {
        static $cobrosDeudas = null;
        
        if ($cobrosDeudas === null) {
            $cobrosDeudas = DB::table('movimientos_deuda')
                ->where('tipo', 'abono')
                ->whereNotNull('id_registro_cobro')
                ->pluck('id_registro_cobro')
                ->toArray();
        }
        
        return in_array($cobroId, $cobrosDeudas);
    }
    
    /**
     * Obtiene los servicios de un cobro siguiendo prioridad correcta
     * 
     * @param RegistroCobro $cobro
     * @return \Illuminate\Database\Eloquent\Collection|null
     */
    private function obtenerServiciosCobro(RegistroCobro $cobro)
    {
        // PRIORIDAD 1: Servicios de cita individual
        if ($cobro->cita && $cobro->cita->servicios && $cobro->cita->servicios->count() > 0) {
            return $cobro->cita->servicios;
        }
        
        // PRIORIDAD 2: Servicios de citas agrupadas
        if ($cobro->citasAgrupadas && $cobro->citasAgrupadas->count() > 0) {
            $servicios = collect();
            foreach ($cobro->citasAgrupadas as $citaGrupo) {
                if ($citaGrupo->servicios && $citaGrupo->servicios->count() > 0) {
                    $servicios = $servicios->merge($citaGrupo->servicios);
                }
            }
            if ($servicios->count() > 0) {
                return $servicios;
            }
        }
        
        // PRIORIDAD 3: Servicios directos del cobro
        if ($cobro->servicios && $cobro->servicios->count() > 0) {
            return $cobro->servicios;
        }
        
        return collect();
    }
    
    /**
     * Obtiene el ID del empleado responsable de un servicio
     * 
     * Prioridad:
     * 1. empleado_id en pivot de registro_cobro_servicio
     * 2. empleado de la cita individual (si existe)
     * 3. empleado de citas agrupadas (buscar qué cita tiene este servicio)
     * 4. empleado del cobro (fallback)
     * 
     * @param \App\Models\Servicio $servicio
     * @param RegistroCobro $cobro
     * @return int|null
     */
    private function obtenerEmpleadoServicio($servicio, RegistroCobro $cobro)
    {
        // Prioridad 1: empleado_id en el pivot
        if (isset($servicio->pivot->empleado_id) && $servicio->pivot->empleado_id) {
            return $servicio->pivot->empleado_id;
        }
        
        // Prioridad 2: Cita individual
        if ($cobro->cita && $cobro->cita->id_empleado) {
            return $cobro->cita->id_empleado;
        }
        
        // Prioridad 3: Citas agrupadas - buscar en qué cita está este servicio
        if ($cobro->citasAgrupadas && $cobro->citasAgrupadas->count() > 0) {
            foreach ($cobro->citasAgrupadas as $cita) {
                if ($cita->servicios && $cita->servicios->count() > 0) {
                    // Buscar si este servicio está en esta cita
                    $servicioEnCita = $cita->servicios->first(function($s) use ($servicio) {
                        // Comparar por ID y precio para asegurar que es el mismo
                        return $s->id === $servicio->id && 
                               ($s->pivot->precio ?? $s->precio) === ($servicio->pivot->precio ?? $servicio->precio);
                    });
                    
                    if ($servicioEnCita && $cita->id_empleado) {
                        return $cita->id_empleado;
                    }
                }
            }
        }
        
        // Prioridad 4: Empleado del cobro (fallback)
        return $cobro->id_empleado;
    }
    
    /**
     * Calcula bonos vendidos por el empleado en el periodo
     * 
     * @param Empleado $empleado
     * @param Carbon $fechaInicio
     * @param Carbon $fechaFin
     * @return float
     */
    private function calcularBonosEmpleado(Empleado $empleado, Carbon $fechaInicio, Carbon $fechaFin): float
    {
        $facturacionBonos = 0;
        
        try {
            if (DB::getSchemaBuilder()->hasTable('bonos_plantillas')) {
                $facturacionBonos = DB::table('bonos_clientes')
                    ->where('bonos_clientes.id_empleado', $empleado->id)
                    ->whereBetween('bonos_clientes.fecha_compra', [$fechaInicio, $fechaFin])
                    ->sum('bonos_clientes.precio_pagado');
            } else {
                // Alternativa: usar campo total_bonos_vendidos
                $bonosCitasIndividuales = DB::table('registro_cobros')
                    ->join('citas', 'registro_cobros.id_cita', '=', 'citas.id')
                    ->where('citas.id_empleado', $empleado->id)
                    ->whereBetween('registro_cobros.created_at', [$fechaInicio, $fechaFin])
                    ->sum('registro_cobros.total_bonos_vendidos');
                
                $bonosCobrosDirectos = DB::table('registro_cobros')
                    ->whereNull('registro_cobros.id_cita')
                    ->where('registro_cobros.id_empleado', $empleado->id)
                    ->whereBetween('registro_cobros.created_at', [$fechaInicio, $fechaFin])
                    ->sum('registro_cobros.total_bonos_vendidos');
                
                $facturacionBonos = $bonosCitasIndividuales + $bonosCobrosDirectos;
            }
        } catch (\Exception $e) {
            $facturacionBonos = 0;
        }
        
        return $facturacionBonos ?? 0;
    }
}
