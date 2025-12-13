<?php

namespace Database\Factories;

use App\Models\Productos;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Productos>
 */
class ProductosFactory extends Factory
{
    protected $model = Productos::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $categorias = [
            'capilar' => ['Champú', 'Acondicionador', 'Mascarilla', 'Serum', 'Spray', 'Cera', 'Gel'],
            'estetica' => ['Crema facial', 'Crema corporal', 'Exfoliante', 'Tónico', 'Limpiador', 'Mascarilla facial'],
            'unas' => ['Esmalte', 'Base coat', 'Top coat', 'Quitaesmalte', 'Lima', 'Aceite de cutículas'],
            'maquillaje' => ['Base', 'Corrector', 'Polvo', 'Rubor', 'Labial', 'Máscara de pestañas'],
        ];

        $categoria = fake()->randomElement(array_keys($categorias));
        $productos = $categorias[$categoria];

        $precioCoste = fake()->randomFloat(2, 5, 50);
        $precioVenta = $precioCoste * fake()->randomFloat(2, 1.5, 3);

        return [
            'nombre' => fake()->randomElement($productos) . ' ' . fake()->word(),
            'categoria' => $categoria,
            'descripcion' => fake()->optional()->sentence(),
            'precio_venta' => round($precioVenta, 2),
            'precio_coste' => round($precioCoste, 2),
            'stock' => fake()->numberBetween(0, 100),
            'activo' => true,
        ];
    }

    /**
     * Producto de categoría capilar
     */
    public function capilar(): static
    {
        return $this->state(fn (array $attributes) => [
            'categoria' => 'capilar',
            'nombre' => fake()->randomElement(['Champú', 'Acondicionador', 'Mascarilla']) . ' ' . fake()->word(),
        ]);
    }

    /**
     * Producto de categoría estética
     */
    public function estetica(): static
    {
        return $this->state(fn (array $attributes) => [
            'categoria' => 'estetica',
            'nombre' => fake()->randomElement(['Crema facial', 'Crema corporal', 'Exfoliante']) . ' ' . fake()->word(),
        ]);
    }

    /**
     * Producto inactivo
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'activo' => false,
        ]);
    }

    /**
     * Producto sin stock
     */
    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'stock' => 0,
        ]);
    }

    /**
     * Producto con stock bajo
     */
    public function lowStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'stock' => fake()->numberBetween(1, 5),
        ]);
    }

    /**
     * Producto con stock alto
     */
    public function highStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'stock' => fake()->numberBetween(50, 200),
        ]);
    }

    /**
     * Producto económico
     */
    public function cheap(): static
    {
        $precioCoste = fake()->randomFloat(2, 2, 4);
        
        return $this->state(fn (array $attributes) => [
            'precio_coste' => round($precioCoste, 2),
            'precio_venta' => round($precioCoste * 2, 2),
        ]);
    }

    /**
     * Producto premium
     */
    public function premium(): static
    {
        $precioCoste = fake()->randomFloat(2, 30, 80);
        
        return $this->state(fn (array $attributes) => [
            'precio_coste' => round($precioCoste, 2),
            'precio_venta' => round($precioCoste * 2.5, 2),
        ]);
    }
}
