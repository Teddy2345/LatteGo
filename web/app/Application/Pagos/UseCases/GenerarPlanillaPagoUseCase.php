<?php

declare(strict_types=1);

namespace App\Application\Pagos\UseCases;

use App\Application\Pagos\DTOs\PagoData;
use App\Domain\Acopio\Entities\Acopio;
use App\Domain\Acopio\Repositories\AcopioRepository;
use App\Domain\Acopio\ValueObjects\EstadoAcopio;
use App\Domain\Acopio\ValueObjects\SemanaPago;
use App\Domain\Pagos\Entities\Pago;
use App\Domain\Pagos\Repositories\PagoRepository;
use App\Domain\Pagos\ValueObjects\TotalLitros;
use App\Domain\Proveedor\Repositories\ProveedorRepository;
use DateTimeImmutable;

/**
 * Genera la planilla de pago semanal (jueves a miercoles): suma los
 * litros de los acopios ya sincronizados de cada proveedor en la semana
 * y calcula total_litros * precio_litro. Si un proveedor ya tiene un pago
 * generado para esa semana, se omite (no duplica). Proveedores sin
 * acopios en la semana simplemente no generan pago.
 */
final class GenerarPlanillaPagoUseCase
{
    public function __construct(
        private readonly PagoRepository $pagos,
        private readonly AcopioRepository $acopios,
        private readonly ProveedorRepository $proveedores,
    ) {
    }

    /**
     * @return PagoData[]
     */
    public function ejecutar(string $fechaReferencia): array
    {
        $semana = SemanaPago::desde(new DateTimeImmutable($fechaReferencia));

        $litrosPorProveedor = [];
        foreach ($this->acopios->listarPorSemana($semana->inicio, $semana->fin) as $acopio) {
            /** @var Acopio $acopio */
            if ($acopio->estado !== EstadoAcopio::Sincronizado) {
                continue;
            }

            $litrosPorProveedor[$acopio->proveedorId] = ($litrosPorProveedor[$acopio->proveedorId] ?? 0.0)
                + $acopio->cantidadLitros->valor;
        }

        $generados = [];

        foreach ($litrosPorProveedor as $proveedorId => $totalLitros) {
            if ($this->pagos->buscarPorProveedorYSemana($proveedorId, $semana->inicio) !== null) {
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
}
