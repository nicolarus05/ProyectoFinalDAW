<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RegistroCobroResource extends JsonResource
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
            'coste' => $this->coste,
            'coste_formatted' => number_format($this->coste, 2) . ' €',
            'descuento' => $this->descuento,
            'descuento_formatted' => number_format($this->descuento, 2) . ' €',
            'total_final' => $this->total_final,
            'total_final_formatted' => number_format($this->total_final, 2) . ' €',
            'metodo_pago' => $this->metodo_pago,
            'metodo_pago_formatted' => ucfirst(str_replace('_', ' ', $this->metodo_pago)),
            'dinero_cliente' => $this->dinero_cliente,
            'dinero_cliente_formatted' => $this->dinero_cliente ? number_format($this->dinero_cliente, 2) . ' €' : null,
            'cambio' => $this->cambio,
            'cambio_formatted' => $this->cambio ? number_format($this->cambio, 2) . ' €' : null,
            'deuda' => $this->deuda,
            'tiene_deuda' => $this->deuda > 0,
            'deuda_formatted' => $this->deuda ? number_format($this->deuda, 2) . ' €' : null,
            
            // Información de pago mixto
            'efectivo' => $this->efectivo,
            'efectivo_formatted' => $this->efectivo ? number_format($this->efectivo, 2) . ' €' : null,
            'tarjeta' => $this->tarjeta,
            'tarjeta_formatted' => $this->tarjeta ? number_format($this->tarjeta, 2) . ' €' : null,
            
            // Cita asociada
            'cita' => $this->whenLoaded('cita', function () {
                return new CitaResource($this->cita);
            }),
            
            // Citas agrupadas
            'citas_agrupadas' => $this->whenLoaded('citasAgrupadas', function () {
                return CitaResource::collection($this->citasAgrupadas);
            }),
            
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
                return $this->servicios->map(function ($servicio) {
                    return [
                        'id' => $servicio->id,
                        'nombre' => $servicio->nombre,
                        'precio' => $servicio->pivot->precio,
                        'cantidad' => $servicio->pivot->cantidad,
                        'subtotal' => $servicio->pivot->precio * $servicio->pivot->cantidad,
                    ];
                });
            }),
            
            // Productos
            'productos' => $this->whenLoaded('productos', function () {
                return $this->productos->map(function ($producto) {
                    return [
                        'id' => $producto->id,
                        'nombre' => $producto->nombre,
                        'precio' => $producto->pivot->precio,
                        'cantidad' => $producto->pivot->cantidad,
                        'subtotal' => $producto->pivot->precio * $producto->pivot->cantidad,
                    ];
                });
            }),
            
            'fecha_cobro' => $this->created_at?->format('Y-m-d'),
            'fecha_cobro_formatted' => $this->created_at?->format('d/m/Y H:i'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
