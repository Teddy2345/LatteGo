<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Infrastructure\Acopio\Models\AcopioModel;
use App\Infrastructure\Proveedor\Models\ProveedorModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AcopioModel>
 */
final class AcopioModelFactory extends Factory
{
    protected $model = AcopioModel::class;

    public function definition(): array
    {
        $conPerdida = $this->faker->boolean(8);

        return [
            'proveedor_id' => ProveedorModel::factory(),
            'acopiador_id' => null,
            'ruta_id' => null,
            'fecha' => $this->faker->dateTimeBetween('-60 days', 'now')->format('Y-m-d'),
            'cantidad_litros' => $this->faker->randomFloat(2, 5, 80),
            'estado' => $this->faker->randomElement(['pendiente_sincronizar', 'sincronizado']),
            'perdida_litros' => $conPerdida ? $this->faker->randomFloat(2, 0.5, 5) : null,
            'motivo_perdida' => $conPerdida ? $this->faker->randomElement(['Derrame en transporte', 'Accidente en ordeño', 'Envase dañado']) : null,
            'observaciones' => $this->faker->optional(0.2)->sentence(),
        ];
    }

    public function sincronizado(): self
    {
        return $this->state(fn (array $attributes) => ['estado' => 'sincronizado']);
    }

    public function pendiente(): self
    {
        return $this->state(fn (array $attributes) => ['estado' => 'pendiente_sincronizar']);
    }
}
