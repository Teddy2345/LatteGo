<?php

declare(strict_types=1);

namespace App\Infrastructure\Shared\Services;

use App\Domain\Shared\Contracts\TransactionManager;
use Illuminate\Support\Facades\DB;

final class DatabaseTransactionManager implements TransactionManager
{
    public function run(callable $callback): mixed
    {
        return DB::transaction($callback);
    }
}
