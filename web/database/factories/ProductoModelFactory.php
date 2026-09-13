<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Infrastructure\Inventario\Models\ProductoModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductoModel>
 */
final class ProductoModelFactory extends Factory
{
    protected $model = ProductoModel::class;

    public function definition(): array
    {
        return [
            'nombre' => 'Producto '.$this->faker->unique()->numberBetween(1, 100000),
            'tipo' => 'producto_terminado',
            'categoria' => null,
            'unidad' => $this->faker->randomElement(['pieza', 'kilo', 'litro']),
            'precio_referencia' => $this->faker->randomFloat(2, 8, 60),
            'stock_minimo' => null,
            'activo' => true,
        ];
    }

    public function inactivo(): self
    {
        return $this->state(fn (array $attributes) => ['activo' => false]);
    }

    public function insumo(): self
    {
        return $this->state(fn (array $attributes) => [
            'nombre' => 'Insumo '.$this->faker->unique()->numberBetween(1, 100000),
            'tipo' => 'insumo',
            'unidad' => $this->faker->randomElement(['kilo', 'unidad', 'litro']),
            'precio_referencia' => 0,
        ]);
    }
}
