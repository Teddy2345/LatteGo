<?php

declare(strict_types=1);

namespace App\Presentation\Api\V1\Resources;

use App\Application\Acopio\DTOs\AcopioMovilData;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin AcopioMovilData
 */
final class AcopioMovilResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'fecha' => $this->fecha,
            'proveedor' => $this->proveedor,
            'zona' => $this->zona,
            'cantidad_litros' => $this->cantidadLitros,
            'perdida_litros' => $this->perdidaLitros,
            'motivo_perdida' => $this->motivoPerdida,
            'observaciones' => $this->observaciones,
            'estado' => $this->estado,
            'latitud' => $this->latitud,
            'longitud' => $this->longitud,
            'precision_m' => $this->precisionMetros,
            'capturado_en' => $this->capturadoEn,
        ];
    }
}
