<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Fotografia del equipo (LactoScan u otro) usada como referencia del
     * analisis, con o sin lectura automatica por OCR.
     */
    public function up(): void
    {
        Schema::table('analisis_calidad', function (Blueprint $table) {
            $table->string('foto_path', 255)->nullable()->after('sancion_aplicada');
        });
    }

    public function down(): void
    {
        Schema::table('analisis_calidad', function (Blueprint $table) {
            $table->dropColumn('foto_path');
        });
    }
};
