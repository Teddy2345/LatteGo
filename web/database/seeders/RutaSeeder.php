<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Infrastructure\Proveedor\Models\RutaModel;
use Illuminate\Database\Seeder;

class RutaSeeder extends Seeder
{
    /**
     * Rutas con sus paradas de referencia para el mapa (lugares aproximados
     * hasta tener el trazado oficial de cada una).
     */
    public function run(): void
    {
        $rutas = [
            'Huata Centro' => ['Pucuro Grande, La Paz, Bolivia'],
            'Achacachi' => ['Achacachi, La Paz, Bolivia'],
            'Copacabana' => ['Copacabana, La Paz, Bolivia'],
            'Puerto Perez' => ['Puerto Pérez, La Paz, Bolivia'],
            'Batallas' => ['Batallas, La Paz, Bolivia'],
        ];

        foreach ($rutas as $nombre => $paradas) {
            RutaModel::query()->firstOrCreate(['nombre' => $nombre], ['paradas_referencia' => $paradas]);
        }
    }
}
