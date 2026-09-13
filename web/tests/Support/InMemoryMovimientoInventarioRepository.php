<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Domain\Inventario\Entities\MovimientoInventario;
use App\Domain\Inventario\ValueObjects\TipoMovimiento;

final class InMemoryMovimientoInventarioRepository implements \App\Domain\Inventario\Repositories\MovimientoInventarioRepository
{
    /** @var array<int, MovimientoInventario> */
    private array $movimientos = [];

    private int $siguienteId = 1;

    public function guardar(MovimientoInventario $movimiento): MovimientoInventario
    {
        $guardado = $movimiento->id === null
            ? MovimientoInventario::reconstituir(
                id: $this->siguienteId++,
                productoId: $movimiento->productoId,
                tipo: $movimiento->tipo,
                fecha: $movimiento->fecha,
                cantidad: $movimiento->cantidad,
                litrosProcesados: $movimiento->litrosProcesados,
                precioUnitario: $movimiento->precioUnitario,
                cliente: $movimiento->cliente,
                observaciones: $movimiento->observaciones,
                usuarioId: $movimiento->usuarioId,
                requestId: $movimiento->requestId,
                lote: $movimiento->lote,
                produccionId: $movimiento->produccionId,
            )
            : $movimiento;

        $this->movimientos[$guardado->id] = $guardado;

        return $guardado;
    }

    public function buscarPorRequestId(string $requestId): ?MovimientoInventario
    {
        foreach ($this->movimientos as $movimiento) {
            if ($movimiento->requestId === $requestId) {
                return $movimiento;
            }
        }

        return null;
    }

    public function listarPorTipo(TipoMovimiento $tipo): array
    {
        return array_values(array_filter($this->movimientos, static fn (MovimientoInventario $m): bool => $m->tipo === $tipo));
    }

    public function stockPorProducto(): array
    {
        $stock = [];

        foreach ($this->movimientos as $movimiento) {
            $stock[$movimiento->productoId] = ($stock[$movimiento->productoId] ?? 0.0)
                + $movimiento->cantidad->valor * $movimiento->tipo->signo();
        }

        return array_map(static fn (float $s): float => round($s, 2), $stock);
    }

    public function stockDe(int $productoId): float
    {
        return $this->stockPorProducto()[$productoId] ?? 0.0;
    }

    public function listarPorProduccion(int $produccionId): array
    {
        return array_values(array_filter(
            $this->movimientos,
            static fn (MovimientoInventario $m): bool => $m->produccionId === $produccionId,
        ));
    }

    public function costoPromedioPonderado(int $productoId): ?float
    {
        $costoTotal = 0.0;
        $cantidadTotal = 0.0;

        foreach ($this->movimientos as $movimiento) {
            if ($movimiento->productoId !== $productoId || $movimiento->tipo !== TipoMovimiento::Compra || $movimiento->precioUnitario === null) {
                continue;
            }

            $costoTotal += $movimiento->cantidad->valor * $movimiento->precioUnitario;
            $cantidadTotal += $movimiento->cantidad->valor;
        }

        return $cantidadTotal > 0.0 ? round($costoTotal / $cantidadTotal, 2) : null;
    }
}
