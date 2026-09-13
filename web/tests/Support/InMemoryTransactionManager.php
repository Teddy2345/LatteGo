<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Domain\Shared\Contracts\TransactionManager;

/**
 * Ejecuta el callback directamente, sin una base de datos real detras:
 * para pruebas de Application/Domain que usan repositorios en memoria.
 */
final class InMemoryTransactionManager implements TransactionManager
{
    public function run(callable $callback): mixed
    {
        return $callback();
    }
}
