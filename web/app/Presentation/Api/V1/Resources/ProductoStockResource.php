<?php

declare(strict_types=1);

namespace App\Presentation\Api\V1\Resources;

use App\Application\Inventario\DTOs\ProductoStockData;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ProductoStockData
 */
final class ProductoStockResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'tipo' => $this->tipo,
            'categoria' => $this->categoria,
            'unidad' => $this->unidad,
            'precio_referencia' => $this->precioReferencia,
            'stock' => $this->stock,
            'stock_minimo' => $this->stockMinimo,
            'stock_bajo' => $this->stockBajo,
            'activo' => $this->activo,
        ];
    }
}
