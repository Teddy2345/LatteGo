<?php

declare(strict_types=1);

namespace App\Application\Compras\UseCases;

use App\Domain\Inventario\Repositories\CompraRepository;

final class ListarComprasUseCase
{
    public function __construct(private readonly CompraRepository $compras)
    {
    }

    /** @return array<int, array<string, mixed>> */
    public function ejecutar(int $limite = 100): array
    {
        return $this->compras->listar($limite);
    }
}
