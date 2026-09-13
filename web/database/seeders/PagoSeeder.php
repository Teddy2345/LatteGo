<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Application\Pagos\UseCases\GenerarPlanillaPagoUseCase;
use App\Application\Pagos\UseCases\MarcarPagoComoPagadoUseCase;
use App\Infrastructure\Acopio\Models\AcopioModel;
use App\Infrastructure\Pagos\Models\PagoModel;
use Illuminate\Database\Seeder;

/**
 * Genera la planilla de pago real (via GenerarPlanillaPagoUseCase) para
 * cada semana jueves-miercoles cubierta por los acopios ya sembrados, en
 * vez de insertar filas sueltas: así los totales quedan consistentes con
 * los acopios sincronizados existentes.
 */
class PagoSeeder extends Seeder
{
    public function run(): void
    {
        if (AcopioModel::query()->doesntExist()) {
            $this->call(AcopioSeeder::class);
        }

        $generarPlanilla = app(GenerarPlanillaPagoUseCase::class);
        $marcarPagado = app(MarcarPagoComoPagadoUseCase::class);

        // GenerarPlanillaPagoUseCase es idempotente (omite semanas ya
        // generadas para un proveedor), asi que se puede invocar una vez
        // por cada fecha distinta sin preocuparse por deduplicar semanas.
        $fechas = AcopioModel::query()->selectRaw('DISTINCT fecha')->pluck('fecha');

        foreach ($fechas as $fecha) {
            foreach ($generarPlanilla->ejecutar($fecha->format('Y-m-d')) as $pago) {
                if (fake()->boolean(70)) {
                    $marcarPagado->ejecutar($pago->id);
                }
            }
        }

        if (PagoModel::query()->doesntExist()) {
            PagoModel::factory()->count(10)->create();
        }
    }
}
