<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Que producto del catalogo de Almacen corresponde a este lote de
     * quesos. Nullable: sigue siendo opcional registrar produccion sin
     * conectarla al almacen (comportamiento actual, sin cambios).
     */
    public function up(): void
    {
        Schema::table('produccion', function (Blueprint $table) {
            $table->foreignId('producto_id')->nullable()->after('quesos_producidos')
                ->constrained('productos')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('produccion', function (Blueprint $table) {
            $table->dropConstrainedForeignId('producto_id');
        });
    }
};
