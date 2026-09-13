<?php

declare(strict_types=1);

namespace App\Presentation\Api\V1\Controllers;

use App\Application\Proveedor\DTOs\ActualizarProveedorData;
use App\Application\Proveedor\DTOs\RegistrarProveedorData;
use App\Application\Proveedor\UseCases\ActualizarProveedorUseCase;
use App\Application\Proveedor\UseCases\CambiarEstadoProveedorUseCase;
use App\Application\Proveedor\UseCases\EliminarProveedorUseCase;
use App\Application\Proveedor\UseCases\ListarProveedoresActivosUseCase;
use App\Application\Proveedor\UseCases\ListarProveedoresUseCase;
use App\Application\Proveedor\UseCases\ObtenerProveedorUseCase;
use App\Application\Proveedor\UseCases\ReasignarRutaProveedorUseCase;
use App\Application\Proveedor\UseCases\RegistrarProveedorUseCase;
use App\Http\Controllers\Controller;
use App\Infrastructure\Proveedor\Models\ProveedorModel;
use App\Presentation\Api\V1\Requests\CambiarEstadoProveedorRequest;
use App\Presentation\Api\V1\Requests\ReasignarRutaProveedorRequest;
use App\Presentation\Api\V1\Requests\StoreProveedorRequest;
use App\Presentation\Api\V1\Requests\UpdateProveedorRequest;
use App\Presentation\Api\V1\Resources\ProveedorResource;
use Illuminate\Http\Request;

final class ProveedorController extends Controller
{
    public function index(Request $request, ListarProveedoresUseCase $listar, ListarProveedoresActivosUseCase $listarActivos): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $this->authorize('viewAny', ProveedorModel::class);

        $proveedores = $request->boolean('solo_activos')
            ? $listarActivos->ejecutar()
            : $listar->ejecutar();

        return ProveedorResource::collection($proveedores);
    }

    public function store(StoreProveedorRequest $request, RegistrarProveedorUseCase $registrar): \Illuminate\Http\JsonResponse
    {
        $this->authorize('create', ProveedorModel::class);

        $proveedor = $registrar->ejecutar(new RegistrarProveedorData(
            nombre: $request->string('nombre')->toString(),
            cedula: $request->string('cedula')->toString(),
            telefono: $request->input('telefono'),
            finca: $request->input('finca'),
            litrosProm: $request->integer('litros_prom'),
            precioLitro: (float) $request->input('precio_litro'),
            rutaId: $request->input('ruta_id'),
        ));

        return (new ProveedorResource($proveedor))->response()->setStatusCode(201);
    }

    public function show(int $proveedor, ObtenerProveedorUseCase $obtener): ProveedorResource
    {
        $this->authorize('view', ProveedorModel::class);

        return new ProveedorResource($obtener->ejecutar($proveedor));
    }

    public function update(UpdateProveedorRequest $request, int $proveedor, ActualizarProveedorUseCase $actualizar): ProveedorResource
    {
        $this->authorize('update', ProveedorModel::class);

        $actualizado = $actualizar->ejecutar(new ActualizarProveedorData(
            id: $proveedor,
            nombre: $request->string('nombre')->toString(),
            telefono: $request->input('telefono'),
            finca: $request->input('finca'),
            litrosProm: $request->integer('litros_prom'),
            precioLitro: (float) $request->input('precio_litro'),
        ));

        return new ProveedorResource($actualizado);
    }

    public function destroy(int $proveedor, EliminarProveedorUseCase $eliminar): \Illuminate\Http\Response
    {
        $this->authorize('delete', ProveedorModel::class);

        $eliminar->ejecutar($proveedor);

        return response()->noContent();
    }

    public function reasignarRuta(ReasignarRutaProveedorRequest $request, int $proveedor, ReasignarRutaProveedorUseCase $reasignar): ProveedorResource
    {
        $this->authorize('update', ProveedorModel::class);

        return new ProveedorResource(
            $reasignar->ejecutar($proveedor, $request->input('ruta_id')),
        );
    }

    public function cambiarEstado(CambiarEstadoProveedorRequest $request, int $proveedor, CambiarEstadoProveedorUseCase $cambiarEstado): ProveedorResource
    {
        $this->authorize('update', ProveedorModel::class);

        return new ProveedorResource(
            $cambiarEstado->ejecutar($proveedor, $request->boolean('activo')),
        );
    }
}
