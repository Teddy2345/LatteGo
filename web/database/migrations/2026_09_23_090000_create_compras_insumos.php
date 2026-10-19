<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Compras de insumos: la puerta por la que entran al almacen la sal, el
 * cuajo, los envases y todo lo que despues consume produccion.
 *
 * Una compra es un documento (la boleta del proveedor) con varias lineas.
 * Cada linea deja ademas su movimiento de almacen, que es de donde sale el
 * stock: la tabla de compras guarda el papel, no el saldo.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::create('compras', function (Blueprint $tabla): void {
            $tabla->id();
            $tabla->date('fecha')->index();
            $tabla->string('proveedor', 160);
            $tabla->string('documento', 60)->nullable();
            $tabla->decimal('total', 10, 2)->default(0);
            $tabla->text('observaciones')->nullable();
            $tabla->foreignId('usuario_id')->nullable()->constrained('users')->nullOnDelete();
            // Reintentar desde una conexion mala no debe comprar dos veces.
            $tabla->string('request_id', 64)->nullable()->unique();
            $tabla->timestamps();
        });

        Schema::create('compra_detalles', function (Blueprint $tabla): void {
            $tabla->id();
            $tabla->foreignId('compra_id')->constrained('compras')->cascadeOnDelete();
            $tabla->foreignId('producto_id')->constrained('productos')->restrictOnDelete();
            $tabla->decimal('cantidad', 10, 2);
            $tabla->decimal('costo_unitario', 8, 2);
            $tabla->string('unidad', 20);
            // El movimiento que esta linea dejo en almacen, para poder rastrearlo.
            $tabla->foreignId('movimiento_inventario_id')->nullable()->unique()
                ->constrained('movimientos_inventario')->nullOnDelete();
            $tabla->timestamps();
        });

        Schema::table('movimientos_inventario', function (Blueprint $tabla): void {
            $tabla->foreignId('compra_id')->nullable()->after('produccion_id')
                ->constrained('compras')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('movimientos_inventario', function (Blueprint $tabla): void {
            $tabla->dropConstrainedForeignId('compra_id');
        });

        Schema::dropIfExists('compra_detalles');
        Schema::dropIfExists('compras');
    }
};
