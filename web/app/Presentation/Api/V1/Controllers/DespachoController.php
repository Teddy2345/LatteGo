<?php

declare(strict_types=1);

namespace App\Presentation\Api\V1\Controllers;

use App\Application\Produccion\DTOs\RegistrarDespachoData;
use App\Application\Produccion\UseCases\ListarDespachoPorProduccionUseCase;
use App\Application\Produccion\UseCases\ObtenerDespachoUseCase;
use App\Application\Produccion\UseCases\RegistrarDespachoUseCase;
use App\Http\Controllers\Controller;
use App\Infrastructure\Produccion\Models\DespachoModel;
use App\Presentation\Api\V1\Requests\StoreDespachoRequest;
use App\Presentation\Api\V1\Resources\DespachoResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class DespachoController extends Controller
{
    public function index(Request $request, ListarDespachoPorProduccionUseCase $listar): AnonymousResourceCollection
    {
        $this->authorize('viewAny', DespachoModel::class);

        return DespachoResource::collection($listar->ejecutar((int) $request->integer('produccion_id')));
    }

    public function store(StoreDespachoRequest $request, RegistrarDespachoUseCase $registrar): JsonResponse
    {
        $this->authorize('create', DespachoModel::class);

        $despacho = $registrar->ejecutar(new RegistrarDespachoData(
            produccionId: $request->integer('produccion_id'),
            despachadorId: $request->user()?->id,
            quesosRecibidos: $request->integer('quesos_recibidos'),
            quesosDespachados: $request->integer('quesos_despachados'),
            observaciones: $request->input('observaciones'),
        ));

        return (new DespachoResource($despacho))->response()->setStatusCode(201);
    }

    public function show(int $despacho, ObtenerDespachoUseCase $obtener): DespachoResource
    {
        $this->authorize('view', DespachoModel::class);

        return new DespachoResource($obtener->ejecutar($despacho));
    }
}
