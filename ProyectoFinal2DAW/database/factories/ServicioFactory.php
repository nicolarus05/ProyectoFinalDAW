<?php

namespace Database\Factories;

use App\Models\Servicio;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Servicio>
 */
class ServicioFactory extends Factory
{
    protected $model = Servicio::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $categorias = ['peluqueria', 'estetica'];
        $categoria = fake()->randomElement($categorias);
        
        $servicios = [
            'peluqueria' => [
                'Corte de pelo',
                'Tinte',
                'Mechas',
                'Permanente',
                'Alisado',
                'Peinado',
                'Tratamiento capilar',
            ],
            'estetica' => [
                'Limpieza facial',
                'Manicura',
                'Pedicura',
                'Depilación',
                'Masaje',
                'Tratamiento corporal',
                'Maquillaje',
            ],
        ];

        return [
            'nombre' => fake()->randomElement($servicios[$categoria]),
            'tiempo_estimado' => fake()->randomElement([15, 30, 45, 60, 90, 120]),
            'precio' => fake()->randomFloat(2, 10, 150),
            'categoria' => $categoria,  // La columna se llama 'categoria'
            'activo' => true,
        ];
    }

    /**
     * Servicio de peluquería
     */
    public function peluqueria(): static
    {
        return $this->state(fn (array $attributes) => [
            'categoria' => 'peluqueria',
            'nombre' => fake()->randomElement([
                'Corte de pelo',
                'Tinte',
                'Mechas',
                'Permanente',
                'Alisado',
            ]),
        ]);
    }

    /**
     * Servicio de estética
     */
    public function estetica(): static
    {
        return $this->state(fn (array $attributes) => [
            'categoria' => 'estetica',
            'nombre' => fake()->randomElement([
                'Limpieza facial',
                'Manicura',
                'Pedicura',
                'Depilación',
                'Masaje',
            ]),
        ]);
    }

    /**
     * Servicio inactivo
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'activo' => false,
        ]);
    }

    /**
     * Servicio corto (15-30 minutos)
     */
    public function short(): static
    {
        return $this->state(fn (array $attributes) => [
            'tiempo_estimado' => fake()->randomElement([15, 20, 25]),
            'precio' => fake()->randomFloat(2, 10, 40),
        ]);
    }

    /**
     * Servicio largo (90-120 minutos)
     */
    public function long(): static
    {
        return $this->state(fn (array $attributes) => [
            'tiempo_estimado' => fake()->randomElement([95, 100, 105, 110, 120]),
            'precio' => fake()->randomFloat(2, 60, 150),
        ]);
    }

    /**
     * Servicio económico
     */
    public function cheap(): static
    {
        return $this->state(fn (array $attributes) => [
            'precio' => fake()->randomFloat(2, 10, 19),
        ]);
    }

    /**
     * Servicio premium
     */
    public function premium(): static
    {
        return $this->state(fn (array $attributes) => [
            'precio' => fake()->randomFloat(2, 101, 200),
        ]);
    }
}
