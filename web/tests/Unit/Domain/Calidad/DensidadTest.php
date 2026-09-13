<?php

declare(strict_types=1);

use App\Domain\Calidad\ValueObjects\ClasificacionDensidad;
use App\Domain\Calidad\ValueObjects\Densidad;

test('densidad mayor o igual a 30 es buena', function () {
    expect((new Densidad(30.0))->clasificacion())->toBe(ClasificacionDensidad::Buena)
        ->and((new Densidad(32.0))->clasificacion())->toBe(ClasificacionDensidad::Buena);
});

test('densidad entre 28 y 30 es regular', function () {
    expect((new Densidad(28.0))->clasificacion())->toBe(ClasificacionDensidad::Regular)
        ->and((new Densidad(29.5))->clasificacion())->toBe(ClasificacionDensidad::Regular);
});

test('densidad menor a 28 indica agua añadida', function () {
    expect((new Densidad(27.0))->clasificacion())->toBe(ClasificacionDensidad::AguaAnadida)
        ->and((new Densidad(20.0))->clasificacion())->toBe(ClasificacionDensidad::AguaAnadida);
});
