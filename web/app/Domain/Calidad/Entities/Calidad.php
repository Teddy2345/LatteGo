<?php

declare(strict_types=1);

namespace App\Domain\Calidad\Entities;

use App\Domain\Calidad\ValueObjects\Densidad;
use App\Domain\Calidad\ValueObjects\MotivoRechazo;
use App\Domain\Calidad\ValueObjects\Ph;
use App\Domain\Calidad\ValueObjects\PorcentajeAguaAgregada;
use App\Domain\Calidad\ValueObjects\PruebaAlcohol;
use App\Domain\Calidad\ValueObjects\ResultadoCalidad;
use App\Domain\Calidad\ValueObjects\SancionAplicada;
use DateTimeImmutable;

final class Calidad
{
    private function __construct(
        public readonly ?int $id,
        public readonly int $proveedorId,
        public readonly int $acopioId,
        public readonly DateTimeImmutable $fecha,
        public readonly float $temperatura,
        public readonly float $grasa,
        public readonly float $solidosNoGrasos,
        public readonly Densidad $densidad,
        public readonly float $proteina,
        public readonly float $lactosa,
        public readonly float $sales,
        public readonly PorcentajeAguaAgregada $aguaAgregada,
        public readonly Ph $ph,
        public readonly PruebaAlcohol $pruebaAlcohol,
        public readonly ResultadoCalidad $resultado,
        public readonly ?MotivoRechazo $motivoRechazo,
        public readonly SancionAplicada $sancionAplicada,
        public readonly ?string $fotoPath,
    ) {
    }

    /**
     * Aplica la regla: si el agua añadida es mayor a 0% o la prueba de
     * alcohol es rechazada, la leche se rechaza automaticamente por
     * adulteracion (esta decision no puede ser revertida manualmente). Un
     * tecnico puede ademas rechazar por otro motivo medido (ej. acidez)
     * aunque las dos condiciones automaticas no se cumplan.
     */
    public static function crear(
        int $proveedorId,
        int $acopioId,
        DateTimeImmutable $fecha,
        float $temperatura,
        float $grasa,
        float $solidosNoGrasos,
        Densidad $densidad,
        float $proteina,
        float $lactosa,
        float $sales,
        PorcentajeAguaAgregada $aguaAgregada,
        Ph $ph,
        PruebaAlcohol $pruebaAlcohol,
        ?MotivoRechazo $motivoRechazoManual,
        ?string $fotoPath = null,
    ): self {
        [$resultado, $motivoRechazo] = self::evaluarResultado($aguaAgregada, $pruebaAlcohol, $motivoRechazoManual);

        return new self(
            id: null,
            proveedorId: $proveedorId,
            acopioId: $acopioId,
            fecha: $fecha,
            temperatura: $temperatura,
            grasa: $grasa,
            solidosNoGrasos: $solidosNoGrasos,
            densidad: $densidad,
            proteina: $proteina,
            lactosa: $lactosa,
            sales: $sales,
            aguaAgregada: $aguaAgregada,
            ph: $ph,
            pruebaAlcohol: $pruebaAlcohol,
            resultado: $resultado,
            motivoRechazo: $motivoRechazo,
            sancionAplicada: SancionAplicada::Ninguna,
            fotoPath: $fotoPath,
        );
    }

    public static function reconstituir(
        int $id,
        int $proveedorId,
        int $acopioId,
        DateTimeImmutable $fecha,
        float $temperatura,
        float $grasa,
        float $solidosNoGrasos,
        Densidad $densidad,
        float $proteina,
        float $lactosa,
        float $sales,
        PorcentajeAguaAgregada $aguaAgregada,
        Ph $ph,
        PruebaAlcohol $pruebaAlcohol,
        ResultadoCalidad $resultado,
        ?MotivoRechazo $motivoRechazo,
        SancionAplicada $sancionAplicada,
        ?string $fotoPath = null,
    ): self {
        return new self($id, $proveedorId, $acopioId, $fecha, $temperatura, $grasa, $solidosNoGrasos, $densidad, $proteina, $lactosa, $sales, $aguaAgregada, $ph, $pruebaAlcohol, $resultado, $motivoRechazo, $sancionAplicada, $fotoPath);
    }

    /**
     * La sancion solo aplica a rechazos por adulteracion. La primera vez
     * que un proveedor adultera se le descuenta la semana; de la segunda
     * vez en adelante se le retira temporalmente de la planta.
     */
    public function determinarSancion(int $adulteracionesPreviasDelProveedor): SancionAplicada
    {
        if ($this->motivoRechazo !== MotivoRechazo::Adulteracion) {
            return SancionAplicada::Ninguna;
        }

        return $adulteracionesPreviasDelProveedor === 0
            ? SancionAplicada::DescuentoSemana
            : SancionAplicada::RetiroTemporal;
    }

    public function conSancion(SancionAplicada $sancion): self
    {
        return new self(
            id: $this->id,
            proveedorId: $this->proveedorId,
            acopioId: $this->acopioId,
            fecha: $this->fecha,
            temperatura: $this->temperatura,
            grasa: $this->grasa,
            solidosNoGrasos: $this->solidosNoGrasos,
            densidad: $this->densidad,
            proteina: $this->proteina,
            lactosa: $this->lactosa,
            sales: $this->sales,
            aguaAgregada: $this->aguaAgregada,
            ph: $this->ph,
            pruebaAlcohol: $this->pruebaAlcohol,
            resultado: $this->resultado,
            motivoRechazo: $this->motivoRechazo,
            sancionAplicada: $sancion,
            fotoPath: $this->fotoPath,
        );
    }

    /**
     * @return array{0: ResultadoCalidad, 1: ?MotivoRechazo}
     */
    private static function evaluarResultado(
        PorcentajeAguaAgregada $aguaAgregada,
        PruebaAlcohol $pruebaAlcohol,
        ?MotivoRechazo $motivoRechazoManual,
    ): array {
        if ($aguaAgregada->valor > 0.0 || $pruebaAlcohol === PruebaAlcohol::Rechazada) {
            return [ResultadoCalidad::Rechazada, MotivoRechazo::Adulteracion];
        }

        if ($motivoRechazoManual !== null) {
            return [ResultadoCalidad::Rechazada, $motivoRechazoManual];
        }

        return [ResultadoCalidad::Aceptada, null];
    }
}
