<?php

declare(strict_types=1);

namespace App\Domain\Inventario\Entities;

use App\Domain\Inventario\Exceptions\CantidadProductoInvalidaException;
use App\Domain\Inventario\ValueObjects\CantidadProducto;
use App\Domain\Inventario\ValueObjects\TipoMovimiento;
use DateTimeImmutable;

/**
 * Una linea del libro de almacen. Las transformaciones ingresan producto
 * terminado y registran cuanta leche consumieron; las ventas lo retiran y
 * registran a que precio y a quien.
 */
final class MovimientoInventario
{
    private function __construct(
        public readonly ?int $id,
        public readonly int $productoId,
        public readonly TipoMovimiento $tipo,
        public readonly DateTimeImmutable $fecha,
        public readonly CantidadProducto $cantidad,
        public readonly ?float $litrosProcesados,
        public readonly ?float $precioUnitario,
        public readonly ?string $cliente,
        public readonly ?string $observaciones,
        public readonly ?int $usuarioId,
        public readonly ?string $requestId,
        public readonly ?string $lote,
        public readonly ?int $produccionId,
    ) {
    }

    public static function produccion(
        int $productoId,
        DateTimeImmutable $fecha,
        CantidadProducto $cantidad,
        float $litrosProcesados,
        ?string $observaciones,
        ?int $usuarioId,
        ?string $requestId = null,
        ?int $produccionId = null,
    ): self {
        if (! is_finite($litrosProcesados) || $litrosProcesados <= 0) {
            throw new CantidadProductoInvalidaException('Los litros procesados deben ser mayores a cero.');
        }

        return new self(
            id: null,
            productoId: $productoId,
            tipo: TipoMovimiento::Produccion,
            fecha: $fecha,
            cantidad: $cantidad,
            litrosProcesados: round($litrosProcesados, 2),
            precioUnitario: null,
            cliente: null,
            observaciones: $observaciones,
            usuarioId: $usuarioId,
            requestId: $requestId,
            lote: null,
            produccionId: $produccionId,
        );
    }

    public static function venta(
        int $productoId,
        DateTimeImmutable $fecha,
        CantidadProducto $cantidad,
        float $precioUnitario,
        ?string $cliente,
        ?int $usuarioId,
        ?string $requestId = null,
    ): self {
        if (! is_finite($precioUnitario) || $precioUnitario <= 0) {
            throw new CantidadProductoInvalidaException('El precio unitario debe ser mayor a cero.');
        }

        return new self(
            id: null,
            productoId: $productoId,
            tipo: TipoMovimiento::Venta,
            fecha: $fecha,
            cantidad: $cantidad,
            litrosProcesados: null,
            precioUnitario: round($precioUnitario, 2),
            cliente: $cliente,
            observaciones: null,
            usuarioId: $usuarioId,
            requestId: $requestId,
            lote: null,
            produccionId: null,
        );
    }

    public static function compra(
        int $productoId,
        DateTimeImmutable $fecha,
        CantidadProducto $cantidad,
        ?string $lote,
        ?string $observaciones,
        ?int $usuarioId,
        ?string $requestId = null,
        ?float $precioUnitario = null,
    ): self {
        return new self(
            id: null,
            productoId: $productoId,
            tipo: TipoMovimiento::Compra,
            fecha: $fecha,
            cantidad: $cantidad,
            litrosProcesados: null,
            precioUnitario: $precioUnitario === null ? null : round($precioUnitario, 2),
            cliente: null,
            observaciones: $observaciones,
            usuarioId: $usuarioId,
            requestId: $requestId,
            lote: $lote,
            produccionId: null,
        );
    }

    public static function usoProduccion(
        int $productoId,
        DateTimeImmutable $fecha,
        CantidadProducto $cantidad,
        ?string $observaciones,
        ?int $usuarioId,
        ?string $requestId = null,
        ?int $produccionId = null,
    ): self {
        return new self(
            id: null,
            productoId: $productoId,
            tipo: TipoMovimiento::UsoProduccion,
            fecha: $fecha,
            cantidad: $cantidad,
            litrosProcesados: null,
            precioUnitario: null,
            cliente: null,
            observaciones: $observaciones,
            usuarioId: $usuarioId,
            requestId: $requestId,
            lote: null,
            produccionId: $produccionId,
        );
    }

    public static function reconstituir(
        int $id,
        int $productoId,
        TipoMovimiento $tipo,
        DateTimeImmutable $fecha,
        CantidadProducto $cantidad,
        ?float $litrosProcesados,
        ?float $precioUnitario,
        ?string $cliente,
        ?string $observaciones,
        ?int $usuarioId,
        ?string $requestId,
        ?string $lote = null,
        ?int $produccionId = null,
    ): self {
        return new self(
            $id,
            $productoId,
            $tipo,
            $fecha,
            $cantidad,
            $litrosProcesados,
            $precioUnitario,
            $cliente,
            $observaciones,
            $usuarioId,
            $requestId,
            $lote,
            $produccionId,
        );
    }

    /** Importe de la venta; null en las transformaciones. */
    public function total(): ?float
    {
        return $this->precioUnitario === null
            ? null
            : round($this->cantidad->valor * $this->precioUnitario, 2);
    }

    /**
     * Rendimiento de la transformacion: litros de leche por unidad obtenida.
     */
    public function litrosPorUnidad(): ?float
    {
        return $this->litrosProcesados === null
            ? null
            : round($this->litrosProcesados / $this->cantidad->valor, 2);
    }
}
