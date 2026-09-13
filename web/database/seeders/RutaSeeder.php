<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Infrastructure\Proveedor\Models\RutaModel;
use Illuminate\Database\Seeder;

class RutaSeeder extends Seeder
{
    public function run(): void
    {
        $rutas = [
            'Huata Centro',
            'Achacachi',
            'Copacabana',
            'Puerto Perez',
            'Batallas',
        ];

        foreach ($rutas as $nombre) {
            RutaModel::query()->firstOrCreate(['nombre' => $nombre]);
        }
    }
}
