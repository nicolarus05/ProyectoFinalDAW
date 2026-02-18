<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRegistroCobroRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Solo admins y empleados pueden registrar cobros
        return auth()->check() && in_array(auth()->user()->rol, ['admin', 'empleado']);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'id_cita' => 'nullable|exists:citas,id',
            'citas_ids' => 'nullable|array',
            'citas_ids.*' => 'exists:citas,id',
            'id_cliente' => 'nullable|exists:clientes,id',
            'id_empleado' => 'nullable|exists:empleados,id',
            'coste' => 'required|numeric|min:0',
            'descuento_porcentaje' => 'nullable|numeric|min:0|max:100',
            'descuento_euro' => 'nullable|numeric|min:0',
            'descuento_servicios_porcentaje' => 'nullable|numeric|min:0|max:100',
            'descuento_servicios_euro' => 'nullable|numeric|min:0',
            'descuento_productos_porcentaje' => 'nullable|numeric|min:0|max:100',
            'descuento_productos_euro' => 'nullable|numeric|min:0',
            'total_final' => 'required|numeric|min:0',
            'metodo_pago' => 'required|in:efectivo,tarjeta,mixto',
            'dinero_cliente' => 'required_if:metodo_pago,efectivo|numeric|min:0',
            'cambio' => 'nullable|numeric|min:0',
            'pago_efectivo' => 'nullable|numeric|min:0',
            'pago_tarjeta' => 'nullable|numeric|min:0',
            'productos_data' => 'nullable|json',
            'servicios_data' => 'nullable|json',
            'bono_plantilla_id' => 'nullable|exists:bonos_plantilla,id',
        ];
    }

    /**
     * Get custom error messages for validation.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'id_cita.exists' => 'La cita seleccionada no existe.',
            'citas_ids.array' => 'Las citas deben ser un array.',
            'citas_ids.*.exists' => 'Una o más citas seleccionadas no existen.',
            'id_cliente.exists' => 'El cliente seleccionado no existe.',
            'id_empleado.exists' => 'El empleado seleccionado no existe.',
            'coste.required' => 'El coste es obligatorio.',
            'coste.numeric' => 'El coste debe ser un número.',
            'coste.min' => 'El coste no puede ser negativo.',
            'descuento_porcentaje.numeric' => 'El descuento porcentual debe ser un número.',
            'descuento_porcentaje.min' => 'El descuento porcentual no puede ser negativo.',
            'descuento_porcentaje.max' => 'El descuento porcentual no puede exceder el 100%.',
            'descuento_euro.numeric' => 'El descuento en euros debe ser un número.',
            'descuento_euro.min' => 'El descuento en euros no puede ser negativo.',
            'descuento_servicios_porcentaje.max' => 'El descuento de servicios no puede exceder el 100%.',
            'descuento_productos_porcentaje.max' => 'El descuento de productos no puede exceder el 100%.',
            'total_final.required' => 'El total final es obligatorio.',
            'total_final.numeric' => 'El total final debe ser un número.',
            'total_final.min' => 'El total final no puede ser negativo.',
            'metodo_pago.required' => 'El método de pago es obligatorio.',
            'metodo_pago.in' => 'El método de pago debe ser: efectivo, tarjeta o mixto.',
            'dinero_cliente.required_if' => 'El dinero del cliente es obligatorio cuando el método de pago es efectivo.',
            'dinero_cliente.numeric' => 'El dinero del cliente debe ser un número.',
            'dinero_cliente.min' => 'El dinero del cliente no puede ser negativo.',
            'cambio.numeric' => 'El cambio debe ser un número.',
            'cambio.min' => 'El cambio no puede ser negativo.',
            'pago_efectivo.numeric' => 'El pago en efectivo debe ser un número.',
            'pago_efectivo.min' => 'El pago en efectivo no puede ser negativo.',
            'pago_tarjeta.numeric' => 'El pago con tarjeta debe ser un número.',
            'pago_tarjeta.min' => 'El pago con tarjeta no puede ser negativo.',
            'productos_data.json' => 'Los datos de productos deben estar en formato JSON.',
            'servicios_data.json' => 'Los datos de servicios deben estar en formato JSON.',
            'bono_plantilla_id.exists' => 'La plantilla de bono seleccionada no existe.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Sanitizar campos de texto si existen
        $sanitized = [];
        
        // JSON data no se sanitiza con strip_tags porque rompería el formato
        foreach (['metodo_pago'] as $field) {
            if ($this->has($field)) {
                $sanitized[$field] = strip_tags($this->$field);
            }
        }
        
        $this->merge($sanitized);
    }
}
