<?php

declare(strict_types=1);

namespace App\Presentation\Api\V1\Controllers\Mobile;

use App\Application\Acopio\DTOs\RegistrarAcopioMovilData;
use App\Application\Acopio\UseCases\ListarAcopiosMovilUseCase;
use App\Application\Acopio\UseCases\ObtenerAcopioMovilUseCase;
use App\Application\Acopio\UseCases\RegistrarAcopioMovilUseCase;
use App\Http\Controllers\Controller;
use App\Infrastructure\Acopio\Models\AcopioModel;
use App\Presentation\Api\V1\Requests\StoreAcopioMovilRequest;
use App\Presentation\Api\V1\Resources\AcopioMovilResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class AcopioMovilController extends Controller
{
    /**
     * Quien puede ver reportes recibe la recepcion de toda la planta; el
     * acopiador de campo solo ve lo que el mismo registro.
     */
    public function index(Request $request, ListarAcopiosMovilUseCase $listar): AnonymousResourceCollection
    {
        $this->authorize('viewAny', AcopioModel::class);

        $usuario = $request->user();
        $acopiadorId = $usuario->can('reportes.ver') ? null : $usuario->id;

        return AcopioMovilResource::collection($listar->ejecutar($acopiadorId));
    }

    public function show(int $acopio, ObtenerAcopioMovilUseCase $obtener): AcopioMovilResource
    {
        $this->authorize('view', AcopioModel::class);

        return new AcopioMovilResource($obtener->ejecutar($acopio));
    }

    public function store(StoreAcopioMovilRequest $request, RegistrarAcopioMovilUseCase $registrar): JsonResponse
    {
        $this->authorize('create', AcopioModel::class);

        $acopio = $registrar->ejecutar(new RegistrarAcopioMovilData(
            requestId: $request->string('request_id')->toString(),
            proveedorId: $request->integer('proveedor_id'),
            acopiadorId: $request->user()?->id,
            rutaId: $request->input('ruta_id'),
            fecha: $request->string('fecha')->toString(),
            cantidadLitros: (float) $request->input('cantidad_litros'),
            observaciones: $request->input('observaciones'),
            perdidaLitros: $request->has('perdida_litros') ? (float) $request->input('perdida_litros') : null,
            motivoPerdida: $request->input('motivo_perdida'),
            latitud: (float) $request->input('latitud'),
            longitud: (float) $request->input('longitud'),
            precisionMetros: (float) $request->input('precision_m'),
            capturadoEn: $request->string('capturado_en')->toString(),
        ));

        return response()->json(['data' => ['id' => $acopio->id, 'estado' => $acopio->estado]], 201);
    }
}
