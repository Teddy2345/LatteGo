<?php

declare(strict_types=1);

namespace App\Presentation\Api\V1\Resources;

use App\Application\Produccion\DTOs\DespachoData;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin DespachoData
 */
final class DespachoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'produccion_id' => $this->produccionId,
            'despachador_id' => $this->despachadorId,
            'quesos_recibidos' => $this->quesosRecibidos,
            'quesos_despachados' => $this->quesosDespachados,
            'merma' => $this->merma,
            'observaciones' => $this->observaciones,
        ];
    }
}
