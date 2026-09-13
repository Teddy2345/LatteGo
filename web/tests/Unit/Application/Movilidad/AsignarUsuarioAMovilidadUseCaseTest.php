<?php

declare(strict_types=1);

use App\Application\Movilidad\UseCases\AsignarUsuarioAMovilidadUseCase;
use App\Domain\Movilidad\Entities\Movilidad;
use App\Domain\Movilidad\Exceptions\MovilidadNoEncontradaException;
use App\Domain\Movilidad\ValueObjects\TipoMovilidad;
use Tests\Support\InMemoryMovilidadRepository;

test('asigna un usuario como acopiador titular de una movilidad', function () {
    $repo = new InMemoryMovilidadRepository();
    $camion = $repo->guardar(Movilidad::crear('Camión 01', TipoMovilidad::Camion, rutaId: null));

    $asignado = (new AsignarUsuarioAMovilidadUseCase($repo))->ejecutar($camion->id, 7);

    expect($asignado->usuarioId)->toBe(7)
        ->and($repo->buscarPorId($camion->id)->usuarioId)->toBe(7);
});

test('reasignar un usuario a otra movilidad lo quita de la anterior', function () {
    $repo = new InMemoryMovilidadRepository();
    $camion1 = $repo->guardar(Movilidad::crear('Camión 01', TipoMovilidad::Camion, rutaId: null));
    $camion2 = $repo->guardar(Movilidad::crear('Camión 02', TipoMovilidad::Camion, rutaId: null));

    $useCase = new AsignarUsuarioAMovilidadUseCase($repo);
    $useCase->ejecutar($camion1->id, 7);
    $useCase->ejecutar($camion2->id, 7);

    expect($repo->buscarPorId($camion1->id)->usuarioId)->toBeNull()
        ->and($repo->buscarPorId($camion2->id)->usuarioId)->toBe(7);
});

test('quitar la asignacion con usuarioId null deja la movilidad sin acopiador titular', function () {
    $repo = new InMemoryMovilidadRepository();
    $camion = $repo->guardar(Movilidad::crear('Camión 01', TipoMovilidad::Camion, rutaId: null));

    $useCase = new AsignarUsuarioAMovilidadUseCase($repo);
    $useCase->ejecutar($camion->id, 7);
    $useCase->ejecutar($camion->id, null);

    expect($repo->buscarPorId($camion->id)->usuarioId)->toBeNull();
});

test('asignar un usuario a una movilidad inexistente lanza una excepcion', function () {
    $repo = new InMemoryMovilidadRepository();

    expect(fn () => (new AsignarUsuarioAMovilidadUseCase($repo))->ejecutar(999, 7))
        ->toThrow(MovilidadNoEncontradaException::class);
});
