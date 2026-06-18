<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{RegistroCobro, Cita, Empleado};
use App\Services\FacturacionService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class FacturacionController extends Controller
{
    /**
     * Mostrar facturación mensual desglosada
     */
    public function index(Request $request)
    {
        Carbon::setLocale('es');
        
        // Obtener mes y año (por defecto el mes actual)
        $mes = $request->get('mes', now()->month);
        $anio = $request->get('anio', now()->year);
        
        $fechaInicio = Carbon::create($anio, $mes, 1)->startOfMonth();
        $fechaFin = Carbon::create($anio, $mes, 1)->endOfMonth();
        
        // ============================================================================
        // USAR EL NUEVO SISTEMA DE FACTURACIÓN POR CATEGORÍA
        // ============================================================================
        $facturacionCategoria = Empleado::facturacionPorCategoriaPorFechas($fechaInicio, $fechaFin);
        
        // Extraer datos por categoría
        $serviciosPeluqueria = $facturacionCategoria['peluqueria']['servicios'];
        $serviciosEstetica = $facturacionCategoria['estetica']['servicios'];
        $productosPeluqueria = $facturacionCategoria['peluqueria']['productos'];
        $productosEstetica = $facturacionCategoria['estetica']['productos'];
        $bonosPeluqueria = $facturacionCategoria['peluqueria']['bonos'];
        $bonosEstetica = $facturacionCategoria['estetica']['bonos'];
        
        // ============================================================================
        // CÁLCULO DE CAJAS DIARIAS
        // ============================================================================
        // Obtener todos los cobros del mes para calcular cajas diarias
        $cobros = RegistroCobro::with(['bonosVendidos.bonoPlantilla', 'servicios', 'productos', 'cita.servicios', 'citasAgrupadas.servicios'])
            ->whereBetween('created_at', [$fechaInicio, $fechaFin])
            ->get();
        
        // Initializar array de cajas diarias con desglose efectivo/tarjeta
        $cajasDiarias = [];
        $diasDelMes = $fechaFin->day;
        
        for ($i = 1; $i <= $diasDelMes; $i++) {
            $fecha = Carbon::create($anio, $mes, $i)->format('Y-m-d');
            $cajasDiarias[$fecha] = [
                'total' => 0,
                'efectivo' => 0,
                'tarjeta' => 0,
                'peluqueria' => 0,
                'estetica' => 0,
            ];
        }

        // Procesar cada cobro para cajas diarias
        $facturacionService = new FacturacionService();
        
        foreach($cobros as $cobro) {
            $fechaCobro = $cobro->created_at->format('Y-m-d');
            if (isset($cajasDiarias[$fechaCobro])) {
                $montoPagadoServicios = $cobro->total_final;
                
                if ($cobro->metodo_pago === 'efectivo') {
                    $cajasDiarias[$fechaCobro]['total'] += $montoPagadoServicios;
                    $cajasDiarias[$fechaCobro]['efectivo'] += $montoPagadoServicios;
                } elseif ($cobro->metodo_pago === 'tarjeta') {
                    $cajasDiarias[$fechaCobro]['total'] += $montoPagadoServicios;
                    $cajasDiarias[$fechaCobro]['tarjeta'] += $montoPagadoServicios;
                } elseif ($cobro->metodo_pago === 'mixto') {
                    $cajasDiarias[$fechaCobro]['total'] += $montoPagadoServicios;
                    $cajasDiarias[$fechaCobro]['efectivo'] += $cobro->pago_efectivo ?? 0;
                    $cajasDiarias[$fechaCobro]['tarjeta'] += $cobro->pago_tarjeta ?? 0;
                } elseif ($cobro->metodo_pago === 'bono') {
                    // Cobro con bono: si total_final > 0 hay servicios extras que se cobraron
                    if ($montoPagadoServicios > 0.01) {
                        $cajasDiarias[$fechaCobro]['total'] += $montoPagadoServicios;
                        if (($cobro->pago_tarjeta ?? 0) > 0 || ($cobro->pago_efectivo ?? 0) > 0) {
                            $cajasDiarias[$fechaCobro]['tarjeta'] += $cobro->pago_tarjeta ?? 0;
                            $cajasDiarias[$fechaCobro]['efectivo'] += $cobro->pago_efectivo ?? 0;
                        }
                    }
                } elseif ($cobro->metodo_pago === 'deuda') {
                    // Deuda = dinero NO cobrado, no sumar a ningún método.
                    // Cuando se pague, el DeudaController crea un nuevo cobro
                    // con metodo_pago real (efectivo/tarjeta) que se contará normalmente.
                }
                    
                // Desglose peluquería/estética usando FacturacionService
                // Solo para cobros que no son deuda pura
                if ($cobro->metodo_pago !== 'deuda') {
                    $desglose = $facturacionService->desglosarCobroPorCategoria($cobro);
                    $cajasDiarias[$fechaCobro]['peluqueria'] += ($desglose['peluqueria']['servicios'] ?? 0)
                        + ($desglose['peluqueria']['productos'] ?? 0);
                    $cajasDiarias[$fechaCobro]['estetica'] += ($desglose['estetica']['servicios'] ?? 0)
                        + ($desglose['estetica']['productos'] ?? 0);
                }
                    
                // Sumar bonos vendidos por su propio método de pago
                if ($cobro->bonosVendidos && $cobro->bonosVendidos->count() > 0) {
                    foreach ($cobro->bonosVendidos as $bono) {
                        $metodoPagoBono = $bono->metodo_pago;
                        
                        if ($metodoPagoBono !== 'deuda') {
                            $precioBonoPagado = $bono->precio_pagado ?? 0;
                            $cajasDiarias[$fechaCobro]['total'] += $precioBonoPagado;
                            
                            if ($metodoPagoBono === 'efectivo') {
                                $cajasDiarias[$fechaCobro]['efectivo'] += $precioBonoPagado;
                            } elseif ($metodoPagoBono === 'tarjeta') {
                                $cajasDiarias[$fechaCobro]['tarjeta'] += $precioBonoPagado;
                            } elseif ($metodoPagoBono === 'mixto') {
                                // Usar desglose real si existe, sino fallback 50/50 para datos antiguos
                                if ($bono->pago_efectivo !== null && $bono->pago_tarjeta !== null) {
                                    $cajasDiarias[$fechaCobro]['efectivo'] += $bono->pago_efectivo;
                                    $cajasDiarias[$fechaCobro]['tarjeta'] += $bono->pago_tarjeta;
                                } else {
                                    $cajasDiarias[$fechaCobro]['efectivo'] += $precioBonoPagado / 2;
                                    $cajasDiarias[$fechaCobro]['tarjeta'] += $precioBonoPagado / 2;
                                }
                            }
                        }
                    }
                }
            }
        }
        
        // ============================================================================
        // CALCULAR BONOS VENDIDOS TOTALES (desglose por categoría incluido)
        // ============================================================================
        $bonosVendidos = $bonosPeluqueria + $bonosEstetica;
        
        // Calcular totales
        $totalServicios = $serviciosPeluqueria + $serviciosEstetica;
        $totalProductos = $productosPeluqueria + $productosEstetica;
        $totalGeneral = $totalServicios + $totalProductos + $bonosVendidos;
        
        // Calcular deuda total del mes (solo deudas pendientes)
        $deudaTotal = $cobros->sum('deuda');
        
        // Calcular suma de cajas diarias (debe ser igual a totalGeneral - deudaTotal)
        $sumaCajasDiarias = array_sum(array_column($cajasDiarias, 'total'));
        
        // Total realmente cobrado (lo que ingresó en caja)
        $totalRealmenteCobrado = $totalGeneral - $deudaTotal;

        // ============================================================================
        // CONSULTA DE FACTURACIÓN POR DÍA
        // ============================================================================
        $diaSeleccionado = $request->get('dia');
        $fechaDiaSeleccionado = null;
        $datosDiaSeleccionado = null;
        $cobrosDiaSeleccionado = collect();
        $deudaDiaSeleccionado = 0;
        $deudaBonosDiaSeleccionado = 0;
        $facturacionDia = [
            'serviciosPeluqueria' => 0,
            'serviciosEstetica' => 0,
            'productosPeluqueria' => 0,
            'productosEstetica' => 0,
            'bonosPeluqueria' => 0,
            'bonosEstetica' => 0,
            'totalServicios' => 0,
            'totalProductos' => 0,
            'totalBonos' => 0,
            'totalGeneral' => 0,
        ];

        if ($diaSeleccionado !== null && $diaSeleccionado !== '') {
            $diaEntero = (int) $diaSeleccionado;

            if ($diaEntero >= 1 && $diaEntero <= $fechaFin->day) {
                $fechaDiaSeleccionado = Carbon::create($anio, $mes, $diaEntero)->startOfDay();
                $fechaDiaKey = $fechaDiaSeleccionado->toDateString();
                $datosDiaSeleccionado = $cajasDiarias[$fechaDiaKey] ?? [
                    'total' => 0,
                    'efectivo' => 0,
                    'tarjeta' => 0,
                    'peluqueria' => 0,
                    'estetica' => 0,
                ];

                $cobrosDiaSeleccionado = $cobros
                    ->filter(fn ($cobro) => $cobro->created_at->isSameDay($fechaDiaSeleccionado))
                    ->values();

                $bonosDiaContados = collect();
                foreach ($cobrosDiaSeleccionado as $cobroDia) {
                    foreach ($cobroDia->bonosVendidos ?? collect() as $bonoDia) {
                        if (!$bonosDiaContados->contains($bonoDia->id)) {
                            $bonosDiaContados->push($bonoDia->id);
                            $deudaBonosDiaSeleccionado += max(0, ($bonoDia->pivot->precio ?? 0) - ($bonoDia->precio_pagado ?? 0));
                        }
                    }
                }

                $cobrosDiaFacturables = $cobrosDiaSeleccionado
                    ->filter(fn ($cobro) => $cobro->metodo_pago !== 'deuda')
                    ->values();

                foreach ($cobrosDiaFacturables as $cobroDia) {
                    $desgloseDia = $facturacionService->desglosarCobroPorCategoria($cobroDia);

                    $facturacionDia['serviciosPeluqueria'] += $desgloseDia['peluqueria']['servicios'] ?? 0;
                    $facturacionDia['serviciosEstetica'] += $desgloseDia['estetica']['servicios'] ?? 0;
                    $facturacionDia['productosPeluqueria'] += $desgloseDia['peluqueria']['productos'] ?? 0;
                    $facturacionDia['productosEstetica'] += $desgloseDia['estetica']['productos'] ?? 0;
                    $facturacionDia['bonosPeluqueria'] += $desgloseDia['peluqueria']['bonos'] ?? 0;
                    $facturacionDia['bonosEstetica'] += $desgloseDia['estetica']['bonos'] ?? 0;

                    $tieneServicios = ($cobroDia->cita && $cobroDia->cita->servicios && $cobroDia->cita->servicios->count() > 0)
                        || ($cobroDia->citasAgrupadas && $cobroDia->citasAgrupadas->count() > 0)
                        || ($cobroDia->servicios && $cobroDia->servicios->count() > 0);
                    $tieneProductos = $cobroDia->productos && $cobroDia->productos->count() > 0;
                    $desgloseTotalDia = ($desgloseDia['peluqueria']['servicios'] ?? 0)
                        + ($desgloseDia['peluqueria']['productos'] ?? 0)
                        + ($desgloseDia['peluqueria']['bonos'] ?? 0)
                        + ($desgloseDia['estetica']['servicios'] ?? 0)
                        + ($desgloseDia['estetica']['productos'] ?? 0)
                        + ($desgloseDia['estetica']['bonos'] ?? 0);

                    if (!$tieneServicios && !$tieneProductos && $cobroDia->total_final > 0 && $desgloseTotalDia < 0.01) {
                        $empleado = Empleado::find($cobroDia->id_empleado);
                        $categoriaEmpleado = $empleado?->categoria === 'estetica' ? 'Estetica' : 'Peluqueria';
                        $facturacionDia['servicios' . $categoriaEmpleado] += $cobroDia->total_final;
                    }
                }

                $facturacionDia['totalServicios'] = $facturacionDia['serviciosPeluqueria'] + $facturacionDia['serviciosEstetica'];
                $facturacionDia['totalProductos'] = $facturacionDia['productosPeluqueria'] + $facturacionDia['productosEstetica'];
                $facturacionDia['totalBonos'] = $facturacionDia['bonosPeluqueria'] + $facturacionDia['bonosEstetica'];
                $facturacionDia['totalGeneral'] = $facturacionDia['totalServicios'] + $facturacionDia['totalProductos'] + $facturacionDia['totalBonos'];
                $deudaDiaSeleccionado = $cobrosDiaSeleccionado->sum('deuda') + $deudaBonosDiaSeleccionado;

                foreach ($facturacionDia as $clave => $valor) {
                    $facturacionDia[$clave] = round($valor, 2);
                }
            } else {
                $diaSeleccionado = null;
            }
        }
        
        $resumenActual = [
            'serviciosPeluqueria' => $serviciosPeluqueria,
            'serviciosEstetica' => $serviciosEstetica,
            'productosPeluqueria' => $productosPeluqueria,
            'productosEstetica' => $productosEstetica,
            'bonosPeluqueria' => $bonosPeluqueria,
            'bonosEstetica' => $bonosEstetica,
            'bonosVendidos' => $bonosVendidos,
            'totalServicios' => $totalServicios,
            'totalProductos' => $totalProductos,
            'totalGeneral' => $totalGeneral,
            'deudaTotal' => $deudaTotal,
            'sumaCajasDiarias' => $sumaCajasDiarias,
            'totalRealmenteCobrado' => $totalRealmenteCobrado,
            'totalPeluqueria' => $serviciosPeluqueria + $productosPeluqueria + $bonosPeluqueria,
            'totalEstetica' => $serviciosEstetica + $productosEstetica + $bonosEstetica,
            'cobrosCount' => $cobros->count(),
        ];

        $estadisticasAvanzadas = $this->calcularEstadisticasAvanzadas($mes, $anio, $resumenActual, $cajasDiarias, $cobros);

        // Obtener lista de meses para el selector
        $meses = $this->mesesDisponibles();

        return view('facturacion.index', compact(
            'serviciosPeluqueria',
            'serviciosEstetica',
            'productosPeluqueria',
            'productosEstetica',
            'bonosPeluqueria',
            'bonosEstetica',
            'bonosVendidos',
            'totalServicios',
            'totalProductos',
            'totalGeneral',
            'deudaTotal',
            'sumaCajasDiarias',
            'totalRealmenteCobrado',
            'mes',
            'anio',
            'meses',
            'fechaInicio',
            'fechaFin',
            'cajasDiarias',
            'diaSeleccionado',
            'fechaDiaSeleccionado',
            'datosDiaSeleccionado',
            'cobrosDiaSeleccionado',
            'deudaDiaSeleccionado',
            'facturacionDia',
            'estadisticasAvanzadas'
        ));
    }

    public function exportar(Request $request, string $formato)
    {
        Carbon::setLocale('es');

        $formato = strtolower($formato);
        if (!in_array($formato, ['excel', 'word', 'pdf'], true)) {
            abort(404);
        }

        [$mes, $anio] = $this->resolverMesAnio($request);
        $fechaInicio = Carbon::create($anio, $mes, 1)->startOfMonth();
        $fechaFin = Carbon::create($anio, $mes, 1)->endOfMonth();
        $meses = $this->mesesDisponibles();

        $resumen = $this->resumenFacturacionPeriodo($fechaInicio, $fechaFin, true);
        $estadisticas = $this->calcularEstadisticasAvanzadas(
            $mes,
            $anio,
            $resumen,
            $resumen['cajasDiarias'],
            $resumen['cobros']
        );

        $nombreBase = 'facturacion_' . $anio . '_' . str_pad((string) $mes, 2, '0', STR_PAD_LEFT);

        if ($formato === 'pdf') {
            $pdf = $this->generarPdfDesdeLineas(
                $this->lineasPdfExportacion($resumen, $estadisticas, $meses[$mes], $anio, $fechaInicio, $fechaFin)
            );

            return response($pdf, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $nombreBase . '.pdf"',
            ]);
        }

        $html = $this->htmlExportacion($resumen, $estadisticas, $meses[$mes], $anio, $fechaInicio, $fechaFin);
        $extension = $formato === 'excel' ? 'xls' : 'doc';
        $contentType = $formato === 'excel'
            ? 'application/vnd.ms-excel; charset=UTF-8'
            : 'application/msword; charset=UTF-8';

        return response("\xEF\xBB\xBF" . $html, 200, [
            'Content-Type' => $contentType,
            'Content-Disposition' => 'attachment; filename="' . $nombreBase . '.' . $extension . '"',
        ]);
    }

    private function resolverMesAnio(Request $request): array
    {
        $mes = (int) $request->get('mes', now()->month);
        $anio = (int) $request->get('anio', now()->year);

        if ($mes < 1 || $mes > 12) {
            $mes = now()->month;
        }

        if ($anio < 2000 || $anio > now()->year + 5) {
            $anio = now()->year;
        }

        return [$mes, $anio];
    }

    private function mesesDisponibles(): array
    {
        return [
            1 => 'Enero',
            2 => 'Febrero',
            3 => 'Marzo',
            4 => 'Abril',
            5 => 'Mayo',
            6 => 'Junio',
            7 => 'Julio',
            8 => 'Agosto',
            9 => 'Septiembre',
            10 => 'Octubre',
            11 => 'Noviembre',
            12 => 'Diciembre',
        ];
    }

    private function resumenFacturacionPeriodo(Carbon $fechaInicio, Carbon $fechaFin, bool $incluirCajas = false): array
    {
        $inicio = $fechaInicio->copy()->startOfDay();
        $fin = $fechaFin->copy()->endOfDay();

        $facturacionCategoria = Empleado::facturacionPorCategoriaPorFechas($inicio->copy(), $fin->copy());

        $serviciosPeluqueria = $facturacionCategoria['peluqueria']['servicios'] ?? 0;
        $serviciosEstetica = $facturacionCategoria['estetica']['servicios'] ?? 0;
        $productosPeluqueria = $facturacionCategoria['peluqueria']['productos'] ?? 0;
        $productosEstetica = $facturacionCategoria['estetica']['productos'] ?? 0;
        $bonosPeluqueria = $facturacionCategoria['peluqueria']['bonos'] ?? 0;
        $bonosEstetica = $facturacionCategoria['estetica']['bonos'] ?? 0;

        $cobros = RegistroCobro::with([
            'bonosVendidos.bonoPlantilla',
            'servicios',
            'productos',
            'cita.servicios',
            'citasAgrupadas.servicios',
        ])
            ->whereBetween('created_at', [$inicio, $fin])
            ->get();

        $bonosVendidos = $bonosPeluqueria + $bonosEstetica;
        $totalServicios = $serviciosPeluqueria + $serviciosEstetica;
        $totalProductos = $productosPeluqueria + $productosEstetica;
        $totalGeneral = $totalServicios + $totalProductos + $bonosVendidos;
        $deudaTotal = $cobros->sum('deuda');
        $cajasDiarias = $incluirCajas ? $this->calcularCajasDiarias($cobros, $inicio, $fin) : [];
        $sumaCajasDiarias = $incluirCajas ? array_sum(array_column($cajasDiarias, 'total')) : ($totalGeneral - $deudaTotal);

        return [
            'serviciosPeluqueria' => round($serviciosPeluqueria, 2),
            'serviciosEstetica' => round($serviciosEstetica, 2),
            'productosPeluqueria' => round($productosPeluqueria, 2),
            'productosEstetica' => round($productosEstetica, 2),
            'bonosPeluqueria' => round($bonosPeluqueria, 2),
            'bonosEstetica' => round($bonosEstetica, 2),
            'bonosVendidos' => round($bonosVendidos, 2),
            'totalServicios' => round($totalServicios, 2),
            'totalProductos' => round($totalProductos, 2),
            'totalGeneral' => round($totalGeneral, 2),
            'deudaTotal' => round($deudaTotal, 2),
            'sumaCajasDiarias' => round($sumaCajasDiarias, 2),
            'totalRealmenteCobrado' => round($sumaCajasDiarias, 2),
            'totalPeluqueria' => round($serviciosPeluqueria + $productosPeluqueria + $bonosPeluqueria, 2),
            'totalEstetica' => round($serviciosEstetica + $productosEstetica + $bonosEstetica, 2),
            'cobrosCount' => $cobros->count(),
            'cobros' => $cobros,
            'cajasDiarias' => $cajasDiarias,
        ];
    }

    private function calcularCajasDiarias($cobros, Carbon $fechaInicio, Carbon $fechaFin): array
    {
        $cajasDiarias = [];
        $cursor = $fechaInicio->copy()->startOfDay();
        $fin = $fechaFin->copy()->endOfDay();
        $facturacionService = new FacturacionService();

        while ($cursor->lte($fin)) {
            $cajasDiarias[$cursor->toDateString()] = [
                'total' => 0,
                'efectivo' => 0,
                'tarjeta' => 0,
                'peluqueria' => 0,
                'estetica' => 0,
            ];
            $cursor->addDay();
        }

        foreach ($cobros as $cobro) {
            $fechaCobro = $cobro->created_at->format('Y-m-d');
            if (!isset($cajasDiarias[$fechaCobro])) {
                continue;
            }

            $montoPagadoServicios = (float) $cobro->total_final;

            if ($cobro->metodo_pago === 'efectivo') {
                $cajasDiarias[$fechaCobro]['total'] += $montoPagadoServicios;
                $cajasDiarias[$fechaCobro]['efectivo'] += $montoPagadoServicios;
            } elseif ($cobro->metodo_pago === 'tarjeta') {
                $cajasDiarias[$fechaCobro]['total'] += $montoPagadoServicios;
                $cajasDiarias[$fechaCobro]['tarjeta'] += $montoPagadoServicios;
            } elseif ($cobro->metodo_pago === 'mixto') {
                $cajasDiarias[$fechaCobro]['total'] += $montoPagadoServicios;
                $cajasDiarias[$fechaCobro]['efectivo'] += $cobro->pago_efectivo ?? 0;
                $cajasDiarias[$fechaCobro]['tarjeta'] += $cobro->pago_tarjeta ?? 0;
            } elseif ($cobro->metodo_pago === 'bono' && $montoPagadoServicios > 0.01) {
                $cajasDiarias[$fechaCobro]['total'] += $montoPagadoServicios;
                $cajasDiarias[$fechaCobro]['efectivo'] += $cobro->pago_efectivo ?? 0;
                $cajasDiarias[$fechaCobro]['tarjeta'] += $cobro->pago_tarjeta ?? 0;
            }

            if ($cobro->metodo_pago !== 'deuda') {
                $desglose = $facturacionService->desglosarCobroPorCategoria($cobro);
                $cajasDiarias[$fechaCobro]['peluqueria'] += ($desglose['peluqueria']['servicios'] ?? 0)
                    + ($desglose['peluqueria']['productos'] ?? 0);
                $cajasDiarias[$fechaCobro]['estetica'] += ($desglose['estetica']['servicios'] ?? 0)
                    + ($desglose['estetica']['productos'] ?? 0);
            }

            foreach ($cobro->bonosVendidos ?? collect() as $bono) {
                $metodoPagoBono = $bono->metodo_pago;
                if ($metodoPagoBono === 'deuda') {
                    continue;
                }

                $precioBonoPagado = $bono->precio_pagado ?? 0;
                $cajasDiarias[$fechaCobro]['total'] += $precioBonoPagado;

                if ($metodoPagoBono === 'efectivo') {
                    $cajasDiarias[$fechaCobro]['efectivo'] += $precioBonoPagado;
                } elseif ($metodoPagoBono === 'tarjeta') {
                    $cajasDiarias[$fechaCobro]['tarjeta'] += $precioBonoPagado;
                } elseif ($metodoPagoBono === 'mixto') {
                    if ($bono->pago_efectivo !== null && $bono->pago_tarjeta !== null) {
                        $cajasDiarias[$fechaCobro]['efectivo'] += $bono->pago_efectivo;
                        $cajasDiarias[$fechaCobro]['tarjeta'] += $bono->pago_tarjeta;
                    } else {
                        $cajasDiarias[$fechaCobro]['efectivo'] += $precioBonoPagado / 2;
                        $cajasDiarias[$fechaCobro]['tarjeta'] += $precioBonoPagado / 2;
                    }
                }
            }
        }

        foreach ($cajasDiarias as $fecha => $datos) {
            foreach ($datos as $clave => $valor) {
                $cajasDiarias[$fecha][$clave] = round($valor, 2);
            }
        }

        return $cajasDiarias;
    }

    private function calcularEstadisticasAvanzadas(int $mes, int $anio, array $actual, array $cajasDiarias, $cobros): array
    {
        $periodo = Carbon::create($anio, $mes, 1);

        $mesAnteriorInicio = $periodo->copy()->subMonthNoOverflow()->startOfMonth();
        $mesAnteriorFin = $mesAnteriorInicio->copy()->endOfMonth();
        $mesAnterior = $this->resumenFacturacionPeriodo($mesAnteriorInicio, $mesAnteriorFin);

        $mismoMesAnioAnteriorInicio = $periodo->copy()->subYearNoOverflow()->startOfMonth();
        $mismoMesAnioAnteriorFin = $mismoMesAnioAnteriorInicio->copy()->endOfMonth();
        $mismoMesAnioAnterior = $this->resumenFacturacionPeriodo($mismoMesAnioAnteriorInicio, $mismoMesAnioAnteriorFin);

        $anioActual = $this->resumenFacturacionPeriodo(
            Carbon::create($anio, 1, 1)->startOfYear(),
            Carbon::create($anio, 12, 31)->endOfYear()
        );
        $anioAnterior = $this->resumenFacturacionPeriodo(
            Carbon::create($anio - 1, 1, 1)->startOfYear(),
            Carbon::create($anio - 1, 12, 31)->endOfYear()
        );

        $diasConVentas = collect($cajasDiarias)->filter(fn ($dia) => ($dia['total'] ?? 0) > 0.01)->count();
        $mejorDiaFecha = null;
        $mejorDiaTotal = 0;

        foreach ($cajasDiarias as $fecha => $datos) {
            if (($datos['total'] ?? 0) > $mejorDiaTotal) {
                $mejorDiaTotal = $datos['total'];
                $mejorDiaFecha = $fecha;
            }
        }

        $cobrosFacturables = $cobros->filter(fn ($cobro) => $cobro->metodo_pago !== 'deuda')->count();
        $totalGeneral = $actual['totalGeneral'] ?? 0;

        return [
            'mesAnterior' => [
                'etiqueta' => $this->mesesDisponibles()[$mesAnteriorInicio->month] . ' ' . $mesAnteriorInicio->year,
                'resumen' => $mesAnterior,
                'variacionTotal' => $this->calcularVariacion($totalGeneral, $mesAnterior['totalGeneral'] ?? 0),
                'variacionProductos' => $this->calcularVariacion($actual['totalProductos'] ?? 0, $mesAnterior['totalProductos'] ?? 0),
                'variacionServicios' => $this->calcularVariacion($actual['totalServicios'] ?? 0, $mesAnterior['totalServicios'] ?? 0),
            ],
            'mismoMesAnioAnterior' => [
                'etiqueta' => $this->mesesDisponibles()[$mismoMesAnioAnteriorInicio->month] . ' ' . $mismoMesAnioAnteriorInicio->year,
                'resumen' => $mismoMesAnioAnterior,
                'variacionTotal' => $this->calcularVariacion($totalGeneral, $mismoMesAnioAnterior['totalGeneral'] ?? 0),
                'variacionProductos' => $this->calcularVariacion($actual['totalProductos'] ?? 0, $mismoMesAnioAnterior['totalProductos'] ?? 0),
                'variacionServicios' => $this->calcularVariacion($actual['totalServicios'] ?? 0, $mismoMesAnioAnterior['totalServicios'] ?? 0),
            ],
            'anio' => [
                'actual' => $anioActual,
                'anterior' => $anioAnterior,
                'variacionTotal' => $this->calcularVariacion($anioActual['totalGeneral'] ?? 0, $anioAnterior['totalGeneral'] ?? 0),
                'variacionProductos' => $this->calcularVariacion($anioActual['totalProductos'] ?? 0, $anioAnterior['totalProductos'] ?? 0),
            ],
            'operativa' => [
                'diasConVentas' => $diasConVentas,
                'promedioDiaActivo' => $diasConVentas > 0 ? round($totalGeneral / $diasConVentas, 2) : 0,
                'ticketMedio' => $cobrosFacturables > 0 ? round(($actual['sumaCajasDiarias'] ?? 0) / $cobrosFacturables, 2) : 0,
                'mejorDiaFecha' => $mejorDiaFecha,
                'mejorDiaLabel' => $mejorDiaFecha ? Carbon::parse($mejorDiaFecha)->format('d/m/Y') : 'Sin ventas',
                'mejorDiaTotal' => round($mejorDiaTotal, 2),
                'cobrosFacturables' => $cobrosFacturables,
            ],
            'mix' => [
                'peluqueria' => $totalGeneral > 0 ? round((($actual['totalPeluqueria'] ?? 0) / $totalGeneral) * 100, 1) : 0,
                'estetica' => $totalGeneral > 0 ? round((($actual['totalEstetica'] ?? 0) / $totalGeneral) * 100, 1) : 0,
                'servicios' => $totalGeneral > 0 ? round((($actual['totalServicios'] ?? 0) / $totalGeneral) * 100, 1) : 0,
                'productos' => $totalGeneral > 0 ? round((($actual['totalProductos'] ?? 0) / $totalGeneral) * 100, 1) : 0,
                'bonos' => $totalGeneral > 0 ? round((($actual['bonosVendidos'] ?? 0) / $totalGeneral) * 100, 1) : 0,
            ],
        ];
    }

    private function calcularVariacion(float $actual, float $anterior): array
    {
        $diferencia = round($actual - $anterior, 2);
        $porcentaje = abs($anterior) > 0.01
            ? round(($diferencia / $anterior) * 100, 1)
            : ($actual > 0.01 ? 100.0 : 0.0);

        return [
            'diferencia' => $diferencia,
            'porcentaje' => $porcentaje,
            'estado' => $diferencia > 0.01 ? 'up' : ($diferencia < -0.01 ? 'down' : 'flat'),
        ];
    }

    private function htmlExportacion(array $resumen, array $estadisticas, string $nombreMes, int $anio, Carbon $fechaInicio, Carbon $fechaFin): string
    {
        $e = fn ($valor) => htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
        $money = fn ($valor) => $this->formatoMoneda($valor);

        $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>
            body{font-family:Arial,sans-serif;color:#1f2937}
            h1{font-size:22px;margin-bottom:4px}
            h2{font-size:16px;margin-top:22px}
            table{border-collapse:collapse;width:100%;margin-top:10px}
            th,td{border:1px solid #d1d5db;padding:7px;text-align:left;font-size:12px}
            th{background:#1e1a4b;color:#fff}
            .num{text-align:right}
            .muted{color:#6b7280;font-size:12px}
        </style></head><body>';

        $html .= '<h1>Facturacion mensual - ' . $e($nombreMes . ' ' . $anio) . '</h1>';
        $html .= '<p class="muted">Periodo: ' . $e($fechaInicio->format('d/m/Y')) . ' - ' . $e($fechaFin->format('d/m/Y')) . '</p>';

        $html .= '<h2>Resumen</h2><table><tr><th>Concepto</th><th>Importe</th></tr>';
        foreach ([
            'Servicios' => $resumen['totalServicios'],
            'Productos' => $resumen['totalProductos'],
            'Bonos' => $resumen['bonosVendidos'],
            'Total facturado' => $resumen['totalGeneral'],
            'Deuda pendiente' => $resumen['deudaTotal'],
            'Total cobrado en caja' => $resumen['sumaCajasDiarias'],
        ] as $label => $valor) {
            $html .= '<tr><td>' . $e($label) . '</td><td class="num">' . $e($money($valor)) . '</td></tr>';
        }
        $html .= '</table>';

        $html .= '<h2>Comparativas</h2><table><tr><th>Comparativa</th><th>Total base</th><th>Diferencia</th><th>Variacion</th></tr>';
        foreach ([
            'Mes anterior (' . $estadisticas['mesAnterior']['etiqueta'] . ')' => $estadisticas['mesAnterior'],
            'Mismo mes año anterior (' . $estadisticas['mismoMesAnioAnterior']['etiqueta'] . ')' => $estadisticas['mismoMesAnioAnterior'],
            'Año anterior' => $estadisticas['anio'],
        ] as $label => $datos) {
            $base = $datos['resumen']['totalGeneral'] ?? $datos['anterior']['totalGeneral'] ?? 0;
            $variacion = $datos['variacionTotal'];
            $html .= '<tr><td>' . $e($label) . '</td><td class="num">' . $e($money($base)) . '</td><td class="num">' . $e($money($variacion['diferencia'])) . '</td><td class="num">' . $e($variacion['porcentaje'] . '%') . '</td></tr>';
        }
        $html .= '</table>';

        $html .= '<h2>Desglose por categoria</h2><table><tr><th>Categoria</th><th>Servicios</th><th>Productos</th><th>Bonos</th><th>Total</th></tr>';
        $html .= '<tr><td>Peluqueria</td><td class="num">' . $e($money($resumen['serviciosPeluqueria'])) . '</td><td class="num">' . $e($money($resumen['productosPeluqueria'])) . '</td><td class="num">' . $e($money($resumen['bonosPeluqueria'])) . '</td><td class="num">' . $e($money($resumen['totalPeluqueria'])) . '</td></tr>';
        $html .= '<tr><td>Estetica</td><td class="num">' . $e($money($resumen['serviciosEstetica'])) . '</td><td class="num">' . $e($money($resumen['productosEstetica'])) . '</td><td class="num">' . $e($money($resumen['bonosEstetica'])) . '</td><td class="num">' . $e($money($resumen['totalEstetica'])) . '</td></tr>';
        $html .= '</table>';

        $html .= '<h2>Cajas diarias</h2><table><tr><th>Fecha</th><th>Total</th><th>Efectivo</th><th>Tarjeta</th><th>Peluqueria</th><th>Estetica</th></tr>';
        foreach ($resumen['cajasDiarias'] as $fecha => $datos) {
            $html .= '<tr><td>' . $e(Carbon::parse($fecha)->format('d/m/Y')) . '</td><td class="num">' . $e($money($datos['total'])) . '</td><td class="num">' . $e($money($datos['efectivo'])) . '</td><td class="num">' . $e($money($datos['tarjeta'])) . '</td><td class="num">' . $e($money($datos['peluqueria'])) . '</td><td class="num">' . $e($money($datos['estetica'])) . '</td></tr>';
        }
        $html .= '</table></body></html>';

        return $html;
    }

    private function lineasPdfExportacion(array $resumen, array $estadisticas, string $nombreMes, int $anio, Carbon $fechaInicio, Carbon $fechaFin): array
    {
        $lineas = [
            'Facturacion mensual - ' . $nombreMes . ' ' . $anio,
            'Periodo: ' . $fechaInicio->format('d/m/Y') . ' - ' . $fechaFin->format('d/m/Y'),
            '',
            'Resumen',
            'Servicios: ' . $this->formatoMonedaPlano($resumen['totalServicios']),
            'Productos: ' . $this->formatoMonedaPlano($resumen['totalProductos']),
            'Bonos: ' . $this->formatoMonedaPlano($resumen['bonosVendidos']),
            'Total facturado: ' . $this->formatoMonedaPlano($resumen['totalGeneral']),
            'Deuda pendiente: ' . $this->formatoMonedaPlano($resumen['deudaTotal']),
            'Total cobrado en caja: ' . $this->formatoMonedaPlano($resumen['sumaCajasDiarias']),
            '',
            'Comparativas',
            'Mes anterior (' . $estadisticas['mesAnterior']['etiqueta'] . '): ' . $this->formatoVariacionPlano($estadisticas['mesAnterior']['variacionTotal']),
            'Mismo mes anio anterior (' . $estadisticas['mismoMesAnioAnterior']['etiqueta'] . '): ' . $this->formatoVariacionPlano($estadisticas['mismoMesAnioAnterior']['variacionTotal']),
            'Anio ' . $anio . ' vs ' . ($anio - 1) . ': ' . $this->formatoVariacionPlano($estadisticas['anio']['variacionTotal']),
            'Productos vs mes anterior: ' . $this->formatoVariacionPlano($estadisticas['mesAnterior']['variacionProductos']),
            '',
            'Indicadores',
            'Dias con ventas: ' . $estadisticas['operativa']['diasConVentas'],
            'Ticket medio: ' . $this->formatoMonedaPlano($estadisticas['operativa']['ticketMedio']),
            'Mejor dia: ' . $estadisticas['operativa']['mejorDiaLabel'] . ' (' . $this->formatoMonedaPlano($estadisticas['operativa']['mejorDiaTotal']) . ')',
            '',
            'Cajas diarias',
        ];

        foreach ($resumen['cajasDiarias'] as $fecha => $datos) {
            $lineas[] = Carbon::parse($fecha)->format('d/m/Y')
                . ' | Total ' . $this->formatoMonedaPlano($datos['total'])
                . ' | Efec. ' . $this->formatoMonedaPlano($datos['efectivo'])
                . ' | Tarj. ' . $this->formatoMonedaPlano($datos['tarjeta']);
        }

        return $lineas;
    }

    private function generarPdfDesdeLineas(array $lineas): string
    {
        $chunks = array_chunk($lineas, 44);
        $objects = [];
        $pageIds = [];
        $fontId = 3 + (count($chunks) * 2);

        $objects[1] = '<< /Type /Catalog /Pages 2 0 R >>';

        foreach ($chunks as $index => $chunk) {
            $pageId = 3 + ($index * 2);
            $contentId = $pageId + 1;
            $pageIds[] = $pageId . ' 0 R';
            $stream = $this->contenidoPdfPagina($chunk);

            $objects[$pageId] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 ' . $fontId . ' 0 R >> >> /Contents ' . $contentId . ' 0 R >>';
            $objects[$contentId] = "<< /Length " . strlen($stream) . " >>\nstream\n" . $stream . "\nendstream";
        }

        $objects[2] = '<< /Type /Pages /Kids [' . implode(' ', $pageIds) . '] /Count ' . count($pageIds) . ' >>';
        $objects[$fontId] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';

        ksort($objects);

        $pdf = "%PDF-1.4\n";
        $offsets = [];
        foreach ($objects as $id => $body) {
            $offsets[$id] = strlen($pdf);
            $pdf .= $id . " 0 obj\n" . $body . "\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $maxId = max(array_keys($objects));
        $pdf .= "xref\n0 " . ($maxId + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";
        for ($id = 1; $id <= $maxId; $id++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$id] ?? 0);
        }
        $pdf .= "trailer\n<< /Size " . ($maxId + 1) . " /Root 1 0 R >>\n";
        $pdf .= "startxref\n" . $xrefOffset . "\n%%EOF";

        return $pdf;
    }

    private function contenidoPdfPagina(array $lineas): string
    {
        $stream = "BT\n/F1 10 Tf\n50 800 Td\n14 TL\n";

        foreach ($lineas as $linea) {
            $stream .= '(' . $this->escaparTextoPdf($linea) . ") Tj\nT*\n";
        }

        return $stream . "ET";
    }

    private function escaparTextoPdf(string $texto): string
    {
        $texto = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto) ?: $texto;
        $texto = substr($texto, 0, 105);

        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $texto);
    }

    private function formatoMoneda(float|int $valor): string
    {
        return number_format((float) $valor, 2, ',', '.') . ' €';
    }

    private function formatoMonedaPlano(float|int $valor): string
    {
        return number_format((float) $valor, 2, ',', '.') . ' EUR';
    }

    private function formatoVariacionPlano(array $variacion): string
    {
        $signo = ($variacion['diferencia'] ?? 0) > 0 ? '+' : '';

        return $signo . $this->formatoMonedaPlano($variacion['diferencia'] ?? 0)
            . ' (' . $signo . number_format((float) ($variacion['porcentaje'] ?? 0), 1, ',', '.') . '%)';
    }
}
