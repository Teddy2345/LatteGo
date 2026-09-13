<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Une un movimiento de almacen al lote de produccion que lo origino:
     * tanto la entrada del producto terminado como la salida de cada
     * insumo consumido. Reemplaza tener que adivinarlo desde el texto de
     * "observaciones" (ej. "Uso en Produccion #125").
     */
    public function up(): void
    {
        Schema::table('movimientos_inventario', function (Blueprint $table) {
            $table->foreignId('produccion_id')->nullable()->after('producto_id')
                ->constrained('produccion')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('movimientos_inventario', function (Blueprint $table) {
            $table->dropConstrainedForeignId('produccion_id');
        });
    }
};
