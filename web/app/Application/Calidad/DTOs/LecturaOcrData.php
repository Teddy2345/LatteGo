<?php

declare(strict_types=1);

namespace App\Application\Calidad\DTOs;

use App\Domain\Calidad\ValueObjects\LecturaOcr;

final readonly class LecturaOcrData
{
    /**
     * @param  array<string, float>  $valores
     */
    public function __construct(
        public array $valores,
        public float $confianza,
        public bool $huboReconocimiento,
    ) {
    }

    public static function desdeLectura(LecturaOcr $lectura): self
    {
        return new self(
            valores: $lectura->valores,
            confianza: $lectura->confianza,
            huboReconocimiento: ! $lectura->sinValoresReconocidos(),
        );
    }
}
