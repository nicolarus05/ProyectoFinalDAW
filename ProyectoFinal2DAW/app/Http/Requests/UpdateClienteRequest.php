<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateClienteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Solo admins y empleados pueden actualizar clientes
        return auth()->check() && in_array(auth()->user()->rol, ['admin', 'empleado']);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // Obtener el ID del user del cliente actual desde la ruta
        $cliente = $this->route('cliente');
        $userId = $cliente?->id_user;

        return [
            'nombre' => 'required|string|max:255',
            'apellidos' => 'required|string|max:255',
            'telefono' => 'nullable|string|max:20',
            'email' => 'required|email|unique:users,email,' . $userId,
            'genero' => 'required|string|max:20',
            'edad' => 'required|integer|min:0|max:120',
            'direccion' => 'required|string|max:255',
            'notas_adicionales' => 'nullable|string|max:255',
            'fecha_registro' => 'required|date|before_or_equal:today',
            'password' => 'nullable|string|min:8',
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
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.max' => 'El nombre no puede exceder los 255 caracteres.',
            'apellidos.required' => 'Los apellidos son obligatorios.',
            'apellidos.max' => 'Los apellidos no pueden exceder los 255 caracteres.',
            'telefono.max' => 'El teléfono no puede exceder los 20 caracteres.',
            'email.required' => 'El email es obligatorio.',
            'email.email' => 'El email debe ser una dirección válida.',
            'email.unique' => 'Este email ya está registrado.',
            'genero.required' => 'El género es obligatorio.',
            'edad.required' => 'La edad es obligatoria.',
            'edad.integer' => 'La edad debe ser un número entero.',
            'edad.min' => 'La edad debe ser mayor a 0.',
            'edad.max' => 'La edad no puede ser mayor a 120.',
            'direccion.required' => 'La dirección es obligatoria.',
            'fecha_registro.required' => 'La fecha de registro es obligatoria.',
            'fecha_registro.date' => 'La fecha de registro debe ser una fecha válida.',
            'fecha_registro.before_or_equal' => 'La fecha de registro no puede ser futura.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Sanitizar todos los campos de texto
        $sanitized = [];
        
        foreach (['nombre', 'apellidos', 'telefono', 'email', 'genero', 'direccion', 'notas_adicionales'] as $field) {
            if ($this->has($field)) {
                $sanitized[$field] = strip_tags($this->$field);
            }
        }
        
        $this->merge($sanitized);
    }
}
