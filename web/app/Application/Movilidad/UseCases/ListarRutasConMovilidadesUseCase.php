<?php

declare(strict_types=1);

namespace App\Application\Movilidad\UseCases;

use App\Application\Movilidad\DTOs\MovilidadData;
use App\Domain\Movilidad\Repositories\MovilidadRepository;
use App\Domain\Proveedor\Entities\Ruta;
use App\Domain\Proveedor\Repositories\RutaRepository;

/**
 * Rutas activas con sus camiones ya asignados, para el modal "Rutas y
 * Camiones" del panel de Acopios.
 */
final class ListarRutasConMovilidadesUseCase
{
    public function __construct(
        private readonly RutaRepository $rutas,
        private readonly MovilidadRepository $movilidades,
    ) {
    }

    /**
     * @return array<int, array{id: int, nombre: string, camiones: MovilidadData[]}>
     */
    public function ejecutar(): array
    {
        return array_map(
            fn (Ruta $ruta): array => [
                'id' => $ruta->id,
                'nombre' => $ruta->nombre,
                'camiones' => array_map(
                    static fn ($movilidad): MovilidadData => MovilidadData::desdeEntidad($movilidad),
                    $this->movilidades->listarPorRuta($ruta->id),
                ),
            ],
            $this->rutas->listarActivas(),
        );
    }
}
