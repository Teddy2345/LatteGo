<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Infrastructure\Acopio\Models\AcopioModel;
use App\Infrastructure\Proveedor\Models\ProveedorModel;
use Illuminate\Database\Seeder;

class AcopioSeeder extends Seeder
{
    public function run(int $cantidad = 100): void
    {
        $proveedores = ProveedorModel::query()->get(['id', 'ruta_id']);

        if ($proveedores->isEmpty()) {
            $this->call(ProveedorSeeder::class);
            $proveedores = ProveedorModel::query()->get(['id', 'ruta_id']);
        }

        // La zona del acopio es la ruta del proveedor: sin esto el
        // consolidado diario agrupa todo en "Sin zona asignada".
        AcopioModel::factory()
            ->count($cantidad)
            ->sequence(function () use ($proveedores): array {
                $proveedor = $proveedores->random();

                return ['proveedor_id' => $proveedor->id, 'ruta_id' => $proveedor->ruta_id];
            })
            ->create();
    }
}
