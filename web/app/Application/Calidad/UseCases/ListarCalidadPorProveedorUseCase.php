<?php

declare(strict_types=1);

namespace App\Application\Calidad\UseCases;

use App\Application\Calidad\DTOs\CalidadData;
use App\Domain\Calidad\Entities\Calidad;
use App\Domain\Calidad\Repositories\CalidadRepository;

/**
 * Historial de calidad de un proveedor, para clasificarlo como apto o no
 * para leche pasteurizada (usado luego por el modulo de Produccion).
 */
final class ListarCalidadPorProveedorUseCase
{
    public function __construct(
        private readonly CalidadRepository $analisis,
    ) {
    }

    /**
     * @return CalidadData[]
     */
    public function ejecutar(int $proveedorId): array
    {
        return array_map(
            static fn (Calidad $calidad): CalidadData => CalidadData::desdeEntidad($calidad),
            $this->analisis->listarPorProveedor($proveedorId),
        );
    }
}
