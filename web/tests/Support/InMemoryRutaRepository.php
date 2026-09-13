<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Domain\Proveedor\Entities\Ruta;
use App\Domain\Proveedor\Repositories\RutaRepository;

final class InMemoryRutaRepository implements RutaRepository
{
    /** @var Ruta[] */
    private array $rutas = [];

    public function agregar(Ruta $ruta): void
    {
        $this->rutas[] = $ruta;
    }

    public function listarActivas(): array
    {
        return $this->rutas;
    }

    public function listarTodas(): array
    {
        return $this->rutas;
    }
}
