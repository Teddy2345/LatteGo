<?php

declare(strict_types=1);

namespace App\Domain\Shared\Contracts;

/**
 * Puerto para ejecutar un bloque de codigo dentro de una transaccion de
 * base de datos, sin acoplar Domain/Application a Illuminate. Se usa en
 * operaciones criticas que escriben en mas de una tabla y deben quedar
 * todas o ninguna: aprobacion de cambio de zona, produccion + inventario,
 * venta + stock, pagos, anulaciones.
 */
interface TransactionManager
{
    /**
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    public function run(callable $callback): mixed;
}
