<?php

declare(strict_types=1);

namespace App\Presentation\Api\V1\Controllers;

use App\Application\Produccion\DTOs\RegistrarProduccionData;
use App\Application\Produccion\UseCases\ClasificarProveedorAptoPasteurizadoUseCase;
use App\Application\Produccion\UseCases\ListarProduccionUseCase;
use App\Application\Produccion\UseCases\ObtenerProduccionUseCase;
use App\Application\Produccion\UseCases\RegistrarProduccionUseCase;
use App\Http\Controllers\Controller;
use App\Infrastructure\Produccion\Models\ProduccionModel;
use App\Presentation\Api\V1\Requests\StoreProduccionRequest;
use App\Presentation\Api\V1\Resources\ProduccionResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class ProduccionController extends Controller
{
    public function index(ListarProduccionUseCase $listar): AnonymousResourceCollection
    {
        $this->authorize('viewAny', ProduccionModel::class);

        return ProduccionResource::collection($listar->ejecutar());
    }

    public function store(StoreProduccionRequest $request, RegistrarProduccionUseCase $registrar): JsonResponse
    {
        $this->authorize('create', ProduccionModel::class);

        $produccion = $registrar->ejecutar(new RegistrarProduccionData(
            fecha: $request->string('fecha')->toString(),
            litrosProcesados: (float) $request->input('litros_procesados'),
            quesosProducidos: $request->integer('quesos_producidos'),
            jefaProduccionId: $request->user()?->id,
            observaciones: $request->input('observaciones'),
        ));

        return (new ProduccionResource($produccion))->response()->setStatusCode(201);
    }

    public function show(int $produccion, ObtenerProduccionUseCase $obtener): ProduccionResource
    {
        $this->authorize('view', ProduccionModel::class);

        return new ProduccionResource($obtener->ejecutar($produccion));
    }

    public function aptitudPasteurizada(int $proveedor, ClasificarProveedorAptoPasteurizadoUseCase $clasificar): JsonResponse
    {
        $this->authorize('viewAny', ProduccionModel::class);

        $resultado = $clasificar->ejecutar($proveedor);

        return response()->json([
            'data' => [
                'proveedor_id' => $resultado->proveedorId,
                'apto' => $resultado->apto,
                'motivo' => $resultado->motivo,
            ],
        ]);
    }
}
