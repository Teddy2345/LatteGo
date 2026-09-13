<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Infrastructure\Pedidos\Models\PedidoModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PedidoModel>
 */
final class PedidoModelFactory extends Factory
{
    protected $model = PedidoModel::class;

    public function definition(): array
    {
        return [
            'cliente_nombre' => $this->faker->name(),
            'cliente_telefono' => $this->faker->numerify('7#######'),
            'cliente_direccion' => $this->faker->streetAddress(),
            'fecha' => $this->faker->dateTimeBetween('-20 days', 'now')->format('Y-m-d'),
            'estado' => 'pendiente',
            'observaciones' => null,
        ];
    }

    public function confirmado(): self
    {
        return $this->state(fn (array $attributes) => ['estado' => 'confirmado']);
    }

    public function cancelado(): self
    {
        return $this->state(fn (array $attributes) => ['estado' => 'cancelado']);
    }
}
