<?php

declare(strict_types=1);

namespace App\Infrastructure\Inventario\Repositories;

use App\Domain\Inventario\Entities\MovimientoInventario;
use App\Domain\Inventario\Repositories\MovimientoInventarioRepository;
use App\Domain\Inventario\ValueObjects\CantidadProducto;
use App\Domain\Inventario\ValueObjects\TipoMovimiento;
use App\Infrastructure\Inventario\Models\MovimientoInventarioModel;
use DateTimeImmutable;

final class EloquentMovimientoInventarioRepository implements MovimientoInventarioRepository
{
    public function guardar(MovimientoInventario $movimiento): MovimientoInventario
    {
        $modelo = $movimiento->id === null
            ? new MovimientoInventarioModel()
            : MovimientoInventarioModel::query()->findOrFail($movimiento->id);

        $modelo->fill([
            'producto_id' => $movimiento->productoId,
            'produccion_id' => $movimiento->produccionId,
            'tipo' => $movimiento->tipo->value,
            'fecha' => $movimiento->fecha->format('Y-m-d'),
            'cantidad' => $movimiento->cantidad->valor,
            'lote' => $movimiento->lote,
            'litros_procesados' => $movimiento->litrosProcesados,
            'precio_unitario' => $movimiento->precioUnitario,
            'total' => $movimiento->total(),
            'cliente' => $movimiento->cliente,
            'observaciones' => $movimiento->observaciones,
            'usuario_id' => $movimiento->usuarioId,
            'request_id' => $movimiento->requestId,
        ]);

        $modelo->save();

        return $this->aDominio($modelo);
    }

    public function buscarPorRequestId(string $requestId): ?MovimientoInventario
    {
        $modelo = MovimientoInventarioModel::query()->where('request_id', $requestId)->first();

        return $modelo === null ? null : $this->aDominio($modelo);
    }

    public function listarPorTipo(TipoMovimiento $tipo): array
    {
        return MovimientoInventarioModel::query()
            ->where('tipo', $tipo->value)
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->get()
            ->map(fn (MovimientoInventarioModel $modelo) => $this->aDominio($modelo))
            ->all();
    }

    public function stockPorProducto(): array
    {
        return MovimientoInventarioModel::query()
            ->selectRaw($this->expresionSaldo().', producto_id', $this->bindingsSaldo())
            ->groupBy('producto_id')
            ->pluck('saldo', 'producto_id')
            ->map(static fn ($saldo): float => round((float) $saldo, 2))
            ->all();
    }

    public function stockDe(int $productoId): float
    {
        $saldo = MovimientoInventarioModel::query()
            ->where('producto_id', $productoId)
            ->selectRaw($this->expresionSaldo(), $this->bindingsSaldo())
            ->value('saldo');

        return round((float) ($saldo ?? 0), 2);
    }

    public function listarPorProduccion(int $produccionId): array
    {
        return MovimientoInventarioModel::query()
            ->where('produccion_id', $produccionId)
            ->orderBy('id')
            ->get()
            ->map(fn (MovimientoInventarioModel $modelo) => $this->aDominio($modelo))
            ->all();
    }

    public function costoPromedioPonderado(int $productoId): ?float
    {
        $fila = MovimientoInventarioModel::query()
            ->where('producto_id', $productoId)
            ->where('tipo', TipoMovimiento::Compra->value)
            ->whereNotNull('precio_unitario')
            ->selectRaw('SUM(cantidad * precio_unitario) AS costo_total, SUM(cantidad) AS cantidad_total')
            ->first();

        if ($fila === null || (float) $fila->cantidad_total <= 0.0) {
            return null;
        }

        return round((float) $fila->costo_total / (float) $fila->cantidad_total, 2);
    }

    /**
     * Las entradas (produccion, compra) suman; el resto resta. Se arma la
     * lista de tipos "entrada" desde el enum para no repetirla aqui: si se
     * agrega un tipo nuevo con signo +1, esta consulta lo respeta solo.
     */
    private function expresionSaldo(): string
    {
        $entradas = implode(',', array_fill(0, count(TipoMovimiento::entradas()), '?'));

        return "SUM(CASE WHEN tipo IN ({$entradas}) THEN cantidad ELSE -cantidad END) AS saldo";
    }

    private function bindingsSaldo(): array
    {
        return array_map(static fn (TipoMovimiento $t): string => $t->value, TipoMovimiento::entradas());
    }

    private function aDominio(MovimientoInventarioModel $modelo): MovimientoInventario
    {
        return MovimientoInventario::reconstituir(
            id: $modelo->id,
            productoId: $modelo->producto_id,
            tipo: TipoMovimiento::from($modelo->tipo),
            fecha: new DateTimeImmutable($modelo->fecha->format('Y-m-d')),
            cantidad: new CantidadProducto((float) $modelo->cantidad),
            litrosProcesados: $modelo->litros_procesados === null ? null : (float) $modelo->litros_procesados,
            precioUnitario: $modelo->precio_unitario === null ? null : (float) $modelo->precio_unitario,
            cliente: $modelo->cliente,
            observaciones: $modelo->observaciones,
            usuarioId: $modelo->usuario_id,
            requestId: $modelo->request_id,
            lote: $modelo->lote,
            produccionId: $modelo->produccion_id,
        );
    }
}
