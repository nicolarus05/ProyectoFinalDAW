<?php

namespace Database\Factories;

use App\Models\Cliente;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Cliente>
 */
class ClienteFactory extends Factory
{
    protected $model = Cliente::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id_user' => User::factory(),
            'direccion' => fake()->address(),
            'notas_adicionales' => fake()->optional()->sentence(),
            'fecha_registro' => fake()->dateTimeBetween('-2 years', 'now'),
        ];
    }

    /**
     * Cliente con notas adicionales
     */
    public function withNotas(): static
    {
        return $this->state(fn (array $attributes) => [
            'notas_adicionales' => fake()->paragraph(),
        ]);
    }

    /**
     * Cliente sin notas
     */
    public function withoutNotas(): static
    {
        return $this->state(fn (array $attributes) => [
            'notas_adicionales' => null,
        ]);
    }

    /**
     * Cliente reciente (registrado en el último mes)
     */
    public function recent(): static
    {
        return $this->state(fn (array $attributes) => [
            'fecha_registro' => fake()->dateTimeBetween('-30 days', 'now'),
        ]);
    }

    /**
     * Cliente antiguo (registrado hace más de 1 año)
     */
    public function old(): static
    {
        return $this->state(fn (array $attributes) => [
            'fecha_registro' => fake()->dateTimeBetween('-2 years', '-1 year'),
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
}
