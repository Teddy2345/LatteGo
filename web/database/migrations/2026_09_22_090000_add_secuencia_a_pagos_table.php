<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Un proveedor puede tener mas de un pago por semana: la planilla
     * (secuencia 0) y, si despues de pagarla llegan acopios atrasados desde
     * el telefono, pagos complementarios (1, 2...) por esos litros.
     */
    public function up(): void
    {
        Schema::table('pagos', function (Blueprint $table) {
            $table->unsignedTinyInteger('secuencia')->default(0)->after('semana_fin');
        });

        // Primero el indice nuevo: la clave foranea de proveedor_id se apoya
        // en el unico viejo y no se puede soltar sin otro que la cubra.
        Schema::table('pagos', function (Blueprint $table) {
            $table->unique(['proveedor_id', 'semana_inicio', 'secuencia'], 'pagos_proveedor_semana_secuencia_unique');
        });

        Schema::table('pagos', function (Blueprint $table) {
            $table->dropUnique(['proveedor_id', 'semana_inicio']);
        });
    }

    public function down(): void
    {
        Schema::table('pagos', function (Blueprint $table) {
            $table->unique(['proveedor_id', 'semana_inicio']);
        });

        Schema::table('pagos', function (Blueprint $table) {
            $table->dropUnique('pagos_proveedor_semana_secuencia_unique');
            $table->dropColumn('secuencia');
        });
    }
};
