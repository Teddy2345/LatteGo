<?php

declare(strict_types=1);

namespace App\Presentation\Api\V1\Controllers\Mobile;

use App\Application\Acopio\UseCases\ConsolidarAcopiosPorZonaUseCase;
use App\Http\Controllers\Controller;
use App\Infrastructure\Acopio\Models\AcopioModel;
use App\Presentation\Api\V1\Requests\ConsolidadoMovilRequest;
use App\Presentation\Api\V1\Resources\ConsolidadoMovilResource;
use DateTimeImmutable;

/**
 * Litraje recibido en el dia, agrupado por zona de acopio. La app lo
 * refresca cada 20 segundos durante la jornada de recepcion.
 */
final class ConsolidadoController extends Controller
{
    public function __invoke(
        ConsolidadoMovilRequest $request,
        ConsolidarAcopiosPorZonaUseCase $consolidar,
    ): ConsolidadoMovilResource {
        $this->authorize('consolidar', AcopioModel::class);

        $fecha = $request->filled('fecha')
            ? new DateTimeImmutable($request->string('fecha')->toString())
            : new DateTimeImmutable();

        return new ConsolidadoMovilResource($consolidar->ejecutar($fecha));
    }
}
