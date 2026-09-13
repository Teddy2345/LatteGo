<?php

declare(strict_types=1);

namespace App\Domain\Calidad\Contracts;

use App\Domain\Calidad\ValueObjects\LecturaOcr;

/**
 * Puerto para reconocer los valores de una fotografia del equipo de
 * analisis (LactoScan u otro). Hoy esta enlazado a NullOcrProvider, que no
 * reconoce nada: deja la arquitectura lista para conectar un motor real
 * (Tesseract local, Google Vision, AWS Textract...) sin tocar Domain,
 * Application ni la pantalla de Calidad, solo el binding en
 * CalidadServiceProvider.
 */
interface OcrProvider
{
    public function reconocer(string $rutaImagen): LecturaOcr;
}
