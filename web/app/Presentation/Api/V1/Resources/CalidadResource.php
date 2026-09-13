<?php

declare(strict_types=1);

namespace App\Presentation\Api\V1\Resources;

use App\Application\Calidad\DTOs\CalidadData;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin CalidadData
 */
final class CalidadResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'proveedor_id' => $this->proveedorId,
            'acopio_id' => $this->acopioId,
            'fecha' => $this->fecha,
            'temperatura' => $this->temperatura,
            'grasa' => $this->grasa,
            'solidos_no_grasos' => $this->solidosNoGrasos,
            'densidad' => $this->densidad,
            'densidad_clasificacion' => $this->densidadClasificacion,
            'proteina' => $this->proteina,
            'lactosa' => $this->lactosa,
            'sales' => $this->sales,
            'agua_agregada' => $this->aguaAgregada,
            'ph' => $this->ph,
            'prueba_alcohol' => $this->pruebaAlcohol,
            'resultado' => $this->resultado,
            'motivo_rechazo' => $this->motivoRechazo,
            'sancion_aplicada' => $this->sancionAplicada,
        ];
    }
}
