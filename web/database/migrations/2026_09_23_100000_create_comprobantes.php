<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Boletas y facturas de la planta. Es numeracion propia, no electronica:
 * sirve de comprobante para el cliente y de respaldo interno, y cada una
 * apunta a la venta o al pedido que la origino para que no haya dos
 * documentos por el mismo cobro.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::create('comprobantes', function (Blueprint $tabla): void {
            $tabla->id();
            $tabla->string('tipo', 20);
            $tabla->string('serie', 8);
            $tabla->unsignedInteger('numero');
            $tabla->date('fecha')->index();
            $tabla->string('cliente_nombre', 160);
            $tabla->string('cliente_documento', 20)->nullable();
            $tabla->string('cliente_direccion', 255)->nullable();
            $tabla->decimal('total', 10, 2);
            // Se emite por una venta de mostrador o por un pedido de la tienda.
            $tabla->foreignId('movimiento_inventario_id')->nullable()->unique()
                ->constrained('movimientos_inventario')->cascadeOnDelete();
            $tabla->foreignId('pedido_id')->nullable()->unique()
                ->constrained('pedidos')->cascadeOnDelete();
            $tabla->foreignId('usuario_id')->nullable()->constrained('users')->nullOnDelete();
            $tabla->timestamps();

            // Dos documentos con el mismo numero serian un problema contable.
            $tabla->unique(['serie', 'numero']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comprobantes');
    }
};
