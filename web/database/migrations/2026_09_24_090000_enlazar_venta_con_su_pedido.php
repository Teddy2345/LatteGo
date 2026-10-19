<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Ata cada venta con el pedido que la origino.
 *
 * Sin esa atadura, la venta que deja una entrega aparece en el historial
 * como si nadie la hubiera facturado, y se le podria emitir una segunda
 * boleta por el mismo dinero.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::table('movimientos_inventario', function (Blueprint $tabla): void {
            $tabla->foreignId('pedido_id')->nullable()->after('compra_id')
                ->constrained('pedidos')->nullOnDelete();
        });

        // Las entregas que ya se facturaron antes de existir esta columna:
        // se enlazan solo cuando el pedido es uno y nada mas uno, para no
        // atar una venta al pedido equivocado.
        foreach (DB::table('comprobantes')->whereNotNull('pedido_id')->pluck('pedido_id') as $pedidoId) {
            $pedido = DB::table('pedidos')->find($pedidoId);

            if (! $pedido || $pedido->fecha_entrega === null) {
                continue;
            }

            $candidatas = DB::table('movimientos_inventario')
                ->where('tipo', 'venta')
                ->whereNull('pedido_id')
                ->where('cliente', $pedido->cliente_nombre)
                ->whereDate('fecha', $pedido->fecha_entrega)
                ->where('usuario_id', $pedido->repartidor_id);

            $otrosPedidosIguales = DB::table('pedidos')
                ->where('id', '!=', $pedido->id)
                ->where('cliente_nombre', $pedido->cliente_nombre)
                ->whereDate('fecha_entrega', $pedido->fecha_entrega)
                ->count();

            if ($otrosPedidosIguales === 0) {
                $candidatas->update(['pedido_id' => $pedido->id]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('movimientos_inventario', function (Blueprint $tabla): void {
            $tabla->dropConstrainedForeignId('pedido_id');
        });
    }
};
