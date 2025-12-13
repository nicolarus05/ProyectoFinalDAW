<?php

namespace Database\Factories;

use App\Models\Empleado;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Empleado>
 */
class EmpleadoFactory extends Factory
{
    protected $model = Empleado::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id_user' => User::factory()->state([
                'rol' => 'empleado',
            ]),
            'categoria' => fake()->randomElement(['peluqueria', 'estetica']),
            'horario_invierno' => $this->generateHorario(),
            'horario_verano' => $this->generateHorario(),
        ];
    }

    /**
     * Empleado de categoría peluquería
     */
    public function peluqueria(): static
    {
        return $this->state(fn (array $attributes) => [
            'categoria' => 'peluqueria',
        ]);
    }

    /**
     * Empleado de categoría estética
     */
    public function estetica(): static
    {
        return $this->state(fn (array $attributes) => [
            'categoria' => 'estetica',
        ]);
    }

    /**
     * Empleado sin horarios configurados
     */
    public function withoutSchedule(): static
    {
        return $this->state(fn (array $attributes) => [
            'horario_invierno' => null,
            'horario_verano' => null,
        ]);
    }

    /**
     * Empleado con horario personalizado
     */
    public function withCustomSchedule(array $invierno, array $verano): static
    {
        return $this->state(fn (array $attributes) => [
            'horario_invierno' => $invierno,
            'horario_verano' => $verano,
        ]);
    }

    /**
     * Configure the model factory to create with a specific user
     */
    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'id_user' => $user->id,
        ]);
    }

    /**
     * Generar horario aleatorio
     */
    protected function generateHorario(): array
    {
        return [
            'lunes' => ['inicio' => '09:00', 'fin' => '18:00'],
            'martes' => ['inicio' => '09:00', 'fin' => '18:00'],
            'miercoles' => ['inicio' => '09:00', 'fin' => '18:00'],
            'jueves' => ['inicio' => '09:00', 'fin' => '18:00'],
            'viernes' => ['inicio' => '09:00', 'fin' => '18:00'],
            'sabado' => ['inicio' => '09:00', 'fin' => '14:00'],
            'domingo' => null,
        ];
    }
}
