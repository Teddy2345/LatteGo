<?php

declare(strict_types=1);

namespace App\Presentation\Api\V1\Controllers;

use App\Application\Acopio\DTOs\AcopioData;
use App\Application\Acopio\DTOs\RegistrarAcopioData;
use App\Application\Acopio\UseCases\ListarAcopiosPendientesUseCase;
use App\Application\Acopio\UseCases\ListarAcopiosPorProveedorUseCase;
use App\Application\Acopio\UseCases\ListarAcopiosUseCase;
use App\Application\Acopio\UseCases\ObtenerAcopioUseCase;
use App\Application\Acopio\UseCases\RegistrarAcopioUseCase;
use App\Application\Acopio\UseCases\SincronizarAcopioUseCase;
use App\Application\Proveedor\UseCases\ObtenerProveedorUseCase;
use App\Http\Controllers\Controller;
use App\Infrastructure\Acopio\Models\AcopioModel;
use App\Presentation\Api\V1\Requests\StoreAcopioRequest;
use App\Presentation\Api\V1\Resources\AcopioResource;
use App\Presentation\Support\AlcanceAcopios;
use App\Presentation\Support\AlcanceProveedores;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class AcopioController extends Controller
{
    public function index(
        Request $request,
        ListarAcopiosUseCase $listar,
        ListarAcopiosPorProveedorUseCase $listarPorProveedor,
        ListarAcopiosPendientesUseCase $listarPendientes,
    ): AnonymousResourceCollection {
        $this->authorize('viewAny', AcopioModel::class);

        $acopios = match (true) {
            $request->filled('proveedor_id') => $listarPorProveedor->ejecutar((int) $request->integer('proveedor_id')),
            $request->boolean('pendientes') => $listarPendientes->ejecutar(),
            default => $listar->ejecutar(),
        };

        $usuario = $request->user();
        $acopios = array_values(array_filter(
            $acopios,
            static fn (AcopioData $acopio): bool => AlcanceAcopios::puedeVer($usuario, $acopio->acopiadorId),
        ));

        return AcopioResource::collection($acopios);
    }

    public function store(StoreAcopioRequest $request, RegistrarAcopioUseCase $registrar, ObtenerProveedorUseCase $obtenerProveedor): \Illuminate\Http\JsonResponse
    {
        $this->authorize('create', AcopioModel::class);

        $proveedor = $obtenerProveedor->ejecutar($request->integer('proveedor_id'));
        abort_unless(AlcanceProveedores::puedeAtender($request->user(), $proveedor->id, $proveedor->rutaId), 403, 'Ese proveedor no pertenece a tu ruta.');

        $acopio = $registrar->ejecutar(new RegistrarAcopioData(
            proveedorId: $request->integer('proveedor_id'),
            acopiadorId: $request->user()?->id,
            rutaId: $request->input('ruta_id'),
            fecha: $request->string('fecha')->toString(),
            cantidadLitros: (float) $request->input('cantidad_litros'),
            observaciones: $request->input('observaciones'),
            perdidaLitros: $request->has('perdida_litros') ? (float) $request->input('perdida_litros') : null,
            motivoPerdida: $request->input('motivo_perdida'),
        ));

        return (new AcopioResource($acopio))->response()->setStatusCode(201);
    }

    public function show(Request $request, int $acopio, ObtenerAcopioUseCase $obtener): AcopioResource
    {
        $this->authorize('view', AcopioModel::class);

        $datos = $obtener->ejecutar($acopio);
        abort_unless(AlcanceAcopios::puedeVer($request->user(), $datos->acopiadorId), 403, 'Ese acopio lo registró otro acopiador.');

        return new AcopioResource($datos);
    }

    public function sincronizar(Request $request, int $acopio, ObtenerAcopioUseCase $obtener, SincronizarAcopioUseCase $sincronizar): AcopioResource
    {
        $this->authorize('sincronizar', AcopioModel::class);

        abort_unless(AlcanceAcopios::puedeVer($request->user(), $obtener->ejecutar($acopio)->acopiadorId), 403, 'Ese acopio lo registró otro acopiador.');

        return new AcopioResource($sincronizar->ejecutar($acopio));
    }
}
