<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Un solo analisis por entrega. La regla vive en el caso de uso; el
     * indice cubre el doble envio simultaneo (dos clics, dos pestañas), que
     * de colarse contaria como reincidencia y suspenderia al proveedor.
     */
    public function up(): void
    {
        Schema::table('analisis_calidad', function (Blueprint $table) {
            $table->unique('acopio_id', 'analisis_calidad_acopio_unico');
        });
    }

    public function down(): void
    {
        Schema::table('analisis_calidad', function (Blueprint $table) {
            // MySQL usa el indice unico para la clave foranea de acopio_id:
            // hay que dejarle uno simple antes de soltarlo.
            $table->index('acopio_id', 'analisis_calidad_acopio_id_index');
            $table->dropUnique('analisis_calidad_acopio_unico');
        });
    }
};
