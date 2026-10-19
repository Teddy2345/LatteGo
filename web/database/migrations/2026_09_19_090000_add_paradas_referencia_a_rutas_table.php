<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Paradas de referencia de cada ruta, en orden, para dibujar su recorrido
     * en el mapa desde la planta. Son lugares aproximados mientras no llega
     * el trazado oficial; cuando llegue se reemplazan aqui, sin tocar codigo.
     */
    private const PARADAS = [
        'Huata Centro' => ['Pucuro Grande, La Paz, Bolivia'],
        'Achacachi' => ['Achacachi, La Paz, Bolivia'],
        'Copacabana' => ['Copacabana, La Paz, Bolivia'],
        'Puerto Perez' => ['Puerto Pérez, La Paz, Bolivia'],
        'Batallas' => ['Batallas, La Paz, Bolivia'],
    ];

    public function up(): void
    {
        Schema::table('rutas', function (Blueprint $table) {
            $table->json('paradas_referencia')->nullable()->after('descripcion');
        });

        foreach (self::PARADAS as $nombre => $paradas) {
            DB::table('rutas')->where('nombre', $nombre)->update([
                'paradas_referencia' => json_encode($paradas, JSON_UNESCAPED_UNICODE),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('rutas', function (Blueprint $table) {
            $table->dropColumn('paradas_referencia');
        });
    }
};
