<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UpdateProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Solo el usuario autenticado puede actualizar su propio perfil
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $userId = auth()->id();

        return [
            'nombre' => [
                'required',
                'string',
                'max:255',
                'min:2',
            ],
            'apellidos' => [
                'required',
                'string',
                'max:255',
                'min:2',
            ],
            'telefono' => [
                'nullable',
                'string',
                'max:20',
                'regex:/^[0-9+\s\-()]+$/',
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                'unique:users,email,' . $userId,
            ],
            'genero' => [
                'required',
                'string',
                'in:masculino,femenino,otro',
            ],
            'edad' => [
                'nullable',
                'integer',
                'min:16',
                'max:120',
            ],
            'foto_perfil' => [
                'nullable',
                'image',
                'mimes:jpeg,png,jpg,webp',
                'max:2048', // 2MB máximo
                'dimensions:min_width=100,min_height=100,max_width=2000,max_height=2000',
            ],
            'current_password' => [
                'nullable',
                'string',
                'required_with:password',
            ],
            'password' => [
                'nullable',
                'confirmed',
                'min:8',
                'max:255',
            ],
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
            'nombre.min' => 'El nombre debe tener al menos 2 caracteres.',
            'nombre.max' => 'El nombre no puede exceder los 255 caracteres.',
            
            'apellidos.required' => 'Los apellidos son obligatorios.',
            'apellidos.min' => 'Los apellidos deben tener al menos 2 caracteres.',
            'apellidos.max' => 'Los apellidos no pueden exceder los 255 caracteres.',
            
            'telefono.regex' => 'El teléfono solo puede contener números, espacios, guiones y paréntesis.',
            'telefono.max' => 'El teléfono no puede exceder los 20 caracteres.',
            
            'email.required' => 'El email es obligatorio.',
            'email.email' => 'El email debe ser una dirección válida.',
            'email.unique' => 'Este email ya está registrado por otro usuario.',
            'email.max' => 'El email no puede exceder los 255 caracteres.',
            
            'genero.required' => 'El género es obligatorio.',
            'genero.in' => 'El género debe ser: masculino, femenino u otro.',
            
            'edad.integer' => 'La edad debe ser un número entero.',
            'edad.min' => 'La edad mínima es 16 años.',
            'edad.max' => 'La edad máxima es 120 años.',
            
            'foto_perfil.image' => 'El archivo debe ser una imagen.',
            'foto_perfil.mimes' => 'La foto debe ser de tipo: jpeg, png, jpg o webp.',
            'foto_perfil.max' => 'La foto no puede ser mayor a 2MB.',
            'foto_perfil.dimensions' => 'La foto debe tener un mínimo de 100x100 píxeles y un máximo de 2000x2000 píxeles.',
            
            'current_password.required_with' => 'Debes proporcionar tu contraseña actual para cambiarla.',
            
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.max' => 'La contraseña no puede exceder los 255 caracteres.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Sanitizar campos de texto
        $sanitized = [];
        
        foreach (['nombre', 'apellidos', 'telefono'] as $field) {
            if ($this->has($field)) {
                $sanitized[$field] = strip_tags(trim($this->$field));
            }
        }
        
        // Normalizar email
        if ($this->has('email')) {
            $sanitized['email'] = strtolower(trim(strip_tags($this->email)));
        }
        
        // Sanitizar género
        if ($this->has('genero')) {
            $sanitized['genero'] = strtolower(strip_tags($this->genero));
        }
        
        $this->merge($sanitized);
    }
}
