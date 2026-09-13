<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Reparto y cobro de un pedido: quien lo entrega, con que metodo se
     * cobro, cuanto y cuando. No hay pasarela de pago; solo queda
     * constancia de lo que el repartidor cobro al entregar.
     */
    public function up(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->foreignId('repartidor_id')->nullable()->after('estado')->constrained('users')->nullOnDelete();
            $table->string('metodo_pago', 20)->nullable()->after('repartidor_id');
            $table->decimal('monto_cobrado', 10, 2)->nullable()->after('metodo_pago');
            $table->date('fecha_entrega')->nullable()->after('monto_cobrado');
        });
    }

    public function down(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('repartidor_id');
            $table->dropColumn(['metodo_pago', 'monto_cobrado', 'fecha_entrega']);
        });
    }
};
