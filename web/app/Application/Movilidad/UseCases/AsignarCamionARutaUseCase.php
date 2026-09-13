<?php

declare(strict_types=1);

namespace App\Application\Movilidad\UseCases;

use App\Application\Movilidad\DTOs\MovilidadData;
use App\Domain\Movilidad\Entities\Movilidad;
use App\Domain\Movilidad\Repositories\MovilidadRepository;
use App\Domain\Movilidad\ValueObjects\TipoMovilidad;

/**
 * Crea un camion nuevo para una ruta ("+ Agregar camion" en el modal de
 * Rutas y Camiones). El nombre se numera automaticamente entre los camiones
 * ya asignados a esa ruta, y el nuevo camion queda activo.
 */
final class AsignarCamionARutaUseCase
{
    public function __construct(
        private readonly MovilidadRepository $movilidades,
    ) {
    }

    public function ejecutar(int $rutaId): MovilidadData
    {
        $numero = count($this->movilidades->listarPorRuta($rutaId)) + 1;

        $movilidad = $this->movilidades->guardar(
            Movilidad::crear(nombre: "Camión {$numero}", tipo: TipoMovilidad::Camion, rutaId: $rutaId),
        );

        $this->movilidades->desactivarHermanas($rutaId, (int) $movilidad->id);

        return MovilidadData::desdeEntidad($movilidad);
    }
}
