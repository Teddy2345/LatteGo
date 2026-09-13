<?php

declare(strict_types=1);

namespace App\Presentation\Api\V1\Controllers\Mobile;

use App\Application\Inventario\DTOs\RegistrarProduccionProductoData;
use App\Application\Inventario\DTOs\RegistrarVentaData;
use App\Application\Inventario\UseCases\ListarMovimientosUseCase;
use App\Application\Inventario\UseCases\ListarProductosConStockUseCase;
use App\Application\Inventario\UseCases\RegistrarProduccionProductoUseCase;
use App\Application\Inventario\UseCases\RegistrarVentaUseCase;
use App\Domain\Inventario\ValueObjects\TipoMovimiento;
use App\Http\Controllers\Controller;
use App\Infrastructure\Inventario\Models\MovimientoInventarioModel;
use App\Infrastructure\Inventario\Models\ProductoModel;
use App\Presentation\Api\V1\Requests\StoreProduccionProductoRequest;
use App\Presentation\Api\V1\Requests\StoreVentaRequest;
use App\Presentation\Api\V1\Resources\MovimientoInventarioResource;
use App\Presentation\Api\V1\Resources\ProductoStockResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Almacen de producto terminado visto desde la app: catalogo con stock,
 * transformaciones de leche y ventas.
 */
final class InventarioMovilController extends Controller
{
    public function productos(ListarProductosConStockUseCase $listar): AnonymousResourceCollection
    {
        $this->authorize('viewAny', ProductoModel::class);

        return ProductoStockResource::collection($listar->ejecutar());
    }

    public function producciones(ListarMovimientosUseCase $listar): AnonymousResourceCollection
    {
        $this->authorize('viewAny', MovimientoInventarioModel::class);

        return MovimientoInventarioResource::collection($listar->ejecutar(TipoMovimiento::Produccion));
    }

    public function ventas(ListarMovimientosUseCase $listar): AnonymousResourceCollection
    {
        $this->authorize('verVentas', MovimientoInventarioModel::class);

        return MovimientoInventarioResource::collection($listar->ejecutar(TipoMovimiento::Venta));
    }

    public function registrarProduccion(
        StoreProduccionProductoRequest $request,
        RegistrarProduccionProductoUseCase $registrar,
    ): JsonResponse {
        $this->authorize('transformar', MovimientoInventarioModel::class);

        $movimiento = $registrar->ejecutar(new RegistrarProduccionProductoData(
            productoId: $request->integer('producto_id'),
            fecha: $request->string('fecha')->toString(),
            cantidad: (float) $request->input('cantidad'),
            litrosProcesados: (float) $request->input('litros_procesados'),
            observaciones: $request->input('observaciones'),
            usuarioId: $request->user()?->id,
            requestId: $request->input('request_id'),
        ));

        return (new MovimientoInventarioResource($movimiento))->response()->setStatusCode(201);
    }

    public function registrarVenta(StoreVentaRequest $request, RegistrarVentaUseCase $registrar): JsonResponse
    {
        $this->authorize('vender', MovimientoInventarioModel::class);

        $movimiento = $registrar->ejecutar(new RegistrarVentaData(
            productoId: $request->integer('producto_id'),
            fecha: $request->string('fecha')->toString(),
            cantidad: (float) $request->input('cantidad'),
            precioUnitario: (float) $request->input('precio_unitario'),
            cliente: $request->input('cliente'),
            usuarioId: $request->user()?->id,
            requestId: $request->input('request_id'),
        ));

        return (new MovimientoInventarioResource($movimiento))->response()->setStatusCode(201);
    }
}
