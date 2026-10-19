<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Las sanciones por agua añadida cambian de nombre para decir lo que hacen:
 * - la primera vez ya no "descuenta la semana": esa entrega no se paga;
 * - la reincidencia ya no "retira" al proveedor sin fecha de vuelta: queda
 *   suspendido esa semana sin pago y vuelve solo a la siguiente.
 *
 * Cada valor viejo se traduce al que ocupa su mismo lugar en la escalada.
 */
return new class () extends Migration {
    public function up(): void
    {
        DB::table('analisis_calidad')
            ->where('sancion_aplicada', 'descuento_semana')
            ->update(['sancion_aplicada' => 'sin_pago_entrega']);

        DB::table('analisis_calidad')
            ->where('sancion_aplicada', 'retiro_temporal')
            ->update(['sancion_aplicada' => 'suspension_semana']);
    }

    public function down(): void
    {
        DB::table('analisis_calidad')
            ->where('sancion_aplicada', 'sin_pago_entrega')
            ->update(['sancion_aplicada' => 'descuento_semana']);

        DB::table('analisis_calidad')
            ->where('sancion_aplicada', 'suspension_semana')
            ->update(['sancion_aplicada' => 'retiro_temporal']);
    }
};
