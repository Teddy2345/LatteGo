<?php

declare(strict_types=1);

namespace App\Application\Produccion\UseCases;

use App\Application\Produccion\DTOs\DespachoData;
use App\Domain\Produccion\Exceptions\DespachoNoEncontradoException;
use App\Domain\Produccion\Repositories\DespachoRepository;

final class ObtenerDespachoUseCase
{
    public function __construct(
        private readonly DespachoRepository $despachos,
    ) {
    }

    public function ejecutar(int $id): DespachoData
    {
        $despacho = $this->despachos->buscarPorId($id);

        if ($despacho === null) {
            throw new DespachoNoEncontradoException("No existe un despacho con id {$id}.");
        }

        return DespachoData::desdeEntidad($despacho);
    }
}
