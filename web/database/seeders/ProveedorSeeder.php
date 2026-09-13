<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Infrastructure\Proveedor\Models\ProveedorModel;
use App\Infrastructure\Proveedor\Models\RutaModel;
use Illuminate\Database\Seeder;

class ProveedorSeeder extends Seeder
{
    public function run(int $cantidad = 25): void
    {
        $this->call(RutaSeeder::class);

        $rutaIds = RutaModel::query()->pluck('id');

        ProveedorModel::factory()
            ->count($cantidad)
            ->sequence(fn () => ['ruta_id' => $rutaIds->random()])
            ->create();
    }
}
