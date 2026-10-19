<?php

declare(strict_types=1);

namespace App\Domain\Inventario\Exceptions;

/**
 * La operacion no corresponde al tipo de producto: las entradas y salidas de
 * almacen son de insumos; la transformacion y la venta, de producto
 * terminado.
 */
final class TipoProductoIncorrectoException extends InventarioException
{
}
