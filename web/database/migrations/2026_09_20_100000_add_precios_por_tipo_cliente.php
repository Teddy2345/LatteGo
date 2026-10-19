<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Precio distinto segun a quien se le venda.
 *
 * `precio_referencia` sigue siendo el precio publico —el que muestra la
 * tienda a quien viene de otro distrito— y no se toca, asi que nada cambia
 * para lo ya registrado. Los dos precios nuevos son opcionales: un producto
 * sin ellos se cobra igual a todos, que es como esta hoy el catalogo.
 *
 * En la venta se anota con cual de los tres se cobro. Las ventas anteriores
 * quedan en null y se leen como publicas, que es lo que fueron.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->decimal('precio_vecino', 8, 2)->nullable()->after('precio_referencia');
            $table->decimal('precio_proveedor', 8, 2)->nullable()->after('precio_vecino');
        });

        Schema::table('movimientos_inventario', function (Blueprint $table) {
            $table->string('tipo_cliente', 20)->nullable()->after('cliente');
        });
    }

    public function down(): void
    {
        Schema::table('movimientos_inventario', function (Blueprint $table) {
            $table->dropColumn('tipo_cliente');
        });

        Schema::table('productos', function (Blueprint $table) {
            $table->dropColumn(['precio_vecino', 'precio_proveedor']);
        });
    }
};
