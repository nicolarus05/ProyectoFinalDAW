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
            'estado' => 'required|in:pendiente,completada,cancelada',
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
            'estado.required' => 'El estado de la cita es obligatorio.',
            'estado.in' => 'El estado debe ser: pendiente, completada o cancelada.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Sanitizar el campo estado
        if ($this->has('estado')) {
            $this->merge([
                'estado' => strip_tags($this->estado),
            ]);
        }
    }
}
