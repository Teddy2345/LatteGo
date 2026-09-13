<?php

declare(strict_types=1);

namespace App\Presentation\Api\V1\Controllers;

use App\Application\Pagos\UseCases\GenerarPlanillaPagoUseCase;
use App\Application\Pagos\UseCases\ListarPagosPorProveedorUseCase;
use App\Application\Pagos\UseCases\ListarPagosUseCase;
use App\Application\Pagos\UseCases\MarcarPagoComoPagadoUseCase;
use App\Application\Pagos\UseCases\ObtenerPagoUseCase;
use App\Http\Controllers\Controller;
use App\Infrastructure\Pagos\Models\PagoModel;
use App\Presentation\Api\V1\Requests\GenerarPlanillaPagoRequest;
use App\Presentation\Api\V1\Resources\PagoResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class PagoController extends Controller
{
    public function index(
        Request $request,
        ListarPagosUseCase $listar,
        ListarPagosPorProveedorUseCase $listarPorProveedor,
    ): AnonymousResourceCollection {
        $this->authorize('viewAny', PagoModel::class);

        $pagos = $request->filled('proveedor_id')
            ? $listarPorProveedor->ejecutar((int) $request->integer('proveedor_id'))
            : $listar->ejecutar();

        return PagoResource::collection($pagos);
    }

    public function generarPlanilla(GenerarPlanillaPagoRequest $request, GenerarPlanillaPagoUseCase $generar): AnonymousResourceCollection
    {
        $this->authorize('generarPlanilla', PagoModel::class);

        return PagoResource::collection($generar->ejecutar($request->string('fecha_referencia')->toString()));
    }

    public function show(int $pago, ObtenerPagoUseCase $obtener): PagoResource
    {
        $this->authorize('view', PagoModel::class);

        return new PagoResource($obtener->ejecutar($pago));
    }

    public function marcarPagado(int $pago, MarcarPagoComoPagadoUseCase $marcar): PagoResource
    {
        $this->authorize('generarPlanilla', PagoModel::class);

        return new PagoResource($marcar->ejecutar($pago));
    }
}
