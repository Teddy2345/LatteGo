<?php

declare(strict_types=1);

namespace App\Presentation\Api\V1\Resources;

use App\Application\Acopio\DTOs\ConsolidadoDiarioData;
use App\Application\Acopio\DTOs\ConsolidadoZonaData;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ConsolidadoDiarioData
 */
final class ConsolidadoMovilResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'fecha' => $this->fecha,
            'total_litros' => $this->totalLitros,
            'total_registros' => $this->totalRegistros,
            'actualizado_en' => $this->actualizadoEn,
            'zonas' => array_map(
                static fn (ConsolidadoZonaData $zona): array => [
                    'ruta_id' => $zona->rutaId,
                    'zona' => $zona->zona,
                    'litros' => $zona->litros,
                    'registros' => $zona->registros,
                ],
                $this->zonas,
            ),
        ];
    }
}
