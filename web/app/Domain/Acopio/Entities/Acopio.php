<?php

declare(strict_types=1);

namespace App\Domain\Acopio\Entities;

use App\Domain\Acopio\Exceptions\PerdidaInvalidaException;
use App\Domain\Acopio\ValueObjects\CantidadLitros;
use App\Domain\Acopio\ValueObjects\EstadoAcopio;
use App\Domain\Acopio\ValueObjects\SemanaPago;
use App\Domain\Acopio\ValueObjects\UbicacionCaptura;
use DateTimeImmutable;

final class Acopio
{
    private function __construct(
        public readonly ?int $id,
        public readonly int $proveedorId,
        public readonly ?int $acopiadorId,
        public readonly ?int $rutaId,
        public readonly DateTimeImmutable $fecha,
        public readonly CantidadLitros $cantidadLitros,
        public readonly EstadoAcopio $estado,
        public readonly ?string $observaciones,
        public readonly ?float $perdidaLitros,
        public readonly ?string $motivoPerdida,
        public readonly ?UbicacionCaptura $ubicacion,
        public readonly ?string $requestId,
        public readonly ?int $movilidadId,
    ) {
    }

    public static function crear(
        int $proveedorId,
        ?int $acopiadorId,
        ?int $rutaId,
        DateTimeImmutable $fecha,
        CantidadLitros $cantidadLitros,
        ?string $observaciones,
        ?float $perdidaLitros,
        ?string $motivoPerdida,
        ?UbicacionCaptura $ubicacion = null,
        ?string $requestId = null,
        ?int $movilidadId = null,
    ): self {
        self::validarPerdida($perdidaLitros, $motivoPerdida);

        return new self(
            id: null,
            proveedorId: $proveedorId,
            acopiadorId: $acopiadorId,
            rutaId: $rutaId,
            fecha: $fecha,
            cantidadLitros: $cantidadLitros,
            estado: EstadoAcopio::PendienteSincronizar,
            observaciones: $observaciones,
            perdidaLitros: $perdidaLitros,
            motivoPerdida: $motivoPerdida,
            ubicacion: $ubicacion,
            requestId: $requestId,
            movilidadId: $movilidadId,
        );
    }

    public static function reconstituir(
        int $id,
        int $proveedorId,
        ?int $acopiadorId,
        ?int $rutaId,
        DateTimeImmutable $fecha,
        CantidadLitros $cantidadLitros,
        EstadoAcopio $estado,
        ?string $observaciones,
        ?float $perdidaLitros,
        ?string $motivoPerdida,
        ?UbicacionCaptura $ubicacion = null,
        ?string $requestId = null,
        ?int $movilidadId = null,
    ): self {
        return new self(
            $id,
            $proveedorId,
            $acopiadorId,
            $rutaId,
            $fecha,
            $cantidadLitros,
            $estado,
            $observaciones,
            $perdidaLitros,
            $motivoPerdida,
            $ubicacion,
            $requestId,
            $movilidadId,
        );
    }

    /**
     * Marca el acopio como sincronizado, tras ser recibido desde la app
     * movil que lo registro sin conexion.
     */
    public function marcarSincronizado(): self
    {
        return new self(
            id: $this->id,
            proveedorId: $this->proveedorId,
            acopiadorId: $this->acopiadorId,
            rutaId: $this->rutaId,
            fecha: $this->fecha,
            cantidadLitros: $this->cantidadLitros,
            estado: EstadoAcopio::Sincronizado,
            observaciones: $this->observaciones,
            perdidaLitros: $this->perdidaLitros,
            motivoPerdida: $this->motivoPerdida,
            ubicacion: $this->ubicacion,
            requestId: $this->requestId,
            movilidadId: $this->movilidadId,
        );
    }

    /**
     * La semana de acopio/pago corre de jueves a miercoles.
     */
    public function semanaPago(): SemanaPago
    {
        return SemanaPago::desde($this->fecha);
    }

    private static function validarPerdida(?float $perdidaLitros, ?string $motivoPerdida): void
    {
        if ($perdidaLitros === null) {
            return;
        }

        if ($perdidaLitros < 0) {
            throw new PerdidaInvalidaException('La perdida de litros no puede ser negativa.');
        }

        if ($perdidaLitros > 0 && ($motivoPerdida === null || trim($motivoPerdida) === '')) {
            throw new PerdidaInvalidaException('Debe indicar el motivo de la perdida.');
        }
    }
}
