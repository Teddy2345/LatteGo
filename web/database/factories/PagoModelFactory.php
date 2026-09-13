<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Infrastructure\Pagos\Models\PagoModel;
use App\Infrastructure\Proveedor\Models\ProveedorModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PagoModel>
 */
final class PagoModelFactory extends Factory
{
    protected $model = PagoModel::class;

    public function definition(): array
    {
        $totalLitros = $this->faker->randomFloat(2, 100, 800);
        $precioLitro = $this->faker->randomFloat(2, 3.0, 4.5);
        $semanaInicio = $this->faker->dateTimeBetween('-90 days', '-7 days');
        $semanaFin = (clone $semanaInicio)->modify('+6 days');
        $pagado = $this->faker->boolean(70);

        return [
            'proveedor_id' => ProveedorModel::factory(),
            'semana_inicio' => $semanaInicio->format('Y-m-d'),
            'semana_fin' => $semanaFin->format('Y-m-d'),
            'total_litros' => $totalLitros,
            'precio_litro' => $precioLitro,
            'total_pagar' => round($totalLitros * $precioLitro, 2),
            'fecha_pago' => $pagado ? (clone $semanaFin)->modify('+2 days')->format('Y-m-d') : null,
            'estado' => $pagado ? 'pagado' : 'pendiente',
        ];
    }
}
