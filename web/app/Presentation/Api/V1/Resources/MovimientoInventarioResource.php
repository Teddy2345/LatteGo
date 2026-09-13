<?php

declare(strict_types=1);

namespace App\Presentation\Api\V1\Resources;

use App\Application\Inventario\DTOs\MovimientoInventarioData;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin MovimientoInventarioData
 */
final class MovimientoInventarioResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            // La app usa "nombre" como titulo de la tarjeta del listado.
            'nombre' => $this->producto.' · '.number_format($this->cantidad, 2).' '.$this->unidad,
            'producto_id' => $this->productoId,
            'producto' => $this->producto,
            'unidad' => $this->unidad,
            'tipo' => $this->tipo,
            'fecha' => $this->fecha,
            'cantidad' => $this->cantidad,
            'litros_procesados' => $this->litrosProcesados,
            'litros_por_unidad' => $this->litrosPorUnidad,
            'precio_unitario' => $this->precioUnitario,
            'total' => $this->total,
            'cliente' => $this->cliente,
            'observaciones' => $this->observaciones,
        ];
    }
}
