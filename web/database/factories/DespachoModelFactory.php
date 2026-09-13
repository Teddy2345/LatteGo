<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Infrastructure\Produccion\Models\DespachoModel;
use App\Infrastructure\Produccion\Models\ProduccionModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DespachoModel>
 */
final class DespachoModelFactory extends Factory
{
    protected $model = DespachoModel::class;

    public function definition(): array
    {
        $recibidos = $this->faker->numberBetween(50, 300);
        $despachados = max(0, $recibidos - $this->faker->numberBetween(0, 5));

        return [
            'produccion_id' => ProduccionModel::factory(),
            'despachador_id' => null,
            'quesos_recibidos' => $recibidos,
            'quesos_despachados' => $despachados,
            'merma' => $this->faker->numberBetween(0, 5),
            'observaciones' => $this->faker->optional(0.1)->sentence(),
        ];
    }
}
