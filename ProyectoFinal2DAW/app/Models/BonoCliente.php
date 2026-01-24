<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class BonoCliente extends Model
{
    protected $table = 'bonos_clientes';

    protected $fillable = [
        'cliente_id',
        'bono_plantilla_id',
        'fecha_compra',
        'fecha_expiracion',
        'estado',
        'metodo_pago',
        'precio_pagado',
        'dinero_cliente',
        'cambio',
        'id_empleado'
    ];

    protected $casts = [
        'fecha_compra' => 'date',
        'fecha_expiracion' => 'date'
    ];

    /**
     * Cliente propietario del bono
     */
    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    /**
     * Plantilla del bono
     */
    public function plantilla()
    {
        return $this->belongsTo(BonoPlantilla::class, 'bono_plantilla_id');
    }

    /**
     * Alias para plantilla() - para consistencia con eager loading
     */
    public function bonoPlantilla()
    {
        return $this->plantilla();
    }

    /**
     * Servicios del bono con sus cantidades
     */
    public function servicios()
    {
        return $this->belongsToMany(Servicio::class, 'bono_cliente_servicios', 'bono_cliente_id', 'servicio_id')
                    ->withPivot('cantidad_total', 'cantidad_usada')
                    ->withTimestamps();
    }

    /**
     * Verificar si el bono está expirado
     */
    public function estaExpirado()
    {
        return Carbon::now()->greaterThan($this->fecha_expiracion);
    }

    /**
     * Verificar si el bono está completamente usado
     */
    public function estaCompletamenteUsado()
    {
        foreach ($this->servicios as $servicio) {
            if ($servicio->pivot->cantidad_usada < $servicio->pivot->cantidad_total) {
                return false;
            }
        }
        return true;
    }

    /**
     * Obtener cantidad disponible de un servicio
     */
    public function cantidadDisponible($servicioId)
    {
        $servicio = $this->servicios()->where('servicio_id', $servicioId)->first();
        if (!$servicio) {
            return 0;
        }
        return $servicio->pivot->cantidad_total - $servicio->pivot->cantidad_usada;
    }

    /**
     * Empleado que vendió el bono
     */
    public function empleado()
    {
        return $this->belongsTo(Empleado::class, 'id_empleado');
    }

    /**
     * Detalles de uso del bono (histórico de usos por cita)
     */
    public function usoDetalles()
    {
        return $this->hasMany(BonoUsoDetalle::class, 'bono_cliente_id');
    }

    /**
     * Verificar si el bono está próximo a expirar (menos de 7 días)
     */
    public function proximoAExpirar()
    {
        if (!$this->fecha_expiracion) {
            return false;
        }
        $diasRestantes = Carbon::now()->diffInDays($this->fecha_expiracion, false);
        return $diasRestantes >= 0 && $diasRestantes <= 7;
    }

    /**
     * Verificar si el bono expira en menos de 3 días (crítico)
     */
    public function expiracionCritica()
    {
        if (!$this->fecha_expiracion) {
            return false;
        }
        $diasRestantes = Carbon::now()->diffInDays($this->fecha_expiracion, false);
        return $diasRestantes >= 0 && $diasRestantes <= 3;
    }

    /**
     * Obtener días restantes hasta la expiración
     */
    public function diasRestantes()
    {
        if (!$this->fecha_expiracion) {
            return null;
        }
        return Carbon::now()->diffInDays($this->fecha_expiracion, false);
    }

    /**
     * Verificar si algún servicio tiene solo 1 uso restante
     */
    public function tieneServiciosPorAgotar()
    {
        foreach ($this->servicios as $servicio) {
            $disponible = $servicio->pivot->cantidad_total - $servicio->pivot->cantidad_usada;
            if ($disponible === 1) {
                return true;
            }
        }
        return false;
    }

    /**
     * Obtener información de estado del bono para alertas
     */
    public function obtenerEstadoAlerta()
    {
        $alertas = [];
        
        // Verificar expiración
        $diasRestantes = $this->diasRestantes();
        if ($diasRestantes !== null && $diasRestantes >= 0) {
            if ($diasRestantes <= 3) {
                $alertas[] = [
                    'tipo' => 'critico',
                    'icono' => '🔴',
                    'mensaje' => "El bono expira en " . ceil($diasRestantes) . " día(s)"
                ];
            } elseif ($diasRestantes <= 7) {
                $alertas[] = [
                    'tipo' => 'advertencia',
                    'icono' => '🟡',
                    'mensaje' => "El bono expira en " . ceil($diasRestantes) . " días"
                ];
            }
        }
        
        // Verificar servicios por agotar
        // Primero intentar con servicios del bono cliente (tabla pivot)
        if ($this->servicios && $this->servicios->count() > 0) {
            foreach ($this->servicios as $servicio) {
                $disponible = $servicio->pivot->cantidad_total - $servicio->pivot->cantidad_usada;
                if ($disponible === 1) {
                    $alertas[] = [
                        'tipo' => 'advertencia',
                        'icono' => '🟡',
                        'mensaje' => "Solo queda 1 uso de {$servicio->nombre}"
                    ];
                } elseif ($disponible === 0) {
                    $alertas[] = [
                        'tipo' => 'critico',
                        'icono' => '🔴',
                        'mensaje' => "Servicio {$servicio->nombre} agotado"
                    ];
                }
            }
        } else {
            // Si no tiene servicios en pivot, usar los de la plantilla con cantidadDisponible()
            if ($this->plantilla && $this->plantilla->servicios) {
                foreach ($this->plantilla->servicios as $servicio) {
                    $disponible = $this->cantidadDisponible($servicio->id);
                    if ($disponible === 1) {
                        $alertas[] = [
                            'tipo' => 'advertencia',
                            'icono' => '🟡',
                            'mensaje' => "Solo queda 1 uso de {$servicio->nombre}"
                        ];
                    } elseif ($disponible === 0) {
                        $alertas[] = [
                            'tipo' => 'critico',
                            'icono' => '🔴',
                            'mensaje' => "Servicio {$servicio->nombre} agotado"
                        ];
                    }
                }
            }
        }
        
        return $alertas;
    }
}
