<?php

declare(strict_types=1);

namespace App\Presentation\Api\V1\Controllers;

use App\Application\Calidad\DTOs\RegistrarCalidadData;
use App\Application\Calidad\UseCases\ListarCalidadPorProveedorUseCase;
use App\Application\Calidad\UseCases\ListarCalidadUseCase;
use App\Application\Calidad\UseCases\ObtenerCalidadUseCase;
use App\Application\Calidad\UseCases\RegistrarCalidadUseCase;
use App\Http\Controllers\Controller;
use App\Infrastructure\Calidad\Models\CalidadModel;
use App\Presentation\Api\V1\Requests\StoreCalidadRequest;
use App\Presentation\Api\V1\Resources\CalidadResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class CalidadController extends Controller
{
    public function index(
        Request $request,
        ListarCalidadUseCase $listar,
        ListarCalidadPorProveedorUseCase $listarPorProveedor,
    ): AnonymousResourceCollection {
        $this->authorize('viewAny', CalidadModel::class);

        $analisis = $request->filled('proveedor_id')
            ? $listarPorProveedor->ejecutar((int) $request->integer('proveedor_id'))
            : $listar->ejecutar();

        return CalidadResource::collection($analisis);
    }

    public function store(StoreCalidadRequest $request, RegistrarCalidadUseCase $registrar): \Illuminate\Http\JsonResponse
    {
        $this->authorize('create', CalidadModel::class);

        $calidad = $registrar->ejecutar(new RegistrarCalidadData(
            proveedorId: $request->integer('proveedor_id'),
            acopioId: $request->integer('acopio_id'),
            fecha: $request->string('fecha')->toString(),
            temperatura: (float) $request->input('temperatura'),
            grasa: (float) $request->input('grasa'),
            solidosNoGrasos: (float) $request->input('solidos_no_grasos'),
            densidad: (float) $request->input('densidad'),
            proteina: (float) $request->input('proteina'),
            lactosa: (float) $request->input('lactosa'),
            sales: (float) $request->input('sales'),
            aguaAgregada: (float) $request->input('agua_agregada'),
            ph: (float) $request->input('ph'),
            pruebaAlcoholAceptada: $request->boolean('prueba_alcohol_aceptada'),
            motivoRechazoManual: $request->input('motivo_rechazo_manual'),
        ));

        return (new CalidadResource($calidad))->response()->setStatusCode(201);
    }

    public function show(int $calidad, ObtenerCalidadUseCase $obtener): CalidadResource
    {
        $this->authorize('view', CalidadModel::class);

        return new CalidadResource($obtener->ejecutar($calidad));
    }
}
