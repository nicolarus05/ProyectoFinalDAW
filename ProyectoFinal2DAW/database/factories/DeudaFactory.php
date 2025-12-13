<?php

namespace Database\Factories;

use App\Models\Deuda;
use App\Models\Cliente;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Deuda>
 */
class DeudaFactory extends Factory
{
    protected $model = Deuda::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $saldoTotal = fake()->randomFloat(2, 0, 500);
        $saldoPendiente = fake()->randomFloat(2, 0, $saldoTotal);

        return [
            'id_cliente' => Cliente::factory(),
            'saldo_total' => $saldoTotal,
            'saldo_pendiente' => $saldoPendiente,
        ];
    }

    /**
     * Deuda sin saldo pendiente (saldada)
     */
    public function saldada(): static
    {
        return $this->state(fn (array $attributes) => [
            'saldo_pendiente' => 0,
        ]);
    }

    /**
     * Deuda con saldo pendiente completo
     */
    public function pendiente(): static
    {
        $saldoTotal = fake()->randomFloat(2, 50, 500);
        
        return $this->state(fn (array $attributes) => [
            'saldo_total' => $saldoTotal,
            'saldo_pendiente' => $saldoTotal,
        ]);
    }

    /**
     * Deuda parcialmente pagada
     */
    public function parcial(): static
    {
        $saldoTotal = fake()->randomFloat(2, 100, 500);
        $saldoPendiente = fake()->randomFloat(2, 20, $saldoTotal * 0.8);
        
        return $this->state(fn (array $attributes) => [
            'saldo_total' => $saldoTotal,
            'saldo_pendiente' => $saldoPendiente,
        ]);
    }

    /**
     * Deuda pequeña
     */
    public function small(): static
    {
        $saldoTotal = fake()->randomFloat(2, 10, 50);
        
        return $this->state(fn (array $attributes) => [
            'saldo_total' => $saldoTotal,
            'saldo_pendiente' => fake()->randomFloat(2, 0, $saldoTotal),
        ]);
    }

    /**
     * Deuda grande
     */
    public function large(): static
    {
        $saldoTotal = fake()->randomFloat(2, 300, 1000);
        
        return $this->state(fn (array $attributes) => [
            'saldo_total' => $saldoTotal,
            'saldo_pendiente' => fake()->randomFloat(2, 100, $saldoTotal),
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
}
