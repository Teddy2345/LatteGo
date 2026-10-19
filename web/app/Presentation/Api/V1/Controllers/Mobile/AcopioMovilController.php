<?php

declare(strict_types=1);

namespace App\Presentation\Api\V1\Controllers\Mobile;

use App\Application\Acopio\DTOs\RegistrarAcopioMovilData;
use App\Application\Acopio\UseCases\ListarAcopiosMovilUseCase;
use App\Application\Acopio\UseCases\ObtenerAcopioMovilUseCase;
use App\Application\Acopio\UseCases\RegistrarAcopioMovilUseCase;
use App\Application\Proveedor\UseCases\ObtenerProveedorUseCase;
use App\Http\Controllers\Controller;
use App\Infrastructure\Acopio\Models\AcopioModel;
use App\Presentation\Api\V1\Requests\StoreAcopioMovilRequest;
use App\Presentation\Api\V1\Resources\AcopioMovilResource;
use App\Presentation\Support\AlcanceAcopios;
use App\Presentation\Support\AlcanceProveedores;
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

        return AcopioMovilResource::collection($listar->ejecutar(AlcanceAcopios::soloDe($request->user())));
    }

    public function show(Request $request, int $acopio, ObtenerAcopioMovilUseCase $obtener): AcopioMovilResource
    {
        $this->authorize('view', AcopioModel::class);

        $datos = $obtener->ejecutar($acopio);
        abort_unless(AlcanceAcopios::puedeVer($request->user(), $datos->acopiadorId), 403, 'Ese acopio lo registró otro acopiador.');

        return new AcopioMovilResource($datos);
    }

    public function store(StoreAcopioMovilRequest $request, RegistrarAcopioMovilUseCase $registrar, ObtenerProveedorUseCase $obtenerProveedor): JsonResponse
    {
        $this->authorize('create', AcopioModel::class);

        $proveedor = $obtenerProveedor->ejecutar($request->integer('proveedor_id'));
        abort_unless(
            AlcanceProveedores::puedeAtender($request->user(), $proveedor->id, $proveedor->rutaId, $request->string('fecha')->toString()),
            403,
            'Ese proveedor no pertenece a tu ruta.',
        );

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
