<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * El proveedor entra a la app con su propia cuenta.
 *
 * Hasta ahora el proveedor era solo una ficha de la planta. Se le cuelga un
 * usuario para que pueda entrar con su cedula, comprar en la tienda a su
 * precio y seguir sus entregas. La ficha puede quedarse sin cuenta: quien no
 * use la app sigue funcionando igual que siempre.
 *
 * El pedido gana como lo recibe, su codigo de comprobante y a quien
 * pertenece. La notificacion gana destinatario: el codigo del pedido es de
 * su dueño, no del tablero de toda la planta.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::table('proveedores', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->unique()->after('id')
                ->constrained('users')->nullOnDelete();
        });

        Schema::table('pedidos', function (Blueprint $table) {
            // Quien lo hizo desde la app; null = visitante de la tienda web.
            $table->foreignId('user_id')->nullable()->after('id')->constrained('users')->nullOnDelete();
            // Se muestra al recibir el pedido y sirve para encontrarlo.
            $table->string('codigo', 12)->nullable()->unique()->after('user_id');
            $table->string('tipo_entrega', 20)->nullable()->after('estado');
            // Con que precio se cobro, igual que en las ventas de mostrador.
            $table->string('tipo_cliente', 20)->nullable()->after('tipo_entrega');
        });

        Schema::table('notificaciones', function (Blueprint $table) {
            // null = para todo el personal, como hasta ahora.
            $table->foreignId('user_id')->nullable()->after('id')->constrained('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('notificaciones', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });

        Schema::table('pedidos', function (Blueprint $table) {
            $table->dropColumn(['codigo', 'tipo_entrega', 'tipo_cliente']);
            $table->dropConstrainedForeignId('user_id');
        });

        Schema::table('proveedores', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });
    }
};
