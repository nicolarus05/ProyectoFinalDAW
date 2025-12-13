<?php

namespace Database\Factories;

use App\Models\Cita;
use App\Models\Cliente;
use App\Models\Empleado;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Cita>
 */
class CitaFactory extends Factory
{
    protected $model = Cita::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Fecha aleatoria entre -30 días y +30 días
        $fechaHora = fake()->dateTimeBetween('-30 days', '+30 days');
        
        // Ajustar a horas laborables (9:00 - 18:00) en intervalos de 15 minutos
        $hour = fake()->numberBetween(9, 17);
        $minute = fake()->randomElement([0, 15, 30, 45]);
        $fechaHora->setTime($hour, $minute);

        return [
            'id_cliente' => Cliente::factory(),
            'id_empleado' => Empleado::factory(),
            'fecha_hora' => $fechaHora,
            'estado' => fake()->randomElement(['pendiente', 'confirmada', 'completada', 'cancelada']),
            'notas_adicionales' => fake()->optional()->sentence(),
            'duracion_real' => fake()->optional()->numberBetween(15, 120),
            'grupo_cita_id' => null,
            'orden_servicio' => 1,
        ];
    }

    /**
     * Cita pendiente
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => 'pendiente',
            'duracion_real' => null,
        ]);
    }

    /**
     * Cita confirmada
     */
    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => 'confirmada',
            'duracion_real' => null,
        ]);
    }

    /**
     * Cita completada
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => 'completada',
            'duracion_real' => fake()->numberBetween(15, 120),
        ]);
    }

    /**
     * Cita cancelada
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => 'cancelada',
        ]);
    }

    /**
     * Cita para hoy
     */
    public function today(): static
    {
        $fechaHora = Carbon::today();
        $hour = fake()->numberBetween(9, 17);
        $minute = fake()->randomElement([0, 15, 30, 45]);
        $fechaHora->setTime($hour, $minute);

        return $this->state(fn (array $attributes) => [
            'fecha_hora' => $fechaHora,
        ]);
    }

    /**
     * Cita futura
     */
    public function future(): static
    {
        $fechaHora = fake()->dateTimeBetween('now', '+30 days');
        $hour = fake()->numberBetween(9, 17);
        $minute = fake()->randomElement([0, 15, 30, 45]);
        $fechaHora->setTime($hour, $minute);

        return $this->state(fn (array $attributes) => [
            'fecha_hora' => $fechaHora,
            'estado' => 'pendiente',
        ]);
    }

    /**
     * Cita pasada
     */
    public function past(): static
    {
        $fechaHora = fake()->dateTimeBetween('-30 days', '-1 day');
        $hour = fake()->numberBetween(9, 17);
        $minute = fake()->randomElement([0, 15, 30, 45]);
        $fechaHora->setTime($hour, $minute);

        return $this->state(fn (array $attributes) => [
            'fecha_hora' => $fechaHora,
            'estado' => 'completada',
        ]);
    }

    /**
     * Configure para un cliente específico
     */
    public function forCliente(Cliente $cliente): static
    {
        return $this->state(fn (array $attributes) => [
            'id_cliente' => $cliente->id,
        ]);
    }

    /**
     * Configure para un empleado específico
     */
    public function forEmpleado(Empleado $empleado): static
    {
        return $this->state(fn (array $attributes) => [
            'id_empleado' => $empleado->id,
        ]);
    }

    /**
     * Cita con notas
     */
    public function withNotas(): static
    {
        return $this->state(fn (array $attributes) => [
            'notas_adicionales' => fake()->paragraph(),
        ]);
    }
}
