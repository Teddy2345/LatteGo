<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Infrastructure\Acopio\Models\AcopioModel;
use App\Infrastructure\Calidad\Models\CalidadModel;
use Illuminate\Database\Seeder;

class CalidadSeeder extends Seeder
{
    public function run(int $cantidad = 20): void
    {
        $acopios = AcopioModel::query()->inRandomOrder()->limit($cantidad)->get(['id', 'proveedor_id']);

        if ($acopios->isEmpty()) {
            $this->call(AcopioSeeder::class);
            $acopios = AcopioModel::query()->inRandomOrder()->limit($cantidad)->get(['id', 'proveedor_id']);
        }

        foreach ($acopios as $acopio) {
            CalidadModel::factory()->create([
                'proveedor_id' => $acopio->proveedor_id,
                'acopio_id' => $acopio->id,
            ]);
        }
    }
}
