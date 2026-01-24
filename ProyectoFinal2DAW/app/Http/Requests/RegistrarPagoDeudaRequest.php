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
                'required',
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
