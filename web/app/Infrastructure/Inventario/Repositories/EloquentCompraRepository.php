<?php

declare(strict_types=1);

namespace App\Infrastructure\Inventario\Repositories;

use App\Domain\Inventario\Repositories\CompraRepository;
use App\Infrastructure\Inventario\Models\MovimientoInventarioModel;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class EloquentCompraRepository implements CompraRepository
{
    public function registrar(array $datos, int $usuarioId): int
    {
        $requestId = 'compra:'.$datos['requestId'];

        return DB::transaction(function () use ($datos, $usuarioId, $requestId): int {
            // Reintentar desde una conexion mala no debe comprar dos veces:
            // si el documento ya entro, se devuelve el mismo.
            $previa = DB::table('compras')->where('request_id', $requestId)->first();

            if ($previa) {
                return (int) $previa->id;
            }

            $lineas = [];
            $total = 0.0;

            foreach ($datos['items'] as $item) {
                $insumo = DB::table('productos')->where('id', (int) $item['productoId'])->lockForUpdate()->first();

                if (! $insumo || ! $insumo->activo || $insumo->tipo !== 'insumo') {
                    throw ValidationException::withMessages([
                        'items' => 'Solo se compran insumos activos del catálogo.',
                    ]);
                }

                $lineas[] = [
                    'producto' => $insumo,
                    'cantidad' => round((float) $item['cantidad'], 2),
                    'costoUnitario' => round((float) $item['costoUnitario'], 2),
                ];

                $total += round((float) $item['cantidad'] * (float) $item['costoUnitario'], 2);
            }

            $compraId = DB::table('compras')->insertGetId([
                'fecha' => $datos['fecha'],
                'proveedor' => $datos['proveedor'],
                'documento' => $datos['documento'] ?: null,
                'total' => round($total, 2),
                'observaciones' => $datos['observaciones'] ?: null,
                'usuario_id' => $usuarioId,
                'request_id' => $requestId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($lineas as $indice => $linea) {
                // El stock sale del libro de almacen, no de esta tabla: por
                // eso cada linea deja su movimiento de compra.
                $movimiento = MovimientoInventarioModel::create([
                    'producto_id' => $linea['producto']->id,
                    'compra_id' => $compraId,
                    'tipo' => 'compra',
                    'fecha' => $datos['fecha'],
                    'cantidad' => $linea['cantidad'],
                    'precio_unitario' => $linea['costoUnitario'],
                    'total' => round($linea['cantidad'] * $linea['costoUnitario'], 2),
                    'usuario_id' => $usuarioId,
                    'request_id' => $requestId.'#'.$indice,
                    'observaciones' => 'Compra #'.$compraId.' a '.$datos['proveedor'],
                ]);

                DB::table('compra_detalles')->insert([
                    'compra_id' => $compraId,
                    'producto_id' => $linea['producto']->id,
                    'cantidad' => $linea['cantidad'],
                    'costo_unitario' => $linea['costoUnitario'],
                    'unidad' => $linea['producto']->unidad,
                    'movimiento_inventario_id' => $movimiento->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            activity('inventario')->causedBy(User::find($usuarioId))->withProperties([
                'compra_id' => $compraId,
                'proveedor' => $datos['proveedor'],
                'total' => round($total, 2),
                'lineas' => count($lineas),
            ])->log('Compra de insumos registrada');

            return (int) $compraId;
        }, 3);
    }

    public function listar(int $limite = 100): array
    {
        $compras = DB::table('compras')
            ->leftJoin('users', 'users.id', '=', 'compras.usuario_id')
            ->select('compras.*', 'users.name as usuario')
            ->orderByDesc('compras.fecha')->orderByDesc('compras.id')
            ->limit($limite)->get();

        if ($compras->isEmpty()) {
            return [];
        }

        $detalles = DB::table('compra_detalles')
            ->join('productos', 'productos.id', '=', 'compra_detalles.producto_id')
            ->whereIn('compra_id', $compras->pluck('id'))
            ->select('compra_detalles.*', 'productos.nombre as insumo')
            ->get()->groupBy('compra_id');

        return $compras->map(function ($compra) use ($detalles): array {
            $fila = (array) $compra;
            $fila['items'] = $detalles->get($compra->id, collect())->map(fn ($d) => (array) $d)->all();

            return $fila;
        })->all();
    }

    public function buscar(int $id): array
    {
        $compra = DB::table('compras')->find($id);

        if (! $compra) {
            throw ValidationException::withMessages(['compraId' => 'Esa compra ya no está disponible.']);
        }

        $datos = (array) $compra;
        $datos['items'] = DB::table('compra_detalles')
            ->join('productos', 'productos.id', '=', 'compra_detalles.producto_id')
            ->where('compra_id', $id)
            ->select('compra_detalles.*', 'productos.nombre as insumo')
            ->get()->map(fn ($d) => (array) $d)->all();

        return $datos;
    }

    public function totalGastado(string $desde, string $hasta): float
    {
        return round((float) DB::table('compras')->whereBetween('fecha', [$desde, $hasta])->sum('total'), 2);
    }
}
