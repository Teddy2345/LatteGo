<?php

declare(strict_types=1);

use App\Domain\Pagos\Entities\Pago;
use App\Domain\Pagos\ValueObjects\EstadoPago;
use App\Domain\Pagos\ValueObjects\TotalLitros;

function pagoValido(array $overrides = []): Pago
{
    $datos = array_merge([
        'proveedorId' => 1,
        'semanaInicio' => new DateTimeImmutable('2024-01-04'),
        'semanaFin' => new DateTimeImmutable('2024-01-10'),
        'totalLitros' => new TotalLitros(500.0),
        'precioLitro' => 3.5,
    ], $overrides);

    return Pago::crear(...$datos);
}

test('el total a pagar es total_litros por precio_litro', function () {
    $pago = pagoValido(['totalLitros' => new TotalLitros(500.0), 'precioLitro' => 3.5]);

    expect($pago->totalPagar)->toBe(1750.0);
});

test('un pago recien generado queda pendiente', function () {
    expect(pagoValido()->estado)->toBe(EstadoPago::Pendiente);
});

test('marcarComoPagado cambia el estado y fija la fecha', function () {
    $pago = pagoValido();
    $fecha = new DateTimeImmutable('2024-01-12');

    $pagado = $pago->marcarComoPagado($fecha);

    expect($pagado->estado)->toBe(EstadoPago::Pagado)
        ->and($pagado->fechaPago)->toBe($fecha)
        ->and($pagado->totalPagar)->toBe($pago->totalPagar);
});

test('la fecha de pago sugerida es el viernes tras el cierre de semana', function () {
    $pago = pagoValido(['semanaFin' => new DateTimeImmutable('2024-01-10')]); // miercoles

    expect($pago->fechaPagoSugerida()->format('Y-m-d'))->toBe('2024-01-12'); // viernes
});
