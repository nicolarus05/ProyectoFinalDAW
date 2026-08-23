<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCitaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Los usuarios autenticados pueden actualizar citas
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
            'fecha_hora'   => 'required|date',
            'estado'       => 'required|in:pendiente,confirmada,completada,cancelada',
            'id_cliente'   => 'required|exists:clientes,id',
            'id_empleado'  => 'required|exists:empleados,id',
            'servicios'    => 'required|array|min:1',
            'servicios.*'  => 'distinct|exists:servicios,id',
            'notas_adicionales' => 'nullable|string|max:1000',
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
            'fecha_hora.required'  => 'La fecha y hora son obligatorias.',
            'fecha_hora.date'      => 'La fecha y hora no tienen un formato válido.',
            'estado.required'      => 'El estado de la cita es obligatorio.',
            'estado.in'            => 'El estado debe ser: pendiente, confirmada, completada o cancelada.',
            'id_cliente.required'  => 'El cliente es obligatorio.',
            'id_cliente.exists'    => 'El cliente seleccionado no existe.',
            'id_empleado.required' => 'El empleado es obligatorio.',
            'id_empleado.exists'   => 'El empleado seleccionado no existe.',
            'servicios.required'   => 'Debes seleccionar al menos un servicio.',
            'servicios.min'        => 'Debes seleccionar al menos un servicio.',
            'servicios.*.exists'   => 'Uno de los servicios seleccionados no existe.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('estado')) {
            $this->merge([
                'estado' => strip_tags($this->estado),
            ]);
        }
        // Si el rol no es admin, forzar el cliente de la cita
        if (auth()->check() && auth()->user()->rol !== 'admin') {
            $cita = $this->route('cita');
            if ($cita) {
                $this->merge(['id_cliente' => $cita->id_cliente]);
            }
        }
    }
}
