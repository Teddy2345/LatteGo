<?php

declare(strict_types=1);

namespace App\Domain\Produccion\Repositories;

use App\Domain\Produccion\Entities\Despacho;

interface DespachoRepository
{
    public function guardar(Despacho $despacho): Despacho;

    public function buscarPorId(int $id): ?Despacho;

    /**
     * @return Despacho[]
     */
    public function listarPorProduccion(int $produccionId): array;

    /**
     * @return Despacho[]
     */
    public function listarTodos(): array;
}
