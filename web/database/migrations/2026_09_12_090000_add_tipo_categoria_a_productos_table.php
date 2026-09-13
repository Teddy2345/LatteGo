<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * El almacen maneja tanto producto terminado (queso, yogurt) como
     * insumos (sal, cuajo, envases). "tipo" los distingue sin necesitar
     * dos tablas identicas; categoria agrupa dentro de cada tipo
     * (ej. "Lacteos", "Envases") y stock_minimo habilita la alerta de
     * "por agotarse".
     */
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->string('tipo', 20)->default('producto_terminado')->after('nombre');
            $table->string('categoria', 60)->nullable()->after('tipo');
            $table->decimal('stock_minimo', 10, 2)->nullable()->after('precio_referencia');

            $table->index('tipo');
        });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropColumn(['tipo', 'categoria', 'stock_minimo']);
        });
    }
};
