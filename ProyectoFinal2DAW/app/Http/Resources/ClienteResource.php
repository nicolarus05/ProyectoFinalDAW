<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClienteResource extends JsonResource
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
            'nombre_completo' => $this->user->nombre . ' ' . $this->user->apellidos,
            'nombre' => $this->user->nombre,
            'apellidos' => $this->user->apellidos,
            'email' => $this->user->email,
            'telefono' => $this->user->telefono,
            'genero' => $this->user->genero,
            'edad' => $this->user->edad,
            'fecha_registro' => $this->fecha_registro?->format('Y-m-d'),
            'fecha_registro_formatted' => $this->fecha_registro?->format('d/m/Y'),
            'notas' => $this->notas,
            'foto_perfil' => $this->user->foto_perfil ? tenant_asset($this->user->foto_perfil) : null,
            
            // Relaciones opcionales (solo si están cargadas)
            'deuda' => $this->whenLoaded('deuda', function () {
                return [
                    'id' => $this->deuda->id,
                    'saldo_total' => $this->deuda->saldo_total,
                    'saldo_pendiente' => $this->deuda->saldo_pendiente,
                    'tiene_deuda' => $this->deuda->saldo_pendiente > 0,
                ];
            }),
            
            'bonos_activos' => $this->whenLoaded('bonosActivos', function () {
                return BonoClienteResource::collection($this->bonosActivos);
            }),
            
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
