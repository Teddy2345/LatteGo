<?php

declare(strict_types=1);

namespace App\Domain\Proveedor\Entities;

final readonly class Ruta
{
    /**
     * @param  string[]  $paradasReferencia  Lugares por los que pasa la ruta, en orden, saliendo
     *                                        de la planta. Referenciales hasta tener el trazado oficial.
     */
    public function __construct(
        public int $id,
        public string $nombre,
        public array $paradasReferencia = [],
    ) {
    }
}
