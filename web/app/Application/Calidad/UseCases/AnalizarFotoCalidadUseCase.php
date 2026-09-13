<?php

declare(strict_types=1);

namespace App\Application\Calidad\UseCases;

use App\Application\Calidad\DTOs\LecturaOcrData;
use App\Domain\Calidad\Contracts\OcrProvider;

/**
 * Punto de entrada para "leer" la foto del equipo. Con NullOcrProvider
 * (proveedor por defecto) siempre vuelve sin valores reconocidos: la
 * pantalla debe pedir carga manual, nunca guardar algo que nadie confirmo.
 */
final class AnalizarFotoCalidadUseCase
{
    public function __construct(
        private readonly OcrProvider $ocr,
    ) {
    }

    public function ejecutar(string $rutaImagen): LecturaOcrData
    {
        return LecturaOcrData::desdeLectura($this->ocr->reconocer($rutaImagen));
    }
}
