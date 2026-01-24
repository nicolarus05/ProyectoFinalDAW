<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegistrarPagoDeudaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && in_array(auth()->user()->rol, ['admin', 'empleado']);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'monto' => [
                'required',
                'numeric',
                'min:0.01',
                'max:999999.99',
            ],
            'metodo_pago' => [
                'required',
                'string',
                'in:efectivo,tarjeta,transferencia',
            ],
            'empleado_id' => [
                'nullable', // Ahora es opcional, se valida en withValidator
                'integer',
                'exists:empleados,id',
            ],
            'nota' => [
                'nullable',
                'string',
                'max:500',
            ],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $cliente = $this->route('cliente');
            $deuda = $cliente->deuda;
            
            if (!$deuda) {
                $validator->errors()->add('deuda', 'No hay deuda registrada para este cliente.');
                return;
            }

            // Validar que el monto no exceda la deuda pendiente
            if ($this->monto > $deuda->saldo_pendiente) {
                $validator->errors()->add('monto', 'El monto no puede ser mayor a la deuda pendiente.');
            }

            // Verificar si hay un cobro original con servicios
            $ultimoCargo = $deuda->movimientos()
                ->where('tipo', 'cargo')
                ->whereNotNull('id_registro_cobro')
                ->with('registroCobro.servicios', 'registroCobro.productos')
                ->latest()
                ->first();

            $tieneCobroOriginal = $ultimoCargo && 
                $ultimoCargo->registroCobro && 
                ($ultimoCargo->registroCobro->servicios->count() > 0 || 
                 $ultimoCargo->registroCobro->productos->count() > 0);

            // empleado_id es requerido solo si NO hay cobro original con servicios
            if (!$tieneCobroOriginal && !$this->empleado_id) {
                $validator->errors()->add('empleado_id', 'Debe seleccionar un empleado para este pago.');
            }
        });
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'monto.required' => 'El monto es obligatorio.',
            'monto.min' => 'El monto mínimo es 0.01€.',
            'monto.max' => 'El monto máximo permitido es 999,999.99€.',
            'metodo_pago.required' => 'El método de pago es obligatorio.',
            'metodo_pago.in' => 'El método de pago debe ser efectivo, tarjeta o transferencia.',
            'empleado_id.required' => 'Debe seleccionar el empleado que realizó el servicio.',
            'empleado_id.exists' => 'El empleado seleccionado no es válido.',
            'nota.max' => 'La nota no puede exceder 500 caracteres.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        // Sanitizar nota si existe
        if ($this->has('nota') && $this->nota) {
            $this->merge([
                'nota' => strip_tags(trim($this->nota))
            ]);
        }
    }
}
