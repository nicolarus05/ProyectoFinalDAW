<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServicioResource extends JsonResource
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
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion,
            'duracion' => $this->duracion,
            'duracion_formatted' => $this->duracion . ' minutos',
            'precio' => $this->precio,
            'precio_formatted' => number_format($this->precio, 2) . ' €',
            'categoria' => $this->categoria,
            'categoria_formatted' => ucfirst($this->categoria),
            'activo' => $this->activo,
            'estado' => $this->activo ? 'Activo' : 'Inactivo',
            
            // Relaciones opcionales
            'empleados' => $this->whenLoaded('empleados', function () {
                return EmpleadoResource::collection($this->empleados);
            }),
            
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
