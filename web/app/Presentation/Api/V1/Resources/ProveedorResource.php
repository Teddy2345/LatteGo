<?php

declare(strict_types=1);

namespace App\Presentation\Api\V1\Resources;

use App\Application\Proveedor\DTOs\ProveedorData;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ProveedorData
 */
final class ProveedorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'cedula' => $this->cedula,
            'telefono' => $this->telefono,
            'finca' => $this->finca,
            'litros_prom' => $this->litrosProm,
            'precio_litro' => $this->precioLitro,
            'activo' => $this->activo,
            'ruta_id' => $this->rutaId,
        ];
    }
}
