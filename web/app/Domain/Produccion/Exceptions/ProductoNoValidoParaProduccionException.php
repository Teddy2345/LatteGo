<?php

declare(strict_types=1);

namespace App\Domain\Produccion\Exceptions;

/**
 * La produccion solo da entrada a un producto terminado activo y solo
 * consume insumos: ni se "produce" sal ni se gasta queso como insumo.
 */
final class ProductoNoValidoParaProduccionException extends ProduccionException
{
}
