<?php

declare(strict_types=1);

namespace App\Domain\Proveedor\Repositories;

use App\Domain\Proveedor\Entities\Ruta;

interface RutaRepository
{
    /**
     * @return Ruta[]
     */
    public function listarActivas(): array;

    /**
     * Incluye las rutas dadas de baja: los reportes historicos de acopio
     * deben seguir mostrando el nombre de la zona donde se recibio la leche.
     *
     * @return Ruta[]
     */
    public function listarTodas(): array;
}
