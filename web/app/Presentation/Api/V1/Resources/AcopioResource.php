<?php

declare(strict_types=1);

namespace App\Presentation\Api\V1\Resources;

use App\Application\Acopio\DTOs\AcopioData;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin AcopioData
 */
final class AcopioResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'proveedor_id' => $this->proveedorId,
            'acopiador_id' => $this->acopiadorId,
            'ruta_id' => $this->rutaId,
            'fecha' => $this->fecha,
            'cantidad_litros' => $this->cantidadLitros,
            'estado' => $this->estado,
            'observaciones' => $this->observaciones,
            'perdida_litros' => $this->perdidaLitros,
            'motivo_perdida' => $this->motivoPerdida,
            'semana_pago' => [
                'inicio' => $this->semanaPagoInicio,
                'fin' => $this->semanaPagoFin,
            ],
        ];
    }
}
