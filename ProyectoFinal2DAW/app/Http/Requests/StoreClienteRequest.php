<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreClienteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Solo admin y empleado pueden crear clientes
        return auth()->check() && in_array(auth()->user()->rol, ['admin', 'empleado']);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255', 'min:2'],
            'apellidos' => ['required', 'string', 'max:255', 'min:2'],
            'telefono' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\s\-()]+$/'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6', 'max:255'],
            'genero' => ['required', 'string', 'in:Hombre,Mujer,Otro'],
            'edad' => ['required', 'integer', 'min:16', 'max:120'],
            'direccion' => ['required', 'string', 'max:255'],
            'notas_adicionales' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.min' => 'El nombre debe tener al menos 2 caracteres.',
            'apellidos.required' => 'Los apellidos son obligatorios.',
            'apellidos.min' => 'Los apellidos deben tener al menos 2 caracteres.',
            'email.required' => 'El email es obligatorio.',
            'email.email' => 'El email debe ser una dirección válida.',
            'email.unique' => 'Este email ya está registrado.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.min' => 'La contraseña debe tener al menos 6 caracteres.',
            'telefono.regex' => 'El teléfono solo puede contener números, espacios, guiones y paréntesis.',
            'genero.required' => 'El género es obligatorio.',
            'genero.in' => 'El género debe ser Hombre, Mujer u Otro.',
            'edad.required' => 'La edad es obligatoria.',
            'edad.min' => 'La edad mínima es 16 años.',
            'edad.max' => 'La edad máxima es 120 años.',
            'direccion.required' => 'La dirección es obligatoria.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        // Sanitizar campos de texto
        $this->merge([
            'nombre' => strip_tags(trim($this->nombre ?? '')),
            'apellidos' => strip_tags(trim($this->apellidos ?? '')),
            'direccion' => strip_tags(trim($this->direccion ?? '')),
            'email' => strtolower(trim($this->email ?? '')),
        ]);

        if ($this->has('notas_adicionales') && $this->notas_adicionales) {
            $this->merge([
                'notas_adicionales' => strip_tags(trim($this->notas_adicionales))
            ]);
        }
    }
}
