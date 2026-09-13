<?php

declare(strict_types=1);

namespace App\Domain\Produccion\Repositories;

use App\Domain\Produccion\Entities\Produccion;

interface ProduccionRepository
{
    public function guardar(Produccion $produccion): Produccion;

    public function buscarPorId(int $id): ?Produccion;

    /**
     * @return Produccion[]
     */
    public function listarTodos(): array;
}
