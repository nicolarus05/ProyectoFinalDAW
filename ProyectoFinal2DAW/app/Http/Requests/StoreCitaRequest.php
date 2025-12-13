<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCitaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Permitir a usuarios autenticados (admin, empleado pueden crear para cualquiera)
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'fecha_hora' => [
                'required',
                'date',
                'after_or_equal:today',
            ],
            'notas_adicionales' => [
                'nullable',
                'string',
                'max:500',
            ],
            'id_cliente' => [
                'required',
                'integer',
                'exists:clientes,id',
            ],
            'id_empleado' => [
                'required',
                'integer',
                'exists:empleados,id',
            ],
            'servicios' => [
                'required',
                'array',
                'min:1',
                'max:10',
            ],
            'servicios.*' => [
                'distinct',
                'integer',
                'exists:servicios,id',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'fecha_hora.required' => 'La fecha y hora de la cita es obligatoria.',
            'fecha_hora.after_or_equal' => 'La cita debe ser en el futuro.',
            'id_cliente.required' => 'Debes seleccionar un cliente.',
            'id_cliente.exists' => 'El cliente seleccionado no existe.',
            'id_empleado.required' => 'Debes seleccionar un empleado.',
            'id_empleado.exists' => 'El empleado seleccionado no existe.',
            'servicios.required' => 'Debes seleccionar al menos un servicio.',
            'servicios.min' => 'Debes seleccionar al menos un servicio.',
            'servicios.*.distinct' => 'No puedes seleccionar el mismo servicio dos veces.',
            'servicios.*.exists' => 'Uno de los servicios seleccionados no existe.',
            'notas_adicionales.max' => 'Las notas no pueden exceder 500 caracteres.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        // Sanitizar notas si existen
        if ($this->has('notas_adicionales') && $this->notas_adicionales) {
            $this->merge([
                'notas_adicionales' => strip_tags(trim($this->notas_adicionales))
            ]);
        }
    }
}
