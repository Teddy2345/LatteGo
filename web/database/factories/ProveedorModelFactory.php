<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Infrastructure\Proveedor\Models\ProveedorModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProveedorModel>
 */
final class ProveedorModelFactory extends Factory
{
    protected $model = ProveedorModel::class;

    private const PREFIJOS_FINCA = [
        'Finca', 'Estancia', 'Chacra', 'Predio', 'Rancho',
    ];

    private const NOMBRES_FINCA = [
        'Huata', 'Los Andes', 'Sol Naciente', 'Illimani', 'Titicaca',
        'Achacachi', 'Copacabana', 'Wiñay Marka', 'Pachamama', 'Amanecer',
    ];

    public function definition(): array
    {
        return [
            'nombre' => $this->faker->name(),
            'cedula' => $this->faker->unique()->numerify('#######'),
            'telefono' => $this->faker->optional(0.8)->numerify('7#######'),
            'finca' => $this->faker->optional(0.7)->passthrough(
                $this->faker->randomElement(self::PREFIJOS_FINCA).' '.$this->faker->randomElement(self::NOMBRES_FINCA),
            ),
            'litros_prom' => $this->faker->numberBetween(15, 300),
            'precio_litro' => $this->faker->randomFloat(2, 3.0, 4.5),
            'activo' => $this->faker->boolean(90),
            'ruta_id' => null,
        ];
    }

    public function inactivo(): self
    {
        return $this->state(fn (array $attributes) => ['activo' => false]);
    }
}
