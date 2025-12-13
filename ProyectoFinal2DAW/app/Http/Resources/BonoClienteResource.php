<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BonoClienteResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'fecha_compra' => $this->fecha_compra?->format('Y-m-d'),
            'fecha_compra_formatted' => $this->fecha_compra?->format('d/m/Y'),
            'fecha_expiracion' => $this->fecha_expiracion?->format('Y-m-d'),
            'fecha_expiracion_formatted' => $this->fecha_expiracion?->format('d/m/Y'),
            'estado' => $this->estado,
            'estado_formatted' => ucfirst($this->estado),
            'esta_activo' => $this->estado === 'activo',
            'esta_vencido' => $this->fecha_expiracion && $this->fecha_expiracion->isPast(),
            'dias_restantes' => $this->fecha_expiracion ? now()->diffInDays($this->fecha_expiracion, false) : null,
            
            // Bono plantilla
            'bono_plantilla' => $this->whenLoaded('bonoPlantilla', function () {
                return [
                    'id' => $this->bonoPlantilla->id,
                    'nombre' => $this->bonoPlantilla->nombre,
                    'descripcion' => $this->bonoPlantilla->descripcion,
                    'precio' => $this->bonoPlantilla->precio,
                    'precio_formatted' => number_format($this->bonoPlantilla->precio, 2) . ' €',
                    'servicios_incluidos' => $this->bonoPlantilla->servicios->count(),
                ];
            }),
            
            // Cliente
            'cliente' => $this->whenLoaded('cliente', function () {
                return [
                    'id' => $this->cliente->id,
                    'nombre_completo' => $this->cliente->user->nombre . ' ' . $this->cliente->user->apellidos,
                ];
            }),
            
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
