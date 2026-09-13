<?php

declare(strict_types=1);

namespace App\Presentation\Api\V1\Resources;

use App\Application\Produccion\DTOs\ProduccionData;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ProduccionData
 */
final class ProduccionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'fecha' => $this->fecha,
            'litros_procesados' => $this->litrosProcesados,
            'quesos_producidos' => $this->quesosProducidos,
            'rendimiento_porcentaje' => $this->rendimientoPorcentaje,
            'rendimiento_en_rango_esperado' => $this->rendimientoEnRangoEsperado,
            'jefa_produccion_id' => $this->jefaProduccionId,
            'observaciones' => $this->observaciones,
        ];
    }
}
