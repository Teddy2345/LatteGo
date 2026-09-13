<?php

declare(strict_types=1);

namespace App\Presentation\Api\V1\Resources;

use App\Application\Pagos\DTOs\PagoData;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin PagoData
 */
final class PagoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'proveedor_id' => $this->proveedorId,
            'semana_inicio' => $this->semanaInicio,
            'semana_fin' => $this->semanaFin,
            'total_litros' => $this->totalLitros,
            'precio_litro' => $this->precioLitro,
            'total_pagar' => $this->totalPagar,
            'fecha_pago' => $this->fechaPago,
            'estado' => $this->estado,
        ];
    }
}
