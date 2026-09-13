<?php

declare(strict_types=1);

namespace App\Domain\Pagos\Entities;

use App\Domain\Pagos\ValueObjects\EstadoPago;
use App\Domain\Pagos\ValueObjects\TotalLitros;
use DateTimeImmutable;

final class Pago
{
    private function __construct(
        public readonly ?int $id,
        public readonly int $proveedorId,
        public readonly DateTimeImmutable $semanaInicio,
        public readonly DateTimeImmutable $semanaFin,
        public readonly TotalLitros $totalLitros,
        public readonly float $precioLitro,
        public readonly float $totalPagar,
        public readonly ?DateTimeImmutable $fechaPago,
        public readonly EstadoPago $estado,
    ) {
    }

    /**
     * El total a pagar es total_litros * precio_litro. La semana de
     * acopio/pago corre de jueves a miercoles; el pago habitualmente se
     * realiza el viernes siguiente (ver fechaPagoSugerida()), pero esta
     * clase no impone esa fecha, solo la sugiere.
     */
    public static function crear(
        int $proveedorId,
        DateTimeImmutable $semanaInicio,
        DateTimeImmutable $semanaFin,
        TotalLitros $totalLitros,
        float $precioLitro,
    ): self {
        return new self(
            id: null,
            proveedorId: $proveedorId,
            semanaInicio: $semanaInicio,
            semanaFin: $semanaFin,
            totalLitros: $totalLitros,
            precioLitro: $precioLitro,
            totalPagar: round($totalLitros->valor * $precioLitro, 2),
            fechaPago: null,
            estado: EstadoPago::Pendiente,
        );
    }

    public static function reconstituir(
        int $id,
        int $proveedorId,
        DateTimeImmutable $semanaInicio,
        DateTimeImmutable $semanaFin,
        TotalLitros $totalLitros,
        float $precioLitro,
        float $totalPagar,
        ?DateTimeImmutable $fechaPago,
        EstadoPago $estado,
    ): self {
        return new self($id, $proveedorId, $semanaInicio, $semanaFin, $totalLitros, $precioLitro, $totalPagar, $fechaPago, $estado);
    }

    public function marcarComoPagado(DateTimeImmutable $fechaPago): self
    {
        return new self(
            id: $this->id,
            proveedorId: $this->proveedorId,
            semanaInicio: $this->semanaInicio,
            semanaFin: $this->semanaFin,
            totalLitros: $this->totalLitros,
            precioLitro: $this->precioLitro,
            totalPagar: $this->totalPagar,
            fechaPago: $fechaPago,
            estado: EstadoPago::Pagado,
        );
    }

    /**
     * El viernes inmediatamente posterior al cierre de la semana
     * (miercoles), dia habitual de pago.
     */
    public function fechaPagoSugerida(): DateTimeImmutable
    {
        return $this->semanaFin->modify('+2 days');
    }
}
