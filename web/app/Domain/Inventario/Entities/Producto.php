<?php

declare(strict_types=1);

namespace App\Domain\Inventario\Entities;

use App\Domain\Inventario\Exceptions\TipoProductoIncorrectoException;
use App\Domain\Inventario\ValueObjects\TipoCliente;
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
        public ?float $precioVecino = null,
        public ?float $precioProveedor = null,
    ) {
    }

    /**
     * El precio que le toca a ese comprador. Un producto sin precio propio
     * para vecinos o proveedores se cobra al publico, que es como quedan
     * los del catalogo mientras la planta no les fije uno aparte.
     */
    public function precioPara(TipoCliente $cliente): float
    {
        $propio = match ($cliente) {
            TipoCliente::Vecino => $this->precioVecino,
            TipoCliente::Proveedor => $this->precioProveedor,
            TipoCliente::Externo => null,
        };

        return $propio !== null && $propio > 0 ? $propio : $this->precioReferencia;
    }

    /**
     * Cada movimiento de almacen es para un tipo de producto: no se vende
     * sal ni se da entrada de queso como si fuera un insumo comprado.
     */
    public function exigirTipo(TipoProducto $tipo, string $operacion): void
    {
        if ($this->tipo === $tipo) {
            return;
        }

        throw new TipoProductoIncorrectoException(sprintf(
            '%s no es %s: no se puede registrar en %s.',
            $this->nombre,
            $tipo === TipoProducto::Insumo ? 'un insumo' : 'un producto terminado',
            $operacion,
        ));
    }

    /** Unidades que se entregan enteras: no existe media pieza ni medio sobre. */
    private const UNIDADES_ENTERAS = ['pieza', 'unidad', 'sobre', 'bolsa', 'paquete', 'caja', 'botella', 'frasco', 'vaso'];

    public function seVendePorUnidadEntera(): bool
    {
        return self::unidadEsEntera($this->unidad);
    }

    /** Acepta la unidad tal como se escribio en el catalogo ("Piezas", "sobre "…). */
    public static function unidadEsEntera(string $unidad): bool
    {
        $normalizada = mb_strtolower(trim($unidad));

        foreach (self::UNIDADES_ENTERAS as $entera) {
            if ($normalizada === $entera || $normalizada === $entera.'s' || $normalizada === $entera.'es') {
                return true;
            }
        }

        return false;
    }
}
