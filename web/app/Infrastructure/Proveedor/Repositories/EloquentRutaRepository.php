<?php

declare(strict_types=1);

namespace App\Infrastructure\Proveedor\Repositories;

use App\Domain\Proveedor\Entities\Ruta;
use App\Domain\Proveedor\Repositories\RutaRepository;
use App\Infrastructure\Proveedor\Models\RutaModel;

final class EloquentRutaRepository implements RutaRepository
{
    public function listarActivas(): array
    {
        return RutaModel::query()
            ->where('activa', true)
            ->orderBy('nombre')
            ->get()
            ->map(fn (RutaModel $modelo) => new Ruta(id: $modelo->id, nombre: $modelo->nombre))
            ->all();
    }

    public function listarTodas(): array
    {
        return RutaModel::query()
            ->orderBy('nombre')
            ->get()
            ->map(fn (RutaModel $modelo) => new Ruta(id: $modelo->id, nombre: $modelo->nombre))
            ->all();
    }
}
