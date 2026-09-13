<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Inventario\ValueObjects\TipoMovimiento;
use App\Infrastructure\Inventario\Models\MovimientoInventarioModel;
use App\Infrastructure\Inventario\Models\ProductoModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MovimientoInventarioModel>
 */
final class MovimientoInventarioModelFactory extends Factory
{
    protected $model = MovimientoInventarioModel::class;

    public function definition(): array
    {
        $cantidad = $this->faker->randomFloat(2, 5, 80);

        return [
            'producto_id' => ProductoModel::factory(),
            'tipo' => TipoMovimiento::Produccion->value,
            'fecha' => $this->faker->dateTimeBetween('-40 days', 'now')->format('Y-m-d'),
            'cantidad' => $cantidad,
            'litros_procesados' => round($cantidad * $this->faker->randomFloat(2, 7, 9), 2),
            'precio_unitario' => null,
            'total' => null,
            'cliente' => null,
            'observaciones' => null,
            'usuario_id' => null,
            'request_id' => null,
        ];
    }

    public function venta(): self
    {
        return $this->state(function (array $attributes) {
            $precio = $this->faker->randomFloat(2, 10, 45);

            return [
                'tipo' => TipoMovimiento::Venta->value,
                'litros_procesados' => null,
                'precio_unitario' => $precio,
                'total' => round(((float) $attributes['cantidad']) * $precio, 2),
                'cliente' => $this->faker->randomElement([
                    'Mercado Rodriguez', 'Supermercado Ketal', 'Feria de Huata',
                    'Distribuidora Illimani', 'Hotel Titikaka',
                ]),
            ];
        });
    }
}
