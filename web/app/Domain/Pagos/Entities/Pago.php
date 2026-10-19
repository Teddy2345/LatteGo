<?php

declare(strict_types=1);

namespace App\Domain\Pagos\Entities;

use App\Domain\Pagos\ValueObjects\EstadoPago;
use App\Domain\Pagos\ValueObjects\TotalLitros;
use DateTimeImmutable;

final class Pago
{
    /** La planilla de la semana; los complementarios van de 1 en adelante. */
    public const SECUENCIA_PLANILLA = 0;

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
        public readonly int $secuencia = self::SECUENCIA_PLANILLA,
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

    /**
     * Pago por los litros de una semana ya pagada que llegaron despues (un
     * acopio que el telefono sincronizo tarde). Usa el precio de la planilla,
     * que es el que regia esa semana, y la siguiente secuencia libre.
     */
    public static function complementario(self $planilla, TotalLitros $litrosFaltantes, int $secuencia): self
    {
        return new self(
            id: null,
            proveedorId: $planilla->proveedorId,
            semanaInicio: $planilla->semanaInicio,
            semanaFin: $planilla->semanaFin,
            totalLitros: $litrosFaltantes,
            precioLitro: $planilla->precioLitro,
            totalPagar: round($litrosFaltantes->valor * $planilla->precioLitro, 2),
            fechaPago: null,
            estado: EstadoPago::Pendiente,
            secuencia: $secuencia,
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
        int $secuencia = self::SECUENCIA_PLANILLA,
    ): self {
        return new self($id, $proveedorId, $semanaInicio, $semanaFin, $totalLitros, $precioLitro, $totalPagar, $fechaPago, $estado, $secuencia);
    }

    public function esComplementario(): bool
    {
        return $this->secuencia > self::SECUENCIA_PLANILLA;
    }

    /**
     * Rehace los litros de un pago que todavia no se hizo: la app movil
     * sincroniza acopios hasta dias despues, y una sancion de calidad puede
     * registrarse tarde. Conserva el precio con el que se genero, porque es
     * el que regia esa semana. Un pago ya hecho no se toca.
     */
    public function recalcularLitros(TotalLitros $totalLitros): self
    {
        if ($this->estado !== EstadoPago::Pendiente) {
            return $this;
        }

        return new self(
            id: $this->id,
            proveedorId: $this->proveedorId,
            semanaInicio: $this->semanaInicio,
            semanaFin: $this->semanaFin,
            totalLitros: $totalLitros,
            precioLitro: $this->precioLitro,
            totalPagar: round($totalLitros->valor * $this->precioLitro, 2),
            fechaPago: null,
            estado: EstadoPago::Pendiente,
            secuencia: $this->secuencia,
        );
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
            secuencia: $this->secuencia,
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
