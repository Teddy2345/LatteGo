<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Infrastructure\Acopio\Models\AcopioModel;
use App\Infrastructure\Calidad\Models\CalidadModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CalidadModel>
 */
final class CalidadModelFactory extends Factory
{
    protected $model = CalidadModel::class;

    public function definition(): array
    {
        $adulterada = $this->faker->boolean(6);
        $aguaAgregada = $adulterada ? $this->faker->randomFloat(2, 0.5, 8) : 0.0;
        $pruebaAlcohol = $adulterada && $this->faker->boolean(40) ? 'rechazada' : 'aceptada';

        $rechazada = $aguaAgregada > 0.0 || $pruebaAlcohol === 'rechazada';

        return [
            'proveedor_id' => \App\Infrastructure\Proveedor\Models\ProveedorModel::factory(),
            'acopio_id' => AcopioModel::factory(),
            'fecha' => $this->faker->dateTimeBetween('-60 days', 'now')->format('Y-m-d'),
            'temperatura' => $this->faker->randomFloat(2, 2, 6),
            'grasa' => $this->faker->randomFloat(2, 3.0, 4.5),
            'solidos_no_grasos' => $this->faker->randomFloat(2, 8.0, 9.5),
            'densidad' => $rechazada && $aguaAgregada > 0.0
                ? $this->faker->randomFloat(2, 20, 27.9)
                : $this->faker->randomFloat(2, 28.0, 33.0),
            'proteina' => $this->faker->randomFloat(2, 2.8, 3.6),
            'lactosa' => $this->faker->randomFloat(2, 4.2, 5.0),
            'sales' => $this->faker->randomFloat(2, 0.5, 0.9),
            'agua_agregada' => $aguaAgregada,
            'ph' => $this->faker->randomFloat(2, 6.4, 6.8),
            'prueba_alcohol' => $pruebaAlcohol,
            'resultado' => $rechazada ? 'rechazada' : 'aceptada',
            'motivo_rechazo' => $rechazada ? 'adulteracion' : null,
            'sancion_aplicada' => $rechazada ? 'descuento_semana' : 'ninguna',
        ];
    }
}
