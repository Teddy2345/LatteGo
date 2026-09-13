<?php

declare(strict_types=1);

namespace App\Presentation\Api\V1\Controllers\Mobile;

use App\Application\Auditoria\UseCases\ListarActividadRecienteUseCase;
use App\Http\Controllers\Controller;
use App\Presentation\Api\V1\Resources\RegistroActividadResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class AuditoriaController extends Controller
{
    public function __invoke(
        Request $request,
        ListarActividadRecienteUseCase $listar,
    ): AnonymousResourceCollection {
        $this->authorize('auditoria.ver');

        $limite = $request->integer('limite', ListarActividadRecienteUseCase::LIMITE_POR_DEFECTO);

        return RegistroActividadResource::collection($listar->ejecutar($limite));
    }
}
