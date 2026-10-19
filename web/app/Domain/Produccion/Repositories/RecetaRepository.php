<?php

declare(strict_types=1);

namespace App\Domain\Produccion\Repositories;

interface RecetaRepository
{
    public function registrarDinamico(array $datos, int $usuarioId): int;
    public function listar(): array;
    public function buscar(int $id): array;
    public function crear(array $datos, int $usuarioId): int;

    /** Lo que hace falta para imprimir la ficha de un lote: productos, recetas y consumos. */
    public function fichaDeLote(int $produccionId): array;
}
