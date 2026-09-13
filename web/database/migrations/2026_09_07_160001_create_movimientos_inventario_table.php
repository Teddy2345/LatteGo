<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Libro de movimientos del almacen: cada transformacion suma unidades y
     * cada venta las resta. El stock de un producto es siempre la suma de su
     * historial, nunca un contador que se pueda desincronizar.
     */
    public function up(): void
    {
        Schema::create('movimientos_inventario', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos')->restrictOnDelete();
            $table->string('tipo', 20);
            $table->date('fecha');
            $table->decimal('cantidad', 10, 2);

            // Solo en las transformaciones: leche que entro para obtener el producto.
            $table->decimal('litros_procesados', 10, 2)->nullable();

            // Solo en las ventas.
            $table->decimal('precio_unitario', 8, 2)->nullable();
            $table->decimal('total', 10, 2)->nullable();
            $table->string('cliente', 160)->nullable();

            $table->text('observaciones')->nullable();
            $table->foreignId('usuario_id')->nullable()->constrained('users')->nullOnDelete();

            // Clave de idempotencia de la app movil.
            $table->string('request_id', 64)->nullable()->unique();
            $table->timestamps();

            $table->index(['producto_id', 'tipo']);
            $table->index('fecha');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos_inventario');
    }
};
