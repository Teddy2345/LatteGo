<?php

declare(strict_types=1);

namespace App\Application\Pagos\UseCases;

use App\Application\Pagos\DTOs\PagoData;
use App\Domain\Acopio\Entities\Acopio;
use App\Domain\Acopio\Repositories\AcopioRepository;
use App\Domain\Acopio\ValueObjects\EstadoAcopio;
use App\Domain\Acopio\ValueObjects\SemanaPago;
use App\Domain\Calidad\Repositories\CalidadRepository;
use App\Domain\Pagos\Entities\Pago;
use App\Domain\Pagos\Repositories\PagoRepository;
use App\Domain\Pagos\ValueObjects\EstadoPago;
use App\Domain\Pagos\ValueObjects\TotalLitros;
use App\Domain\Proveedor\Repositories\ProveedorRepository;
use DateTimeImmutable;

/**
 * Genera la planilla de pago semanal (jueves a miercoles): suma los litros
 * de los acopios ya sincronizados de cada proveedor y multiplica por su
 * precio por litro. Si un proveedor ya tiene pago pendiente para esa semana,
 * se rehacen sus litros; si ya esta pagado y llegaron litros nuevos, se le
 * genera un pago complementario por la diferencia (ver ajustarPagosDeLaSemana).
 *
 * Aplica el castigo por añadir agua a la leche:
 * - la entrega con agua no se paga;
 * - quien reincide queda suspendido esa semana y no cobra nada de ella.
 *
 * El suspendido igual recibe su fila de pago en S/ 0.00, para que en la
 * planilla se vea que no cobro en lugar de desaparecer sin explicacion.
 */
final class GenerarPlanillaPagoUseCase
{
    public function __construct(
        private readonly PagoRepository $pagos,
        private readonly AcopioRepository $acopios,
        private readonly ProveedorRepository $proveedores,
        private readonly CalidadRepository $calidad,
    ) {
    }

    /**
     * @return PagoData[]
     */
    public function ejecutar(string $fechaReferencia): array
    {
        $semana = SemanaPago::desde(new DateTimeImmutable($fechaReferencia));

        $acopiosSemana = array_values(array_filter(
            $this->acopios->listarPorSemana($semana->inicio, $semana->fin),
            static fn (Acopio $acopio): bool => $acopio->estado === EstadoAcopio::Sincronizado,
        ));

        $conAgua = array_flip($this->calidad->acopiosConAguaAnadida(
            array_map(static fn (Acopio $acopio): int => (int) $acopio->id, $acopiosSemana),
        ));
        $suspendidos = array_flip($this->calidad->proveedoresSuspendidosEntre($semana->inicio, $semana->fin));

        $litrosPorProveedor = [];
        foreach ($acopiosSemana as $acopio) {
            // Se registra al proveedor aunque no cobre nada, para que su fila
            // aparezca en la planilla.
            $litrosPorProveedor[$acopio->proveedorId] ??= 0.0;

            if (isset($conAgua[$acopio->id]) || isset($suspendidos[$acopio->proveedorId])) {
                continue;
            }

            $litrosPorProveedor[$acopio->proveedorId] += $acopio->cantidadLitros->valor;
        }

        $generados = [];

        foreach ($litrosPorProveedor as $proveedorId => $totalLitros) {
            $pagosSemana = $this->pagos->listarPorProveedorYSemana($proveedorId, $semana->inicio);

            if ($pagosSemana !== []) {
                $ajustado = $this->ajustarPagosDeLaSemana($pagosSemana, round($totalLitros, 2));

                if ($ajustado !== null) {
                    $generados[] = PagoData::desdeEntidad($this->pagos->guardar($ajustado));
                }

                continue;
            }

            $proveedor = $this->proveedores->buscarPorId($proveedorId);

            if ($proveedor === null) {
                continue;
            }

            $pago = Pago::crear(
                proveedorId: $proveedorId,
                semanaInicio: $semana->inicio,
                semanaFin: $semana->fin,
                totalLitros: new TotalLitros($totalLitros),
                precioLitro: $proveedor->precioLitro->valor,
            );

            $generados[] = PagoData::desdeEntidad($this->pagos->guardar($pago));
        }

        return $generados;
    }

    /**
     * La semana ya tiene pagos. Lo que se debe es el total de litros menos lo
     * ya pagado:
     * - si hay un pago pendiente (la planilla o un complementario), se rehace
     *   con esa diferencia;
     * - si todo esta pagado y aparecieron litros nuevos (un acopio que el
     *   telefono sincronizo tarde), se crea un pago complementario por ellos.
     * Si lo pagado ya cubre la semana (o la supera por una sancion registrada
     * despues de pagar) no se genera nada: no existen pagos negativos.
     *
     * @param  Pago[]  $pagosSemana  Ordenados por secuencia; el primero es la planilla.
     */
    private function ajustarPagosDeLaSemana(array $pagosSemana, float $totalLitros): ?Pago
    {
        $litrosPagados = 0.0;
        $pendiente = null;

        foreach ($pagosSemana as $pago) {
            if ($pago->estado === EstadoPago::Pagado) {
                $litrosPagados += $pago->totalLitros->valor;
            } else {
                $pendiente = $pago;
            }
        }

        $faltantes = round($totalLitros - $litrosPagados, 2);

        if ($pendiente !== null) {
            $recalculado = $pendiente->recalcularLitros(new TotalLitros(max(0.0, $faltantes)));

            return $recalculado->totalLitros->valor !== $pendiente->totalLitros->valor ? $recalculado : null;
        }

        if ($faltantes <= 0.0) {
            return null;
        }

        $ultimo = $pagosSemana[count($pagosSemana) - 1];

        return Pago::complementario($pagosSemana[0], new TotalLitros($faltantes), $ultimo->secuencia + 1);
    }
}
