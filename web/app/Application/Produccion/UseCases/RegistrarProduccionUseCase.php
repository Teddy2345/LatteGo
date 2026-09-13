<?php

declare(strict_types=1);

namespace App\Application\Produccion\UseCases;

use App\Application\Produccion\DTOs\InsumoUtilizadoData;
use App\Application\Produccion\DTOs\ProduccionData;
use App\Application\Produccion\DTOs\RegistrarProduccionData;
use App\Domain\Inventario\Entities\MovimientoInventario;
use App\Domain\Inventario\Exceptions\ProductoNoEncontradoException;
use App\Domain\Inventario\Exceptions\StockInsuficienteException;
use App\Domain\Inventario\Repositories\MovimientoInventarioRepository;
use App\Domain\Inventario\Repositories\ProductoRepository;
use App\Domain\Inventario\ValueObjects\CantidadProducto;
use App\Domain\Produccion\Entities\Produccion;
use App\Domain\Produccion\Repositories\ProduccionRepository;
use App\Domain\Produccion\ValueObjects\LitrosProcesados;
use App\Domain\Produccion\ValueObjects\QuesosProducidos;
use App\Domain\Shared\Contracts\TransactionManager;
use DateTimeImmutable;

/**
 * Registra la produccion diaria de quesos. El rendimiento (quesos por
 * cada 100 litros) se calcula automaticamente; el rango esperado es
 * 11-12, pero un valor fuera de rango no bloquea el registro, solo se
 * marca para revision (ver ProduccionData::rendimientoEnRangoEsperado).
 *
 * Conectar con Almacen es opcional: si se indica a que producto del
 * catalogo corresponde el lote, se registra su entrada; si ademas se
 * listan los insumos usados, se descuentan del almacen. Sin esos datos,
 * el registro funciona exactamente igual que antes de esta integracion.
 * Toda la operacion es una sola transaccion: si falta stock de un
 * insumo, no se guarda ni la produccion ni ningun movimiento.
 */
final class RegistrarProduccionUseCase
{
    public function __construct(
        private readonly ProduccionRepository $producciones,
        private readonly MovimientoInventarioRepository $movimientos,
        private readonly ProductoRepository $productos,
        private readonly TransactionManager $transacciones,
    ) {
    }

    public function ejecutar(RegistrarProduccionData $datos): ProduccionData
    {
        return $this->transacciones->run(function () use ($datos): ProduccionData {
            $produccion = Produccion::crear(
                fecha: new DateTimeImmutable($datos->fecha),
                litrosProcesados: new LitrosProcesados($datos->litrosProcesados),
                quesosProducidos: new QuesosProducidos($datos->quesosProducidos),
                jefaProduccionId: $datos->jefaProduccionId,
                observaciones: $datos->observaciones,
                productoId: $datos->productoId,
            );

            $guardada = $this->producciones->guardar($produccion);

            if ($datos->productoId !== null) {
                $this->registrarEntradaProductoTerminado($guardada, $datos);
            }

            foreach ($datos->insumosUtilizados as $insumo) {
                $this->registrarSalidaInsumo($guardada, $insumo, $datos->jefaProduccionId);
            }

            return ProduccionData::desdeEntidad($guardada);
        });
    }

    private function registrarEntradaProductoTerminado(Produccion $produccion, RegistrarProduccionData $datos): void
    {
        $producto = $this->productos->buscarPorId((int) $datos->productoId);

        if ($producto === null) {
            throw new ProductoNoEncontradoException("No existe un producto con id {$datos->productoId}.");
        }

        $this->movimientos->guardar(MovimientoInventario::produccion(
            productoId: $producto->id,
            fecha: new DateTimeImmutable($datos->fecha),
            cantidad: new CantidadProducto((float) $datos->quesosProducidos),
            litrosProcesados: $datos->litrosProcesados,
            observaciones: "Producción #{$produccion->id}",
            usuarioId: $datos->jefaProduccionId,
            produccionId: $produccion->id,
        ));
    }

    private function registrarSalidaInsumo(Produccion $produccion, InsumoUtilizadoData $insumo, ?int $usuarioId): void
    {
        $producto = $this->productos->buscarPorId($insumo->productoId);

        if ($producto === null) {
            throw new ProductoNoEncontradoException("No existe un insumo con id {$insumo->productoId}.");
        }

        $cantidad = new CantidadProducto($insumo->cantidad);
        $disponible = $this->movimientos->stockDe($insumo->productoId);

        if ($cantidad->valor > $disponible) {
            throw new StockInsuficienteException(sprintf(
                'Solo hay %s %s de %s en existencia.',
                rtrim(rtrim(number_format($disponible, 2, '.', ''), '0'), '.'),
                $producto->unidad,
                $producto->nombre,
            ));
        }

        $this->movimientos->guardar(MovimientoInventario::usoProduccion(
            productoId: $producto->id,
            fecha: $produccion->fecha,
            cantidad: $cantidad,
            observaciones: "Uso en Producción #{$produccion->id}",
            usuarioId: $usuarioId,
            produccionId: $produccion->id,
        ));
    }
}
