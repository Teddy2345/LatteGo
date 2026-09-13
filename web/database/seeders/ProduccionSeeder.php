<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Infrastructure\Produccion\Models\DespachoModel;
use App\Infrastructure\Produccion\Models\ProduccionModel;
use Illuminate\Database\Seeder;

class ProduccionSeeder extends Seeder
{
    public function run(int $cantidad = 30): void
    {
        ProduccionModel::factory()
            ->count($cantidad)
            ->create()
            ->each(function (ProduccionModel $produccion) {
                $recibidos = max(0, $produccion->quesos_producidos - fake()->numberBetween(0, 3));
                $despachados = max(0, $recibidos - fake()->numberBetween(0, 2));

                DespachoModel::factory()->create([
                    'produccion_id' => $produccion->id,
                    'quesos_recibidos' => $recibidos,
                    'quesos_despachados' => $despachados,
                    'merma' => max(0, $produccion->quesos_producidos - $recibidos),
                ]);
            });
    }
}
