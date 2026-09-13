<?php

declare(strict_types=1);

namespace App\Application\Costeo\UseCases;

use App\Application\Costeo\DTOs\CostoInsumoData;
use App\Application\Costeo\DTOs\CostoProduccionData;
use App\Domain\Acopio\Repositories\AcopioRepository;
use App\Domain\Acopio\ValueObjects\EstadoAcopio;
use App\Domain\Acopio\ValueObjects\SemanaPago;
use App\Domain\Inventario\Repositories\MovimientoInventarioRepository;
use App\Domain\Inventario\Repositories\ProductoRepository;
use App\Domain\Inventario\ValueObjects\TipoMovimiento;
use App\Domain\Produccion\Entities\Produccion;
use App\Domain\Produccion\Exceptions\ProduccionNoEncontradaException;
use App\Domain\Produccion\Repositories\ProduccionRepository;
use App\Domain\Proveedor\Repositories\ProveedorRepository;

/**
 * Calcula el costo de una produccion ya registrada: costo de la leche
 * procesada (precio promedio ponderado que se pago a los proveedores esa
 * semana de acopio, multiplicado por los litros procesados) mas el costo de
 * los insumos consumidos (costo promedio ponderado de sus compras). No
 * persiste nada: es un calculo bajo demanda sobre datos ya existentes de
 * Acopio, Proveedor e Inventario.
 */
final class CalcularCostoProduccionUseCase
{
    public function __construct(
        private readonly ProduccionRepository $producciones,
        private readonly MovimientoInventarioRepository $movimientos,
        private readonly ProductoRepository $productos,
        private readonly AcopioRepository $acopios,
        private readonly ProveedorRepository $proveedores,
    ) {
    }

    public function ejecutar(int $produccionId): CostoProduccionData
    {
        $produccion = $this->producciones->buscarPorId($produccionId);

        if ($produccion === null) {
            throw new ProduccionNoEncontradaException("No existe una produccion con id {$produccionId}.");
        }

        [$precioLeche, $costoLeche] = $this->calcularCostoLeche($produccion);
        [$insumos, $costoInsumosConocido, $incompleto] = $this->calcularCostoInsumos($produccion);

        $costoTotal = $costoLeche === null ? null : round($costoLeche + $costoInsumosConocido, 2);

        return new CostoProduccionData(
            produccionId: (int) $produccion->id,
            fecha: $produccion->fecha->format('Y-m-d'),
            litrosProcesados: $produccion->litrosProcesados->valor,
            quesosProducidos: $produccion->quesosProducidos->valor,
            precioLechePromedioPonderado: $precioLeche,
            costoLeche: $costoLeche,
            insumos: $insumos,
            costoInsumosConocido: $costoInsumosConocido,
            costoInsumosIncompleto: $incompleto,
            costoTotal: $costoTotal,
            costoPorQueso: ($costoTotal !== null && $produccion->quesosProducidos->valor > 0)
                ? round($costoTotal / $produccion->quesosProducidos->valor, 2)
                : null,
        );
    }

    /**
     * @return array{0: ?float, 1: ?float} [precio promedio ponderado del litro, costo total de la leche]
     */
    private function calcularCostoLeche(Produccion $produccion): array
    {
        $semana = SemanaPago::desde($produccion->fecha);

        $litrosPorProveedor = [];
        foreach ($this->acopios->listarPorSemana($semana->inicio, $semana->fin) as $acopio) {
            if ($acopio->estado !== EstadoAcopio::Sincronizado) {
                continue;
            }

            $litrosPorProveedor[$acopio->proveedorId] = ($litrosPorProveedor[$acopio->proveedorId] ?? 0.0)
                + $acopio->cantidadLitros->valor;
        }

        $costoTotalSemana = 0.0;
        $litrosTotalSemana = 0.0;

        foreach ($litrosPorProveedor as $proveedorId => $litros) {
            $proveedor = $this->proveedores->buscarPorId($proveedorId);

            if ($proveedor === null) {
                continue;
            }

            $costoTotalSemana += $litros * $proveedor->precioLitro->valor;
            $litrosTotalSemana += $litros;
        }

        if ($litrosTotalSemana <= 0.0) {
            return [null, null];
        }

        $precioPromedio = round($costoTotalSemana / $litrosTotalSemana, 2);

        return [$precioPromedio, round($produccion->litrosProcesados->valor * $precioPromedio, 2)];
    }

    /**
     * @return array{0: CostoInsumoData[], 1: float, 2: bool}
     */
    private function calcularCostoInsumos(Produccion $produccion): array
    {
        $insumos = [];
        $costoConocido = 0.0;
        $incompleto = false;

        foreach ($this->movimientos->listarPorProduccion((int) $produccion->id) as $movimiento) {
            if ($movimiento->tipo !== TipoMovimiento::UsoProduccion) {
                continue;
            }

            $producto = $this->productos->buscarPorId($movimiento->productoId);
            $costoUnitario = $this->movimientos->costoPromedioPonderado($movimiento->productoId);
            $costoTotal = $costoUnitario === null ? null : round($movimiento->cantidad->valor * $costoUnitario, 2);

            if ($costoTotal === null) {
                $incompleto = true;
            } else {
                $costoConocido += $costoTotal;
            }

            $insumos[] = new CostoInsumoData(
                productoId: $movimiento->productoId,
                nombre: $producto?->nombre ?? "Producto #{$movimiento->productoId}",
                unidad: $producto?->unidad ?? '',
                cantidad: $movimiento->cantidad->valor,
                costoUnitario: $costoUnitario,
                costoTotal: $costoTotal,
            );
        }

        return [$insumos, round($costoConocido, 2), $incompleto];
    }
}
