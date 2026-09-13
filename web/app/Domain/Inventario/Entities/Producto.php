<?php

declare(strict_types=1);

namespace App\Domain\Inventario\Entities;

use App\Domain\Inventario\ValueObjects\TipoProducto;

/**
 * Un item de almacen: producto terminado (queso, yogurt) o insumo (sal,
 * cuajo, envases). La unidad describe en que se mide; el precio de
 * referencia solo aplica a producto terminado (precio sugerido de venta,
 * que cada venta puede ajustar). stockMinimo habilita la alerta de "por
 * agotarse"; null significa que no se vigila.
 */
final readonly class Producto
{
    public function __construct(
        public ?int $id,
        public string $nombre,
        public TipoProducto $tipo,
        public ?string $categoria,
        public string $unidad,
        public float $precioReferencia,
        public ?float $stockMinimo,
        public bool $activo,
        public ?string $descripcion = null,
        public ?string $fotoPath = null,
    ) {
    }
}
