<?php

declare(strict_types=1);

namespace App\Infrastructure\Comprobantes\Repositories;

use App\Domain\Comprobantes\Repositories\ComprobanteRepository;
use App\Domain\Comprobantes\ValueObjects\TipoComprobante;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class EloquentComprobanteRepository implements ComprobanteRepository
{
    public function emitir(array $datos, int $usuarioId): array
    {
        $tipo = TipoComprobante::desde($datos['tipo']);

        return DB::transaction(function () use ($datos, $tipo, $usuarioId): array {
            // Un cobro, un documento: si ya se emitio, se devuelve el mismo
            // en lugar de numerar otro. La venta que dejo una entrega cuenta
            // como el mismo cobro que su pedido, o se facturaria dos veces.
            $existente = isset($datos['movimientoInventarioId'])
                ? $this->porVenta((int) $datos['movimientoInventarioId'])
                : $this->porPedido((int) $datos['pedidoId']);

            if ($existente === null && isset($datos['movimientoInventarioId'])) {
                $pedidoDeLaVenta = DB::table('movimientos_inventario')
                    ->where('id', (int) $datos['movimientoInventarioId'])->value('pedido_id');

                if ($pedidoDeLaVenta !== null) {
                    $existente = $this->porPedido((int) $pedidoDeLaVenta);
                }
            }

            if ($existente !== null) {
                return $existente;
            }

            // El correlativo se toma con la serie bloqueada: dos cajas
            // emitiendo a la vez no pueden sacar el mismo numero.
            $ultimo = (int) DB::table('comprobantes')
                ->where('serie', $tipo->serie())
                ->lockForUpdate()
                ->max('numero');

            $id = DB::table('comprobantes')->insertGetId([
                'tipo' => $tipo->value,
                'serie' => $tipo->serie(),
                'numero' => $ultimo + 1,
                'fecha' => $datos['fecha'],
                'cliente_nombre' => $datos['clienteNombre'],
                'cliente_documento' => $datos['clienteDocumento'] ?: null,
                'cliente_direccion' => $datos['clienteDireccion'] ?? null,
                'total' => round((float) $datos['total'], 2),
                'movimiento_inventario_id' => $datos['movimientoInventarioId'] ?? null,
                'pedido_id' => $datos['pedidoId'] ?? null,
                'usuario_id' => $usuarioId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            activity('ventas')->causedBy(User::find($usuarioId))->withProperties([
                'comprobante' => $tipo->serie().'-'.str_pad((string) ($ultimo + 1), 6, '0', STR_PAD_LEFT),
                'total' => round((float) $datos['total'], 2),
            ])->log($tipo->etiqueta().' emitida');

            return $this->buscar($id);
        }, 3);
    }

    public function porVenta(int $movimientoId): ?array
    {
        $fila = DB::table('comprobantes')->where('movimiento_inventario_id', $movimientoId)->first();

        return $fila === null ? null : $this->conNumero((array) $fila);
    }

    public function porPedido(int $pedidoId): ?array
    {
        $fila = DB::table('comprobantes')->where('pedido_id', $pedidoId)->first();

        return $fila === null ? null : $this->conNumero((array) $fila);
    }

    public function deVentas(array $movimientoIds): array
    {
        if ($movimientoIds === []) {
            return [];
        }

        $filas = [];

        foreach (DB::table('comprobantes')->whereIn('movimiento_inventario_id', $movimientoIds)->get() as $fila) {
            $filas[(int) $fila->movimiento_inventario_id] = $this->conNumero((array) $fila);
        }

        // La venta que dejo una entrega lleva el comprobante de su pedido:
        // es el mismo cobro, y sin esto la pantalla ofreceria facturarla otra vez.
        $dePedidos = DB::table('movimientos_inventario')
            ->join('comprobantes', 'comprobantes.pedido_id', '=', 'movimientos_inventario.pedido_id')
            ->whereIn('movimientos_inventario.id', $movimientoIds)
            ->whereNotNull('movimientos_inventario.pedido_id')
            ->select('movimientos_inventario.id as movimiento', 'comprobantes.*')
            ->get();

        foreach ($dePedidos as $fila) {
            $datos = (array) $fila;
            $movimiento = (int) $datos['movimiento'];
            unset($datos['movimiento']);

            $filas[$movimiento] ??= $this->conNumero($datos);
        }

        return $filas;
    }

    public function buscar(int $id): array
    {
        $fila = DB::table('comprobantes')->find($id);

        if (! $fila) {
            throw ValidationException::withMessages(['comprobanteId' => 'Ese comprobante ya no está disponible.']);
        }

        return $this->conNumero((array) $fila);
    }

    public function lineas(int $id): array
    {
        $comprobante = $this->buscar($id);

        if ($comprobante['pedido_id'] !== null) {
            return DB::table('items_pedido')->where('pedido_id', $comprobante['pedido_id'])
                ->get()->map(static fn ($i): array => [
                    'descripcion' => $i->nombre_producto,
                    'unidad' => '',
                    'cantidad' => (float) $i->cantidad,
                    'precioUnitario' => (float) $i->precio_unitario,
                    'importe' => round((float) $i->cantidad * (float) $i->precio_unitario, 2),
                ])->all();
        }

        $venta = DB::table('movimientos_inventario')
            ->join('productos', 'productos.id', '=', 'movimientos_inventario.producto_id')
            ->where('movimientos_inventario.id', $comprobante['movimiento_inventario_id'])
            ->select('movimientos_inventario.*', 'productos.nombre as producto', 'productos.unidad')
            ->first();

        if (! $venta) {
            return [];
        }

        return [[
            'descripcion' => $venta->producto,
            'unidad' => $venta->unidad,
            'cantidad' => (float) $venta->cantidad,
            'precioUnitario' => (float) $venta->precio_unitario,
            'importe' => round((float) $venta->cantidad * (float) $venta->precio_unitario, 2),
        ]];
    }

    /** El numero como se imprime: B001-000123. */
    private function conNumero(array $fila): array
    {
        $fila['numero_completo'] = $fila['serie'].'-'.str_pad((string) $fila['numero'], 6, '0', STR_PAD_LEFT);

        return $fila;
    }
}
