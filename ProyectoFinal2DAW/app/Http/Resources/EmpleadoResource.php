<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmpleadoResource extends JsonResource
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
            'categoria' => $this->categoria,
            'categoria_formatted' => ucfirst($this->categoria),
            'disponible' => $this->disponible,
            'foto_perfil' => $this->user->foto_perfil ? tenant_asset($this->user->foto_perfil) : null,
            
            // Horarios configurados (solo si están en el modelo)
            'horario_invierno' => $this->when(isset($this->horario_invierno), $this->horario_invierno),
            'horario_verano' => $this->when(isset($this->horario_verano), $this->horario_verano),
            
            // Relaciones opcionales
            'servicios' => $this->whenLoaded('servicios', function () {
                return ServicioResource::collection($this->servicios);
            }),
            
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
