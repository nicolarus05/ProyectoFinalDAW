<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CitaResource extends JsonResource
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
            'fecha_hora' => $this->fecha_hora?->toIso8601String(),
            'fecha' => $this->fecha_hora?->format('Y-m-d'),
            'hora' => $this->fecha_hora?->format('H:i'),
            'fecha_formatted' => $this->fecha_hora?->format('d/m/Y'),
            'hora_formatted' => $this->fecha_hora?->format('H:i'),
            'fecha_hora_formatted' => $this->fecha_hora?->format('d/m/Y H:i'),
            'duracion_total' => $this->duracion_total,
            'duracion_formatted' => $this->duracion_total . ' minutos',
            'estado' => $this->estado,
            'estado_formatted' => ucfirst($this->estado),
            'notas' => $this->notas,
            'grupo_cita_id' => $this->grupo_cita_id,
            
            // Cliente
            'cliente' => $this->whenLoaded('cliente', function () {
                return new ClienteResource($this->cliente);
            }),
            
            // Empleado
            'empleado' => $this->whenLoaded('empleado', function () {
                return new EmpleadoResource($this->empleado);
            }),
            
            // Servicios
            'servicios' => $this->whenLoaded('servicios', function () {
                return ServicioResource::collection($this->servicios);
            }),
            
            // Cobro asociado
            'cobro' => $this->whenLoaded('cobro', function () {
                return [
                    'id' => $this->cobro->id,
                    'total_final' => $this->cobro->total_final,
                    'metodo_pago' => $this->cobro->metodo_pago,
                    'pagado' => true,
                ];
            }),
            
            'tiene_cobro' => $this->relationLoaded('cobro') && $this->cobro !== null,
            
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
