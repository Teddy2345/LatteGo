<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * El comprobante del pago que no es en efectivo.
 *
 * Yape, una transferencia o un QR dejan un numero de operacion. Guardarlo es
 * lo que permite cotejar despues contra el estado de cuenta: sin el, una
 * captura de pantalla es palabra contra palabra. El dia que se contrate una
 * pasarela, este mismo campo lo llenara el webhook en lugar de una persona.
 *
 * Nullable porque el efectivo no tiene referencia, y porque las entregas ya
 * registradas se quedan como estan.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->string('referencia_pago', 40)->nullable()->after('metodo_pago');
            $table->index(['metodo_pago', 'fecha_entrega']);
        });
    }

    public function down(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->dropIndex(['metodo_pago', 'fecha_entrega']);
            $table->dropColumn('referencia_pago');
        });
    }
};
