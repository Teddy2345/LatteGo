<?php

declare(strict_types=1);

namespace App\Infrastructure\Calidad\Services;

use App\Domain\Calidad\Contracts\OcrProvider;
use App\Domain\Calidad\ValueObjects\LecturaOcr;

/**
 * Implementacion de referencia mientras no haya un motor de OCR real
 * conectado: no reconoce nada, siempre pide carga manual. Cuando se
 * decida un proveedor (Tesseract, Google Vision, AWS Textract...), se
 * agrega un XxxOcrProvider y se cambia el binding en
 * CalidadServiceProvider; nada mas del sistema necesita cambiar.
 */
final class NullOcrProvider implements OcrProvider
{
    public function reconocer(string $rutaImagen): LecturaOcr
    {
        return new LecturaOcr(valores: [], confianza: 0.0);
    }
}
