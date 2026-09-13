<?php

declare(strict_types=1);

use App\Domain\Acopio\ValueObjects\SemanaPago;

test('un jueves es el inicio de su propia semana', function () {
    $semana = SemanaPago::desde(new DateTimeImmutable('2024-01-04')); // jueves

    expect($semana->inicio->format('Y-m-d'))->toBe('2024-01-04')
        ->and($semana->fin->format('Y-m-d'))->toBe('2024-01-10');
});

test('un miercoles es el fin de la semana que empezo el jueves anterior', function () {
    $semana = SemanaPago::desde(new DateTimeImmutable('2024-01-10')); // miercoles

    expect($semana->inicio->format('Y-m-d'))->toBe('2024-01-04')
        ->and($semana->fin->format('Y-m-d'))->toBe('2024-01-10');
});

test('un dia intermedio cae dentro de la semana jueves-miercoles correcta', function () {
    $semana = SemanaPago::desde(new DateTimeImmutable('2024-01-07')); // domingo

    expect($semana->inicio->format('Y-m-d'))->toBe('2024-01-04')
        ->and($semana->fin->format('Y-m-d'))->toBe('2024-01-10');
});
