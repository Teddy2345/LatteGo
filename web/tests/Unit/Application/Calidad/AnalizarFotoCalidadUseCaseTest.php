<?php

declare(strict_types=1);

use App\Application\Calidad\UseCases\AnalizarFotoCalidadUseCase;
use App\Infrastructure\Calidad\Services\NullOcrProvider;

test('sin un proveedor de OCR real conectado, no se reconoce ningun valor', function () {
    $resultado = (new AnalizarFotoCalidadUseCase(new NullOcrProvider()))->ejecutar('/ruta/falsa.jpg');

    expect($resultado->huboReconocimiento)->toBeFalse()
        ->and($resultado->valores)->toBe([])
        ->and($resultado->confianza)->toBe(0.0);
});
