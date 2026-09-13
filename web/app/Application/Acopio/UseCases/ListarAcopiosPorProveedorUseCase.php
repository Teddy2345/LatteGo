<?php

declare(strict_types=1);

namespace App\Application\Acopio\UseCases;

use App\Application\Acopio\DTOs\AcopioData;
use App\Domain\Acopio\Entities\Acopio;
use App\Domain\Acopio\Repositories\AcopioRepository;

/**
 * Lista el historial de acopios de un proveedor. Este historial se conserva
 * intacto aunque el proveedor sea reasignado de ruta.
 */
final class ListarAcopiosPorProveedorUseCase
{
    public function __construct(
        private readonly AcopioRepository $acopios,
    ) {
    }

    /**
     * @return AcopioData[]
     */
    public function ejecutar(int $proveedorId): array
    {
        return array_map(
            static fn (Acopio $acopio): AcopioData => AcopioData::desdeEntidad($acopio),
            $this->acopios->listarPorProveedor($proveedorId),
        );
    }
}
