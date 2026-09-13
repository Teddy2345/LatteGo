<?php

declare(strict_types=1);

namespace App\Application\Proveedor\UseCases;

use App\Application\Proveedor\DTOs\RutaData;
use App\Domain\Proveedor\Entities\Ruta;
use App\Domain\Proveedor\Repositories\RutaRepository;

/**
 * Lista las rutas activas disponibles para asignar a un proveedor.
 */
final class ListarRutasDisponiblesUseCase
{
    public function __construct(
        private readonly RutaRepository $rutas,
    ) {
    }

    /**
     * @return RutaData[]
     */
    public function ejecutar(): array
    {
        return array_map(
            static fn (Ruta $ruta): RutaData => new RutaData($ruta->id, $ruta->nombre),
            $this->rutas->listarActivas(),
        );
    }
}
