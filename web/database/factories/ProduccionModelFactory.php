<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Infrastructure\Produccion\Models\ProduccionModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProduccionModel>
 */
final class ProduccionModelFactory extends Factory
{
    protected $model = ProduccionModel::class;

    public function definition(): array
    {
        $litros = $this->faker->randomFloat(2, 500, 2500);
        $rendimiento = $this->faker->randomFloat(2, 10.5, 12.5);
        $quesos = (int) round($litros * $rendimiento / 100);

        return [
            'fecha' => $this->faker->dateTimeBetween('-60 days', 'now')->format('Y-m-d'),
            'litros_procesados' => $litros,
            'quesos_producidos' => $quesos,
            'rendimiento_porcentaje' => round(($quesos / $litros) * 100, 2),
            'jefa_produccion_id' => null,
            'observaciones' => $this->faker->optional(0.15)->sentence(),
        ];
    }
}
